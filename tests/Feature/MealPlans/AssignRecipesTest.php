<?php

use App\Enums\MealType;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows user to assign recipe to meal slot', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe = Recipe::factory()->create();

    $response = $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER->value,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('meal_assignments', [
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER->value,
    ]);
});

it('saves meal assignment with all required fields', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe = Recipe::factory()->create();

    $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-16',
        'meal_type' => MealType::BREAKFAST->value,
    ]);

    $assignment = MealAssignment::where('meal_plan_id', $mealPlan->id)->first();

    expect($assignment)->not->toBeNull();
    expect($assignment->meal_plan_id)->toBe($mealPlan->id);
    expect($assignment->recipe_id)->toBe($recipe->id);
    expect($assignment->date->format('Y-m-d'))->toBe('2025-10-16');
    expect($assignment->meal_type)->toBe(MealType::BREAKFAST);
});

it('prevents duplicate assignments to same meal slot', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe1 = Recipe::factory()->create();
    $recipe2 = Recipe::factory()->create();

    // First assignment should succeed
    $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe1->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER->value,
    ]);

    // Second assignment to same slot should fail
    $response = $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe2->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER->value,
    ]);

    $response->assertSessionHasErrors();

    // Only first assignment should exist
    expect(MealAssignment::count())->toBe(1);
    expect(MealAssignment::first()->recipe_id)->toBe($recipe1->id);
});

it('allows reassigning different recipe to same slot by replacing', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe1 = Recipe::factory()->create();
    $recipe2 = Recipe::factory()->create();

    // Create initial assignment
    $assignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe1->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
    ]);

    // Update assignment to new recipe
    $response = $this->actingAs($user)->put(route('meal-plans.assignments.update', [$mealPlan, $assignment]), [
        'recipe_id' => $recipe2->id,
    ]);

    $response->assertRedirect();

    $assignment->refresh();
    expect($assignment->recipe_id)->toBe($recipe2->id);
});

it('prevents assignment to date outside meal plan range', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe = Recipe::factory()->create();

    // Try to assign before start date
    $response = $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-13',
        'meal_type' => MealType::DINNER->value,
    ]);

    $response->assertSessionHasErrors('date');

    // Try to assign after end date
    $response = $this->actingAs($user)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-21',
        'meal_type' => MealType::DINNER->value,
    ]);

    $response->assertSessionHasErrors('date');
});

it('requires authentication to assign recipes', function () {
    $mealPlan = MealPlan::factory()->create();
    $recipe = Recipe::factory()->create();

    $response = $this->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER->value,
    ]);

    $response->assertRedirect(route('login'));
});

it('prevents user from assigning recipes to another users meal plan', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($owner)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe = Recipe::factory()->create();

    $response = $this->actingAs($otherUser)->post(route('meal-plans.assignments.store', $mealPlan), [
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER->value,
    ]);

    $response->assertForbidden();
});
