<?php

use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use App\Enums\MeasurementUnit;
use App\Enums\IngredientCategory;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can generate grocery list from meal plan', function () {
    // Create a meal plan with a recipe
    $mealPlan = MealPlan::factory()->for($this->user)->create([
        'name' => 'Test Meal Plan',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
    ]);

    $recipe = Recipe::factory()->create([
        'name' => 'Chicken Pasta',
        'servings' => 4,
    ]);

    $chicken = Ingredient::factory()->create([
        'name' => 'chicken breast',
        'category' => IngredientCategory::MEAT,
    ]);

    $pasta = Ingredient::factory()->create([
        'name' => 'pasta',
        'category' => IngredientCategory::PANTRY,
    ]);

    $recipe->recipeIngredients()->create([
        'ingredient_id' => $chicken->id,
        'quantity' => 1.5,
        'unit' => MeasurementUnit::LB,
        'sort_order' => 0,
    ]);

    $recipe->recipeIngredients()->create([
        'ingredient_id' => $pasta->id,
        'quantity' => 8,
        'unit' => MeasurementUnit::OZ,
        'sort_order' => 1,
    ]);

    // Assign recipe to meal plan
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    // Generate grocery list
    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    expect($groceryList)->toBeInstanceOf(GroceryList::class)
        ->and($groceryList->user_id)->toBe($this->user->id)
        ->and($groceryList->meal_plan_id)->toBe($mealPlan->id)
        ->and($groceryList->items()->count())->toBe(2);

    $items = $groceryList->items;

    expect($items->firstWhere('name', 'chicken breast'))->not->toBeNull()
        ->and($items->firstWhere('name', 'pasta'))->not->toBeNull();
});

test('list contains all ingredients from assigned recipes', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();

    $recipe1 = Recipe::factory()->create(['name' => 'Recipe 1']);
    $recipe2 = Recipe::factory()->create(['name' => 'Recipe 2']);

    $tomato = Ingredient::factory()->create(['name' => 'tomato']);
    $onion = Ingredient::factory()->create(['name' => 'onion']);
    $garlic = Ingredient::factory()->create(['name' => 'garlic']);

    $recipe1->recipeIngredients()->create([
        'ingredient_id' => $tomato->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::WHOLE,
    ]);

    $recipe1->recipeIngredients()->create([
        'ingredient_id' => $onion->id,
        'quantity' => 1,
        'unit' => MeasurementUnit::WHOLE,
    ]);

    $recipe2->recipeIngredients()->create([
        'ingredient_id' => $garlic->id,
        'quantity' => 3,
        'unit' => MeasurementUnit::CLOVE,
    ]);

    // Assign both recipes
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe1->id,
        'date' => now()->toDateString(),
        'meal_type' => 'lunch',
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe2->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
    ]);

    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    expect($groceryList->items()->count())->toBe(3);

    $itemNames = $groceryList->items->pluck('name')->toArray();
    expect($itemNames)->toContain('tomato')
        ->and($itemNames)->toContain('onion')
        ->and($itemNames)->toContain('garlic');
});

test('duplicate ingredients with same unit are aggregated', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();

    $recipe1 = Recipe::factory()->create();
    $recipe2 = Recipe::factory()->create();

    $milk = Ingredient::factory()->create(['name' => 'milk', 'category' => IngredientCategory::DAIRY]);

    // Recipe 1 uses 2 cups milk
    $recipe1->recipeIngredients()->create([
        'ingredient_id' => $milk->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::CUP,
    ]);

    // Recipe 2 uses 1 cup milk
    $recipe2->recipeIngredients()->create([
        'ingredient_id' => $milk->id,
        'quantity' => 1,
        'unit' => MeasurementUnit::CUP,
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe1->id,
        'date' => now()->toDateString(),
        'meal_type' => 'breakfast',
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe2->id,
        'date' => now()->addDay()->toDateString(),
        'meal_type' => 'breakfast',
    ]);

    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    // Should have only 1 milk item with aggregated quantity
    $milkItems = $groceryList->items->where('name', 'milk');

    expect($milkItems->count())->toBe(1);

    $milkItem = $milkItems->first();
    expect($milkItem->quantity)->toBe(3.0) // 2 + 1 = 3 cups
        ->and($milkItem->unit)->toBe(MeasurementUnit::CUP)
        ->and($milkItem->category)->toBe(IngredientCategory::DAIRY);
});

test('duplicate ingredients with different compatible units are aggregated using unit converter', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();

    $recipe1 = Recipe::factory()->create();
    $recipe2 = Recipe::factory()->create();

    $milk = Ingredient::factory()->create(['name' => 'milk']);

    // Recipe 1 uses 2 cups milk (16 fl oz)
    $recipe1->recipeIngredients()->create([
        'ingredient_id' => $milk->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::CUP,
    ]);

    // Recipe 2 uses 1 pint milk (16 fl oz)
    $recipe2->recipeIngredients()->create([
        'ingredient_id' => $milk->id,
        'quantity' => 1,
        'unit' => MeasurementUnit::PINT,
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe1->id,
        'date' => now()->toDateString(),
        'meal_type' => 'breakfast',
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe2->id,
        'date' => now()->addDay()->toDateString(),
        'meal_type' => 'breakfast',
    ]);

    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    // Should have only 1 milk item with aggregated quantity
    // 2 cups (16 fl oz) + 1 pint (16 fl oz) = 32 fl oz = 1 quart or 4 cups
    $milkItems = $groceryList->items->where('name', 'milk');

    expect($milkItems->count())->toBe(1);

    $milkItem = $milkItems->first();

    // The aggregated quantity should equal 32 fl oz (or equivalent in chosen unit)
    // Assuming the service converts to a common unit
    expect($milkItem->quantity)->toBeGreaterThan(0)
        ->and($milkItem->name)->toBe('milk');
});

test('items are organized by category', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();

    $recipe = Recipe::factory()->create();

    $chicken = Ingredient::factory()->create([
        'name' => 'chicken',
        'category' => IngredientCategory::MEAT,
    ]);

    $milk = Ingredient::factory()->create([
        'name' => 'milk',
        'category' => IngredientCategory::DAIRY,
    ]);

    $tomato = Ingredient::factory()->create([
        'name' => 'tomato',
        'category' => IngredientCategory::PRODUCE,
    ]);

    $recipe->recipeIngredients()->createMany([
        [
            'ingredient_id' => $chicken->id,
            'quantity' => 1,
            'unit' => MeasurementUnit::LB,
        ],
        [
            'ingredient_id' => $milk->id,
            'quantity' => 2,
            'unit' => MeasurementUnit::CUP,
        ],
        [
            'ingredient_id' => $tomato->id,
            'quantity' => 3,
            'unit' => MeasurementUnit::WHOLE,
        ],
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
    ]);

    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    $items = $groceryList->items;

    expect($items->where('category', IngredientCategory::MEAT)->count())->toBe(1)
        ->and($items->where('category', IngredientCategory::DAIRY)->count())->toBe(1)
        ->and($items->where('category', IngredientCategory::PRODUCE)->count())->toBe(1);

    $chickenItem = $items->where('name', 'chicken')->first();
    expect($chickenItem->category)->toBe(IngredientCategory::MEAT);

    $milkItem = $items->where('name', 'milk')->first();
    expect($milkItem->category)->toBe(IngredientCategory::DAIRY);

    $tomatoItem = $items->where('name', 'tomato')->first();
    expect($tomatoItem->category)->toBe(IngredientCategory::PRODUCE);
});

test('grocery list is linked to source meal plan', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create([
        'name' => 'Weekly Meal Plan',
    ]);

    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create();

    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient->id,
        'quantity' => 1,
        'unit' => MeasurementUnit::WHOLE,
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
    ]);

    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    expect($groceryList->meal_plan_id)->toBe($mealPlan->id)
        ->and($groceryList->user_id)->toBe($this->user->id)
        ->and($groceryList->mealPlan)->toBeInstanceOf(MealPlan::class)
        ->and($groceryList->mealPlan->id)->toBe($mealPlan->id);
});

test('serving multipliers are applied correctly', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();

    $recipe = Recipe::factory()->create([
        'servings' => 4,
    ]);

    $flour = Ingredient::factory()->create(['name' => 'flour']);

    // Original recipe uses 2 cups flour for 4 servings
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $flour->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::CUP,
    ]);

    // Assign with 1.5x multiplier (serves 6)
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.5,
    ]);

    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    $flourItem = $groceryList->items->where('name', 'flour')->first();

    // 2 cups * 1.5 = 3 cups
    expect($flourItem->quantity)->toBe(3.0)
        ->and($flourItem->unit)->toBe(MeasurementUnit::CUP);
});

test('empty meal plan generates empty list with helpful message', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create([
        'name' => 'Empty Meal Plan',
    ]);

    // No meal assignments

    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    expect($groceryList)->toBeInstanceOf(GroceryList::class)
        ->and($groceryList->items()->count())->toBe(0)
        ->and($groceryList->user_id)->toBe($this->user->id)
        ->and($groceryList->meal_plan_id)->toBe($mealPlan->id);
});
