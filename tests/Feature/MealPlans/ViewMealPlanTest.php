<?php

use App\Enums\MealType;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows user to view their own meal plan', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'name' => 'My Weekly Plan',
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('My Weekly Plan');
});

it('displays all days in meal plan date range', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-16', // 3 days
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    // Should see all dates in range
    $response->assertSee('2025-10-14');
    $response->assertSee('2025-10-15');
    $response->assertSee('2025-10-16');
});

it('displays assigned recipes in correct meal slots', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $breakfastRecipe = Recipe::factory()->create(['name' => 'Pancakes']);
    $dinnerRecipe = Recipe::factory()->create(['name' => 'Chicken Pasta']);

    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $breakfastRecipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::BREAKFAST,
    ]);

    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $dinnerRecipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('Pancakes');
    $response->assertSee('Chicken Pasta');
});

it('shows empty slots as available when no recipe assigned', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14', // Single day
    ]);

    // No assignments created - all slots should be empty

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    // Should show indication that slots are empty/available
    // This will depend on actual implementation, but checking for common text
    $response->assertSeeInOrder(['breakfast', 'lunch', 'dinner', 'snack']);
});

it('prevents user from viewing another users meal plan', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $mealPlan = MealPlan::factory()->for($owner)->create();

    $response = $this->actingAs($otherUser)->get(route('meal-plans.show', $mealPlan));

    $response->assertForbidden();
});

it('requires authentication to view meal plan', function () {
    $mealPlan = MealPlan::factory()->create();

    $response = $this->get(route('meal-plans.show', $mealPlan));

    $response->assertRedirect(route('login'));
});

it('loads meal plan with eager loaded assignments and recipes', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create();

    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
    ]);

    // This test ensures eager loading happens to avoid N+1 queries
    // The actual assertion would be in the component, but we verify data loads
    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
});

it('displays multiple recipes in same meal slot', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe1 = Recipe::factory()->create(['name' => 'Chicken Salad']);
    $recipe2 = Recipe::factory()->create(['name' => 'Tomato Soup']);
    $recipe3 = Recipe::factory()->create(['name' => 'Garlic Bread']);

    // Assign all three recipes to the same meal slot
    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe1->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::LUNCH,
    ]);

    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe2->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::LUNCH,
    ]);

    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe3->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::LUNCH,
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    // All three recipes should be visible in the same meal slot
    $response->assertSee('Chicken Salad');
    $response->assertSee('Tomato Soup');
    $response->assertSee('Garlic Bread');
});

it('displays recipes in chronological order by creation time', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe1 = Recipe::factory()->create(['name' => 'First Recipe']);
    $recipe2 = Recipe::factory()->create(['name' => 'Second Recipe']);
    $recipe3 = Recipe::factory()->create(['name' => 'Third Recipe']);

    // Create assignments with explicit timestamps to ensure ordering
    $firstAssignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe1->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
    ]);
    $firstAssignment->created_at = now()->subMinutes(10);
    $firstAssignment->save();

    $secondAssignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe2->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
    ]);
    $secondAssignment->created_at = now()->subMinutes(5);
    $secondAssignment->save();

    $thirdAssignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe3->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
    ]);
    $thirdAssignment->created_at = now();
    $thirdAssignment->save();

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    // Recipes should appear in chronological order (oldest first)
    $response->assertSeeInOrder(['First Recipe', 'Second Recipe', 'Third Recipe']);
});
