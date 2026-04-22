<?php

declare(strict_types=1);

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Route;

test('it can access the dashboard route without errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});

test('it can access the recipes index route without errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/recipes')
        ->assertOk();
});

test('it can access the recipe show route with a valid recipe', function () {
    $user = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get("/recipes/{$recipe->id}")
        ->assertOk();
});

test('it can access the meal plans index route without errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/meal-plans')
        ->assertOk();
});

test('it can access the grocery lists index route without errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/grocery-lists')
        ->assertOk();
});

test('it can access the settings profile route without errors', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/profile')
        ->assertOk();
});

test('it can access the login route as a guest', function () {
    $this->get('/login')
        ->assertOk();
});

test('it can access the register route as a guest', function () {
    $this->get('/register')
        ->assertOk();
});

test('it lists all named routes correctly via php artisan route:list', function () {
    $routes = collect(Route::getRoutes())->map(fn ($route) => $route->getName())->filter()->values();

    expect($routes)->toContain('dashboard')
        ->toContain('recipes.index')
        ->toContain('recipes.show')
        ->toContain('meal-plans.index')
        ->toContain('grocery-lists.index')
        ->toContain('settings.profile')
        ->toContain('login')
        ->toContain('register');
});
