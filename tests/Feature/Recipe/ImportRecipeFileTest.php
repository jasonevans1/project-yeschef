<?php

declare(strict_types=1);

use App\Livewire\Recipes\Import;
use App\Livewire\Recipes\ImportPreview;
use App\Models\Recipe;
use App\Models\User;
use App\Services\RecipeImporter\RecipeImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function uploadRecipeJson(): string
{
    return json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Recipe',
        'name' => 'Uploaded Pancakes',
        'recipeInstructions' => 'Mix and fry',
        'recipeIngredient' => ['2 cups flour', '1 tsp salt'],
    ]);
}

function uploadRecipeFile(?string $json = null, string $name = 'recipe.json'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $json ?? uploadRecipeJson());
}

/**
 * Livewire's test harness leaves a `.json.json` metadata sidecar; ignore it.
 *
 * @return array<int, string>
 */
function uploadedTempFiles(): array
{
    return array_values(array_filter(
        Storage::disk('tmp-for-tests')->allFiles(),
        fn (string $path): bool => ! str_ends_with($path, '.json.json')
    ));
}

beforeEach(function () {
    Storage::fake('tmp-for-tests');
    $this->user = User::factory()->create();
    RateLimiter::clear('recipe-import:'.$this->user->id);
});

it('caches parsed recipe and redirects to preview when a valid JSON file is uploaded', function () {
    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->set('recipeFile', uploadRecipeFile())
        ->call('importFile')
        ->assertHasNoErrors()
        ->assertRedirect(route('recipes.import.preview'));

    expect(Cache::get('recipe_import_preview:'.$this->user->id)['name'])->toBe('Uploaded Pancakes');
});

it('requires a file when importing from file', function () {
    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->call('importFile')
        ->assertHasErrors(['recipeFile' => 'required']);
});

it('rejects files without a json extension', function () {
    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->set('recipeFile', uploadRecipeFile(null, 'recipe.txt'))
        ->call('importFile')
        ->assertHasErrors(['recipeFile']);
});

it('rejects files larger than 1MB', function () {
    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->set('recipeFile', uploadRecipeFile(str_repeat('a', 1025 * 1024)))
        ->call('importFile')
        ->assertHasErrors(['recipeFile' => 'max']);
});

it('shows an error for invalid JSON content', function () {
    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->set('recipeFile', uploadRecipeFile('{not json'))
        ->call('importFile')
        ->assertHasErrors(['recipeFile'])
        ->assertNoRedirect();
});

it('shows an error when the file contains no schema.org Recipe', function () {
    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->set('recipeFile', uploadRecipeFile('{"@type":"Person","name":"Bob"}'))
        ->call('importFile')
        ->assertHasErrors(['recipeFile'])
        ->assertNoRedirect();
});

it('rate limits file imports using the shared import limit', function () {
    $key = 'recipe-import:'.$this->user->id;
    foreach (range(1, config('recipe-import.rate_limit.max_attempts')) as $ignored) {
        RateLimiter::hit($key, config('recipe-import.rate_limit.decay_seconds'));
    }

    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->set('recipeFile', uploadRecipeFile())
        ->call('importFile')
        ->assertHasErrors(['recipeFile'])
        ->assertNoRedirect();
});

it('does not count a failed file validation against the rate limit', function () {
    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->call('importFile')
        ->assertHasErrors(['recipeFile']);

    expect(RateLimiter::attempts('recipe-import:'.$this->user->id))->toBe(0);
});

it('still imports by URL without a recipeFile set', function () {
    $html = '<html><head><script type="application/ld+json">'.uploadRecipeJson().'</script></head></html>';

    Http::fake(['example.com/*' => Http::response($html)]);

    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->set('url', 'https://example.com/recipe')
        ->call('import')
        ->assertHasNoErrors()
        ->assertRedirect(route('recipes.import.preview'));
});

it('shows a generic error on recipeFile when parsing throws an unexpected exception', function () {
    $this->mock(RecipeImportService::class)
        ->shouldReceive('parseJson')->andThrow(new RuntimeException('boom'));

    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->set('recipeFile', uploadRecipeFile())
        ->call('importFile')
        ->assertHasErrors(['recipeFile' => 'An unexpected error occurred. Please try again or contact support.']);
});

it('deletes the temporary upload after reading the file', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Import::class)
        ->set('recipeFile', uploadRecipeFile());

    expect(uploadedTempFiles())->not->toBeEmpty();

    $component->call('importFile');

    expect(uploadedTempFiles())->toBeEmpty();
});

it('deletes the temporary upload even when parsing fails', function () {
    $component = Livewire::actingAs($this->user)
        ->test(Import::class)
        ->set('recipeFile', uploadRecipeFile('{not json'));

    expect(uploadedTempFiles())->not->toBeEmpty();

    $component->call('importFile')->assertHasErrors(['recipeFile']);

    expect(uploadedTempFiles())->toBeEmpty();
});

it('creates a recipe with ingredients from an uploaded file via the preview', function () {
    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->set('recipeFile', uploadRecipeFile())
        ->call('importFile');

    Livewire::actingAs($this->user)->test(ImportPreview::class)->call('save');

    $recipe = Recipe::where('name', 'Uploaded Pancakes')->firstOrFail();
    expect($recipe->recipeIngredients)->toHaveCount(2);
});

it('shows the JSON file upload field on the import page', function () {
    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->assertSeeHtml('type="file"')
        ->assertSeeHtml('wire:model="recipeFile"')
        ->assertSee('Upload File')
        ->assertSee('Import Recipe');
});

it('shows a validation error for recipeFile on the import page', function () {
    Livewire::actingAs($this->user)
        ->test(Import::class)
        ->call('importFile')
        ->assertSee('The recipe file field is required');
});
