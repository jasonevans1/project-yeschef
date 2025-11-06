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

test('user can edit own recipe with all fields', function () {
    actingAs($this->user);

    // Create a recipe first
    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Original Recipe',
        'description' => 'Original description',
        'prep_time' => 10,
        'cook_time' => 20,
        'servings' => 4,
        'meal_type' => MealType::LUNCH,
        'cuisine' => 'American',
        'difficulty' => 'easy',
        'dietary_tags' => ['vegetarian'],
        'instructions' => 'Original instructions',
    ]);

    // Add an ingredient
    $ingredient = Ingredient::factory()->create(['name' => 'salt']);
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient->id,
        'quantity' => 1,
        'unit' => MeasurementUnit::TSP,
        'sort_order' => 0,
    ]);

    // Edit the recipe
    Volt::test('recipes.edit', ['recipe' => $recipe])
        ->set('name', 'Updated Recipe')
        ->set('description', 'Updated description')
        ->set('prep_time', 15)
        ->set('cook_time', 30)
        ->set('servings', 6)
        ->set('meal_type', MealType::DINNER->value)
        ->set('cuisine', 'Italian')
        ->set('difficulty', 'medium')
        ->set('dietary_tags', ['vegetarian', 'gluten-free'])
        ->set('instructions', 'Updated instructions here')
        ->call('update')
        ->assertHasNoErrors()
        ->assertRedirect();

    $recipe->refresh();

    expect($recipe->name)->toBe('Updated Recipe');
    expect($recipe->description)->toBe('Updated description');
    expect($recipe->prep_time)->toBe(15);
    expect($recipe->cook_time)->toBe(30);
    expect($recipe->servings)->toBe(6);
    expect($recipe->meal_type)->toBe(MealType::DINNER);
    expect($recipe->cuisine)->toBe('Italian');
    expect($recipe->difficulty)->toBe('medium');
    expect($recipe->dietary_tags)->toBe(['vegetarian', 'gluten-free']);
    expect($recipe->instructions)->toBe('Updated instructions here');
});

test('user can add ingredients to recipe', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Recipe',
        'instructions' => 'Test instructions',
    ]);

    // Start with one ingredient
    $ingredient1 = Ingredient::factory()->create(['name' => 'flour']);
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient1->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 0,
    ]);

    expect($recipe->recipeIngredients()->count())->toBe(1);

    // Add two more ingredients
    Volt::test('recipes.edit', ['recipe' => $recipe])
        ->set('name', 'Test Recipe')
        ->set('instructions', 'Test instructions')
        ->set('ingredients', [
            [
                'ingredient_name' => 'flour',
                'quantity' => 2,
                'unit' => MeasurementUnit::CUP->value,
                'notes' => null,
            ],
            [
                'ingredient_name' => 'sugar',
                'quantity' => 1,
                'unit' => MeasurementUnit::CUP->value,
                'notes' => null,
            ],
            [
                'ingredient_name' => 'eggs',
                'quantity' => 2,
                'unit' => MeasurementUnit::WHOLE->value,
                'notes' => null,
            ],
        ])
        ->call('update')
        ->assertHasNoErrors();

    $recipe->refresh();
    expect($recipe->recipeIngredients()->count())->toBe(3);
});

test('user can remove ingredients from recipe', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Recipe',
        'instructions' => 'Test instructions',
    ]);

    // Start with three ingredients
    $ingredient1 = Ingredient::factory()->create(['name' => 'flour']);
    $ingredient2 = Ingredient::factory()->create(['name' => 'sugar']);
    $ingredient3 = Ingredient::factory()->create(['name' => 'eggs']);

    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient1->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 0,
    ]);

    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient2->id,
        'quantity' => 1,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 1,
    ]);

    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient3->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::WHOLE,
        'sort_order' => 2,
    ]);

    expect($recipe->recipeIngredients()->count())->toBe(3);

    // Remove one ingredient (sugar)
    Volt::test('recipes.edit', ['recipe' => $recipe])
        ->set('name', 'Test Recipe')
        ->set('instructions', 'Test instructions')
        ->set('ingredients', [
            [
                'ingredient_name' => 'flour',
                'quantity' => 2,
                'unit' => MeasurementUnit::CUP->value,
                'notes' => null,
            ],
            [
                'ingredient_name' => 'eggs',
                'quantity' => 2,
                'unit' => MeasurementUnit::WHOLE->value,
                'notes' => null,
            ],
        ])
        ->call('update')
        ->assertHasNoErrors();

    $recipe->refresh();
    expect($recipe->recipeIngredients()->count())->toBe(2);

    // Verify sugar is not in the recipe anymore
    $ingredientNames = $recipe->recipeIngredients()
        ->with('ingredient')
        ->get()
        ->pluck('ingredient.name')
        ->map(fn ($name) => strtolower($name))
        ->toArray();

    expect($ingredientNames)->not->toContain('sugar');
    expect($ingredientNames)->toContain('flour');
    expect($ingredientNames)->toContain('eggs');
});

test('user can update ingredient quantities', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Recipe',
        'instructions' => 'Test instructions',
    ]);

    $ingredient = Ingredient::factory()->create(['name' => 'flour']);
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient->id,
        'quantity' => 2,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 0,
        'notes' => 'all-purpose',
    ]);

    // Update quantity, unit, and notes
    Volt::test('recipes.edit', ['recipe' => $recipe])
        ->set('name', 'Test Recipe')
        ->set('instructions', 'Test instructions')
        ->set('ingredients', [
            [
                'ingredient_name' => 'flour',
                'quantity' => 3.5,
                'unit' => MeasurementUnit::LB->value,
                'notes' => 'bread flour',
            ],
        ])
        ->call('update')
        ->assertHasNoErrors();

    $recipe->refresh();
    $recipeIngredient = $recipe->recipeIngredients()->first();

    expect((float) $recipeIngredient->quantity)->toBe(3.5);
    expect($recipeIngredient->unit)->toBe(MeasurementUnit::LB);
    expect($recipeIngredient->notes)->toBe('bread flour');
});

test('user cannot edit system recipe', function () {
    actingAs($this->user);

    // Create a system recipe (user_id = null)
    $systemRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'System Recipe',
        'instructions' => 'System instructions',
    ]);

    // Try to edit - should be forbidden
    $response = $this->get(route('recipes.edit', $systemRecipe));
    $response->assertForbidden();
});

test('user cannot edit another user\'s recipe', function () {
    actingAs($this->user);

    $otherUser = User::factory()->create();
    $otherRecipe = Recipe::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other User Recipe',
        'instructions' => 'Other user instructions',
    ]);

    // Try to access edit page - should be forbidden
    $response = $this->get(route('recipes.edit', $otherRecipe));
    $response->assertForbidden();
});

test('changes persist correctly after editing', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Original Name',
        'description' => 'Original description',
        'instructions' => 'Original instructions',
    ]);

    $ingredient1 = Ingredient::factory()->create(['name' => 'ingredient 1']);
    $ingredient2 = Ingredient::factory()->create(['name' => 'ingredient 2']);

    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient1->id,
        'quantity' => 1,
        'unit' => MeasurementUnit::CUP,
        'sort_order' => 0,
    ]);

    // Edit recipe
    Volt::test('recipes.edit', ['recipe' => $recipe])
        ->set('name', 'Modified Name')
        ->set('description', 'Modified description')
        ->set('instructions', 'Modified instructions')
        ->set('ingredients', [
            [
                'ingredient_name' => 'ingredient 2',
                'quantity' => 2,
                'unit' => MeasurementUnit::TBSP->value,
                'notes' => 'chopped',
            ],
        ])
        ->call('update')
        ->assertHasNoErrors();

    // Verify in database
    assertDatabaseHas('recipes', [
        'id' => $recipe->id,
        'name' => 'Modified Name',
        'description' => 'Modified description',
        'instructions' => 'Modified instructions',
    ]);

    // Refresh and verify all changes persisted
    $recipe->refresh();

    expect($recipe->name)->toBe('Modified Name');
    expect($recipe->description)->toBe('Modified description');
    expect($recipe->instructions)->toBe('Modified instructions');

    // Verify ingredient was replaced
    expect($recipe->recipeIngredients()->count())->toBe(1);

    $recipeIngredient = $recipe->recipeIngredients()->first();
    expect($recipeIngredient->ingredient_id)->toBe($ingredient2->id);
    expect((float) $recipeIngredient->quantity)->toBe(2.0);
    expect($recipeIngredient->unit)->toBe(MeasurementUnit::TBSP);
    expect($recipeIngredient->notes)->toBe('chopped');
});

test('validation requires name when editing', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Recipe',
        'instructions' => 'Test instructions',
    ]);

    Volt::test('recipes.edit', ['recipe' => $recipe])
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
        ->call('update')
        ->assertHasErrors(['name']);
});

test('validation requires instructions when editing', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Recipe',
        'instructions' => 'Test instructions',
    ]);

    Volt::test('recipes.edit', ['recipe' => $recipe])
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
        ->call('update')
        ->assertHasErrors(['instructions']);
});

test('validation requires at least one ingredient when editing', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Recipe',
        'instructions' => 'Test instructions',
    ]);

    Volt::test('recipes.edit', ['recipe' => $recipe])
        ->set('name', 'Test Recipe')
        ->set('instructions', 'Test instructions')
        ->set('ingredients', [])
        ->call('update')
        ->assertHasErrors(['ingredients']);
});

test('ingredient sort order is maintained when editing', function () {
    actingAs($this->user);

    $recipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Test Recipe',
        'instructions' => 'Test instructions',
    ]);

    // Edit with specific ingredient order
    Volt::test('recipes.edit', ['recipe' => $recipe])
        ->set('name', 'Test Recipe')
        ->set('instructions', 'Test instructions')
        ->set('ingredients', [
            [
                'ingredient_name' => 'First Ingredient',
                'quantity' => 1,
                'unit' => MeasurementUnit::CUP->value,
                'notes' => null,
            ],
            [
                'ingredient_name' => 'Second Ingredient',
                'quantity' => 2,
                'unit' => MeasurementUnit::TBSP->value,
                'notes' => null,
            ],
            [
                'ingredient_name' => 'Third Ingredient',
                'quantity' => 3,
                'unit' => MeasurementUnit::TSP->value,
                'notes' => null,
            ],
        ])
        ->call('update')
        ->assertHasNoErrors();

    $recipe->refresh();

    $ingredients = $recipe->recipeIngredients()->orderBy('sort_order')->get();

    expect($ingredients)->toHaveCount(3);
    expect(strtolower($ingredients[0]->ingredient->name))->toBe('first ingredient');
    expect($ingredients[0]->sort_order)->toBe(0);
    expect(strtolower($ingredients[1]->ingredient->name))->toBe('second ingredient');
    expect($ingredients[1]->sort_order)->toBe(1);
    expect(strtolower($ingredients[2]->ingredient->name))->toBe('third ingredient');
    expect($ingredients[2]->sort_order)->toBe(2);
});
