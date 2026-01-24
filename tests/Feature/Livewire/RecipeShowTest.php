<?php

declare(strict_types=1);

use App\Enums\MeasurementUnit;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;

use function Pest\Laravel\actingAs;

// User Story 1: Feature test for whole number quantities

test('recipe page displays formatted whole number quantities without decimals', function () {
    $user = User::factory()->create();
    $ingredient1 = Ingredient::factory()->create();
    $ingredient2 = Ingredient::factory()->create();

    $recipe = Recipe::factory()->for($user)->create();

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($ingredient1)
        ->create(['quantity' => 2.000, 'unit' => MeasurementUnit::CUP, 'sort_order' => 1]);

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($ingredient2)
        ->create(['quantity' => 1.0, 'unit' => MeasurementUnit::LB, 'sort_order' => 2]);

    $response = actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();

    // Alpine.js renders quantities client-side, so check the x-text bindings
    // Quantity is cast as decimal:3, so it appears as "2.000" in the binding
    // But display_quantity strips trailing zeros for the fallback
    $response->assertSee('scaleQuantity', false);
    $response->assertSee('scaleQuantity(2.000)', false);
    $response->assertSee('scaleQuantity(1.000)', false);

    // Check units are displayed
    $response->assertSeeText('cup');
    $response->assertSeeText('lb');

    // Should NOT see trailing zeros in the fallback values
    $response->assertDontSee("|| '2.000'", false);
    $response->assertDontSee("|| '1.000'", false);
});

// User Story 2: Feature test for fractional quantities

test('recipe page displays fractional quantities with minimal precision', function () {
    $user = User::factory()->create();
    $sugar = Ingredient::factory()->create();
    $butter = Ingredient::factory()->create();

    $recipe = Recipe::factory()->for($user)->create();

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($sugar)
        ->create(['quantity' => 1.500, 'unit' => MeasurementUnit::CUP, 'sort_order' => 1]);

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($butter)
        ->create(['quantity' => 0.750, 'unit' => MeasurementUnit::CUP, 'sort_order' => 2]);

    $response = actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();

    // With Alpine.js, quantities are rendered client-side via scaleQuantity()
    // Check for the Alpine.js binding pattern and ingredient names
    $response->assertSee('x-text');
    $response->assertSeeText($sugar->name);
    $response->assertSeeText($butter->name);

    // Verify the quantity values are passed to scaleQuantity in the x-text binding
    // The raw HTML will contain: x-text="scaleQuantity(1.5) || '1.5'"
    $response->assertSee('scaleQuantity', false);
    $response->assertSee('1.5', false);
    $response->assertSee('0.75', false);
});

// User Story 3: Feature test for edge cases (null quantities)

test('recipe page handles null quantities gracefully', function () {
    $user = User::factory()->create();
    $salt = Ingredient::factory()->create();

    $recipe = Recipe::factory()->for($user)->create();

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($salt)
        ->create(['quantity' => null, 'unit' => null, 'notes' => 'to taste', 'sort_order' => 1]);

    $response = actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();

    // Should display notes when quantity is null (notes takes precedence over ingredient name)
    $response->assertSeeText('to taste');
});

// Test for ingredients with quantity but no unit (like "2 Eggs")

test('recipe page displays quantity even when unit is null', function () {
    $user = User::factory()->create();
    $eggs = Ingredient::factory()->create();

    $recipe = Recipe::factory()->for($user)->create();

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($eggs)
        ->create(['quantity' => 2.000, 'unit' => null, 'sort_order' => 1]);

    $response = actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();

    // Alpine.js renders quantities client-side
    // Quantity is cast as decimal:3, so it appears as "2.000" in the binding
    $response->assertSee('scaleQuantity(2.000)', false);
    $response->assertSeeText($eggs->name);

    // Should NOT see trailing zeros in the fallback value
    $response->assertDontSee("|| '2.000'", false);
});

// User Story 1 (009-recipe-servings-multiplier): Test multiplier state management

test('recipe show page loads with default multiplier state', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create(['servings' => 4]);

    $response = actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();
    $response->assertSeeLivewire('recipes.show');
});

test('recipe show page displays servings info', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->for($user)->create(['servings' => 4]);

    $response = actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();
    $response->assertSeeText('Servings');
    $response->assertSeeText('4');
});
