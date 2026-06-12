<?php

declare(strict_types=1);

use App\Exceptions\BlockedUrlException;
use App\Livewire\Recipes\Import;
use App\Models\User;
use App\Services\RecipeImporter\RecipeImportService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

it('rejects a non http or https url at validation', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'ftp://example.com/recipe')
        ->call('import')
        ->assertHasErrors(['url' => 'url']);
});

it('allows imports up to the configured rate limit', function () {
    $user = User::factory()->create();
    RateLimiter::clear('recipe-import:'.$user->id);

    $mockService = mock(RecipeImportService::class)
        ->shouldReceive('fetchAndParse')
        ->times(config('recipe-import.rate_limit.max_attempts'))
        ->andReturn([
            'name' => 'Test Recipe',
            'instructions' => 'Mix and bake',
            'recipeIngredient' => ['2 cups flour'],
        ])
        ->getMock();

    app()->instance(RecipeImportService::class, $mockService);

    $maxAttempts = config('recipe-import.rate_limit.max_attempts');

    for ($i = 0; $i < $maxAttempts; $i++) {
        Livewire::actingAs($user)
            ->test(Import::class)
            ->set('url', 'https://example.com/recipe')
            ->call('import')
            ->assertHasNoErrors(['url']);
    }
});

it('blocks an import that exceeds the rate limit and does not call the import service', function () {
    $user = User::factory()->create();
    RateLimiter::clear('recipe-import:'.$user->id);

    $maxAttempts = config('recipe-import.rate_limit.max_attempts');

    // Exhaust rate limit by hitting directly
    for ($i = 0; $i < $maxAttempts; $i++) {
        RateLimiter::hit('recipe-import:'.$user->id, config('recipe-import.rate_limit.decay_seconds'));
    }

    $mockService = mock(RecipeImportService::class)
        ->shouldNotReceive('fetchAndParse')
        ->getMock();

    app()->instance(RecipeImportService::class, $mockService);

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/recipe')
        ->call('import')
        ->assertHasErrors(['url']);
});

it('shows a friendly throttle message on the url field when rate limited', function () {
    $user = User::factory()->create();
    RateLimiter::clear('recipe-import:'.$user->id);

    $maxAttempts = config('recipe-import.rate_limit.max_attempts');

    for ($i = 0; $i < $maxAttempts; $i++) {
        RateLimiter::hit('recipe-import:'.$user->id, config('recipe-import.rate_limit.decay_seconds'));
    }

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/recipe')
        ->call('import')
        ->assertHasErrors(['url']);

    $errorMessage = $component->instance()->getErrorBag()->first('url');
    expect($errorMessage)->toContain('Too many import attempts');
});

it('surfaces a friendly url error when the import service throws BlockedUrlException', function () {
    $user = User::factory()->create();
    RateLimiter::clear('recipe-import:'.$user->id);

    $mockService = mock(RecipeImportService::class)
        ->shouldReceive('fetchAndParse')
        ->once()
        ->andThrow(new BlockedUrlException('This URL is not allowed.'))
        ->getMock();

    app()->instance(RecipeImportService::class, $mockService);

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/recipe')
        ->call('import')
        ->assertHasErrors(['url']);

    $errorMessage = $component->instance()->getErrorBag()->first('url');
    expect($errorMessage)->toBe('This URL is not allowed.');
});

it('does not count a failed validation attempt against the fetch rate limit', function () {
    $user = User::factory()->create();
    RateLimiter::clear('recipe-import:'.$user->id);

    // Trigger validation failures (invalid URL) many times
    $maxAttempts = config('recipe-import.rate_limit.max_attempts');
    for ($i = 0; $i < $maxAttempts + 5; $i++) {
        Livewire::actingAs($user)
            ->test(Import::class)
            ->set('url', 'not-a-valid-url')
            ->call('import')
            ->assertHasErrors(['url']);
    }

    // The rate limiter should NOT have been hit, so a valid URL should still work
    $mockService = mock(RecipeImportService::class)
        ->shouldReceive('fetchAndParse')
        ->once()
        ->andReturn([
            'name' => 'Test Recipe',
            'instructions' => 'Mix and bake',
            'recipeIngredient' => ['2 cups flour'],
        ])
        ->getMock();

    app()->instance(RecipeImportService::class, $mockService);

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/recipe')
        ->call('import')
        ->assertHasNoErrors(['url']);
});

it('accepts a well formed https url at validation', function () {
    $user = User::factory()->create();

    $mockService = mock(RecipeImportService::class)
        ->shouldReceive('fetchAndParse')
        ->once()
        ->andReturn([
            'name' => 'Test Recipe',
            'instructions' => 'Mix and bake',
            'recipeIngredient' => ['2 cups flour'],
            'source_url' => 'https://example.com/recipe',
        ])
        ->getMock();

    app()->instance(RecipeImportService::class, $mockService);

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/recipe')
        ->call('import')
        ->assertHasNoErrors(['url']);
});
