<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Livewire\GroceryLists\Generate;
use App\Livewire\GroceryLists\Show;
use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

// ──────────────────────────────────────────────────────────
// T008: Feature tests for Generate and Show components (US1)
// ──────────────────────────────────────────────────────────

// --- Generate component ---

test('generate component mounts with excluded categories pre-populated from existing list', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $existingList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'excluded_categories' => ['soups-and-canned-goods', 'dairy'],
    ]);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertSet('excludedCategories', ['soups-and-canned-goods', 'dairy']);
});

test('generate component mounts with empty excluded categories when no existing list', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertSet('excludedCategories', []);
});

test('generate component has category item counts when recipes exist', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $ingredient = Ingredient::factory()->create(['name' => 'Flour', 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS]);
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient->id,
        'quantity' => 2.0,
        'unit' => MeasurementUnit::CUP,
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan]);

    $counts = $component->get('categoryItemCounts');
    expect($counts)->toBeArray()
        ->and($counts)->toHaveKey(IngredientCategory::SOUPS_AND_CANNED_GOODS->value);
});

test('generate action passes excluded categories to generator and stores on list', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $pantryIngredient = Ingredient::factory()->create(['name' => 'Salt', 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS]);
    $dairyIngredient = Ingredient::factory()->create(['name' => 'Milk', 'category' => IngredientCategory::DAIRY]);

    $recipe->recipeIngredients()->createMany([
        ['ingredient_id' => $pantryIngredient->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::TSP],
        ['ingredient_id' => $dairyIngredient->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP],
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->set('excludedCategories', ['soups-and-canned-goods'])
        ->call('generate');

    $groceryList = GroceryList::where('meal_plan_id', $mealPlan->id)->first();
    expect($groceryList)->not->toBeNull()
        ->and($groceryList->excluded_categories)->toBe(['soups-and-canned-goods']);

    $itemCategories = $groceryList->groceryItems->pluck('category');
    expect($itemCategories)->not->toContain(IngredientCategory::SOUPS_AND_CANNED_GOODS)
        ->and($itemCategories)->toContain(IngredientCategory::DAIRY);
});

// --- Show component ---

test('show component displays exclusion callout when excluded_categories is set', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'excluded_categories' => ['soups-and-canned-goods'],
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('Soups And Canned Goods'); // Excluded category should be visible in the callout
});

test('show component does not display exclusion callout when excluded_categories is null', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'excluded_categories' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertDontSee('categories excluded'); // Callout phrase should not appear
});

test('show component showRegenerateConfirmation pre-populates regenerateExcludedCategories from list', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'excluded_categories' => ['soups-and-canned-goods', 'dairy'],
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->call('showRegenerateConfirmation')
        ->assertSet('regenerateExcludedCategories', ['soups-and-canned-goods', 'dairy'])
        ->assertSet('showRegenerateConfirm', true);
});

test('show regenerate action passes excluded categories to generator', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $recipe = Recipe::factory()->create(['user_id' => $user->id]);
    $pantryIngredient = Ingredient::factory()->create(['name' => 'Salt', 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS]);
    $dairyIngredient = Ingredient::factory()->create(['name' => 'Butter', 'category' => IngredientCategory::DAIRY]);

    $recipe->recipeIngredients()->createMany([
        ['ingredient_id' => $pantryIngredient->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::TSP],
        ['ingredient_id' => $dairyIngredient->id, 'quantity' => 0.5, 'unit' => MeasurementUnit::CUP],
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    // Pre-existing grocery list (so it can be regenerated)
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'excluded_categories' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->set('regenerateExcludedCategories', ['soups-and-canned-goods'])
        ->call('regenerate');

    $updatedList = $groceryList->fresh();
    expect($updatedList->excluded_categories)->toBe(['soups-and-canned-goods']);

    $itemCategories = $updatedList->groceryItems->pluck('category');
    expect($itemCategories)->not->toContain(IngredientCategory::SOUPS_AND_CANNED_GOODS);
});

// ──────────────────────────────────────────────────────────
// T024: Feature tests for preference lifecycle (US3)
// ──────────────────────────────────────────────────────────

test('savePreferences stores selection on users grocery_category_exclusions', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->set('excludedCategories', ['soups-and-canned-goods', 'dairy'])
        ->call('savePreferences');

    expect($user->fresh()->grocery_category_exclusions)->toBe(['soups-and-canned-goods', 'dairy']);
});

test('clearPreferences sets grocery_category_exclusions to null', function () {
    $user = User::factory()->create(['grocery_category_exclusions' => ['soups-and-canned-goods']]);
    $mealPlan = MealPlan::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->call('clearPreferences');

    expect($user->fresh()->grocery_category_exclusions)->toBeNull();
});

test('generate component mounts with saved preferences when no existing list', function () {
    $user = User::factory()->create(['grocery_category_exclusions' => ['dairy']]);
    $mealPlan = MealPlan::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertSet('excludedCategories', ['dairy']);
});

test('existing list excluded_categories takes precedence over saved preferences', function () {
    $user = User::factory()->create(['grocery_category_exclusions' => ['dairy']]);
    $mealPlan = MealPlan::factory()->for($user)->create();

    GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'excluded_categories' => ['soups-and-canned-goods'],
    ]);

    // The list's exclusions (pantry) should override the user's saved preference (dairy)
    Livewire::actingAs($user)
        ->test(Generate::class, ['mealPlan' => $mealPlan])
        ->assertSet('excludedCategories', ['soups-and-canned-goods']);
});
