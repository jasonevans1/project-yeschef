<?php

namespace App\Services;

use App\Enums\IngredientCategory;
use App\Enums\SourceType;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\MealPlan;
use Illuminate\Support\Collection;

class GroceryListGenerator
{
    public function __construct(
        private ServingSizeScaler $scaler,
        private IngredientAggregator $aggregator,
        private UnitConverter $unitConverter,
        private IngredientCategorizationService $categorizationService = new IngredientCategorizationService
    ) {}

    /**
     * Get the full aggregated ingredient list for a meal plan (used for UI preview).
     * Returns plain arrays (no enum instances) suitable for Livewire public properties.
     *
     * Sorted by IngredientCategory declaration order first, then alphabetically by name.
     *
     * @return \Illuminate\Support\Collection<int, array{name: string, quantity: numeric|null, unit: string|null, category: string, category_label: string}>
     */
    public function getIngredientPreview(MealPlan $mealPlan, int $userId): Collection
    {
        $aggregated = $this->getAggregatedIngredients($mealPlan, $userId);

        $categoryOrder = array_flip(array_map(fn (IngredientCategory $c) => $c->value, IngredientCategory::cases()));

        return $aggregated
            ->map(function (array $ingredient): array {
                /** @var IngredientCategory $category */
                $category = $ingredient['category'];
                /** @var \App\Enums\MeasurementUnit|null $unit */
                $unit = $ingredient['unit'];

                return [
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity'],
                    'unit' => $unit?->value,
                    'category' => $category->value,
                    'category_label' => $category->label(),
                ];
            })
            ->sortBy([
                fn (array $a, array $b) => ($categoryOrder[$a['category']] ?? 999) <=> ($categoryOrder[$b['category']] ?? 999),
                fn (array $a, array $b) => $a['name'] <=> $b['name'],
            ])
            ->values();
    }

    /**
     * Generate a new grocery list from a meal plan
     *
     * @param  MealPlan  $mealPlan  The meal plan to generate from
     * @param  array<int, IngredientCategory>  $excludedCategories  Categories to exclude from the list
     * @param  array<int, string>  $excludedIngredients  Lowercase ingredient names to exclude
     * @return GroceryList The generated grocery list
     */
    public function generate(MealPlan $mealPlan, array $excludedCategories = [], array $excludedIngredients = []): GroceryList
    {
        $excludedValues = array_map(fn (IngredientCategory $c) => $c->value, $excludedCategories);
        $excludedIngredientNames = array_map('strtolower', $excludedIngredients);

        // Create the grocery list
        $groceryList = GroceryList::create([
            'user_id' => $mealPlan->user_id,
            'meal_plan_id' => $mealPlan->id,
            'name' => "Grocery List for {$mealPlan->name}",
            'generated_at' => now(),
            'excluded_categories' => $excludedValues ?: null,
            'excluded_ingredients' => $excludedIngredientNames ?: null,
        ]);

        // Collect and process all ingredients from meal plan
        $allIngredients = $this->collectIngredientsFromMealPlan($mealPlan, $mealPlan->user_id);

        // Aggregate ingredients
        $aggregatedIngredients = $this->aggregateIngredients($allIngredients);

        // Organize by category
        $organizedIngredients = $this->organizeByCategory($aggregatedIngredients);

        // Create grocery items
        $sortOrder = 0;
        foreach ($organizedIngredients as $category => $ingredients) {
            // Skip excluded categories
            if (in_array($category, $excludedValues, true)) {
                continue;
            }

            foreach ($ingredients as $ingredient) {
                // Skip excluded ingredient names
                if (in_array(strtolower($ingredient['name']), $excludedIngredientNames, true)) {
                    continue;
                }

                GroceryItem::create([
                    'grocery_list_id' => $groceryList->id,
                    'name' => $ingredient['name'],
                    'quantity' => $ingredient['quantity'],
                    'unit' => $ingredient['unit'],
                    'category' => $ingredient['category'],
                    'source_type' => SourceType::GENERATED,
                    'sort_order' => $sortOrder++,
                ]);
            }
        }

        return $groceryList->fresh('groceryItems');
    }

    /**
     * Regenerate an existing grocery list from its meal plan
     * Preserves manual items and user edits
     *
     * @param  GroceryList  $groceryList  The grocery list to regenerate
     * @param  array<int, IngredientCategory>  $excludedCategories  Categories to exclude from the list
     * @param  array<int, string>  $excludedIngredients  Lowercase ingredient names to exclude
     * @return GroceryList The updated grocery list
     */
    public function regenerate(GroceryList $groceryList, array $excludedCategories = [], array $excludedIngredients = []): GroceryList
    {
        if ($groceryList->meal_plan_id === null) {
            throw new \InvalidArgumentException('Cannot regenerate a standalone grocery list');
        }

        $excludedValues = array_map(fn (IngredientCategory $c) => $c->value, $excludedCategories);
        $excludedIngredientNames = array_map('strtolower', $excludedIngredients);

        $mealPlan = $groceryList->mealPlan;

        // Collect existing items (including soft-deleted)
        $existingItems = $groceryList->groceryItems()->withTrashed()->get();

        // Separate manual and generated items
        $manualItems = $existingItems->where('source_type', SourceType::MANUAL);
        $editedGeneratedItems = $existingItems->where('source_type', SourceType::GENERATED)
            ->whereNotNull('original_values');
        $deletedGeneratedItems = $existingItems->filter(function ($item) {
            return $item->trashed() && $item->source_type === SourceType::GENERATED;
        });

        // Generate fresh ingredient list from meal plan
        $freshIngredients = $this->collectIngredientsFromMealPlan($mealPlan, $groceryList->user_id);
        $aggregatedIngredients = $this->aggregateIngredients($freshIngredients);

        // Delete unmodified generated items that are no longer in meal plan
        $existingItems->where('source_type', SourceType::GENERATED)
            ->whereNull('original_values')
            ->whereNull('deleted_at')
            ->each->delete();

        // Add new generated items (skip if user deleted or manually edited, or in excluded category)
        $sortOrder = $existingItems->max('sort_order') ?? 0;
        foreach ($aggregatedIngredients as $ingredient) {
            $ingredientName = strtolower($ingredient['name']);
            $categoryValue = $ingredient['category']->value;

            // Skip excluded categories (never affects manual items — they are handled separately)
            if (in_array($categoryValue, $excludedValues, true)) {
                continue;
            }

            // Skip excluded ingredient names (never affects manual items — they are handled separately)
            if (in_array($ingredientName, $excludedIngredientNames, true)) {
                continue;
            }

            // Check if user manually deleted this ingredient
            $wasDeleted = $deletedGeneratedItems->contains(function ($item) use ($ingredientName) {
                return strtolower($item->name) === $ingredientName;
            });

            if ($wasDeleted) {
                continue; // Respect user's deletion
            }

            // Check if user manually edited this ingredient
            $wasEdited = $editedGeneratedItems->contains(function ($item) use ($ingredientName) {
                return strtolower($item->name) === $ingredientName;
            });

            if ($wasEdited) {
                continue; // Keep user's edited version
            }

            // Check if this ingredient already exists (manual)
            $existsAsManual = $manualItems->contains(function ($item) use ($ingredientName) {
                return strtolower($item->name) === $ingredientName;
            });

            if ($existsAsManual) {
                continue; // Don't override manual items
            }

            // Add new generated item
            GroceryItem::create([
                'grocery_list_id' => $groceryList->id,
                'name' => $ingredient['name'],
                'quantity' => $ingredient['quantity'],
                'unit' => $ingredient['unit'],
                'category' => $ingredient['category'],
                'source_type' => SourceType::GENERATED,
                'sort_order' => ++$sortOrder,
            ]);
        }

        $groceryList->update([
            'regenerated_at' => now(),
            'excluded_categories' => $excludedValues ?: null,
            'excluded_ingredients' => $excludedIngredientNames ?: null,
        ]);

        return $groceryList->fresh('groceryItems');
    }

    /**
     * Get item counts per category for a meal plan (before generation)
     * Used to populate the exclusion checkboxes with context on the Generate page.
     *
     * @return array<string, int> Category value => count
     */
    public function getCategoryItemCounts(MealPlan $mealPlan, int $userId): array
    {
        $aggregated = $this->getAggregatedIngredients($mealPlan, $userId);

        $counts = [];
        foreach ($aggregated as $ingredient) {
            $value = $ingredient['category']->value;
            $counts[$value] = ($counts[$value] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Collect and aggregate all ingredients from a meal plan.
     * Used by getCategoryItemCounts and getIngredientPreview.
     */
    private function getAggregatedIngredients(MealPlan $mealPlan, int $userId): Collection
    {
        $allIngredients = $this->collectIngredientsFromMealPlan($mealPlan, $userId);

        return $this->aggregateIngredients($allIngredients);
    }

    /**
     * Collect all ingredients from a meal plan's assigned recipes.
     * Applies template-based category resolution for OTHER-categorized ingredients.
     */
    private function collectIngredientsFromMealPlan(MealPlan $mealPlan, int $userId = 0): Collection
    {
        $allIngredients = collect();

        // Load meal assignments with recipes and ingredients
        $mealAssignments = $mealPlan->mealAssignments()
            ->with('recipe.recipeIngredients.ingredient')
            ->get();

        foreach ($mealAssignments as $assignment) {
            $recipe = $assignment->recipe;
            $servingMultiplier = $assignment->serving_multiplier ?? 1.0;

            foreach ($recipe->recipeIngredients as $recipeIngredient) {
                $allIngredients->push([
                    'name' => $recipeIngredient->ingredient->name,
                    'quantity' => $recipeIngredient->quantity,
                    'unit' => $recipeIngredient->unit,
                    'category' => $recipeIngredient->ingredient->category,
                    'serving_multiplier' => $servingMultiplier,
                ]);
            }
        }

        // Scale all ingredients by their serving multipliers and resolve categories via templates
        return $allIngredients->map(function ($ingredient) use ($userId) {
            $multiplier = $ingredient['serving_multiplier'];
            unset($ingredient['serving_multiplier']);

            $scaled = $this->processIngredients(collect([$ingredient]), $multiplier)->first();
            $scaled['category'] = $this->resolveIngredientCategory($scaled['name'], $scaled['category'], $userId);

            return $scaled;
        });
    }

    /**
     * Resolve the category for an ingredient, upgrading OTHER via the categorization service.
     *
     * Returns the original category immediately when it is not OTHER.
     * Delegates the full resolution chain (UserItemTemplate → CommonItemTemplate → keyword matching → OTHER)
     * to IngredientCategorizationService when the current category is OTHER.
     */
    private function resolveIngredientCategory(string $name, IngredientCategory $currentCategory, int $userId): IngredientCategory
    {
        if ($currentCategory !== IngredientCategory::OTHER) {
            return $currentCategory;
        }

        return $this->categorizationService->resolve($name, $userId);
    }

    /**
     * Process ingredients with serving multiplier (exposed for testing)
     */
    public function processIngredients(Collection $ingredients, float $multiplier): Collection
    {
        return $this->scaler->scaleIngredients($ingredients, $multiplier);
    }

    /**
     * Aggregate ingredients (exposed for testing)
     */
    public function aggregateIngredients(Collection $ingredients): Collection
    {
        return $this->aggregator->aggregate($ingredients);
    }

    /**
     * Organize ingredients by category (exposed for testing)
     */
    public function organizeByCategory(Collection $ingredients): array
    {
        return $ingredients->groupBy(function ($ingredient) {
            return $ingredient['category']->value;
        })->toArray();
    }
}
