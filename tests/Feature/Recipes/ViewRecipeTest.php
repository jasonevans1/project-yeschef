<?php

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\User;

test('user can view system recipe details', function () {
    $user = User::factory()->create();

    // Create a system recipe with all fields
    $recipe = Recipe::factory()->create([
        'user_id' => null, // System recipe
        'name' => 'System Recipe Name',
        'description' => 'Detailed description of the recipe',
        'prep_time' => 15,
        'cook_time' => 30,
        'servings' => 4,
        'instructions' => 'Step 1: Do this. Step 2: Do that.',
    ]);

    $response = $this->actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();
    $response->assertSee('System Recipe Name');
    $response->assertSee('Detailed description of the recipe');
    $response->assertSee('Step 1: Do this. Step 2: Do that.');
});

test('user can view own recipe details', function () {
    $user = User::factory()->create();

    // Create user's personal recipe
    $recipe = Recipe::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Personal Recipe',
        'description' => 'My recipe description',
        'instructions' => 'My cooking instructions',
    ]);

    $response = $this->actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();
    $response->assertSee('My Personal Recipe');
    $response->assertSee('My recipe description');
    $response->assertSee('My cooking instructions');
});

test('recipe shows all fields', function () {
    $user = User::factory()->create();

    $recipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Complete Recipe',
        'description' => 'A complete recipe with all fields',
        'prep_time' => 20,
        'cook_time' => 45,
        'servings' => 6,
        'meal_type' => 'dinner',
        'cuisine' => 'Italian',
        'difficulty' => 'medium',
        'dietary_tags' => ['vegetarian', 'gluten-free'],
        'instructions' => 'Detailed cooking instructions here',
    ]);

    $response = $this->actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();
    $response->assertSee('Complete Recipe');
    $response->assertSee('A complete recipe with all fields');
    $response->assertSee('20'); // prep_time
    $response->assertSee('45'); // cook_time
    $response->assertSee('6'); // servings
    $response->assertSee('Detailed cooking instructions here');
});

test('ingredients list displays with quantities and units', function () {
    $user = User::factory()->create();

    $recipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Recipe with Ingredients',
    ]);

    // Create ingredients and attach to recipe with quantities/units
    $ingredient1 = Ingredient::factory()->create(['name' => 'Flour']);
    $ingredient2 = Ingredient::factory()->create(['name' => 'Sugar']);
    $ingredient3 = Ingredient::factory()->create(['name' => 'Eggs']);

    $recipe->ingredients()->attach($ingredient1->id, [
        'quantity' => 2.5,
        'unit' => 'cup',
        'notes' => 'all-purpose',
    ]);

    $recipe->ingredients()->attach($ingredient2->id, [
        'quantity' => 1.0,
        'unit' => 'cup',
        'notes' => null,
    ]);

    $recipe->ingredients()->attach($ingredient3->id, [
        'quantity' => 3.0,
        'unit' => 'whole',
        'notes' => 'large',
    ]);

    $response = $this->actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();
    $response->assertSee('Flour');
    $response->assertSee('2.5');
    $response->assertSee('cup');
    $response->assertSee('all-purpose');

    $response->assertSee('Sugar');
    $response->assertSee('1');

    $response->assertSee('Eggs');
    $response->assertSee('3');
    $response->assertSee('whole');
    $response->assertSee('large');
});

test('unauthorized user cannot view another users personal recipe', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Create user1's personal recipe
    $recipe = Recipe::factory()->create([
        'user_id' => $user1->id,
        'name' => 'User1 Private Recipe',
    ]);

    // User2 should not be able to view user1's personal recipe
    $response = $this->actingAs($user2)->get(route('recipes.show', $recipe));

    // Should get 403 Forbidden due to authorization policy
    $response->assertForbidden();
});

it('displays source url as a link on the recipe show page when source_url is present', function () {
    $user = User::factory()->create();
    $url = 'https://example.com/original-recipe';

    $recipe = Recipe::factory()->create([
        'user_id' => null,
        'source_url' => $url,
    ]);

    $response = $this->actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();
    $response->assertSee($url, false);
});

it('does not display source url on the recipe show page when source_url is null', function () {
    $user = User::factory()->create();

    $recipe = Recipe::factory()->create([
        'user_id' => null,
        'source_url' => null,
    ]);

    $response = $this->actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();
    $response->assertDontSee('View Original Recipe');
});

it('renders source url link with target blank and rel noopener noreferrer', function () {
    $user = User::factory()->create();

    $recipe = Recipe::factory()->create([
        'user_id' => null,
        'source_url' => 'https://example.com/original-recipe',
    ]);

    $response = $this->actingAs($user)->get(route('recipes.show', $recipe));

    $response->assertOk();
    $response->assertSee('target="_blank"', false);
    $response->assertSee('rel="noopener noreferrer"', false);
});

test('guest cannot view recipes', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'System Recipe',
    ]);

    // Guest user (not authenticated) should be redirected to login
    $response = $this->get(route('recipes.show', $recipe));

    $response->assertRedirect(route('login'));
});
