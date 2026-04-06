<?php

declare(strict_types=1);

use App\Enums\MealType;
use App\Enums\SharePermission;
use App\Models\ContentShare;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\MealPlanNote;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the mobile calendar container in the page HTML', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-16',
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('data-mobile-calendar', false);
});

it('renders a day header for each date in the meal plan on mobile', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-16',
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    // l, M j format: "Tuesday, Oct 14"
    $response->assertSee('Tuesday, Oct 14');
    $response->assertSee('Wednesday, Oct 15');
    $response->assertSee('Thursday, Oct 16');
});

it('renders all four meal type section labels on mobile', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('uppercase tracking-wide mb-2', false);
    $response->assertSee('Breakfast');
    $response->assertSee('Lunch');
    $response->assertSee('Dinner');
    $response->assertSee('Snack');
});

it('does not show mobile container above md breakpoint', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    // Mobile container uses "block md:hidden" to hide above md breakpoint
    $response->assertSee('block md:hidden', false);
});

it('preserves the desktop table in the rendered output', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    // Desktop table wrapped in hidden md:block
    $response->assertSee('hidden md:block', false);
    $response->assertSee('<table', false);
});

it('renders assigned recipe names in mobile day card slots', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);
    $recipe = Recipe::factory()->create(['name' => 'Spaghetti Bolognese']);
    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-14',
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('Spaghetti Bolognese');
    $response->assertSee('mobile-recipe-', false);
});

it('renders note titles in mobile day card slots', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);
    MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-14',
        'meal_type' => MealType::DINNER,
        'title' => 'My Special Note',
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('My Special Note');
    $response->assertSee('mobile-note-', false);
});

it('shows nothing planned text when a meal slot has no items', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('Nothing planned');
});

it('includes openRecipeDrawer wire handler on mobile recipe cards', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);
    $recipe = Recipe::factory()->create(['name' => 'Pasta Primavera']);
    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-14',
        'meal_type' => 'lunch',
        'serving_multiplier' => 1.0,
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('openRecipeDrawer', false);
});

it('includes openNoteDrawer wire handler on mobile note cards', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);
    MealPlanNote::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'date' => '2025-10-14',
        'meal_type' => MealType::LUNCH,
        'title' => 'Note With Handler',
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('openNoteDrawer', false);
});

it('includes openRecipeSelector wire handler in mobile add buttons', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('openRecipeSelector', false);
});

it('includes openNoteForm wire handler in mobile add buttons', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('openNoteForm', false);
});

it('does not show add buttons for read-only shared users', function () {
    $owner = User::factory()->create();
    $reader = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($owner)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);
    ContentShare::factory()->forMealPlan($mealPlan)->create([
        'owner_id' => $owner->id,
        'recipient_id' => $reader->id,
        'permission' => SharePermission::Read,
    ]);

    $response = $this->actingAs($reader)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertDontSee('openRecipeSelector', false);
    $response->assertDontSee('openNoteForm', false);
});

it('renders serving multiplier on mobile recipe card when not 1x', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);
    $recipe = Recipe::factory()->create(['name' => 'Chicken Tikka', 'servings' => 4]);
    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-14',
        'meal_type' => 'dinner',
        'serving_multiplier' => 2.0,
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertSee('2.00x');
    $response->assertSee('8 servings');
});

it('does not render serving multiplier on mobile recipe card when 1x', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-14',
    ]);
    $recipe = Recipe::factory()->create(['name' => 'Simple Salad', 'servings' => 2]);
    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-14',
        'meal_type' => 'lunch',
        'serving_multiplier' => 1.0,
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    $response->assertDontSee('1.00x');
    $response->assertSee('2 servings');
});
