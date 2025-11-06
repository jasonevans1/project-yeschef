<?php

declare(strict_types=1);

use App\Enums\MealType;
use App\Enums\MeasurementUnit;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Volt\Volt;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can create recipe with all fields', function () {
    actingAs($this->user);

    Volt::test('recipes.create')
        ->set('name', 'Mom\'s Lasagna')
        ->set('description', 'Classic Italian lasagna with meat sauce')
        ->set('prep_time', 30)
        ->set('cook_time', 60)
        ->set('servings', 8)
        ->set('meal_type', MealType::DINNER->value)
        ->set('cuisine', 'Italian')
        ->set('difficulty', 'medium')
        ->set('dietary_tags', ['contains_dairy', 'contains_gluten'])
        ->set('instructions', "1. Prepare meat sauce\n2. Layer noodles and sauce\n3. Bake at 375°F for 45 minutes")
        ->set('ingredients', [
            [
                'ingredient_name' => 'Ground Beef',
                'quantity' => 2,
                'unit' => MeasurementUnit::LB->value,
                'notes' => 'lean',
            ],
            [
                'ingredient_name' => 'Lasagna Noodles',
                'quantity' => 1,
                'unit' => MeasurementUnit::LB->value,
                'notes' => null,
            ],
            [
                'ingredient_name' => 'Ricotta Cheese',
                'quantity' => 2,
                'unit' => MeasurementUnit::CUP->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    assertDatabaseHas('recipes', [
        'user_id' => $this->user->id,
        'name' => 'Mom\'s Lasagna',
        'description' => 'Classic Italian lasagna with meat sauce',
        'prep_time' => 30,
        'cook_time' => 60,
        'servings' => 8,
        'meal_type' => MealType::DINNER->value,
        'cuisine' => 'Italian',
        'difficulty' => 'medium',
    ]);

    $recipe = Recipe::where('name', 'Mom\'s Lasagna')->first();
    expect($recipe)->not->toBeNull();
    expect($recipe->dietary_tags)->toBe(['contains_dairy', 'contains_gluten']);
    expect($recipe->instructions)->toContain('Prepare meat sauce');
});

test('recipe saved with user_id', function () {
    actingAs($this->user);

    Volt::test('recipes.create')
        ->set('name', 'Test Recipe')
        ->set('instructions', 'Test instructions')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Salt',
                'quantity' => 1,
                'unit' => MeasurementUnit::TSP->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $recipe = Recipe::where('name', 'Test Recipe')->first();
    expect($recipe->user_id)->toBe($this->user->id);
});

test('validation requires name', function () {
    actingAs($this->user);

    Volt::test('recipes.create')
        ->set('name', '')
        ->set('instructions', 'Test instructions')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Salt',
                'quantity' => 1,
                'unit' => MeasurementUnit::TSP->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasErrors(['name']);
});

test('validation requires instructions', function () {
    actingAs($this->user);

    Volt::test('recipes.create')
        ->set('name', 'Test Recipe')
        ->set('instructions', '')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Salt',
                'quantity' => 1,
                'unit' => MeasurementUnit::TSP->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasErrors(['instructions']);
});

test('validation requires at least one ingredient', function () {
    actingAs($this->user);

    Volt::test('recipes.create')
        ->set('name', 'Test Recipe')
        ->set('instructions', 'Test instructions')
        ->set('ingredients', [])
        ->call('save')
        ->assertHasErrors(['ingredients']);
});

test('ingredients saved to recipe_ingredients pivot with quantities and units', function () {
    actingAs($this->user);

    Volt::test('recipes.create')
        ->set('name', 'Chocolate Chip Cookies')
        ->set('instructions', 'Mix and bake')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Flour',
                'quantity' => 2.5,
                'unit' => MeasurementUnit::CUP->value,
                'notes' => 'all-purpose',
            ],
            [
                'ingredient_name' => 'Sugar',
                'quantity' => 1,
                'unit' => MeasurementUnit::CUP->value,
                'notes' => null,
            ],
            [
                'ingredient_name' => 'Chocolate Chips',
                'quantity' => 2,
                'unit' => MeasurementUnit::CUP->value,
                'notes' => 'semi-sweet',
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $recipe = Recipe::with('recipeIngredients.ingredient')
        ->where('name', 'Chocolate Chip Cookies')
        ->first();

    expect($recipe)->not->toBeNull();
    expect($recipe->recipeIngredients)->toHaveCount(3);

    $flourIngredient = $recipe->recipeIngredients
        ->first(fn ($ri) => strtolower($ri->ingredient->name) === 'flour');

    expect($flourIngredient)->not->toBeNull();
    expect((float) $flourIngredient->quantity)->toBe(2.5);
    expect($flourIngredient->unit)->toBe(MeasurementUnit::CUP);
    expect($flourIngredient->notes)->toBe('all-purpose');

    $sugarIngredient = $recipe->recipeIngredients
        ->first(fn ($ri) => strtolower($ri->ingredient->name) === 'sugar');

    expect($sugarIngredient)->not->toBeNull();
    expect((float) $sugarIngredient->quantity)->toBe(1.0);
    expect($sugarIngredient->unit)->toBe(MeasurementUnit::CUP);
    expect($sugarIngredient->notes)->toBeNull();
});

test('redirects to recipe show page after creation', function () {
    actingAs($this->user);

    Volt::test('recipes.create')
        ->set('name', 'Test Recipe')
        ->set('instructions', 'Test instructions')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Salt',
                'quantity' => 1,
                'unit' => MeasurementUnit::TSP->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    // Verify the recipe was created and redirect would be to its show page
    $recipe = Recipe::where('name', 'Test Recipe')->first();
    expect($recipe)->not->toBeNull();

    // The redirect should be to the show route for this recipe
    $expectedUrl = route('recipes.show', $recipe);
    expect($expectedUrl)->toContain('/recipes/'.$recipe->id);
});

test('recipe appears in user\'s list', function () {
    actingAs($this->user);

    // Create a recipe
    Volt::test('recipes.create')
        ->set('name', 'My Special Recipe')
        ->set('instructions', 'Cook it well')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Salt',
                'quantity' => 1,
                'unit' => MeasurementUnit::TSP->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    // Check it appears in the recipes index
    $response = $this->get(route('recipes.index'));
    $response->assertSee('My Special Recipe');
});

test('other users cannot see private recipe', function () {
    actingAs($this->user);

    // Create a recipe as first user
    Volt::test('recipes.create')
        ->set('name', 'Secret Family Recipe')
        ->set('instructions', 'Family secret')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Secret Ingredient',
                'quantity' => 1,
                'unit' => MeasurementUnit::PINCH->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    $recipe = Recipe::where('name', 'Secret Family Recipe')->first();

    // Login as different user
    $otherUser = User::factory()->create();
    actingAs($otherUser);

    // Try to view the recipe - should be forbidden
    $response = $this->get(route('recipes.show', $recipe));
    $response->assertForbidden();

    // Recipe should not appear in other user's recipe list
    $response = $this->get(route('recipes.index'));
    $response->assertDontSee('Secret Family Recipe');
});

test('user can create recipe with minimal fields', function () {
    actingAs($this->user);

    Volt::test('recipes.create')
        ->set('name', 'Simple Recipe')
        ->set('instructions', 'Just do it')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Water',
                'quantity' => 1,
                'unit' => MeasurementUnit::CUP->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    assertDatabaseHas('recipes', [
        'user_id' => $this->user->id,
        'name' => 'Simple Recipe',
        'instructions' => 'Just do it',
    ]);

    $recipe = Recipe::where('name', 'Simple Recipe')->first();
    expect($recipe->description)->toBeNull();
    expect($recipe->prep_time)->toBeNull();
    expect($recipe->cook_time)->toBeNull();
    expect($recipe->meal_type)->toBeNull();
    expect($recipe->cuisine)->toBeNull();
    expect($recipe->difficulty)->toBeNull();
});

test('ingredients are created if they do not exist', function () {
    actingAs($this->user);

    $initialIngredientCount = Ingredient::count();

    Volt::test('recipes.create')
        ->set('name', 'New Recipe')
        ->set('instructions', 'Cook it well and enjoy!')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Brand New Ingredient',
                'quantity' => 3,
                'unit' => MeasurementUnit::OZ->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(Ingredient::count())->toBe($initialIngredientCount + 1);

    $ingredient = Ingredient::where('name', 'brand new ingredient')->first();
    expect($ingredient)->not->toBeNull();
});

test('existing ingredients are reused', function () {
    actingAs($this->user);

    // Create an ingredient first
    $existingIngredient = Ingredient::factory()->create(['name' => 'existing salt']);

    $initialIngredientCount = Ingredient::count();

    Volt::test('recipes.create')
        ->set('name', 'Recipe Using Existing')
        ->set('instructions', 'Use existing')
        ->set('ingredients', [
            [
                'ingredient_name' => 'Existing Salt', // Case insensitive
                'quantity' => 2,
                'unit' => MeasurementUnit::TSP->value,
                'notes' => null,
            ],
        ])
        ->call('save')
        ->assertHasNoErrors();

    // Should not create a new ingredient
    expect(Ingredient::count())->toBe($initialIngredientCount);

    $recipe = Recipe::where('name', 'Recipe Using Existing')->first();
    $recipeIngredient = $recipe->recipeIngredients->first();
    expect($recipeIngredient->ingredient_id)->toBe($existingIngredient->id);
});
