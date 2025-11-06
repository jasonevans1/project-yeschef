<?php

declare(strict_types=1);

use App\Enums\MealType;
use App\Enums\MeasurementUnit;
use App\Models\Ingredient;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can delete own recipe', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Recipe to Delete',
        'instructions' => 'Test instructions',
    ]);

    $recipeId = $recipe->id;

    // Verify recipe exists
    assertDatabaseHas('recipes', [
        'id' => $recipeId,
        'name' => 'Recipe to Delete',
    ]);

    // Delete the recipe
    $response = $this->delete(route('recipes.destroy', $recipe));

    // Verify redirect
    $response->assertRedirect(route('recipes.index'));

    // Verify recipe is deleted from database
    assertDatabaseMissing('recipes', [
        'id' => $recipeId,
    ]);
});

test('recipe and recipe_ingredients cascade delete', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Recipe with Ingredients',
        'instructions' => 'Test instructions',
    ]);

    // Add ingredients to the recipe
    $ingredient1 = Ingredient::factory()->create(['name' => 'flour']);
    $ingredient2 = Ingredient::factory()->create(['name' => 'sugar']);
    $ingredient3 = Ingredient::factory()->create(['name' => 'eggs']);

    $recipeIngredient1 = $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient1->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 0,
    ]);

    $recipeIngredient2 = $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient2->id,
        'quantity' => 1,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 1,
    ]);

    $recipeIngredient3 = $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient3->id,
        'quantity' => 3,
        'unit' => MeasurementUnit::WHOLE,
        'sort_order' => 2,
    ]);

    $recipeId = $recipe->id;
    $recipeIngredient1Id = $recipeIngredient1->id;
    $recipeIngredient2Id = $recipeIngredient2->id;
    $recipeIngredient3Id = $recipeIngredient3->id;

    // Verify recipe and recipe_ingredients exist
    expect(Recipe::find($recipeId))->not->toBeNull();
    expect(RecipeIngredient::find($recipeIngredient1Id))->not->toBeNull();
    expect(RecipeIngredient::find($recipeIngredient2Id))->not->toBeNull();
    expect(RecipeIngredient::find($recipeIngredient3Id))->not->toBeNull();

    // Delete the recipe
    $response = $this->delete(route('recipes.destroy', $recipe));
    $response->assertRedirect();

    // Verify recipe is deleted
    expect(Recipe::find($recipeId))->toBeNull();

    // Verify recipe_ingredients are cascade deleted
    expect(RecipeIngredient::find($recipeIngredient1Id))->toBeNull();
    expect(RecipeIngredient::find($recipeIngredient2Id))->toBeNull();
    expect(RecipeIngredient::find($recipeIngredient3Id))->toBeNull();

    // Verify ingredients themselves still exist (not deleted)
    expect(Ingredient::find($ingredient1->id))->not->toBeNull();
    expect(Ingredient::find($ingredient2->id))->not->toBeNull();
    expect(Ingredient::find($ingredient3->id))->not->toBeNull();
});

test('user cannot delete system recipe', function () {
    actingAs($this->user);

    // Create a system recipe (user_id = null)
    $systemRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'System Recipe',
        'instructions' => 'System instructions',
    ]);

    // Try to delete - should be forbidden
    $response = $this->delete(route('recipes.destroy', $systemRecipe));
    $response->assertForbidden();

    // Verify recipe still exists
    assertDatabaseHas('recipes', [
        'id' => $systemRecipe->id,
        'name' => 'System Recipe',
    ]);
});

test('user cannot delete another user\'s recipe', function () {
    actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRecipe = Recipe::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other User Recipe',
        'instructions' => 'Other user instructions',
    ]);

    // Try to delete - should be forbidden
    $response = $this->delete(route('recipes.destroy', $otherRecipe));
    $response->assertForbidden();

    // Verify recipe still exists
    assertDatabaseHas('recipes', [
        'id' => $otherRecipe->id,
        'name' => 'Other User Recipe',
    ]);
});

test('recipe preserved in existing meal plans due to ON DELETE RESTRICT on meal_assignments', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Recipe in Meal Plan',
        'instructions' => 'Test instructions',
    ]);

    // Create a meal plan
    $mealPlan = MealPlan::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Meal Plan',
        'start_date' => now(),
        'end_date' => now()->addDays(7),
    ]);

    // Assign the recipe to the meal plan
    $mealAssignment = MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
        'date' => now()->addDay(),
        'meal_type' => MealType::DINNER,
        'serving_multiplier' => 1.0,
    ]);

    // Verify meal assignment exists
    assertDatabaseHas('meal_assignments', [
        'id' => $mealAssignment->id,
        'recipe_id' => $recipe->id,
    ]);

    // Try to delete the recipe - should redirect with error message
    $response = $this->delete(route('recipes.destroy', $recipe));

    // Verify redirect to recipe show page with error message
    $response->assertRedirect(route('recipes.show', $recipe));
    $response->assertSessionHas('error');

    // Verify recipe still exists
    assertDatabaseHas('recipes', [
        'id' => $recipe->id,
        'name' => 'Recipe in Meal Plan',
    ]);

    // Verify meal assignment still exists
    assertDatabaseHas('meal_assignments', [
        'id' => $mealAssignment->id,
        'recipe_id' => $recipe->id,
    ]);
});

test('deleting recipe removes it from user\'s recipe list', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Recipe to Remove',
        'instructions' => 'Test instructions',
    ]);

    // Verify user has the recipe
    expect($this->user->recipes()->where('id', $recipe->id)->exists())->toBeTrue();

    // Delete the recipe
    $response = $this->delete(route('recipes.destroy', $recipe));
    $response->assertRedirect();

    // Verify user no longer has the recipe
    $this->user->refresh();
    expect($this->user->recipes()->where('id', $recipe->id)->exists())->toBeFalse();
});

test('delete route returns 404 for non-existent recipe', function () {
    actingAs($this->user);

    // Try to delete a non-existent recipe
    $response = $this->delete(route('recipes.destroy', 999999));
    $response->assertNotFound();
});

test('multiple recipes can be deleted independently', function () {
    actingAs($this->user);

    $recipe1 = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Recipe 1',
        'instructions' => 'Instructions 1',
    ]);

    $recipe2 = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Recipe 2',
        'instructions' => 'Instructions 2',
    ]);

    $recipe3 = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Recipe 3',
        'instructions' => 'Instructions 3',
    ]);

    $recipe1Id = $recipe1->id;
    $recipe2Id = $recipe2->id;
    $recipe3Id = $recipe3->id;

    // Delete recipe 2
    $response = $this->delete(route('recipes.destroy', $recipe2));
    $response->assertRedirect();

    // Verify only recipe 2 is deleted
    assertDatabaseHas('recipes', ['id' => $recipe1Id]);
    assertDatabaseMissing('recipes', ['id' => $recipe2Id]);
    assertDatabaseHas('recipes', ['id' => $recipe3Id]);

    // Delete recipe 1
    $response = $this->delete(route('recipes.destroy', $recipe1));
    $response->assertRedirect();

    // Verify recipe 1 is deleted, recipe 3 still exists
    assertDatabaseMissing('recipes', ['id' => $recipe1Id]);
    assertDatabaseHas('recipes', ['id' => $recipe3Id]);
});

test('unauthenticated user cannot delete recipe', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Protected Recipe',
        'instructions' => 'Test instructions',
    ]);

    // Try to delete without authentication
    $response = $this->delete(route('recipes.destroy', $recipe));
    $response->assertRedirect(route('login'));

    // Verify recipe still exists
    assertDatabaseHas('recipes', [
        'id' => $recipe->id,
        'name' => 'Protected Recipe',
    ]);
});
