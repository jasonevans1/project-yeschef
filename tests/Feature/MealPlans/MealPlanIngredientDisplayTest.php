<?php

declare(strict_types=1);

use App\Enums\MealType;
use App\Enums\MeasurementUnit;
use App\Livewire\MealPlans\Show;
use App\Models\Ingredient;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('displays fraction quantity for meal plan recipe ingredients', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $ingredient = Ingredient::factory()->create(['name' => 'Flour']);

    $recipe = Recipe::factory()
        ->hasAttached($ingredient, [
            'quantity' => 0.5,
            'unit' => MeasurementUnit::CUP,
            'sort_order' => 0,
        ])
        ->create(['servings' => 4]);

    $assignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 1.0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->set('selectedAssignmentId', $assignment->id);

    $scaledIngredients = $component->get('scaledIngredients');

    expect($scaledIngredients)->toHaveCount(1)
        ->and($scaledIngredients[0]['quantity'])->toBe('½');
});

it('displays whole number quantity without fraction for integer values', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $ingredient = Ingredient::factory()->create(['name' => 'Eggs']);

    $recipe = Recipe::factory()
        ->hasAttached($ingredient, [
            'quantity' => 2.0,
            'unit' => MeasurementUnit::WHOLE,
            'sort_order' => 0,
        ])
        ->create(['servings' => 4]);

    $assignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 1.0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->set('selectedAssignmentId', $assignment->id);

    $scaledIngredients = $component->get('scaledIngredients');

    expect($scaledIngredients)->toHaveCount(1)
        ->and($scaledIngredients[0]['quantity'])->toBe('2');
});

it('displays nothing for null quantity on meal plan ingredient', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $ingredient = Ingredient::factory()->create(['name' => 'Salt']);

    $recipe = Recipe::factory()
        ->hasAttached($ingredient, [
            'quantity' => null,
            'unit' => MeasurementUnit::TSP,
            'sort_order' => 0,
        ])
        ->create(['servings' => 4]);

    $assignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 1.0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(Show::class, ['mealPlan' => $mealPlan])
        ->set('selectedAssignmentId', $assignment->id);

    $scaledIngredients = $component->get('scaledIngredients');

    expect($scaledIngredients)->toHaveCount(1)
        ->and($scaledIngredients[0]['quantity'])->toBeNull();
});
