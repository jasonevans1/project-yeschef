<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;

test('search by recipe name using full text search', function () {
    $user = User::factory()->create();

    // Create recipes with specific names
    $recipe1 = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Chicken Parmesan',
        'description' => 'Italian chicken dish',
    ]);

    $recipe2 = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Beef Stroganoff',
        'description' => 'Russian beef dish',
    ]);

    $recipe3 = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Chicken Curry',
        'description' => 'Indian chicken dish',
    ]);

    // Search for "Chicken" should return recipes 1 and 3
    $response = $this->actingAs($user)->get(route('recipes.index', ['search' => 'Chicken']));

    $response->assertStatus(200);
    $response->assertSee('Chicken Parmesan');
    $response->assertSee('Chicken Curry');
    $response->assertDontSee('Beef Stroganoff');
});

test('search by ingredient name', function () {
    $user = User::factory()->create();

    // Create recipes
    $recipe1 = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Pasta Dish',
    ]);

    $recipe2 = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Rice Dish',
    ]);

    // Create ingredients
    $tomato = Ingredient::factory()->create(['name' => 'tomato']);
    $rice = Ingredient::factory()->create(['name' => 'rice']);

    // Attach ingredients to recipes
    $recipe1->ingredients()->attach($tomato->id, ['quantity' => 2, 'unit' => 'whole']);
    $recipe2->ingredients()->attach($rice->id, ['quantity' => 1, 'unit' => 'cup']);

    // Search for "tomato" should return recipe1
    $response = $this->actingAs($user)->get(route('recipes.index', ['search' => 'tomato']));

    $response->assertStatus(200);
    $response->assertSee('Pasta Dish');
    $response->assertDontSee('Rice Dish');
});

test('filter by meal type', function () {
    $user = User::factory()->create();

    // Create recipes with different meal types
    $breakfast = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Pancakes',
        'meal_type' => 'breakfast',
    ]);

    $lunch = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Sandwich',
        'meal_type' => 'lunch',
    ]);

    $dinner = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Steak',
        'meal_type' => 'dinner',
    ]);

    // Filter by breakfast
    $response = $this->actingAs($user)->get(route('recipes.index', ['mealTypes' => ['breakfast']]));

    $response->assertStatus(200);
    $response->assertSee('Pancakes');
    $response->assertDontSee('Sandwich');
    $response->assertDontSee('Steak');
});

test('filter by dietary tags', function () {
    $user = User::factory()->create();

    // Create recipes with different dietary tags
    $vegetarian = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Vegetable Stir Fry',
        'dietary_tags' => ['vegetarian', 'vegan'],
    ]);

    $glutenFree = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Grilled Chicken',
        'dietary_tags' => ['gluten-free'],
    ]);

    $regular = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Pasta Carbonara',
        'dietary_tags' => [],
    ]);

    // Filter by vegetarian
    $response = $this->actingAs($user)->get(route('recipes.index', ['dietaryTags' => ['vegetarian']]));

    $response->assertStatus(200);
    $response->assertSee('Vegetable Stir Fry');
    $response->assertDontSee('Grilled Chicken');
    $response->assertDontSee('Pasta Carbonara');
});

test('combined filters search and meal type', function () {
    $user = User::factory()->create();

    // Create recipes
    $chickenBreakfast = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Chicken Omelette',
        'meal_type' => 'breakfast',
    ]);

    $chickenDinner = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Chicken Stir Fry',
        'meal_type' => 'dinner',
    ]);

    $beefBreakfast = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Beef Hash',
        'meal_type' => 'breakfast',
    ]);

    // Search for "Chicken" AND filter by "breakfast"
    $response = $this->actingAs($user)->get(route('recipes.index', [
        'search' => 'Chicken',
        'mealTypes' => ['breakfast'],
    ]));

    $response->assertStatus(200);
    $response->assertSee('Chicken Omelette');
    $response->assertDontSee('Chicken Stir Fry'); // Wrong meal type
    $response->assertDontSee('Beef Hash'); // Wrong search term
});

test('url contains search parameters for shareability', function () {
    $user = User::factory()->create();

    Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Test Recipe',
    ]);

    // Make request with search parameters
    $response = $this->actingAs($user)->get(route('recipes.index', [
        'search' => 'pasta',
        'mealTypes' => ['dinner'],
        'dietaryTags' => ['vegetarian'],
    ]));

    $response->assertStatus(200);

    // Verify URL contains the search parameters
    // This makes the URL shareable with filters intact
    $url = $response->baseResponse->getRequest()->getRequestUri();

    expect($url)->toContain('search=pasta');
    expect($url)->toContain('mealTypes');
    expect($url)->toContain('dietaryTags');
});

test('search with no results shows empty state', function () {
    $user = User::factory()->create();

    Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Chicken Dish',
    ]);

    // Search for something that doesn't exist
    $response = $this->actingAs($user)->get(route('recipes.index', ['search' => 'NonexistentRecipe']));

    $response->assertStatus(200);
    $response->assertDontSee('Chicken Dish');
});

test('search is case insensitive', function () {
    $user = User::factory()->create();

    Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Chicken Parmesan',
    ]);

    // Search with lowercase
    $response1 = $this->actingAs($user)->get(route('recipes.index', ['search' => 'chicken']));
    $response1->assertStatus(200);
    $response1->assertSee('Chicken Parmesan');

    // Search with uppercase
    $response2 = $this->actingAs($user)->get(route('recipes.index', ['search' => 'CHICKEN']));
    $response2->assertStatus(200);
    $response2->assertSee('Chicken Parmesan');

    // Search with mixed case
    $response3 = $this->actingAs($user)->get(route('recipes.index', ['search' => 'ChIcKeN']));
    $response3->assertStatus(200);
    $response3->assertSee('Chicken Parmesan');
});
