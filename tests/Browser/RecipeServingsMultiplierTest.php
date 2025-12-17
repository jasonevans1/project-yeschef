<?php

declare(strict_types=1);

use App\Enums\MeasurementUnit;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;

use function Pest\Laravel\actingAs;

// User Story 1 (009-recipe-servings-multiplier): Browser tests for multiplier interactions
// Note: These are TDD placeholder tests - will be fully implemented after E2E tests (Playwright) are working

test('user can type custom multiplier value', function () {
    $this->markTestSkipped('Browser test placeholder - use Playwright E2E tests instead');
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create(['servings' => 4]);
    $flour = Ingredient::factory()->create(['name' => 'Flour']);

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($flour)
        ->create(['quantity' => 2.000, 'unit' => MeasurementUnit::CUP, 'sort_order' => 1]);

    $page = actingAs($user)
        ->visit("/recipes/{$recipe->id}");

    $page->assertSee('Servings')
        ->assertSee('4');

    // Note: Actual multiplier input interaction will be tested after implementation
    // This test will fail until T014-T016 are complete (TDD approach)
});

test('ingredient quantities update when multiplier changes', function () {
    $this->markTestSkipped('Browser test placeholder - use Playwright E2E tests instead');
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create(['servings' => 4]);
    $flour = Ingredient::factory()->create(['name' => 'Flour']);
    $sugar = Ingredient::factory()->create(['name' => 'Sugar']);

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($flour)
        ->create(['quantity' => 2.000, 'unit' => MeasurementUnit::CUP, 'sort_order' => 1]);

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($sugar)
        ->create(['quantity' => 1.5, 'unit' => MeasurementUnit::CUP, 'sort_order' => 2]);

    $page = actingAs($user)
        ->visit("/recipes/{$recipe->id}");

    // Verify original quantities are displayed
    $page->assertSee('2')
        ->assertSee('cup')
        ->assertSee('Flour')
        ->assertSee('1.5')
        ->assertSee('Sugar');

    // Note: Multiplier interaction and quantity update verification
    // will be completed after T014-T016 (TDD approach)
});

test('multiplier validates range (0.25 to 10)', function () {
    $this->markTestSkipped('Browser test placeholder - use Playwright E2E tests instead');
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create(['servings' => 4]);

    $page = actingAs($user)
        ->visit("/recipes/{$recipe->id}");

    $page->assertSee('Servings');

    // Note: Range validation will be tested after multiplier input is implemented
    // This test will fail until T014-T015 are complete (TDD approach)
});

test('multiplier resets to 1x on page reload', function () {
    $this->markTestSkipped('Browser test placeholder - use Playwright E2E tests instead');
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create(['servings' => 4]);
    $flour = Ingredient::factory()->create(['name' => 'Flour']);

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($flour)
        ->create(['quantity' => 2.000, 'unit' => MeasurementUnit::CUP, 'sort_order' => 1]);

    $page = actingAs($user)
        ->visit("/recipes/{$recipe->id}");

    // Verify initial state
    $page->assertSee('2')
        ->assertSee('cup')
        ->assertSee('Flour');

    // Note: Multiplier change and reload verification will be completed
    // after T014-T016 (TDD approach)
});

test('ingredients without quantities remain unchanged', function () {
    $this->markTestSkipped('Browser test placeholder - use Playwright E2E tests instead');
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create(['servings' => 4]);
    $salt = Ingredient::factory()->create(['name' => 'Salt']);
    $flour = Ingredient::factory()->create(['name' => 'Flour']);

    // Ingredient with quantity
    RecipeIngredient::factory()
        ->for($recipe)
        ->for($flour)
        ->create(['quantity' => 2.000, 'unit' => MeasurementUnit::CUP, 'sort_order' => 1]);

    // Ingredient without quantity (to taste)
    RecipeIngredient::factory()
        ->for($recipe)
        ->for($salt)
        ->create(['quantity' => null, 'unit' => null, 'notes' => 'to taste', 'sort_order' => 2]);

    $page = actingAs($user)
        ->visit("/recipes/{$recipe->id}");

    // Verify ingredients are displayed
    $page->assertSee('2')
        ->assertSee('cup')
        ->assertSee('Flour')
        ->assertSee('to taste');

    // Note: Null quantity handling with multiplier will be verified
    // after T014-T016 (TDD approach)
});
