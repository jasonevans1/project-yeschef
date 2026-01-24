<?php

declare(strict_types=1);

use App\Enums\MeasurementUnit;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;

use function Pest\Laravel\actingAs;

// T009: Recipe ingredients display with checkboxes
test('recipe ingredients display with checkboxes', function () {
    $user = User::factory()->create();
    $flour = Ingredient::factory()->create(['name' => 'Flour']);
    $sugar = Ingredient::factory()->create(['name' => 'Sugar']);

    $recipe = Recipe::factory()->for($user)->create(['name' => 'Test Recipe']);

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($flour)
        ->create(['quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'sort_order' => 1]);

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($sugar)
        ->create(['quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'sort_order' => 2]);

    actingAs($user);

    $page = visit(route('recipes.show', $recipe));

    // Verify checkboxes are present
    $page->assertSee('Ingredients')
        ->assertSee('Flour')
        ->assertSee('Sugar');

    // Verify checkboxes exist (role="checkbox" from Flux component)
    $checkboxes = $page->locator('input[type="checkbox"]');
    expect($checkboxes->count())->toBe(2);

    // Verify checkboxes are initially unchecked
    expect($checkboxes->first()->isChecked())->toBeFalse();
    expect($checkboxes->last()->isChecked())->toBeFalse();
})->group('browser');

// T010: Checking ingredient applies visual feedback (strikethrough + opacity)
test('checking ingredient applies visual feedback', function () {
    $user = User::factory()->create();
    $flour = Ingredient::factory()->create(['name' => 'Flour']);

    $recipe = Recipe::factory()->for($user)->create();

    $ingredient = RecipeIngredient::factory()
        ->for($recipe)
        ->for($flour)
        ->create(['quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'sort_order' => 1]);

    actingAs($user);

    $page = visit(route('recipes.show', $recipe));

    // Find the checkbox for the first ingredient
    $checkbox = $page->locator('input[type="checkbox"]')->first();

    // Get the parent container that should have visual feedback
    $ingredientContainer = $page->locator('.flex-1')->first();

    // Verify initial state (no strikethrough, full opacity)
    expect($ingredientContainer->getAttribute('class'))->not->toContain('line-through');
    expect($ingredientContainer->getAttribute('class'))->not->toContain('opacity-50');

    // Check the checkbox
    $checkbox->click();

    // Verify visual feedback is applied
    $page->waitForFunction(
        fn () => document.querySelector('.flex-1').classList.contains('line-through')
    );

    expect($ingredientContainer->getAttribute('class'))->toContain('line-through');
    expect($ingredientContainer->getAttribute('class'))->toContain('opacity-50');

    // Uncheck the checkbox
    $checkbox->click();

    // Verify visual feedback is removed
    $page->waitForFunction(
        fn () => ! document.querySelector('.flex-1').classList.contains('line-through')
    );

    expect($ingredientContainer->getAttribute('class'))->not->toContain('line-through');
})->group('browser');

// T011: Checkbox state persists during in-app navigation
test('checkbox state persists during in-app navigation', function () {
    $user = User::factory()->create();
    $flour = Ingredient::factory()->create(['name' => 'Flour']);

    $recipe = Recipe::factory()->for($user)->create();

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($flour)
        ->create(['quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'sort_order' => 1]);

    actingAs($user);

    $page = visit(route('recipes.show', $recipe));

    // Check the first ingredient
    $checkbox = $page->locator('input[type="checkbox"]')->first();
    $checkbox->click();

    // Verify it's checked
    expect($checkbox->isChecked())->toBeTrue();

    // Navigate to recipes index using wire:navigate link
    $page->click('text=Back to Recipes');
    $page->assertSee('Recipes'); // On index page

    // Navigate back to the recipe
    $page->goBack();

    // Checkbox state should be reset (new component instance)
    $checkboxAfterNav = $page->locator('input[type="checkbox"]')->first();
    expect($checkboxAfterNav->isChecked())->toBeFalse();
})->group('browser');

// T012: Checkbox state resets on page refresh
test('checkbox state resets on page refresh', function () {
    $user = User::factory()->create();
    $flour = Ingredient::factory()->create(['name' => 'Flour']);

    $recipe = Recipe::factory()->for($user)->create();

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($flour)
        ->create(['quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'sort_order' => 1]);

    actingAs($user);

    $page = visit(route('recipes.show', $recipe));

    // Check the ingredient
    $checkbox = $page->locator('input[type="checkbox"]')->first();
    $checkbox->click();
    expect($checkbox->isChecked())->toBeTrue();

    // Refresh the page
    $page->reload();

    // Checkbox should be unchecked after refresh
    $checkboxAfterRefresh = $page->locator('input[type="checkbox"]')->first();
    expect($checkboxAfterRefresh->isChecked())->toBeFalse();
})->group('browser');

// T013: Multiple ingredients can be checked independently
test('multiple ingredients can be checked independently', function () {
    $user = User::factory()->create();
    $flour = Ingredient::factory()->create(['name' => 'Flour']);
    $sugar = Ingredient::factory()->create(['name' => 'Sugar']);
    $eggs = Ingredient::factory()->create(['name' => 'Eggs']);

    $recipe = Recipe::factory()->for($user)->create();

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($flour)
        ->create(['quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'sort_order' => 1]);

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($sugar)
        ->create(['quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'sort_order' => 2]);

    RecipeIngredient::factory()
        ->for($recipe)
        ->for($eggs)
        ->create(['quantity' => 3.0, 'unit' => MeasurementUnit::WHOLE, 'sort_order' => 3]);

    actingAs($user);

    $page = visit(route('recipes.show', $recipe));

    $checkboxes = $page->locator('input[type="checkbox"]');

    // Check first and third ingredients
    $checkboxes->nth(0)->click();
    $checkboxes->nth(2)->click();

    // Verify state
    expect($checkboxes->nth(0)->isChecked())->toBeTrue();
    expect($checkboxes->nth(1)->isChecked())->toBeFalse();
    expect($checkboxes->nth(2)->isChecked())->toBeTrue();
})->group('browser');
