<?php

use App\Enums\IngredientCategory;
use App\Enums\MealType;
use App\Enums\MeasurementUnit;
use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('grocery list items reflect serving multipliers', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create(['servings' => 4]);

    $milk = Ingredient::factory()->create([
        'name' => 'milk',
        'category' => IngredientCategory::DAIRY,
    ]);

    // Original recipe uses 2 cups milk for 4 servings
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $milk->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 0,
    ]);

    // Assign with 1.5x multiplier (serves 6)
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 1.5,
    ]);

    // Generate grocery list
    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    expect($groceryList)->toBeInstanceOf(GroceryList::class);

    $milkItem = $groceryList->groceryItems->where('name', 'Milk')->first();

    // 2 cups * 1.5 = 3 cups
    expect($milkItem)->not->toBeNull()
        ->and((float) $milkItem->quantity)->toBe(3.0)
        ->and($milkItem->unit)->toBe(MeasurementUnit::CUP);
});

test('multiple recipes with different multipliers aggregate correctly', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    // Create two recipes that use the same ingredient
    $recipe1 = Recipe::factory()->create(['servings' => 4]);
    $recipe2 = Recipe::factory()->create(['servings' => 2]);

    $flour = Ingredient::factory()->create([
        'name' => 'flour',
        'category' => IngredientCategory::PANTRY,
    ]);

    // Recipe 1 uses 2 cups flour for 4 servings
    $recipe1->recipeIngredients()->create([
        'ingredient_id' => $flour->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 0,
    ]);

    // Recipe 2 uses 1 cup flour for 2 servings
    $recipe2->recipeIngredients()->create([
        'ingredient_id' => $flour->id,
        'quantity' => 1,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 0,
    ]);

    // Assign recipe 1 with 1.5x multiplier (serves 6): 2 cups * 1.5 = 3 cups
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe1->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 1.5,
    ]);

    // Assign recipe 2 with 2.0x multiplier (serves 4): 1 cup * 2.0 = 2 cups
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe2->id,
        'date' => '2025-10-16',
        'meal_type' => MealType::BREAKFAST,
        'serving_multiplier' => 2.0,
    ]);

    // Generate grocery list
    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    expect($groceryList)->toBeInstanceOf(GroceryList::class);

    $flourItem = $groceryList->groceryItems->where('name', 'Flour')->first();

    // Total: 3 cups + 2 cups = 5 cups
    expect($flourItem)->not->toBeNull()
        ->and((float) $flourItem->quantity)->toBe(5.0)
        ->and($flourItem->unit)->toBe(MeasurementUnit::CUP);
});

test('fractional results are handled correctly with decimals', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create(['servings' => 3]);

    $sugar = Ingredient::factory()->create([
        'name' => 'sugar',
        'category' => IngredientCategory::PANTRY,
    ]);

    // Original recipe uses 1 cup sugar for 3 servings
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $sugar->id,
        'quantity' => 1,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 0,
    ]);

    // Assign with 0.5x multiplier (serves 1.5): 1 cup * 0.5 = 0.5 cups
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::BREAKFAST,
        'serving_multiplier' => 0.5,
    ]);

    // Generate grocery list
    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    expect($groceryList)->toBeInstanceOf(GroceryList::class);

    $sugarItem = $groceryList->groceryItems->where('name', 'Sugar')->first();

    // 1 cup * 0.5 = 0.5 cups
    expect($sugarItem)->not->toBeNull()
        ->and((float) $sugarItem->quantity)->toBe(0.5)
        ->and($sugarItem->unit)->toBe(MeasurementUnit::CUP);
});

test('fractional results with thirds are handled correctly', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create(['servings' => 3]);

    $butter = Ingredient::factory()->create([
        'name' => 'butter',
        'category' => IngredientCategory::DAIRY,
    ]);

    // Original recipe uses 3 tablespoons butter for 3 servings
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $butter->id,
        'quantity' => 3,
        'unit' => MeasurementUnit::TBSP,
        'sort_order' => 0,
    ]);

    // Assign with 0.333... multiplier (serves 1): 3 tbsp * 0.333... ≈ 1 tbsp
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 0.33,
    ]);

    // Generate grocery list
    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    expect($groceryList)->toBeInstanceOf(GroceryList::class);

    $butterItem = $groceryList->groceryItems->where('name', 'Butter')->first();

    // 3 tbsp * 0.33 ≈ 0.99 tbsp (stored with precision)
    expect($butterItem)->not->toBeNull()
        ->and((float) $butterItem->quantity)->toBeGreaterThan(0.98)
        ->and((float) $butterItem->quantity)->toBeLessThan(1.0)
        ->and($butterItem->unit)->toBe(MeasurementUnit::TBSP);
});

test('very small fractional multipliers produce correct small quantities', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create(['servings' => 8]);

    $salt = Ingredient::factory()->create([
        'name' => 'salt',
        'category' => IngredientCategory::PANTRY,
    ]);

    // Original recipe uses 2 teaspoons salt for 8 servings
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $salt->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::TSP,
        'sort_order' => 0,
    ]);

    // Assign with 0.25x multiplier (serves 2): 2 tsp * 0.25 = 0.5 tsp
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::LUNCH,
        'serving_multiplier' => 0.25,
    ]);

    // Generate grocery list
    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    expect($groceryList)->toBeInstanceOf(GroceryList::class);

    $saltItem = $groceryList->groceryItems->where('name', 'Salt')->first();

    // 2 tsp * 0.25 = 0.5 tsp
    expect($saltItem)->not->toBeNull()
        ->and((float) $saltItem->quantity)->toBe(0.5)
        ->and($saltItem->unit)->toBe(MeasurementUnit::TSP);
});

test('large multipliers produce correct large quantities', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create(['servings' => 4]);

    $chicken = Ingredient::factory()->create([
        'name' => 'chicken',
        'category' => IngredientCategory::MEAT,
    ]);

    // Original recipe uses 2 pounds chicken for 4 servings
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $chicken->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::LB,
        'sort_order' => 0,
    ]);

    // Assign with 5.0x multiplier (serves 20): 2 lbs * 5.0 = 10 lbs
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 5.0,
    ]);

    // Generate grocery list
    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    expect($groceryList)->toBeInstanceOf(GroceryList::class);

    $chickenItem = $groceryList->groceryItems->where('name', 'Chicken')->first();

    // 2 lbs * 5.0 = 10 lbs
    expect($chickenItem)->not->toBeNull()
        ->and((float) $chickenItem->quantity)->toBe(10.0)
        ->and($chickenItem->unit)->toBe(MeasurementUnit::LB);
});

test('aggregation works correctly with scaled quantities from same recipe used multiple times', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create(['servings' => 4]);

    $rice = Ingredient::factory()->create([
        'name' => 'rice',
        'category' => IngredientCategory::PANTRY,
    ]);

    // Original recipe uses 1 cup rice for 4 servings
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $rice->id,
        'quantity' => 1,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 0,
    ]);

    // Assign same recipe twice with different multipliers
    // Monday dinner: 1 cup * 1.0 = 1 cup
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 1.0,
    ]);

    // Tuesday lunch: 1 cup * 1.5 = 1.5 cups
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => '2025-10-16',
        'meal_type' => MealType::LUNCH,
        'serving_multiplier' => 1.5,
    ]);

    // Wednesday dinner: 1 cup * 0.5 = 0.5 cups
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => '2025-10-17',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 0.5,
    ]);

    // Generate grocery list
    $groceryList = app(\App\Services\GroceryListGenerator::class)->generate($mealPlan);

    expect($groceryList)->toBeInstanceOf(GroceryList::class);

    $riceItem = $groceryList->groceryItems->where('name', 'Rice')->first();

    // Total: 1 + 1.5 + 0.5 = 3 cups
    expect($riceItem)->not->toBeNull()
        ->and((float) $riceItem->quantity)->toBe(3.0)
        ->and($riceItem->unit)->toBe(MeasurementUnit::CUP);
});
