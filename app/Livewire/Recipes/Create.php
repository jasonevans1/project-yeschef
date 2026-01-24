<?php

namespace App\Livewire\Recipes;

use App\Enums\IngredientCategory;
use App\Enums\MealType;
use App\Enums\MeasurementUnit;
use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Create extends Component
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

    #[Validate('nullable|string')]
    public ?string $difficulty = null;

    #[Validate('nullable|array')]
    public array $dietary_tags = [];

    #[Validate('required|string|min:10')]
    public string $instructions = '';

    #[Validate('nullable|url|max:255')]
    public ?string $image_url = null;

    #[Validate('required|array|min:1')]
    public array $ingredients = [];

    public function mount(): void
    {
        // Initialize with one empty ingredient row
        $this->ingredients = [
            [
                'ingredient_name' => '',
                'quantity' => null,
                'unit' => null,
                'notes' => null,
            ],
        ];
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

    public function save(): void
    {
        $this->validate();

        // Additional validation for ingredients
        $this->validateIngredients();

        // Create the recipe
        $recipe = auth()->user()->recipes()->create([
            'name' => $this->name,
            'description' => $this->description,
            'prep_time' => $this->prep_time,
            'cook_time' => $this->cook_time,
            'servings' => $this->servings,
            'meal_type' => $this->meal_type ? MealType::from($this->meal_type) : null,
            'cuisine' => $this->cuisine,
            'difficulty' => $this->difficulty,
            'dietary_tags' => ! empty($this->dietary_tags) ? $this->dietary_tags : null,
            'instructions' => $this->instructions,
            'image_url' => $this->image_url,
        ]);

        // Handle ingredients
        foreach ($this->ingredients as $index => $ingredientData) {
            if (empty($ingredientData['ingredient_name'])) {
                continue;
            }

            // Find or create ingredient (case-insensitive)
            $ingredient = Ingredient::firstOrCreate(
                ['name' => strtolower(trim($ingredientData['ingredient_name']))],
                ['category' => $this->guessIngredientCategory($ingredientData['ingredient_name'])]
            );

            // Create recipe ingredient pivot record
            $recipe->recipeIngredients()->create([
                'ingredient_id' => $ingredient->id,
                'quantity' => $ingredientData['quantity'] ?? 1,
                'unit' => $ingredientData['unit'] ? MeasurementUnit::from($ingredientData['unit']) : MeasurementUnit::WHOLE,
                'sort_order' => $index,
                'notes' => $ingredientData['notes'],
            ]);
        }

        session()->flash('success', 'Recipe created successfully!');

        $this->redirect(route('recipes.show', $recipe), navigate: true);
    }

    protected function validateIngredients(): void
    {
        foreach ($this->ingredients as $index => $ingredient) {
            if (empty($ingredient['ingredient_name'])) {
                $this->addError("ingredients.{$index}.ingredient_name", 'Ingredient name is required.');
            }

            if (isset($ingredient['quantity']) && $ingredient['quantity'] !== null && $ingredient['quantity'] <= 0) {
                $this->addError("ingredients.{$index}.quantity", 'Quantity must be greater than 0.');
            }
        }

        // Check that at least one ingredient has a name
        $hasValidIngredient = collect($this->ingredients)
            ->filter(fn ($ing) => ! empty($ing['ingredient_name']))
            ->isNotEmpty();

        if (! $hasValidIngredient) {
            $this->addError('ingredients', 'At least one ingredient is required.');
        }
    }

    protected function guessIngredientCategory(string $name): IngredientCategory
    {
        $name = strtolower($name);

        // Simple category guessing based on keywords
        $categoryKeywords = [
            IngredientCategory::PRODUCE->value => ['lettuce', 'tomato', 'onion', 'garlic', 'pepper', 'carrot', 'celery', 'potato', 'spinach', 'broccoli', 'cucumber', 'apple', 'banana', 'orange', 'lemon', 'lime', 'herb', 'basil', 'parsley', 'cilantro'],
            IngredientCategory::DAIRY->value => ['milk', 'cheese', 'butter', 'cream', 'yogurt', 'sour cream', 'ricotta', 'mozzarella', 'parmesan', 'cheddar'],
            IngredientCategory::MEAT->value => ['chicken', 'beef', 'pork', 'turkey', 'lamb', 'bacon', 'sausage', 'ground beef', 'steak'],
            IngredientCategory::SEAFOOD->value => ['fish', 'salmon', 'tuna', 'shrimp', 'crab', 'lobster', 'cod', 'tilapia'],
            IngredientCategory::PANTRY->value => ['flour', 'sugar', 'salt', 'pepper', 'oil', 'olive oil', 'vinegar', 'rice', 'pasta', 'beans', 'sauce', 'spice', 'cumin', 'paprika', 'oregano'],
            IngredientCategory::FROZEN->value => ['frozen'],
            IngredientCategory::BAKERY->value => ['bread', 'bun', 'roll', 'tortilla', 'pita', 'bagel'],
            IngredientCategory::BEVERAGES->value => ['juice', 'soda', 'water', 'coffee', 'tea', 'wine', 'beer'],
        ];

        foreach ($categoryKeywords as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($name, $keyword)) {
                    return IngredientCategory::from($category);
                }
            }
        }

        return IngredientCategory::OTHER;
    }

    public function render(): View
    {
        return view('livewire.recipes.create', [
            'mealTypes' => MealType::cases(),
            'measurementUnits' => MeasurementUnit::cases(),
            'ingredientCategories' => IngredientCategory::cases(),
        ]);
    }
}
