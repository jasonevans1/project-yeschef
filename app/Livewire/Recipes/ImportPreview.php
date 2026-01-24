<?php

declare(strict_types=1);

namespace App\Livewire\Recipes;

use App\Enums\IngredientCategory;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ImportPreview extends Component
{
    public array $recipeData = [];

    public function mount(): void
    {
        $this->recipeData = session('recipe_import_preview', []);

        if (empty($this->recipeData)) {
            $this->redirect(route('recipes.import'), navigate: true);
        }
    }

    public function confirmImport(): void
    {
        try {
            DB::transaction(function () {
                // Create recipe
                $recipe = auth()->user()->recipes()->create([
                    'name' => $this->recipeData['name'],
                    'description' => $this->recipeData['description'] ?? null,
                    'prep_time' => $this->recipeData['prep_time'] ?? null,
                    'cook_time' => $this->recipeData['cook_time'] ?? null,
                    'servings' => $this->recipeData['servings'] ?? 4,
                    'cuisine' => $this->recipeData['cuisine'] ?? null,
                    'meal_type' => $this->recipeData['meal_type'] ?? null,
                    'instructions' => $this->recipeData['instructions'],
                    'image_url' => $this->recipeData['image_url'] ?? null,
                    'source_url' => $this->recipeData['source_url'],
                ]);

                // Parse and create ingredients
                $this->createIngredients($recipe);

                // Clear session
                session()->forget('recipe_import_preview');

                // Redirect to recipe show page
                session()->flash('success', 'Recipe imported successfully!');
                $this->redirect(route('recipes.show', $recipe), navigate: true);
            });
        } catch (\Exception $e) {
            logger()->error('Import failed: '.$e->getMessage(), ['exception' => $e]);
            $this->addError('import', 'An error occurred while saving the recipe. Please try again.');
        }
    }

    protected function createIngredients(Recipe $recipe): void
    {
        $ingredients = $this->recipeData['recipeIngredient'] ?? [];
        $parser = app(\App\Services\RecipeImporter\IngredientParser::class);

        foreach ($ingredients as $index => $ingredientText) {
            // Parse ingredient text to extract quantity, unit, and name
            $parsed = $parser->parse(trim($ingredientText));

            // Find or create ingredient using parsed name (or original if parsing failed)
            $ingredient = Ingredient::firstOrCreate(
                ['name' => $parsed['name']],
                ['category' => IngredientCategory::OTHER] // Default to OTHER for imported ingredients
            );

            // Create recipe-ingredient relationship
            $recipe->recipeIngredients()->create([
                'ingredient_id' => $ingredient->id,
                'quantity' => $parsed['quantity'],
                'unit' => $parsed['unit'],
                'notes' => $parsed['quantity'] || $parsed['unit'] ? null : $parsed['original'], // Only store original as notes if parsing failed
                'sort_order' => $index,
            ]);
        }
    }

    public function cancel(): void
    {
        session()->forget('recipe_import_preview');
        $this->redirect(route('recipes.import'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.recipes.import-preview');
    }
}
