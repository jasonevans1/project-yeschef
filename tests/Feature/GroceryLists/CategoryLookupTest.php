<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Livewire\GroceryLists\Generate;
use App\Models\CommonItemTemplate;
use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use App\Models\UserItemTemplate;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────────────────────
// T020: Feature tests for template-based categorization (US2)
// ──────────────────────────────────────────────────────────

test('ingredient with OTHER category gets resolved to user template category on generate', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $ingredient = Ingredient::factory()->create(['name' => 'Cumin', 'category' => IngredientCategory::OTHER]);
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::TSP,
    ]);
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    // User has previously classified "Cumin" as PANTRY
    UserItemTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Cumin',
        'category' => IngredientCategory::PANTRY,
    ]);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->call('generate');

    $groceryList = GroceryList::where('meal_plan_id', $mealPlan->id)->first();
    $item = $groceryList->groceryItems->first();

    expect($item->category)->toBe(IngredientCategory::PANTRY);
});

test('ingredient with OTHER category falls back to common template category when no user template', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $ingredient = Ingredient::factory()->create(['name' => 'Paprika', 'category' => IngredientCategory::OTHER]);
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::TSP,
    ]);
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    // No user template — common template resolves to PANTRY
    CommonItemTemplate::create([
        'name' => 'Paprika',
        'category' => IngredientCategory::PANTRY,
        'unit' => MeasurementUnit::TSP,
        'default_quantity' => 1.0,
        'usage_count' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->call('generate');

    $groceryList = GroceryList::where('meal_plan_id', $mealPlan->id)->first();
    $item = $groceryList->groceryItems->first();

    expect($item->category)->toBe(IngredientCategory::PANTRY);
});

test('ingredient with non-OTHER category is unchanged regardless of templates', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $ingredient = Ingredient::factory()->create(['name' => 'Milk', 'category' => IngredientCategory::DAIRY]);
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::CUP,
    ]);
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    // Template says PANTRY — must NOT override because DAIRY is not OTHER
    UserItemTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Milk',
        'category' => IngredientCategory::PANTRY,
    ]);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->call('generate');

    $groceryList = GroceryList::where('meal_plan_id', $mealPlan->id)->first();
    $item = $groceryList->groceryItems->first();

    expect($item->category)->toBe(IngredientCategory::DAIRY);
});

test('getCategoryItemCounts reflects template-resolved categories', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    // Ingredient categorized as OTHER — template resolves to PANTRY
    $ingredient = Ingredient::factory()->create(['name' => 'Cumin', 'category' => IngredientCategory::OTHER]);
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::TSP,
    ]);
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    UserItemTemplate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Cumin',
        'category' => IngredientCategory::PANTRY,
    ]);

    $component = Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan]);

    $counts = $component->get('categoryItemCounts');

    // Should be counted under PANTRY (not OTHER) after template resolution
    expect($counts)->toHaveKey(IngredientCategory::PANTRY->value);
    expect($counts)->not->toHaveKey(IngredientCategory::OTHER->value);
});
