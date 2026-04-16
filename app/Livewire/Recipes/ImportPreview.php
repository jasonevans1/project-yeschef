<?php

declare(strict_types=1);

namespace App\Livewire\Recipes;

use App\Enums\MealType;
use App\Enums\MeasurementUnit;
use App\Models\Ingredient;
use App\Services\IngredientCategorizationService;
use App\Services\QuantityFormatter;
use App\Services\RecipeImporter\IngredientParser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ImportPreview extends Component
{
    #[Validate('required|string|min:3|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:1000')]
    public ?string $description = null;

    #[Validate('nullable|integer|min:0|max:1440')]
    public ?int $prep_time = null;

    #[Validate('nullable|integer|min:0|max:1440')]
    public ?int $cook_time = null;

    #[Validate('required|integer|min:1|max:100')]
    public int $servings = 4;

    #[Validate('nullable|string')]
    public ?string $meal_type = null;

    #[Validate('nullable|string|max:100')]
    public ?string $cuisine = null;

    #[Validate('required|string|min:10')]
    public string $instructions = '';

    #[Validate('nullable|url|max:255')]
    public ?string $image_url = null;

    #[Validate('nullable|string|in:easy,medium,hard')]
    public ?string $difficulty = null;

    #[Validate('nullable|array')]
    public array $dietary_tags = [];

    #[Validate('required|array|min:1')]
    public array $ingredients = [];

    public ?string $source_url = null;

    public function mount(): void
    {
        $cacheKey = 'recipe_import_preview:'.auth()->id();
        $recipeData = Cache::get($cacheKey, []);

        if (empty($recipeData)) {
            session()->flash('error', 'Recipe import data was lost. Please try importing again.');
            $this->redirect(route('recipes.import'));

            return;
        }

        $this->name = $recipeData['name'] ?? '';
        $this->description = $recipeData['description'] ?? null;
        $this->prep_time = $recipeData['prep_time'] ?? null;
        $this->cook_time = $recipeData['cook_time'] ?? null;
        $this->servings = $recipeData['servings'] ?? 4;
        $this->meal_type = $recipeData['meal_type'] ?? null;
        $this->cuisine = $recipeData['cuisine'] ?? null;
        $this->instructions = $recipeData['instructions'] ?? '';
        $this->image_url = $recipeData['image_url'] ?? null;
        $this->source_url = $recipeData['source_url'] ?? null;

        $this->ingredients = $this->parseRawIngredients($recipeData['recipeIngredient'] ?? []);

        if (empty($this->ingredients)) {
            $this->ingredients = [
                [
                    'ingredient_name' => '',
                    'quantity' => null,
                    'unit' => null,
                    'notes' => null,
                ],
            ];
        }
    }

    public function addIngredient(): void
    {
        $this->ingredients[] = [
            'ingredient_name' => '',
            'quantity' => null,
            'unit' => null,
            'notes' => null,
        ];
    }

    public function removeIngredient(int $index): void
    {
        if (count($this->ingredients) > 1) {
            unset($this->ingredients[$index]);
            $this->ingredients = array_values($this->ingredients);
        }
    }

    public function save(IngredientCategorizationService $categorizationService): void
    {
        $this->parseIngredientQuantities();

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->validate();
        $this->validateIngredients();

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        try {
            DB::transaction(function () use ($categorizationService) {
                $recipe = auth()->user()->recipes()->create([
                    'name' => $this->name,
                    'description' => $this->description,
                    'prep_time' => $this->prep_time,
                    'cook_time' => $this->cook_time,
                    'servings' => $this->servings,
                    'meal_type' => $this->meal_type ? MealType::from($this->meal_type) : null,
                    'cuisine' => $this->cuisine,
                    'instructions' => $this->instructions,
                    'image_url' => $this->image_url,
                    'source_url' => $this->source_url,
                ]);

                $addedIngredientIds = [];

                foreach ($this->ingredients as $index => $ingredientData) {
                    if (empty($ingredientData['ingredient_name'])) {
                        continue;
                    }

                    $ingredient = Ingredient::firstOrCreate(
                        ['name' => strtolower(trim($ingredientData['ingredient_name']))],
                        ['category' => $categorizationService->resolve($ingredientData['ingredient_name'], auth()->id())]
                    );

                    if (in_array($ingredient->id, $addedIngredientIds, true)) {
                        continue;
                    }

                    $addedIngredientIds[] = $ingredient->id;

                    $recipe->recipeIngredients()->create([
                        'ingredient_id' => $ingredient->id,
                        'quantity' => $ingredientData['quantity'] ?? 1,
                        'unit' => $ingredientData['unit'] ? MeasurementUnit::from($ingredientData['unit']) : MeasurementUnit::WHOLE,
                        'sort_order' => $index,
                        'notes' => $ingredientData['notes'],
                    ]);
                }

                Cache::forget('recipe_import_preview:'.auth()->id());

                session()->flash('success', 'Recipe imported successfully!');
                $this->redirect(route('recipes.show', $recipe));
            });
        } catch (\Exception $e) {
            $this->addError('import', 'An error occurred while saving the recipe. Please try again.');
        }
    }

    public function cancel(): void
    {
        Cache::forget('recipe_import_preview:'.auth()->id());
        $this->redirect(route('recipes.import'));
    }

    public function render(): View
    {
        return view('livewire.recipes.import-preview', [
            'mealTypes' => MealType::cases(),
            'measurementUnits' => MeasurementUnit::cases(),
        ]);
    }

    /**
     * @param  array<int, string>  $rawIngredients
     * @return array<int, array{ingredient_name: string, quantity: float|null, unit: string|null, notes: string|null}>
     */
    private function parseRawIngredients(array $rawIngredients): array
    {
        $parser = app(IngredientParser::class);
        $parsed = [];

        foreach ($rawIngredients as $ingredientText) {
            if (empty(trim($ingredientText))) {
                continue;
            }

            $result = $parser->parse(trim($ingredientText));

            $parsed[] = [
                'ingredient_name' => $result['name'],
                'quantity' => $result['quantity'],
                'unit' => $result['unit']?->value,
                'notes' => ($result['quantity'] || $result['unit']) ? null : $result['original'],
            ];
        }

        return $parsed;
    }

    private function parseIngredientQuantities(): void
    {
        foreach ($this->ingredients as $index => $ingredient) {
            if (! isset($ingredient['quantity']) || $ingredient['quantity'] === null || $ingredient['quantity'] === '') {
                continue;
            }

            $parsed = QuantityFormatter::parse((string) $ingredient['quantity']);

            if ($parsed === null) {
                $this->addError("ingredients.{$index}.quantity", 'Quantity must be a number or fraction (e.g. 1/2, 1 1/2).');

                continue;
            }

            $this->ingredients[$index]['quantity'] = $parsed;
        }
    }

    protected function validateIngredients(): void
    {
        foreach ($this->ingredients as $index => $ingredient) {
            if (empty($ingredient['ingredient_name'])) {
                $this->addError("ingredients.{$index}.ingredient_name", 'Ingredient name is required.');
            }

            if (isset($ingredient['quantity']) && $ingredient['quantity'] !== null && $ingredient['quantity'] <= 0) {
                $this->addError("ingredients.{$index}.quantity", 'Quantity must be a number or fraction (e.g. 1/2, 1 1/2).');
            }
        }

        $hasValidIngredient = collect($this->ingredients)
            ->filter(fn ($ing) => ! empty($ing['ingredient_name']))
            ->isNotEmpty();

        if (! $hasValidIngredient) {
            $this->addError('ingredients', 'At least one ingredient is required.');
        }
    }
}
