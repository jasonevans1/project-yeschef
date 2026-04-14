<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Enums\SourceType;
use App\Models\GroceryItem;
use App\Models\Ingredient;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use App\Services\GroceryListGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->generator = app(GroceryListGenerator::class);
});

it('stores recipe_ids on generated grocery items when generating from a meal plan', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create(['name' => 'carrot', 'category' => IngredientCategory::PRODUCE]);

    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 2.0,
        'unit' => MeasurementUnit::WHOLE,
    ]);

    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'serving_multiplier' => 1.0,
    ]);

    $groceryList = $this->generator->generate($mealPlan);

    $item = $groceryList->groceryItems->first();
    expect($item->recipe_ids)->toBeArray()
        ->and($item->recipe_ids)->toBe([$recipe->id]);
});

it('stores multiple recipe_ids when the same ingredient appears in multiple recipes (aggregation merges them)', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $recipe1 = Recipe::factory()->create();
    $recipe2 = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create(['name' => 'butter', 'category' => IngredientCategory::DAIRY]);

    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe1->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::CUP,
    ]);
    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe2->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 0.5,
        'unit' => MeasurementUnit::CUP,
    ]);

    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe1->id,
        'serving_multiplier' => 1.0,
    ]);
    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe2->id,
        'serving_multiplier' => 1.0,
    ]);

    $groceryList = $this->generator->generate($mealPlan);

    $item = $groceryList->groceryItems->first();
    expect($item->recipe_ids)->toBeArray()
        ->and($item->recipe_ids)->toHaveCount(2)
        ->and($item->recipe_ids)->toContain($recipe1->id)
        ->and($item->recipe_ids)->toContain($recipe2->id);
});

it('stores recipe_ids on newly added items during regeneration', function () {
    // Start with an empty meal plan, generate an empty grocery list
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $groceryList = $this->generator->generate($mealPlan);

    // Now add a recipe and ingredient assignment to the meal plan
    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create(['name' => 'onion', 'category' => IngredientCategory::PRODUCE]);

    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::WHOLE,
    ]);

    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'serving_multiplier' => 1.0,
    ]);

    // Regenerate — the new ingredient should appear with recipe_ids populated
    $regenerated = $this->generator->regenerate($groceryList);

    $item = $regenerated->groceryItems->first();
    expect($item->recipe_ids)->toBeArray()
        ->and($item->recipe_ids)->toBe([$recipe->id]);
});

it('leaves recipe_ids untouched on existing manual items during regeneration', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $groceryList = $this->generator->generate($mealPlan);

    // Add a manual item with a specific recipe_ids value (unusual but should be preserved)
    $manualItem = GroceryItem::create([
        'grocery_list_id' => $groceryList->id,
        'name' => 'Handpicked Apple',
        'quantity' => 3.0,
        'unit' => MeasurementUnit::WHOLE,
        'category' => IngredientCategory::PRODUCE,
        'source_type' => SourceType::MANUAL,
        'sort_order' => 99,
        'recipe_ids' => [999],
    ]);

    $regenerated = $this->generator->regenerate($groceryList);

    $found = $regenerated->groceryItems->firstWhere('name', 'Handpicked Apple');
    expect($found)->not->toBeNull()
        ->and($found->source_type)->toBe(SourceType::MANUAL)
        ->and($found->recipe_ids)->toBe([999]);
});

it('leaves recipe_ids untouched on edited generated items during regeneration', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create(['name' => 'garlic', 'category' => IngredientCategory::PRODUCE]);

    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 2.0,
        'unit' => MeasurementUnit::CLOVE,
    ]);

    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'serving_multiplier' => 1.0,
    ]);

    $groceryList = $this->generator->generate($mealPlan);

    // Simulate a user editing the generated garlic item (sets original_values)
    $garlicItem = $groceryList->groceryItems->firstWhere('name', 'Garlic');
    $garlicItem->update([
        'quantity' => 5.0,
        'original_values' => ['quantity' => 2.0, 'unit' => MeasurementUnit::CLOVE->value],
        'recipe_ids' => [$recipe->id],
    ]);

    $regenerated = $this->generator->regenerate($groceryList);

    $found = $regenerated->groceryItems->firstWhere('name', 'Garlic');
    expect($found)->not->toBeNull()
        ->and($found->recipe_ids)->toBe([$recipe->id])
        ->and((float) $found->quantity)->toBe(5.0); // User's edit is preserved
});

it('does not break getIngredientPreview() — preview output still contains only the 5 expected keys (name, quantity, unit, category, category_label)', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create(['name' => 'tomato', 'category' => IngredientCategory::PRODUCE]);

    RecipeIngredient::factory()->create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 2.0,
        'unit' => MeasurementUnit::WHOLE,
    ]);

    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'serving_multiplier' => 1.0,
    ]);

    $preview = $this->generator->getIngredientPreview($mealPlan, $this->user->id);

    expect($preview)->toHaveCount(1);
    $item = $preview->first();
    $expectedKeys = ['name', 'quantity', 'unit', 'category', 'category_label'];
    expect(array_keys($item))->toBe($expectedKeys)
        ->and($item)->not->toHaveKey('recipe_ids')
        ->and($item)->not->toHaveKey('recipe_id');
});
