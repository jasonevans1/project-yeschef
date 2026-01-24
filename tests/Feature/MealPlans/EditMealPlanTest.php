<?php

use App\Enums\MealType;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows user to remove recipe from meal slot', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe = Recipe::factory()->create();

    $assignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
    ]);

    $response = $this->actingAs($user)->delete(route('meal-plans.assignments.destroy', [$mealPlan, $assignment]));

    $response->assertRedirect();

    $this->assertDatabaseMissing('meal_assignments', [
        'id' => $assignment->id,
    ]);
});

it('allows user to add note to meal assignment', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);
    $recipe = Recipe::factory()->create();

    $assignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
    ]);

    $response = $this->actingAs($user)->put(route('meal-plans.assignments.update', [$mealPlan, $assignment]), [
        'recipe_id' => $recipe->id,
        'notes' => 'Double the recipe for leftovers',
    ]);

    $response->assertRedirect();

    $assignment->refresh();
    expect($assignment->notes)->toBe('Double the recipe for leftovers');
});

it('allows user to update meal plan name and dates', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'name' => 'Original Name',
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
        'description' => 'Original description',
    ]);

    $response = $this->actingAs($user)->put(route('meal-plans.update', $mealPlan), [
        'name' => 'Updated Name',
        'start_date' => '2025-10-15',
        'end_date' => '2025-10-21',
        'description' => 'Updated description',
    ]);

    $response->assertRedirect();

    $mealPlan->refresh();
    expect($mealPlan->name)->toBe('Updated Name');
    expect($mealPlan->start_date->format('Y-m-d'))->toBe('2025-10-15');
    expect($mealPlan->end_date->format('Y-m-d'))->toBe('2025-10-21');
    expect($mealPlan->description)->toBe('Updated description');
});

it('prevents user from editing another users meal plan', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $mealPlan = MealPlan::factory()->for($owner)->create([
        'name' => 'Owners Plan',
    ]);

    $response = $this->actingAs($otherUser)->put(route('meal-plans.update', $mealPlan), [
        'name' => 'Hacked Name',
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $response->assertForbidden();

    $mealPlan->refresh();
    expect($mealPlan->name)->toBe('Owners Plan');
});

it('prevents user from removing assignment from another users meal plan', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $mealPlan = MealPlan::factory()->for($owner)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create();

    $assignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
    ]);

    $response = $this->actingAs($otherUser)->delete(route('meal-plans.assignments.destroy', [$mealPlan, $assignment]));

    $response->assertForbidden();

    $this->assertDatabaseHas('meal_assignments', [
        'id' => $assignment->id,
    ]);
});

it('requires authentication to edit meal plan', function () {
    $mealPlan = MealPlan::factory()->create();

    $response = $this->put(route('meal-plans.update', $mealPlan), [
        'name' => 'New Name',
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $response->assertRedirect(route('login'));
});

it('validates meal plan updates with same rules as creation', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    // Test end_date before start_date
    $response = $this->actingAs($user)->put(route('meal-plans.update', $mealPlan), [
        'name' => 'Updated Plan',
        'start_date' => '2025-10-20',
        'end_date' => '2025-10-14',
    ]);

    $response->assertSessionHasErrors('end_date');

    // Test duration > 28 days
    $response = $this->actingAs($user)->put(route('meal-plans.update', $mealPlan), [
        'name' => 'Updated Plan',
        'start_date' => '2025-10-01',
        'end_date' => '2025-11-01',
    ]);

    $response->assertSessionHasErrors('end_date');
});
