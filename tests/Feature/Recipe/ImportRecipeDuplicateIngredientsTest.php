<?php

declare(strict_types=1);

use App\Livewire\Recipes\ImportPreview;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

// Tests for duplicate ingredient handling during recipe imports

test('basic duplicate ingredients are combined with notes', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe with Duplicate Ingredients',
        'instructions' => 'Mix everything',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => [
            '2 cups flour',
            '1 cup water',
            '1 cup flour', // Duplicate of first ingredient
        ],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'Recipe with Duplicate Ingredients')->first();

    // Should only create 2 recipe ingredients (flour and water)
    expect($recipe->recipeIngredients)->toHaveCount(2);

    // First ingredient (flour) should have notes about duplicate
    $flourIngredient = $recipe->recipeIngredients()
        ->whereHas('ingredient', fn ($q) => $q->where('name', 'flour'))
        ->first();

    expect($flourIngredient->quantity)->toBe('2.000');
    expect($flourIngredient->unit->value)->toBe('cup');
    expect($flourIngredient->notes)->toContain('Also listed as: 1 cup flour');
    expect($flourIngredient->sort_order)->toBe(0);

    // Second ingredient (water) should have no notes
    $waterIngredient = $recipe->recipeIngredients()
        ->whereHas('ingredient', fn ($q) => $q->where('name', 'water'))
        ->first();

    expect($waterIngredient->notes)->toBeNull();
});

test('multiple duplicates are all noted with pipe separator', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe with Multiple Duplicates',
        'instructions' => 'Cook',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => [
            '3 cups flour',
            '1 cup water',
            '1 cup flour',      // First duplicate of flour
            '2 cups flour',     // Second duplicate of flour
        ],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'Recipe with Multiple Duplicates')->first();

    // Should only create 2 recipe ingredients
    expect($recipe->recipeIngredients)->toHaveCount(2);

    $flourIngredient = $recipe->recipeIngredients()
        ->whereHas('ingredient', fn ($q) => $q->where('name', 'flour'))
        ->first();

    expect($flourIngredient->quantity)->toBe('3.000');
    expect($flourIngredient->notes)
        ->toContain('Also listed as: 1 cup flour')
        ->toContain(' | Also listed as: 2 cup flour');
});

test('duplicate with unparseable quantity uses original text', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe with Unparseable Duplicate',
        'instructions' => 'Season to taste',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => [
            '2 tsp salt',
            '1 tsp salt', // Duplicate with same ingredient
        ],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'Recipe with Unparseable Duplicate')->first();

    expect($recipe->recipeIngredients)->toHaveCount(1);

    $saltIngredient = $recipe->recipeIngredients()->first();

    expect($saltIngredient->quantity)->toBe('2.000');
    expect($saltIngredient->unit->value)->toBe('tsp');
    expect($saltIngredient->notes)->toContain('Also listed as: 1 tsp salt');
});

test('first without quantity, duplicate with quantity', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe with Reverse Parsing',
        'instructions' => 'Add water',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => [
            'water',        // First occurrence has no quantity
            '1 cup water',  // Duplicate with quantity
        ],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'Recipe with Reverse Parsing')->first();

    expect($recipe->recipeIngredients)->toHaveCount(1);

    $waterIngredient = $recipe->recipeIngredients()->first();

    // First occurrence stored original as notes (per line 95 of ImportPreview)
    // Then duplicate appends to it
    expect($waterIngredient->notes)
        ->toContain('water')
        ->toContain(' | Also listed as: 1 cup water');
    expect($waterIngredient->sort_order)->toBe(0);
});

test('mixed case and whitespace are normalized', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe with Case Variations',
        'instructions' => 'Mix',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => [
            '2 cups  FLOUR  ',  // Extra whitespace and uppercase
            '1 cup flour',       // Lowercase
        ],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'Recipe with Case Variations')->first();

    // Should detect as duplicate despite case/whitespace differences
    expect($recipe->recipeIngredients)->toHaveCount(1);

    $flourIngredient = $recipe->recipeIngredients()->first();
    expect($flourIngredient->notes)->toContain('Also listed as: 1 cup flour');
});

test('preserves sort_order for first occurrence only', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe with Sort Order Test',
        'instructions' => 'Mix',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => [
            '2 cups flour',  // Index 0
            '1 cup sugar',   // Index 1
            '1 tsp salt',    // Index 2
            '1 cup flour',   // Index 3 - duplicate of index 0
        ],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'Recipe with Sort Order Test')->first();

    expect($recipe->recipeIngredients)->toHaveCount(3);

    // Flour should have sort_order 0 (from first occurrence)
    $flourIngredient = $recipe->recipeIngredients()
        ->whereHas('ingredient', fn ($q) => $q->where('name', 'flour'))
        ->first();
    expect($flourIngredient->sort_order)->toBe(0);

    // Sugar should have sort_order 1
    $sugarIngredient = $recipe->recipeIngredients()
        ->whereHas('ingredient', fn ($q) => $q->where('name', 'sugar'))
        ->first();
    expect($sugarIngredient->sort_order)->toBe(1);

    // Salt should have sort_order 2
    $saltIngredient = $recipe->recipeIngredients()
        ->whereHas('ingredient', fn ($q) => $q->where('name', 'salt'))
        ->first();
    expect($saltIngredient->sort_order)->toBe(2);
});

test('complete import integration with duplicates', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Instant Pot Loaded Potato Soup',
        'description' => 'A hearty soup',
        'instructions' => 'Cook everything together',
        'prep_time' => 15,
        'cook_time' => 30,
        'servings' => 6,
        'source_url' => 'https://fedandfit.com/instant-pot-loaded-potato-soup/',
        'recipeIngredient' => [
            '2 cups water',
            '1 cup chicken broth',
            '4 potatoes',
            '1 cup water',  // Duplicate
            '2 cups potatoes', // Duplicate
        ],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport')
        ->assertHasNoErrors()
        ->assertRedirect();

    $recipe = Recipe::where('name', 'Instant Pot Loaded Potato Soup')->first();

    // Verify recipe created successfully
    expect($recipe)->not->toBeNull();
    expect($recipe->user_id)->toBe($user->id);
    expect($recipe->source_url)->toBe('https://fedandfit.com/instant-pot-loaded-potato-soup/');

    // Should only create 3 unique ingredients
    expect($recipe->recipeIngredients)->toHaveCount(3);

    // Water should have duplicate note
    $waterIngredient = $recipe->recipeIngredients()
        ->whereHas('ingredient', fn ($q) => $q->where('name', 'water'))
        ->first();
    expect($waterIngredient->notes)->toContain('Also listed as: 1 cup water');

    // Potatoes should have duplicate note
    $potatoIngredient = $recipe->recipeIngredients()
        ->whereHas('ingredient', fn ($q) => $q->where('name', 'potatoes'))
        ->first();
    expect($potatoIngredient->notes)->toContain('Also listed as: 2 cup potatoes');
});

test('empty and whitespace ingredients are skipped', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe with Empty Ingredients',
        'instructions' => 'Mix',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => [
            '2 cups flour',
            '',             // Empty string
            '   ',          // Whitespace only
            '1 cup sugar',
        ],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'Recipe with Empty Ingredients')->first();

    // Should only create 2 ingredients (flour and sugar)
    expect($recipe->recipeIngredients)->toHaveCount(2);
});

test('duplicate detection within database transaction', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Transaction Test Recipe',
        'instructions' => 'Mix',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => [
            '2 cups flour',
            '1 cup flour',  // Duplicate - should not cause constraint violation
        ],
    ]);

    // This should not throw a database constraint violation exception
    expect(function () use ($user) {
        Livewire::actingAs($user)
            ->test(ImportPreview::class)
            ->call('confirmImport');
    })->not->toThrow(\Illuminate\Database\QueryException::class);

    $recipe = Recipe::where('name', 'Transaction Test Recipe')->first();
    expect($recipe->recipeIngredients)->toHaveCount(1);
});

test('all ingredients are duplicates of first', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'All Duplicates Recipe',
        'instructions' => 'Add salt multiple times',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => [
            '1 tsp salt',
            '2 tsp salt',
            '3 tsp salt',
            '0.5 tsp salt',
        ],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'All Duplicates Recipe')->first();

    // Should only create 1 ingredient
    expect($recipe->recipeIngredients)->toHaveCount(1);

    $saltIngredient = $recipe->recipeIngredients()->first();

    // Should have all duplicates in notes
    expect($saltIngredient->notes)
        ->toContain('Also listed as: 2 tsp salt')
        ->toContain(' | Also listed as: 3 tsp salt')
        ->toContain(' | Also listed as: 0.5 tsp salt');
});
