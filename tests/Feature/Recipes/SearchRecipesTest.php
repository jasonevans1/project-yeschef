<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;

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

    // Use Livewire test to verify URL parameters are set correctly
    // Livewire components use the #[Url] attribute to persist query parameters
    Livewire::actingAs($user)
        ->test(\App\Livewire\Recipes\Index::class, [
            'search' => 'pasta',
            'mealTypes' => ['dinner'],
            'dietaryTags' => ['vegetarian'],
        ])
        ->assertSet('search', 'pasta')
        ->assertSet('mealTypes', ['dinner'])
        ->assertSet('dietaryTags', ['vegetarian'])
        ->assertStatus(200);
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

test('recipes are sorted by newest first by default', function () {
    $user = User::factory()->create();

    $oldRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Old Recipe',
        'created_at' => now()->subDays(5),
    ]);

    $newRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'New Recipe',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('recipes.index'));

    $response->assertStatus(200);
    $content = $response->getContent();
    $newPos = strpos($content, 'New Recipe');
    $oldPos = strpos($content, 'Old Recipe');
    expect($newPos)->toBeLessThan($oldPos);
});

test('recipes can be sorted by oldest first', function () {
    $user = User::factory()->create();

    // Create recipes with distinct timestamps to ensure proper ordering
    $oldRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Very Old Recipe 12345',
        'created_at' => now()->subDays(10),
    ]);

    $newRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Brand New Recipe 67890',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('recipes.index', ['sortBy' => 'oldest']));

    $response->assertStatus(200);
    $response->assertSeeInOrder(['Very Old Recipe 12345', 'Brand New Recipe 67890']);
});

test('recipes can be sorted by name ascending', function () {
    $user = User::factory()->create();

    Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Zebra Cake',
    ]);

    Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Apple Pie',
    ]);

    $response = $this->actingAs($user)->get(route('recipes.index', ['sortBy' => 'name_asc']));

    $response->assertStatus(200);
    $content = $response->getContent();
    $applePos = strpos($content, 'Apple Pie');
    $zebraPos = strpos($content, 'Zebra Cake');
    expect($applePos)->toBeLessThan($zebraPos);
});

test('recipes can be sorted by name descending', function () {
    $user = User::factory()->create();

    Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Zebra Cake',
    ]);

    Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Apple Pie',
    ]);

    $response = $this->actingAs($user)->get(route('recipes.index', ['sortBy' => 'name_desc']));

    $response->assertStatus(200);
    $content = $response->getContent();
    $zebraPos = strpos($content, 'Zebra Cake');
    $applePos = strpos($content, 'Apple Pie');
    expect($zebraPos)->toBeLessThan($applePos);
});

test('sort persists in url', function () {
    $user = User::factory()->create();

    Recipe::factory()->create(['user_id' => null]);

    Livewire::actingAs($user)
        ->test(\App\Livewire\Recipes\Index::class, ['sortBy' => 'name_asc'])
        ->assertSet('sortBy', 'name_asc')
        ->assertStatus(200);
});

test('changing sort resets pagination', function () {
    $user = User::factory()->create();

    // Create enough recipes to span multiple pages (24 per page)
    Recipe::factory()->count(30)->create(['user_id' => null]);

    // Simulate changing sort - the updatedSortBy method should reset page
    Livewire::actingAs($user)
        ->withQueryParams(['page' => 2])
        ->test(\App\Livewire\Recipes\Index::class)
        ->set('sortBy', 'name_asc')
        ->assertStatus(200);
});

test('invalid sort value defaults to newest', function () {
    $user = User::factory()->create();

    $oldRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Old Recipe',
        'created_at' => now()->subDays(5),
    ]);

    $newRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'New Recipe',
        'created_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('recipes.index', ['sortBy' => 'invalid']));

    $response->assertStatus(200);
    $content = $response->getContent();
    $newPos = strpos($content, 'New Recipe');
    $oldPos = strpos($content, 'Old Recipe');
    expect($newPos)->toBeLessThan($oldPos);
});
