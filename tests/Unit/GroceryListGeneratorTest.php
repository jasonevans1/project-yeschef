<?php

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Enums\SourceType;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use App\Services\GroceryListGenerator;
use App\Services\IngredientAggregator;
use App\Services\ServingSizeScaler;
use App\Services\UnitConverter;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('grocery list generator can be instantiated', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);

    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    expect($generator)->toBeInstanceOf(GroceryListGenerator::class);
});

test('processes ingredients with serving multiplier', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);

    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $ingredients = collect([
        [
            'name' => 'flour',
            'quantity' => 2.0,
            'unit' => MeasurementUnit::CUP,
            'category' => IngredientCategory::PANTRY,
        ],
    ]);

    $scaled = $generator->processIngredients($ingredients, 1.5);

    expect($scaled)->toHaveCount(1);
    expect($scaled->first()['quantity'])->toBe(3.0);
});

test('aggregates duplicate ingredients across recipes', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);

    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $allIngredients = collect([
        [
            'name' => 'milk',
            'quantity' => 1.0,
            'unit' => MeasurementUnit::CUP,
            'category' => IngredientCategory::DAIRY,
        ],
        [
            'name' => 'milk',
            'quantity' => 1.0,
            'unit' => MeasurementUnit::CUP,
            'category' => IngredientCategory::DAIRY,
        ],
    ]);

    $aggregated = $generator->aggregateIngredients($allIngredients);

    expect($aggregated)->toHaveCount(1);
    expect($aggregated->first()['quantity'])->toBe(2.0);
});

test('organizes ingredients by category', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);

    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $ingredients = collect([
        [
            'name' => 'milk',
            'quantity' => 1.0,
            'unit' => MeasurementUnit::CUP,
            'category' => IngredientCategory::DAIRY,
        ],
        [
            'name' => 'flour',
            'quantity' => 2.0,
            'unit' => MeasurementUnit::CUP,
            'category' => IngredientCategory::PANTRY,
        ],
    ]);

    $organized = $generator->organizeByCategory($ingredients);

    expect($organized)->toHaveKey(IngredientCategory::DAIRY->value);
    expect($organized)->toHaveKey(IngredientCategory::PANTRY->value);
});

test('generate creates grocery list from meal plan', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    // Create test data
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);

    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $ingredient1 = Ingredient::factory()->create([
        'name' => 'Flour',
        'category' => IngredientCategory::PANTRY,
    ]);
    $ingredient2 = Ingredient::factory()->create([
        'name' => 'Milk',
        'category' => IngredientCategory::DAIRY,
    ]);

    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient1->id,
        'quantity' => 2.0,
        'unit' => MeasurementUnit::CUP,
    ]);
    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient2->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::CUP,
    ]);

    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'serving_multiplier' => 1.0,
    ]);

    // Generate grocery list
    $groceryList = $generator->generate($mealPlan);

    expect($groceryList)->toBeInstanceOf(GroceryList::class);
    expect($groceryList->user_id)->toBe($user->id);
    expect($groceryList->meal_plan_id)->toBe($mealPlan->id);
    expect($groceryList->name)->toBe("Grocery List for {$mealPlan->name}");
    expect($groceryList->generated_at)->not->toBeNull();
    expect($groceryList->groceryItems)->toHaveCount(2);
});

test('generate creates grocery items with correct data', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    // Create test data
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $ingredient = Ingredient::factory()->create([
        'name' => 'sugar',
        'category' => IngredientCategory::PANTRY,
    ]);

    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 3.5,
        'unit' => MeasurementUnit::CUP,
    ]);

    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'serving_multiplier' => 2.0,
    ]);

    $groceryList = $generator->generate($mealPlan);
    $groceryItem = $groceryList->groceryItems->first();

    expect($groceryItem->name)->toBe('Sugar');
    expect((float) $groceryItem->quantity)->toBe(7.0); // 3.5 * 2.0
    expect($groceryItem->unit)->toBe(MeasurementUnit::CUP);
    expect($groceryItem->category)->toBe(IngredientCategory::PANTRY);
    expect($groceryItem->source_type)->toBe(SourceType::GENERATED);
    expect($groceryItem->sort_order)->toBe(0);
});

test('generate aggregates duplicate ingredients from multiple recipes', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);

    $recipe1 = Recipe::factory()->create(['user_id' => $user->id]);
    $recipe2 = Recipe::factory()->create(['user_id' => $user->id]);

    $ingredient = Ingredient::factory()->create([
        'name' => 'Eggs',
        'category' => IngredientCategory::DAIRY,
    ]);

    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe1->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 2.0,
        'unit' => MeasurementUnit::WHOLE,
    ]);
    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe2->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 3.0,
        'unit' => MeasurementUnit::WHOLE,
    ]);

    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe1->id,
    ]);
    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe2->id,
    ]);

    $groceryList = $generator->generate($mealPlan);

    expect($groceryList->groceryItems)->toHaveCount(1);
    expect((float) $groceryList->groceryItems->first()->quantity)->toBe(5.0);
});

test('generate organizes items by category with sort order', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $pantryItem = Ingredient::factory()->create(['category' => IngredientCategory::PANTRY]);
    $dairyItem = Ingredient::factory()->create(['category' => IngredientCategory::DAIRY]);

    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $pantryItem->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::CUP,
    ]);
    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $dairyItem->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::CUP,
    ]);

    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
    ]);

    $groceryList = $generator->generate($mealPlan);
    $items = $groceryList->groceryItems->sortBy('sort_order');

    expect($items)->toHaveCount(2);
    expect($items->first()->sort_order)->toBe(0);
    expect($items->last()->sort_order)->toBe(1);
});

test('regenerate throws exception for standalone grocery list', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => null,
    ]);

    expect(fn () => $generator->regenerate($groceryList))
        ->toThrow(InvalidArgumentException::class, 'Cannot regenerate a standalone grocery list');
});

test('regenerate removes unmodified generated items', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
    ]);

    // Create an unmodified generated item
    GroceryItem::factory()->create([
        'grocery_list_id' => $groceryList->id,
        'source_type' => SourceType::GENERATED,
        'original_values' => null,
    ]);

    $generator->regenerate($groceryList);

    expect($groceryList->fresh()->groceryItems()->count())->toBe(0);
});

test('regenerate preserves manual items', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
    ]);

    $manualItem = GroceryItem::factory()->create([
        'grocery_list_id' => $groceryList->id,
        'name' => 'Paper Towels',
        'source_type' => SourceType::MANUAL,
    ]);

    $generator->regenerate($groceryList);

    expect($groceryList->fresh()->groceryItems()->count())->toBe(1);
    expect($groceryList->fresh()->groceryItems->first()->name)->toBe('Paper Towels');
});

test('regenerate preserves edited generated items', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
    ]);

    GroceryItem::factory()->create([
        'grocery_list_id' => $groceryList->id,
        'name' => 'Flour',
        'quantity' => 5.0,
        'source_type' => SourceType::GENERATED,
        'original_values' => json_encode(['quantity' => 2.0]),
    ]);

    $generator->regenerate($groceryList);

    expect($groceryList->fresh()->groceryItems()->count())->toBe(1);
    expect((float) $groceryList->fresh()->groceryItems->first()->quantity)->toBe(5.0);
});

test('regenerate respects user deletions', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $ingredient = Ingredient::factory()->create(['name' => 'Salt']);
    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::TSP,
    ]);
    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
    ]);

    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
    ]);

    // Create a soft-deleted item (user deleted it)
    $deletedItem = GroceryItem::factory()->create([
        'grocery_list_id' => $groceryList->id,
        'name' => 'Salt',
        'source_type' => SourceType::GENERATED,
    ]);
    $deletedItem->delete();

    $generator->regenerate($groceryList);

    // Salt should not be re-added
    expect($groceryList->fresh()->groceryItems()->count())->toBe(0);
});

test('regenerate adds new items from updated meal plan', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $ingredient = Ingredient::factory()->create(['name' => 'pepper']);
    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::TSP,
    ]);
    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
    ]);

    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
    ]);

    $generator->regenerate($groceryList);

    expect($groceryList->fresh()->groceryItems()->count())->toBe(1);
    expect($groceryList->fresh()->groceryItems->first()->name)->toBe('Pepper');
});

test('regenerate updates regenerated_at timestamp', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'regenerated_at' => null,
    ]);

    $generator->regenerate($groceryList);

    expect($groceryList->fresh()->regenerated_at)->not->toBeNull();
});

test('regenerate does not override manual items with same name', function () {
    $unitConverter = new UnitConverter;
    $scaler = new ServingSizeScaler;
    $aggregator = new IngredientAggregator($unitConverter);
    $generator = new GroceryListGenerator($scaler, $aggregator, $unitConverter);

    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->create(['user_id' => $user->id]);
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $ingredient = Ingredient::factory()->create(['name' => 'Bread']);
    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::WHOLE,
    ]);
    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
    ]);

    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
    ]);

    // User manually added bread before
    GroceryItem::factory()->create([
        'grocery_list_id' => $groceryList->id,
        'name' => 'Bread',
        'quantity' => 2.0,
        'source_type' => SourceType::MANUAL,
    ]);

    $generator->regenerate($groceryList);

    // Should still have only 1 bread item (the manual one)
    expect($groceryList->fresh()->groceryItems()->count())->toBe(1);
    expect((float) $groceryList->fresh()->groceryItems->first()->quantity)->toBe(2.0);
    expect($groceryList->fresh()->groceryItems->first()->source_type)->toBe(SourceType::MANUAL);
});
