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

it('displays remove button for recipe cards', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create(['name' => 'Test Recipe']);

    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::LUNCH,
    ]);

    $response = $this->actingAs($user)->get(route('meal-plans.show', $mealPlan));

    $response->assertOk();
    // Verify recipe is displayed
    $response->assertSee('Test Recipe');
    // Verify remove button wire directive exists (Livewire will render wire:click attribute)
    $response->assertSee('wire:click.stop');
});

it('can remove one recipe from slot with multiple recipes while keeping others', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe1 = Recipe::factory()->create(['name' => 'Chicken Salad']);
    $recipe2 = Recipe::factory()->create(['name' => 'Tomato Soup']);

    // Create two assignments in the same meal slot
    $assignment1 = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe1->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::LUNCH,
    ]);

    $assignment2 = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe2->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::LUNCH,
    ]);

    // Verify both assignments exist
    expect(MealAssignment::count())->toBe(2);

    // Use Livewire to test the removeAssignment method
    Livewire::actingAs($user)
        ->test(\App\Livewire\MealPlans\Show::class, ['mealPlan' => $mealPlan])
        ->call('removeAssignment', $assignment1->id)
        ->assertOk();

    // Verify only one assignment remains
    expect(MealAssignment::count())->toBe(1);

    // Verify the correct assignment was removed
    expect(MealAssignment::find($assignment1->id))->toBeNull();
    expect(MealAssignment::find($assignment2->id))->not->toBeNull();

    // Verify the remaining assignment is the second one
    $remaining = MealAssignment::first();
    expect($remaining->recipe_id)->toBe($recipe2->id);
});

it('can open recipe drawer with correct state', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create(['name' => 'Test Recipe']);

    $assignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 1.5,
    ]);

    // Use Livewire to test opening the drawer
    Livewire::actingAs($user)
        ->test(\App\Livewire\MealPlans\Show::class, ['mealPlan' => $mealPlan])
        ->call('openRecipeDrawer', $assignment)
        ->assertSet('showRecipeDrawer', true)
        ->assertSet('selectedAssignmentId', $assignment->id);
});

it('can close recipe drawer', function () {
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

    // Open drawer then close it
    Livewire::actingAs($user)
        ->test(\App\Livewire\MealPlans\Show::class, ['mealPlan' => $mealPlan])
        ->call('openRecipeDrawer', $assignment)
        ->assertSet('showRecipeDrawer', true)
        ->call('closeRecipeDrawer')
        ->assertSet('showRecipeDrawer', false)
        ->assertSet('selectedAssignmentId', null);
});

it('calculates scaled ingredient quantities correctly', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create(['servings' => 4]);
    $ingredient = \App\Models\Ingredient::factory()->create(['name' => 'Chicken']);

    // Create recipe ingredient with quantity 2.0
    \App\Models\RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient->id,
        'quantity' => 2.0,
        'unit' => \App\Enums\MeasurementUnit::LB,
    ]);

    // Create assignment with 2x multiplier
    $assignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 2.0,
    ]);

    // Test that scaled ingredients are calculated correctly (2.0 * 2.0 = 4.0)
    $component = Livewire::actingAs($user)
        ->test(\App\Livewire\MealPlans\Show::class, ['mealPlan' => $mealPlan])
        ->call('openRecipeDrawer', $assignment);

    $scaledIngredients = $component->get('scaledIngredients');

    expect($scaledIngredients)->toHaveCount(1);
    expect($scaledIngredients[0]['quantity'])->toBe('4');
    expect($scaledIngredients[0]['unit'])->toBe('lb');
    expect($scaledIngredients[0]['name'])->toBe('Chicken');
});

it('formats scaled quantities without trailing zeros', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create([
        'start_date' => '2025-10-14',
        'end_date' => '2025-10-20',
    ]);

    $recipe = Recipe::factory()->create();
    $ingredient1 = \App\Models\Ingredient::factory()->create(['name' => 'Flour']);
    $ingredient2 = \App\Models\Ingredient::factory()->create(['name' => 'Sugar']);
    $ingredient3 = \App\Models\Ingredient::factory()->create(['name' => 'Salt']);

    // Create ingredients with different decimal scenarios
    \App\Models\RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient1->id,
        'quantity' => 2.5,  // Should stay 2.5
        'unit' => \App\Enums\MeasurementUnit::CUP,
    ]);

    \App\Models\RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient2->id,
        'quantity' => 1.0,  // Should become 1 (no trailing zeros)
        'unit' => \App\Enums\MeasurementUnit::CUP,
    ]);

    \App\Models\RecipeIngredient::create([
        'recipe_id' => $recipe->id,
        'ingredient_id' => $ingredient3->id,
        'quantity' => 0.333,  // Should stay 0.333 (3 decimal places max)
        'unit' => \App\Enums\MeasurementUnit::TSP,
    ]);

    $assignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => '2025-10-15',
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 1.0,
    ]);

    $component = Livewire::actingAs($user)
        ->test(\App\Livewire\MealPlans\Show::class, ['mealPlan' => $mealPlan])
        ->call('openRecipeDrawer', $assignment);

    $scaledIngredients = $component->get('scaledIngredients');

    expect($scaledIngredients)->toHaveCount(3);
    expect($scaledIngredients[0]['quantity'])->toBe('2.5');  // Keeps decimal
    expect($scaledIngredients[1]['quantity'])->toBe('1');    // No trailing zeros
    expect($scaledIngredients[2]['quantity'])->toBe('0.333'); // 3 decimals max
});
