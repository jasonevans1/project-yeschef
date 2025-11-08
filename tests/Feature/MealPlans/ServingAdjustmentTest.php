<?php

use App\Enums\MealType;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows user to set serving_multiplier when assigning recipe', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe = Recipe::factory()->create(['servings' => 4]);

    $response = $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER->value,
        'serving_multiplier' => 1.5,
    ]);

    $response->assertRedirect();

    $assignment = MealAssignment::where('meal_plan_id', $mealPlan->id)
        ->where('recipe_id', $recipe->id)
        ->first();

    expect($assignment)->not->toBeNull();
    expect($assignment->serving_multiplier)->toBe('1.50');
});

it('uses default multiplier of 1.0 when not specified', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe = Recipe::factory()->create(['servings' => 4]);

    $response = $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER->value,
    ]);

    $response->assertRedirect();

    $assignment = MealAssignment::where('meal_plan_id', $mealPlan->id)
        ->where('recipe_id', $recipe->id)
        ->first();

    expect($assignment)->not->toBeNull();
    expect($assignment->serving_multiplier)->toBe('1.00');
});

it('validates serving_multiplier range is between 0.25 and 10.0', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe = Recipe::factory()->create(['servings' => 4]);

    // Test below minimum
    $response = $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER->value,
        'serving_multiplier' => 0.2,
    ]);

    $response->assertSessionHasErrors('serving_multiplier');

    // Test above maximum
    $response = $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER->value,
        'serving_multiplier' => 11.0,
    ]);

    $response->assertSessionHasErrors('serving_multiplier');

    // Test within range (minimum)
    $response = $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER->value,
        'serving_multiplier' => 0.25,
    ]);

    $response->assertRedirect();

    // Test within range (maximum)
    $response = $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-16',
        'meal_type' => MealType::DINNER->value,
        'serving_multiplier' => 10.0,
    ]);

    $response->assertRedirect();

    expect(MealAssignment::count())->toBe(2);
});

it('saves meal assignment with multiplier correctly', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe = Recipe::factory()->create(['servings' => 4]);

    $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-16',
        'meal_type' => MealType::BREAKFAST->value,
        'serving_multiplier' => 2.5,
    ]);

    $assignment = MealAssignment::where('meal_plan_id', $mealPlan->id)->first();

    expect($assignment)->not->toBeNull();
    expect($assignment->meal_plan_id)->toBe($mealPlan->id);
    expect($assignment->recipe_id)->toBe($recipe->id);
    expect($assignment->date->format('Y-m-d'))->toBe('2025-10-16');
    expect($assignment->meal_type)->toBe(MealType::BREAKFAST);
    expect($assignment->serving_multiplier)->toBe('2.50');
});

it('shows adjusted serving count in meal plan view', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe = Recipe::factory()->create(['servings' => 4]);

    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 1.5,
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertSuccessful();

    // Calculate expected servings: 4 * 1.5 = 6
    $expectedServings = $recipe->servings * 1.5;

    // Check that the view shows the adjusted servings
    $response->assertSee((string) $expectedServings);
});

it('uses serving_multiplier in grocery list generation via ServingSizeScaler service', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    // Create a recipe with ingredients
    $recipe = Recipe::factory()
        ->hasAttached(
            \App\Models\Ingredient::factory()->create(['name' => 'Chicken Breast']),
            [
                'quantity' => 2,
                'unit' => \App\Enums\MeasurementUnit::LB,
                'sort_order' => 0,
            ]
        )
        ->create(['servings' => 4]);

    // Assign recipe with 1.5x multiplier
    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 1.5,
    ]);

    // Generate grocery list
    $groceryListGenerator = app(\App\Services\GroceryListGenerator::class);
    $groceryList = $groceryListGenerator->generate($mealPlan);

    expect($groceryList)->not->toBeNull();
    expect($groceryList->groceryItems)->toHaveCount(1);

    // Check that quantity was scaled: 2 lbs * 1.5 = 3 lbs
    $item = $groceryList->groceryItems->first();
    expect($item->name)->toBe('Chicken breast');
    expect($item->quantity)->toBe('3.000');
});
