<?php

declare(strict_types=1);

use App\Models\User;

test('header displays YesChef branding', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSee('YesChef');
    $response->assertDontSee('Laravel Starter Kit');
});

test('page title includes YesChef', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSee('YesChef', false);
    $response->assertDontSee('Laravel Starter Kit', false);
    $response->assertSee('<title>', false);
});

test('search link is not present in header', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertDontSee('Search');
    $response->assertDontSee('magnifying-glass');
});

test('repository link is not present in header', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertDontSee('https://github.com/laravel/livewire-starter-kit');
    $response->assertDontSee('Repository');
});

test('documentation link is not present in header', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertDontSee('https://laravel.com/docs/starter-kits#livewire');
    $response->assertDontSee('Documentation');
});

it('sets the theme-color meta tag to the torch steel blue brand color', function () {
    $response = $this->get('/login');

    $response->assertSee('<meta name="theme-color" content="#06377e">', false);
});

it('renders the app name wordmark in uppercase with wide letter spacing', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSee('class="mb-0.5 truncate leading-tight font-semibold uppercase tracking-wide"', false);
});

it('renders the login submit button bound to the accent token', function () {
    $response = $this->get('/login');

    $response->assertSee('var(--color-accent)', false);
});
