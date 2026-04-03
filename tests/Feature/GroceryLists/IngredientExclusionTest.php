<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Livewire\GroceryLists\Show;
use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('it pre-populates regenerateExcludedIngredients from groceryList excluded_ingredients on showRegenerateConfirmation', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'excluded_ingredients' => ['salt', 'pepper'],
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->call('showRegenerateConfirmation')
        ->assertSet('regenerateExcludedIngredients', ['salt', 'pepper'])
        ->assertSet('showRegenerateConfirm', true);
});

test('it passes regenerateExcludedIngredients to generator on regenerate', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $recipe = Recipe::factory()->create(['user_id' => $user->id]);
    $saltIngredient = Ingredient::factory()->create(['name' => 'Salt', 'category' => IngredientCategory::COOKING_AND_BAKING]);
    $butterIngredient = Ingredient::factory()->create(['name' => 'Butter', 'category' => IngredientCategory::DAIRY]);

    $recipe->recipeIngredients()->createMany([
        ['ingredient_id' => $saltIngredient->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::TSP],
        ['ingredient_id' => $butterIngredient->id, 'quantity' => 0.5, 'unit' => MeasurementUnit::CUP],
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'excluded_ingredients' => ['salt'],
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->set('regenerateExcludedIngredients', ['salt'])
        ->call('regenerate');

    $updatedList = $groceryList->fresh();
    expect($updatedList->excluded_ingredients)->toBe(['salt']);
});

test('it excluded ingredient is absent from regenerated grocery items', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();

    $recipe = Recipe::factory()->create(['user_id' => $user->id]);
    $saltIngredient = Ingredient::factory()->create(['name' => 'Salt', 'category' => IngredientCategory::COOKING_AND_BAKING]);
    $butterIngredient = Ingredient::factory()->create(['name' => 'Butter', 'category' => IngredientCategory::DAIRY]);

    $recipe->recipeIngredients()->createMany([
        ['ingredient_id' => $saltIngredient->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::TSP],
        ['ingredient_id' => $butterIngredient->id, 'quantity' => 0.5, 'unit' => MeasurementUnit::CUP],
    ]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'excluded_ingredients' => ['salt'],
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->set('regenerateExcludedIngredients', ['salt'])
        ->call('regenerate');

    $itemNames = $groceryList->fresh()->groceryItems->pluck('name')->map(fn ($n) => strtolower($n));
    expect($itemNames)->not->toContain('salt')
        ->and($itemNames)->toContain('butter');
});

test('it resets regenerateExcludedIngredients on cancelRegenerate', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'excluded_ingredients' => ['salt'],
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->call('showRegenerateConfirmation')
        ->assertSet('regenerateExcludedIngredients', ['salt'])
        ->call('cancelRegenerate')
        ->assertSet('regenerateExcludedIngredients', []);
});

test('it uses empty array when groceryList has no excluded_ingredients', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $groceryList = GroceryList::factory()->create([
        'user_id' => $user->id,
        'meal_plan_id' => $mealPlan->id,
        'excluded_ingredients' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->call('showRegenerateConfirmation')
        ->assertSet('regenerateExcludedIngredients', []);
});
