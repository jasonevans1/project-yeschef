<?php

declare(strict_types=1);

namespace App\Livewire\Recipes;

use App\Exceptions\CloudflareBlockedException;
use App\Exceptions\InvalidHTTPStatusException;
use App\Exceptions\MalformedRecipeDataException;
use App\Exceptions\MissingRecipeDataException;
use App\Exceptions\NetworkTimeoutException;
use App\Services\RecipeImporter\RecipeImportService;
use Exception;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Import extends Component
{
    #[Validate('required|url|max:2048')]
    public string $url = '';

    public function import(RecipeImportService $importService): void
    {
        $this->validate();

        try {
            $recipeData = $importService->fetchAndParse($this->url);

            // Store in session with source URL
            $recipeData['source_url'] = $this->url;
            session()->put('recipe_import_preview', $recipeData);
            session()->save();

            $this->redirect(route('recipes.import.preview'), navigate: true);
        } catch (NetworkTimeoutException $e) {
            $this->addError('url', $e->getMessage());
        } catch (InvalidHTTPStatusException $e) {
            $this->addError('url', $e->getMessage());
        } catch (CloudflareBlockedException $e) {
            $this->addError('url', $e->getMessage());
        } catch (MissingRecipeDataException $e) {
            $this->addError('url', $e->getMessage());
        } catch (MalformedRecipeDataException $e) {
            $this->addError('url', $e->getMessage());
        } catch (Exception $e) {
            // Check if this is a known error message from RecipeFetcher
            if (str_contains($e->getMessage(), 'Could not connect')) {
                $this->addError('url', $e->getMessage());

                return;
            }

            // Log unexpected errors for debugging
            logger()->error('Unexpected import error', [
                'url' => $this->url,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->addError('url', 'An unexpected error occurred. Please try again or contact support.');
        }
    }

    public function render(): View
    {
        return view('livewire.recipes.import');
    }
}
