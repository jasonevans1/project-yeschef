<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Livewire\GroceryLists\Generate;
use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Helper to create a meal plan with a recipe and one ingredient assigned to it.
 */
function createMealPlanWithIngredient(User $user, string $ingredientName = 'Salt', IngredientCategory $category = IngredientCategory::SOUPS_AND_CANNED_GOODS): MealPlan
{
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);
    $ingredient = Ingredient::factory()->create(['name' => $ingredientName, 'category' => $category]);
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

    return $mealPlan;
}

it('loads ingredientPreview with items when meal plan has recipes', function () {
    $user = User::factory()->create();
    $mealPlan = createMealPlanWithIngredient($user);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertSet('ingredientPreview', fn ($preview) => count($preview) > 0);
});

it('loads empty ingredientPreview when meal plan has no recipes', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertSet('ingredientPreview', []);
});

it('pre-populates excludedIngredients from existing list excluded_ingredients', function () {
    $user = User::factory()->create();
    $mealPlan = createMealPlanWithIngredient($user);
    GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'excluded_ingredients' => ['salt', 'pepper'],
    ]);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertSet('excludedIngredients', ['salt', 'pepper']);
});

it('falls back to user pantry_items when no existing list', function () {
    $user = User::factory()->create(['pantry_items' => ['olive oil', 'garlic']]);
    $mealPlan = createMealPlanWithIngredient($user);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertSet('excludedIngredients', ['olive oil', 'garlic']);
});

it('uses empty array when no existing list and no pantry', function () {
    $user = User::factory()->create(['pantry_items' => null]);
    $mealPlan = createMealPlanWithIngredient($user);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertSet('excludedIngredients', []);
});

it('prefers existing list excluded_ingredients over pantry_items', function () {
    $user = User::factory()->create(['pantry_items' => ['olive oil']]);
    $mealPlan = createMealPlanWithIngredient($user);
    GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'excluded_ingredients' => ['salt'],
    ]);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertSet('excludedIngredients', ['salt']);
});

it('generates list excluding checked ingredients', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $salt = Ingredient::factory()->create(['name' => 'Salt', 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS]);
    $milk = Ingredient::factory()->create(['name' => 'Milk', 'category' => IngredientCategory::DAIRY]);

    $recipe->recipeIngredients()->createMany([
        ['ingredient_id' => $salt->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::TSP],
        ['ingredient_id' => $milk->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP],
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->set('excludedIngredients', ['salt'])
        ->call('generate');

    $groceryList = GroceryList::where('meal_plan_id', $mealPlan->id)->first();
    $itemNames = $groceryList->groceryItems->pluck('name')->map(fn ($n) => strtolower($n))->toArray();
    expect($itemNames)->not->toContain('salt')
        ->and($itemNames)->toContain('milk');
});

it('regenerates list excluding checked ingredients', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $salt = Ingredient::factory()->create(['name' => 'Salt', 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS]);
    $milk = Ingredient::factory()->create(['name' => 'Milk', 'category' => IngredientCategory::DAIRY]);

    $recipe->recipeIngredients()->createMany([
        ['ingredient_id' => $salt->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::TSP],
        ['ingredient_id' => $milk->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP],
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    $existingList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
    ]);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->set('excludedIngredients', ['salt'])
        ->call('generate');

    $updatedList = $existingList->fresh('groceryItems');
    $itemNames = $updatedList->groceryItems->pluck('name')->map(fn ($n) => strtolower($n))->toArray();
    expect($itemNames)->not->toContain('salt')
        ->and($itemNames)->toContain('milk');
});

it('savePantry persists excludedIngredients to user pantry_items', function () {
    $user = User::factory()->create();
    $mealPlan = createMealPlanWithIngredient($user);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->set('excludedIngredients', ['salt', 'pepper'])
        ->call('savePantry');

    expect($user->fresh()->pantry_items)->toBe(['salt', 'pepper']);
});

it('savePantry sets pantry_items to null when no ingredients are excluded', function () {
    $user = User::factory()->create(['pantry_items' => ['old item']]);
    $mealPlan = createMealPlanWithIngredient($user);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->set('excludedIngredients', [])
        ->call('savePantry');

    expect($user->fresh()->pantry_items)->toBeNull();
});

it('clearPantry sets user pantry_items to null', function () {
    $user = User::factory()->create(['pantry_items' => ['salt', 'pepper']]);
    $mealPlan = createMealPlanWithIngredient($user);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->call('clearPantry');

    expect($user->fresh()->pantry_items)->toBeNull();
});

it('clearPantry resets savedPantry to empty array', function () {
    $user = User::factory()->create(['pantry_items' => ['salt', 'pepper']]);
    $mealPlan = createMealPlanWithIngredient($user);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->call('clearPantry')
        ->assertSet('savedPantry', []);
});

it('shows ingredient exclusion panel when recipes exist', function () {
    $user = User::factory()->create();
    $mealPlan = createMealPlanWithIngredient($user, 'Salt', IngredientCategory::SOUPS_AND_CANNED_GOODS);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertSee('Exclude specific ingredients');
});

it('does not show ingredient exclusion panel when no recipes', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertDontSee('Exclude specific ingredients');
});

it('shows the real item count from ingredientPreview instead of the estimated heuristic', function () {
    $user = User::factory()->create();
    $mealPlan = createMealPlanWithIngredient($user);

    $component = Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan]);

    $preview = $component->get('ingredientPreview');
    $estimatedItemCount = $component->get('estimatedItemCount');

    expect($estimatedItemCount)->toBe(count($preview));
});
