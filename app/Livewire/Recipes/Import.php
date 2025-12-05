<?php

declare(strict_types=1);

namespace App\Livewire\Recipes;

use App\Services\RecipeImporter\RecipeImportService;
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

            if (! $recipeData) {
                $this->addError('url', 'No recipe data found on this page. Please make sure the page contains a recipe with schema.org markup.');

                return;
            }

            // Store in session with source URL
            $recipeData['source_url'] = $this->url;
            session()->put('recipe_import_preview', $recipeData);

            $this->redirect(route('recipes.import.preview'), navigate: true);
        } catch (\Exception $e) {
            $this->addError('url', 'Could not access the page. Please check the URL and try again.');
        }
    }

    public function render(): View
    {
        return view('livewire.recipes.import');
    }
}
