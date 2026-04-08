<?php

declare(strict_types=1);

use App\Enums\MeasurementUnit;
use App\Livewire\Recipes\Create;
use App\Livewire\Recipes\Edit;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use App\Services\QuantityFormatter;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

it('parses ascii fraction one slash two to zero point five', function () {
    expect(QuantityFormatter::parse('1/2'))->toBe(0.5);
});

it('parses mixed number one space one slash two to one point five', function () {
    expect(QuantityFormatter::parse('1 1/2'))->toBe(1.5);
});

it('parses unicode half character to zero point five', function () {
    expect(QuantityFormatter::parse('½'))->toBe(0.5);
});

it('parses unicode mixed one and half to one point five', function () {
    expect(QuantityFormatter::parse('1½'))->toBe(1.5);
});

it('parses plain decimal string unchanged', function () {
    expect(QuantityFormatter::parse('0.5'))->toBe(0.5);
    expect(QuantityFormatter::parse('2'))->toBe(2.0);
});

it('rejects invalid fraction string with validation error', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'Test Recipe')
        ->set('instructions', 'Test instructions that are long enough')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Flour',
                'quantity' => 'abc',
                'unit' => MeasurementUnit::CUP->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasErrors(['ingredients.0.quantity']);
});

it('saves ingredient with fraction quantity correctly', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'Test Recipe')
        ->set('instructions', 'Test instructions that are long enough')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Flour',
                'quantity' => '1/2',
                'unit' => MeasurementUnit::CUP->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();
});

it('creates recipe with fraction quantity that stores decimal in database', function () {
    $user = User::factory()->create();

    actingAs($user);

    Livewire::test(Create::class)
        ->set('name', 'Fraction Recipe')
        ->set('instructions', 'Test instructions that are long enough')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Sugar',
                'quantity' => '1 1/2',
                'unit' => MeasurementUnit::CUP->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $recipe = Recipe::where('name', 'Fraction Recipe')->first();
    expect($recipe)->not->toBeNull();

    $ingredient = $recipe->recipeIngredients->first();
    expect((float) $ingredient->quantity)->toBe(1.5);
});

it('edits recipe with fraction quantity that stores decimal in database', function () {
    $user = User::factory()->create();

    $recipe = Recipe::factory()->create([
        'user_id' => $user->id,
        'name' => 'Edit Fraction Recipe',
        'instructions' => 'Original instructions',
    ]);

    $recipe->recipeIngredients()->create([
        'ingredient_id' => Ingredient::factory()->create(['name' => 'butter'])->id,
        'quantity' => 1.0,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 0,
    ]);

    actingAs($user);

    Livewire::test(Edit::class, ['recipe' => $recipe])
        ->set('ingredients', [
            [
                'ingredient_name' => 'Butter',
                'quantity' => '¾',
                'unit' => MeasurementUnit::CUP->value,
                'notes' => null,
            ],
        ])
        ->call('update')
        ->assertHasNoErrors();

    $recipe->refresh();
    $updatedIngredient = $recipe->recipeIngredients->first();
    expect((float) $updatedIngredient->quantity)->toBe(0.75);
});
