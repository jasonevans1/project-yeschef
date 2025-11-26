<?php

declare(strict_types=1);

test('header displays project table top branding', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSee('Project Table Top');
    $response->assertDontSee('Laravel Starter Kit');
});

test('page title includes project table top', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    // Page title should include "Project Table Top" somewhere
    $response->assertSee('Project Table Top', false);
    // And should not contain old branding
    $response->assertDontSee('Laravel Starter Kit', false);
    // The title tag should exist
    $response->assertSee('<title>', false);
});

test('search link is not present in header', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertDontSee('Search');
    $response->assertDontSee('magnifying-glass');
});

test('repository link is not present in header', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertDontSee('https://github.com/laravel/livewire-starter-kit');
    $response->assertDontSee('Repository');
});

test('documentation link is not present in header', function () {
    $user = \App\Models\User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertDontSee('https://laravel.com/docs/starter-kits#livewire');
    $response->assertDontSee('Documentation');
});
