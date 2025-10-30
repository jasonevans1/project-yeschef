<?php

namespace App\Services;

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
        private UnitConverter $unitConverter
    ) {}

    /**
     * Generate a new grocery list from a meal plan
     *
     * @param  MealPlan  $mealPlan  The meal plan to generate from
     * @return GroceryList The generated grocery list
     */
    public function generate(MealPlan $mealPlan): GroceryList
    {
        // Create the grocery list
        $groceryList = GroceryList::create([
            'user_id' => $mealPlan->user_id,
            'meal_plan_id' => $mealPlan->id,
            'name' => "Grocery List for {$mealPlan->name}",
            'generated_at' => now(),
        ]);

        // Collect and process all ingredients from meal plan
        $allIngredients = $this->collectIngredientsFromMealPlan($mealPlan);

        // Aggregate ingredients
        $aggregatedIngredients = $this->aggregateIngredients($allIngredients);

        // Organize by category
        $organizedIngredients = $this->organizeByCategory($aggregatedIngredients);

        // Create grocery items
        $sortOrder = 0;
        foreach ($organizedIngredients as $category => $ingredients) {
            foreach ($ingredients as $ingredient) {
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
     * @return GroceryList The updated grocery list
     */
    public function regenerate(GroceryList $groceryList): GroceryList
    {
        if ($groceryList->meal_plan_id === null) {
            throw new \InvalidArgumentException('Cannot regenerate a standalone grocery list');
        }

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
        $freshIngredients = $this->collectIngredientsFromMealPlan($mealPlan);
        $aggregatedIngredients = $this->aggregateIngredients($freshIngredients);

        // Delete unmodified generated items that are no longer in meal plan
        $existingItems->where('source_type', SourceType::GENERATED)
            ->whereNull('original_values')
            ->whereNull('deleted_at')
            ->each->delete();

        // Add new generated items (skip if user deleted or manually edited)
        $sortOrder = $existingItems->max('sort_order') ?? 0;
        foreach ($aggregatedIngredients as $ingredient) {
            $ingredientName = strtolower($ingredient['name']);

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
        ]);

        return $groceryList->fresh('groceryItems');
    }

    /**
     * Collect all ingredients from a meal plan's assigned recipes
     */
    private function collectIngredientsFromMealPlan(MealPlan $mealPlan): Collection
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

        // Scale all ingredients by their serving multipliers
        return $allIngredients->map(function ($ingredient) {
            $multiplier = $ingredient['serving_multiplier'];
            unset($ingredient['serving_multiplier']);

            return $this->processIngredients(collect([$ingredient]), $multiplier)->first();
        });
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
