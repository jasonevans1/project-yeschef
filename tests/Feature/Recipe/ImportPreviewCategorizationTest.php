<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Livewire\Recipes\ImportPreview;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

it('assigns a non-other category to a recognized imported ingredient', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Categorization Test Recipe',
        'instructions' => 'Cook the chicken',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => ['1 lb chicken breast'],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'Categorization Test Recipe')->first();
    $ingredient = $recipe->recipeIngredients()->first()->ingredient;

    expect($ingredient->category)->not->toBe(IngredientCategory::OTHER);
    expect($ingredient->category)->toBe(IngredientCategory::MEAT);
});

it('assigns OTHER to an unrecognized imported ingredient', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Unrecognized Ingredient Recipe',
        'instructions' => 'Use the mystery ingredient',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => ['1 tsp xylotrimazine'],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'Unrecognized Ingredient Recipe')->first();
    $ingredient = $recipe->recipeIngredients()->first()->ingredient;

    expect($ingredient->category)->toBe(IngredientCategory::OTHER);
});

it('uses the existing ingredient category when the ingredient was previously imported', function () {
    $user = User::factory()->create();

    // Pre-create the ingredient with a specific category
    $existingIngredient = Ingredient::factory()->create([
        'name' => 'Salmon',
        'category' => IngredientCategory::SEAFOOD,
    ]);

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Salmon Recipe',
        'instructions' => 'Cook the salmon',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => ['1 lb salmon'],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'Salmon Recipe')->first();
    $ingredient = $recipe->recipeIngredients()->first()->ingredient;

    // The pre-existing ingredient should be reused without overwriting its category
    expect($ingredient->id)->toBe($existingIngredient->id);
    expect($ingredient->fresh()->category)->toBe(IngredientCategory::SEAFOOD);
});
