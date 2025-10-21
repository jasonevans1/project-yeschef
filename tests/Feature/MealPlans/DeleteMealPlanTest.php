<?php

use App\Enums\MealType;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows user to delete their own meal plan', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'name' => 'Plan to Delete',
    ]);

    $response = $this->actingAs($user)->delete(route('meal-plans.destroy', $mealPlan));

    $response->assertRedirect(route('meal-plans.index'));

    $this->assertDatabaseMissing('meal_plans', [
        'id' => $mealPlan->id,
    ]);
});

it('cascades deletion to meal assignments', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create();

    $assignment1 = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::BREAKFAST,
    ]);

    $assignment2 = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-16',
        'meal_type' => MealType::DINNER,
    ]);

    $this->actingAs($user)->delete(route('meal-plans.destroy', $mealPlan));

    // Meal plan should be deleted
    $this->assertDatabaseMissing('meal_plans', [
        'id' => $mealPlan->id,
    ]);

    // All assignments should be cascade deleted
    $this->assertDatabaseMissing('meal_assignments', [
        'id' => $assignment1->id,
    ]);

    $this->assertDatabaseMissing('meal_assignments', [
        'id' => $assignment2->id,
    ]);
});

it('prevents user from deleting another users meal plan', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $mealPlan = MealPlan::factory()->for($owner)->create([
        'name' => 'Owners Plan',
    ]);

    $response = $this->actingAs($otherUser)->delete(route('meal-plans.destroy', $mealPlan));

    $response->assertForbidden();

    // Meal plan should still exist
    $this->assertDatabaseHas('meal_plans', [
        'id' => $mealPlan->id,
        'name' => 'Owners Plan',
    ]);
});

it('requires authentication to delete meal plan', function () {
    $mealPlan = MealPlan::factory()->create();

    $response = $this->delete(route('meal-plans.destroy', $mealPlan));

    $response->assertRedirect(route('login'));

    // Meal plan should still exist
    $this->assertDatabaseHas('meal_plans', [
        'id' => $mealPlan->id,
    ]);
});

it('preserves recipes after meal plan deletion', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create(['name' => 'Preserved Recipe']);

    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
    ]);

    $this->actingAs($user)->delete(route('meal-plans.destroy', $mealPlan));

    // Recipe should still exist
    $this->assertDatabaseHas('recipes', [
        'id' => $recipe->id,
        'name' => 'Preserved Recipe',
    ]);
});
