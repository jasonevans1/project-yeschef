<?php

use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows user to create a meal plan with name, start_date, and end_date', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('meal-plans.store'), [
        'name' => 'Week of Oct 14',
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
        'description' => 'My weekly meal plan',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('meal_plans', [
        'user_id' => $user->id,
        'name' => 'Week of Oct 14',
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
        'description' => 'My weekly meal plan',
    ]);
});

it('requires name, start_date, and end_date fields', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('meal-plans.store'), []);

    $response->assertSessionHasErrors(['name', 'start_date', 'end_date']);
});

it('validates that end_date is greater than or equal to start_date', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('meal-plans.store'), [
        'name' => 'Invalid Plan',
        'start_date' => '2025-10-20',
        'end_date' => '2025-10-14',
    ]);

    $response->assertSessionHasErrors('end_date');
});

it('validates maximum duration of 28 days', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('meal-plans.store'), [
        'name' => 'Too Long Plan',
        'start_date' => '2025-10-01',
        'end_date' => '2025-11-01', // 31 days
    ]);

    $response->assertSessionHasErrors('end_date');
});

it('saves meal plan with correct user_id', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)->post(route('meal-plans.store'), [
        'name' => 'My Plan',
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $mealPlan = MealPlan::where('name', 'My Plan')->first();

    expect($mealPlan->user_id)->toBe($user->id);
    expect($mealPlan->user_id)->not->toBe($otherUser->id);
});

it('redirects to meal plan show page after creation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('meal-plans.store'), [
        'name' => 'Week Plan',
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $mealPlan = MealPlan::where('name', 'Week Plan')->first();

    $response->assertRedirect(route('meal-plans.show', $mealPlan));
});

it('requires authentication to create meal plan', function () {
    $response = $this->post(route('meal-plans.store'), [
        'name' => 'Unauthenticated Plan',
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $response->assertRedirect(route('login'));
});
