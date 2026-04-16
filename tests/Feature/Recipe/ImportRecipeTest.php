<?php

declare(strict_types=1);

use App\Livewire\Recipes\Import;
use App\Livewire\Recipes\ImportPreview;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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

    // Verify cache data
    $cacheKey = 'recipe_import_preview:'.$user->id;
    expect(Cache::has($cacheKey))->toBeTrue();
    $cachedData = Cache::get($cacheKey);
    expect($cachedData['name'])->toBe('Test Recipe')
        ->and($cachedData['source_url'])->toBe('https://example.com/recipe');
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

test('preview page loads cache data', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Preview Test Recipe',
        'instructions' => 'Test instructions',
        'recipeIngredient' => ['Ingredient 1'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertSet('name', 'Preview Test Recipe');
});

test('preview redirects to import if no cache data', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertRedirect(route('recipes.import'))
        ->assertSessionHas('error', 'Recipe import data was lost. Please try importing again.');
});

test('confirming import creates recipe in database', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
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
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    // Verify recipe was created
    $recipe = Recipe::where('name', 'Imported Recipe')->first();
    expect($recipe)->not->toBeNull()
        ->and($recipe->user_id)->toBe($user->id)
        ->and($recipe->description)->toBe('A delicious recipe')
        ->and($recipe->prep_time)->toBe(15)
        ->and($recipe->cook_time)->toBe(30)
        ->and($recipe->servings)->toBe(4)
        ->and($recipe->cuisine)->toBe('Italian')
        ->and($recipe->meal_type->value)->toBe('dinner')
        ->and($recipe->source_url)->toBe('https://example.com/recipe');
});

test('confirming import creates recipe ingredients', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe with Ingredients',
        'instructions' => 'Cook it well for dinner',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => ['2 cups flour', '1 tsp salt', '3 eggs'],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('save');

    $recipe = Recipe::where('name', 'Recipe with Ingredients')->first();
    expect($recipe->recipeIngredients)->toHaveCount(3);

    // Verify ingredient is parsed correctly
    $firstIngredient = $recipe->recipeIngredients()->orderBy('sort_order')->first();
    expect($firstIngredient->quantity)->toBe('2.000')
        ->and($firstIngredient->unit->value)->toBe('cup')
        ->and($firstIngredient->ingredient->name)->toBe('Flour') // Capitalized by model accessor
        ->and($firstIngredient->sort_order)->toBe(0);
});

test('confirming import clears cache data', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Cache Test Recipe',
        'instructions' => 'Cook it well for dinner',
        'source_url' => 'https://example.com/recipe',
        'recipeIngredient' => ['1 cup flour'],
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('save');

    expect(Cache::has('recipe_import_preview:'.$user->id))->toBeFalse();
});

test('cancel clears cache without creating recipe', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
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

    expect(Recipe::count())->toBe($initialCount)
        ->and(Cache::has('recipe_import_preview:'.$user->id))->toBeFalse();
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

// New T038+ tests for editable ImportPreview component

it('populates name from cached import data on mount', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Cached Recipe Name',
        'instructions' => 'Some instructions here',
        'recipeIngredient' => [],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertSet('name', 'Cached Recipe Name');
});

it('populates description from cached import data on mount', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'description' => 'A tasty description',
        'instructions' => 'Some instructions',
        'recipeIngredient' => [],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertSet('description', 'A tasty description');
});

it('populates prep time from cached import data on mount', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Some instructions',
        'prep_time' => 20,
        'recipeIngredient' => [],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertSet('prep_time', 20);
});

it('populates cook time from cached import data on mount', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Some instructions',
        'cook_time' => 45,
        'recipeIngredient' => [],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertSet('cook_time', 45);
});

it('populates servings from cached import data on mount', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Some instructions',
        'servings' => 6,
        'recipeIngredient' => [],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertSet('servings', 6);
});

it('populates cuisine from cached import data on mount', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Some instructions',
        'cuisine' => 'French',
        'recipeIngredient' => [],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertSet('cuisine', 'French');
});

it('populates meal type from cached import data on mount', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Some instructions',
        'meal_type' => 'breakfast',
        'recipeIngredient' => [],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertSet('meal_type', 'breakfast');
});

it('populates instructions from cached import data on mount', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Detailed cooking instructions here',
        'recipeIngredient' => [],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertSet('instructions', 'Detailed cooking instructions here');
});

it('populates image url from cached import data on mount', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Some instructions',
        'image_url' => 'https://example.com/image.jpg',
        'recipeIngredient' => [],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertSet('image_url', 'https://example.com/image.jpg');
});

it('parses raw ingredient strings into structured ingredient rows on mount', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Some instructions',
        'recipeIngredient' => ['2 cups flour', '1 tsp salt'],
        'source_url' => 'https://example.com/recipe',
    ]);

    $component = Livewire::actingAs($user)
        ->test(ImportPreview::class);

    $ingredients = $component->get('ingredients');

    expect($ingredients)->toHaveCount(2)
        ->and($ingredients[0]['ingredient_name'])->toBe('flour')
        ->and($ingredients[0]['quantity'])->toBe(2.0)
        ->and($ingredients[0]['unit'])->toBe('cup')
        ->and($ingredients[1]['ingredient_name'])->toBe('salt')
        ->and($ingredients[1]['unit'])->toBe('tsp');
});

it('redirects to import page if cache is missing on mount', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->assertRedirect(route('recipes.import'));
});

it('saves recipe using edited name when name is changed before saving', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Original Name',
        'instructions' => 'Cook it well for dinner',
        'recipeIngredient' => ['1 cup flour'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->set('name', 'Edited Name')
        ->call('save')
        ->assertHasNoErrors();

    expect(Recipe::where('name', 'Edited Name')->exists())->toBeTrue()
        ->and(Recipe::where('name', 'Original Name')->exists())->toBeFalse();
});

it('saves recipe using edited servings when servings are changed before saving', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Cook it well for dinner',
        'servings' => 4,
        'recipeIngredient' => ['1 cup flour'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->set('servings', 8)
        ->call('save')
        ->assertHasNoErrors();

    $recipe = Recipe::where('name', 'Recipe')->first();
    expect($recipe->servings)->toBe(8);
});

it('saves recipe with edited ingredients when ingredients are modified before saving', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Cook it well for dinner',
        'recipeIngredient' => ['2 cups flour'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->set('ingredients.0.ingredient_name', 'sugar')
        ->call('save')
        ->assertHasNoErrors();

    $recipe = Recipe::where('name', 'Recipe')->first();
    expect($recipe->recipeIngredients->first()->ingredient->name)->toBe('Sugar');
});

it('saves recipe with added ingredient row', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Cook it well for dinner',
        'recipeIngredient' => ['1 cup flour'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('addIngredient')
        ->set('ingredients.1.ingredient_name', 'butter')
        ->set('ingredients.1.quantity', 2)
        ->set('ingredients.1.unit', 'tbsp')
        ->call('save')
        ->assertHasNoErrors();

    $recipe = Recipe::where('name', 'Recipe')->first();
    expect($recipe->recipeIngredients)->toHaveCount(2);
});

it('saves recipe after removing an ingredient row', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Cook it well for dinner',
        'recipeIngredient' => ['1 cup flour', '1 tsp salt'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('removeIngredient', 1)
        ->call('save')
        ->assertHasNoErrors();

    $recipe = Recipe::where('name', 'Recipe')->first();
    expect($recipe->recipeIngredients)->toHaveCount(1);
});

it('stores source url on the recipe when saving', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Cook it well for dinner',
        'recipeIngredient' => ['1 cup flour'],
        'source_url' => 'https://example.com/my-recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('save')
        ->assertHasNoErrors();

    $recipe = Recipe::where('name', 'Recipe')->first();
    expect($recipe->source_url)->toBe('https://example.com/my-recipe');
});

it('clears the cache after saving', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Cook it well',
        'recipeIngredient' => ['1 cup flour'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('save');

    expect(Cache::has('recipe_import_preview:'.$user->id))->toBeFalse();
});

it('redirects to the recipe show page after saving', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Cook it well for dinner',
        'recipeIngredient' => ['1 cup flour'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('save')
        ->assertRedirect();

    $recipe = Recipe::where('name', 'Recipe')->first();
    expect($recipe)->not->toBeNull();
});

it('shows validation error when name is cleared before saving', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Cook it well for dinner',
        'recipeIngredient' => ['1 cup flour'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('shows validation error when instructions are cleared before saving', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Cook it well for dinner',
        'recipeIngredient' => ['1 cup flour'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->set('instructions', '')
        ->call('save')
        ->assertHasErrors(['instructions']);
});

it('shows validation error when all ingredient rows are removed before saving', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Cook it well for dinner',
        'recipeIngredient' => ['1 cup flour'],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->set('ingredients', [])
        ->call('save')
        ->assertHasErrors(['ingredients']);
});

it('cancel clears cache and redirects to import page', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Recipe',
        'instructions' => 'Cook it',
        'recipeIngredient' => [],
        'source_url' => 'https://example.com/recipe',
    ]);

    Livewire::actingAs($user)
        ->test(ImportPreview::class)
        ->call('cancel')
        ->assertRedirect(route('recipes.import'));

    expect(Cache::has('recipe_import_preview:'.$user->id))->toBeFalse();
});

it('renders the import preview page with a form for authenticated users', function () {
    $user = User::factory()->create();

    Cache::put('recipe_import_preview:'.$user->id, [
        'name' => 'Test Recipe',
        'instructions' => 'Cook it well',
        'recipeIngredient' => ['1 cup flour'],
        'source_url' => 'https://example.com/recipe',
    ]);

    $component = Livewire::actingAs($user)
        ->test(ImportPreview::class);

    $html = $component->html();

    expect($html)
        ->toContain('Review')
        ->toContain('Edit Imported Recipe')
        ->toContain('Save Recipe')
        ->toContain('wire:submit="save"')
        ->toContain('wire:click="cancel"')
        ->toContain('data-flux-error')
        ->toContain('https://example.com/recipe');
});
