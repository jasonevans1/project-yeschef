<?php

declare(strict_types=1);

use App\Livewire\Recipes\Import;
use App\Livewire\Recipes\ImportPreview;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

// T031-T032: Tests for Import Component

test('authenticated users can access import page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('recipes.import'))->assertOk();
});

test('guests are redirected to login', function () {
    $this->get(route('recipes.import'))->assertRedirect(route('login'));
});

test('import component validates URL is required', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', '')
        ->call('import')
        ->assertHasErrors(['url' => 'required']);
});

test('import component validates URL format', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'not-a-valid-url')
        ->call('import')
        ->assertHasErrors(['url' => 'url']);
});

test('successful import redirects to preview with session data', function () {
    $user = User::factory()->create();

    // Fake HTTP response with valid recipe JSON-LD
    Http::fake([
        'example.com/*' => Http::response('
            <html>
                <head>
                    <script type="application/ld+json">
                    {
                        "@context": "https://schema.org",
                        "@type": "Recipe",
                        "name": "Test Recipe",
                        "recipeInstructions": "Mix and bake",
                        "recipeIngredient": ["2 cups flour", "1 tsp salt"]
                    }
                    </script>
                </head>
                <body></body>
            </html>
        ', 200),
    ]);

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/recipe')
        ->call('import')
        ->assertHasNoErrors()
        ->assertRedirect(route('recipes.import.preview'));

    // Verify session data
    expect(session()->has('recipe_import_preview'))->toBeTrue();
    $sessionData = session('recipe_import_preview');
    expect($sessionData['name'])->toBe('Test Recipe');
    expect($sessionData['source_url'])->toBe('https://example.com/recipe');
});

test('import displays error when no recipe data found', function () {
    $user = User::factory()->create();

    // Fake HTTP response with no recipe data
    Http::fake([
        'example.com/*' => Http::response('<html><body>No recipe here</body></html>', 200),
    ]);

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/article')
        ->call('import')
        ->assertHasErrors(['url']);
});

test('import displays error on network failure', function () {
    $user = User::factory()->create();

    // Fake HTTP exception
    Http::fake([
        'example.com/*' => Http::response('', 500),
    ]);

    Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/recipe')
        ->call('import')
        ->assertHasErrors(['url']);
});

// T038: Tests for ImportPreview Component

test('preview page loads session data', function () {
    $user = User::factory()->create();

    session()->put('recipe_import_preview', [
        'name' => 'Preview Test Recipe',
        'instructions' => 'Test instructions',
        'recipeIngredient' => ['Ingredient 1'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertSet('recipeData.name', 'Preview Test Recipe');
});

test('preview redirects to import if no session data', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertRedirect(route('recipes.import'))
        ->assertSessionHas('error', 'Recipe import data was lost. Please try importing again.');
});

test('confirming import creates recipe in database', function () {
    $user = User::factory()->create();

    session()->put('recipe_import_preview', [
        'name' => 'Imported Recipe',
        'description' => 'A delicious recipe',
        'instructions' => 'Mix and bake at 350°F',
        'prep_time' => 15,
        'cook_time' => 30,
        'servings' => 4,
        'cuisine' => 'Italian',
        'meal_type' => 'dinner',
        'image_url' => 'https://example.com/image.jpg',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => ['2 cups flour', '1 tsp salt', '1 cup water'],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport')
        ->assertHasNoErrors()
        ->assertRedirect();

    // Verify recipe was created
    $recipe = Recipe::where('name', 'Imported Recipe')->first();
    expect($recipe)->not->toBeNull();
    expect($recipe->user_id)->toBe($user->id);
    expect($recipe->description)->toBe('A delicious recipe');
    expect($recipe->prep_time)->toBe(15);
    expect($recipe->cook_time)->toBe(30);
    expect($recipe->servings)->toBe(4);
    expect($recipe->cuisine)->toBe('Italian');
    expect($recipe->meal_type->value)->toBe('dinner');
    expect($recipe->source_url)->toBe('https://example.com/recipe');
});

test('confirming import creates recipe ingredients', function () {
    $user = User::factory()->create();

    session()->put('recipe_import_preview', [
        'name' => 'Recipe with Ingredients',
        'instructions' => 'Cook it',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => ['2 cups flour', '1 tsp salt', '3 eggs'],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    $recipe = Recipe::where('name', 'Recipe with Ingredients')->first();
    expect($recipe->recipeIngredients)->toHaveCount(3);

    // Verify ingredient is parsed correctly
    $firstIngredient = $recipe->recipeIngredients()->orderBy('sort_order')->first();
    expect($firstIngredient->quantity)->toBe('2.000');
    expect($firstIngredient->unit->value)->toBe('cup');
    expect($firstIngredient->ingredient->name)->toBe('Flour'); // Capitalized by model accessor
    expect($firstIngredient->sort_order)->toBe(0);
});

test('confirming import clears session data', function () {
    $user = User::factory()->create();

    session()->put('recipe_import_preview', [
        'name' => 'Session Test Recipe',
        'instructions' => 'Test',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => [],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('confirmImport');

    expect(session()->has('recipe_import_preview'))->toBeFalse();
});

test('cancel clears session without creating recipe', function () {
    $user = User::factory()->create();

    session()->put('recipe_import_preview', [
        'name' => 'Canceled Recipe',
        'instructions' => 'Should not be saved',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => [],
    ]);

    $initialCount = Recipe::count();

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('cancel')
        ->assertRedirect(route('recipes.import'));

    // Verify no recipe was created
    expect(Recipe::count())->toBe($initialCount);

    // Verify session was cleared
    expect(session()->has('recipe_import_preview'))->toBeFalse();
});

test('import shows helpful error for Cloudflare-protected sites', function () {
    $user = User::factory()->create();

    // Fake HTTP response with Cloudflare challenge page
    Http::fake([
        'example.com/*' => Http::response('
            <!DOCTYPE html>
            <html>
            <head><title>Just a moment...</title></head>
            <body>
                <h1>Checking your browser</h1>
                <div id="cf-browser-verification"></div>
            </body>
            </html>
        ', 200),
    ]);

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/recipe')
        ->call('import')
        ->assertHasErrors('url');

    // Verify the error message mentions Cloudflare
    $errorBag = $component->instance()->getErrorBag();
    $errorMessage = $errorBag->first('url');

    expect($errorMessage)
        ->toContain('Cloudflare')
        ->toContain('cannot be imported automatically');
});

// T053-T054: Phase 4 Error Handling Tests

test('shows error when URL has no recipe data', function () {
    $user = User::factory()->create();

    Http::fake([
        'example.com/*' => Http::response('<html><body>Just a regular page</body></html>', 200),
    ]);

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/no-recipe')
        ->call('import')
        ->assertHasErrors(['url']);

    $errorMessage = $component->instance()->getErrorBag()->first('url');
    expect($errorMessage)->toContain('No recipe data found');
});

test('shows error when request times out', function () {
    $user = User::factory()->create();

    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out after 30000 milliseconds');
    });

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://slow-site.com/recipe')
        ->call('import')
        ->assertHasErrors(['url']);

    $errorMessage = $component->instance()->getErrorBag()->first('url');
    expect($errorMessage)->toContain('timed out');
});

test('shows error when page returns 404', function () {
    $user = User::factory()->create();

    Http::fake([
        'example.com/*' => Http::response('Not Found', 404),
    ]);

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/missing-page')
        ->call('import')
        ->assertHasErrors(['url']);

    $errorMessage = $component->instance()->getErrorBag()->first('url');
    expect($errorMessage)->toContain('not found')->toContain('404');
});

test('shows error when page returns 403', function () {
    $user = User::factory()->create();

    Http::fake([
        'example.com/*' => Http::response('Forbidden', 403),
    ]);

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/forbidden')
        ->call('import')
        ->assertHasErrors(['url']);

    $errorMessage = $component->instance()->getErrorBag()->first('url');
    expect($errorMessage)->toContain('forbidden')->toContain('403');
});

test('shows error when page returns server error', function () {
    $user = User::factory()->create();

    Http::fake([
        'example.com/*' => Http::response('Server Error', 500),
    ]);

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/error-page')
        ->call('import')
        ->assertHasErrors(['url']);

    $errorMessage = $component->instance()->getErrorBag()->first('url');
    expect($errorMessage)->toContain('server error')->toContain('500');
});

test('shows error when JSON-LD is malformed', function () {
    $user = User::factory()->create();

    $html = '<script type="application/ld+json">{invalid json}</script>';

    Http::fake([
        'example.com/*' => Http::response($html, 200),
    ]);

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/bad-json')
        ->call('import')
        ->assertHasErrors(['url']);

    $errorMessage = $component->instance()->getErrorBag()->first('url');
    expect($errorMessage)->toContain('malformed');
});

test('shows error when recipe data is incomplete', function () {
    $user = User::factory()->create();

    $html = '<script type="application/ld+json">
        {"@type": "Recipe", "name": "Test Recipe"}
    </script>';

    Http::fake([
        'example.com/*' => Http::response($html, 200),
    ]);

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://example.com/incomplete')
        ->call('import')
        ->assertHasErrors(['url']);

    $errorMessage = $component->instance()->getErrorBag()->first('url');
    expect($errorMessage)->toContain('Missing required fields');
});

test('shows error when connection fails', function () {
    $user = User::factory()->create();

    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
    });

    $component = Livewire::actingAs($user)
        ->test(Import::class)
        ->set('url', 'https://unreachable.com/recipe')
        ->call('import')
        ->assertHasErrors(['url']);

    $errorMessage = $component->instance()->getErrorBag()->first('url');
    expect($errorMessage)->toContain('Could not connect');
});
