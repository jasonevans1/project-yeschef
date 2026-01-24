<?php

use App\Models\Recipe;
use App\Models\User;

test('authenticated user can view recipes index', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('recipes.index'));

    $response->assertStatus(200);
});

test('recipes display with name image and description', function () {
    $user = User::factory()->create();

    // Create some recipes with varied data
    $systemRecipe = Recipe::factory()->create([
        'user_id' => null, // System recipe
        'name' => 'System Recipe Test',
        'description' => 'This is a system recipe description',
        'image_url' => 'https://example.com/image.jpg',
    ]);

    $userRecipe = Recipe::factory()->create([
        'user_id' => $user->id,
        'name' => 'User Recipe Test',
        'description' => 'This is a user recipe description',
        'image_url' => 'https://example.com/user-image.jpg',
    ]);

    $response = $this->actingAs($user)->get(route('recipes.index'));

    $response->assertStatus(200);
    $response->assertSee('System Recipe Test');
    $response->assertSee('This is a system recipe description');
    $response->assertSee('User Recipe Test');
    $response->assertSee('This is a user recipe description');
});

test('pagination works with 24 recipes per page', function () {
    $user = User::factory()->create();

    // Create 30 recipes to test pagination (system recipes visible to all)
    Recipe::factory()->count(30)->create(['user_id' => null]);

    $response = $this->actingAs($user)->get(route('recipes.index'));

    $response->assertStatus(200);

    // Should see pagination links since we have more than 24 recipes
    $response->assertSee('Next');

    // Test second page
    $response = $this->actingAs($user)->get(route('recipes.index', ['page' => 2]));
    $response->assertStatus(200);
});

test('system recipes visible to all users', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Create system recipes (user_id = null)
    $systemRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Public System Recipe',
    ]);

    // User 1 can see system recipe
    $response1 = $this->actingAs($user1)->get(route('recipes.index'));
    $response1->assertStatus(200);
    $response1->assertSee('Public System Recipe');

    // User 2 can also see the same system recipe
    $response2 = $this->actingAs($user2)->get(route('recipes.index'));
    $response2->assertStatus(200);
    $response2->assertSee('Public System Recipe');
});

test('users personal recipes visible in list', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create personal recipe for the user
    $personalRecipe = Recipe::factory()->create([
        'user_id' => $user->id,
        'name' => 'My Personal Recipe',
    ]);

    // Create another user's personal recipe
    $otherRecipe = Recipe::factory()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other User Recipe',
    ]);

    // User should see their own personal recipe
    $response = $this->actingAs($user)->get(route('recipes.index'));
    $response->assertStatus(200);
    $response->assertSee('My Personal Recipe');

    // User should NOT see other user's personal recipe
    // (Based on RecipePolicy, only owner can view personal recipes)
    $response->assertDontSee('Other User Recipe');
});
