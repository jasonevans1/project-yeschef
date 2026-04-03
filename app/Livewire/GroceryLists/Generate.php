<?php

namespace App\Livewire\GroceryLists;

use App\Enums\IngredientCategory;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Services\GroceryListGenerator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Generate extends Component
{
    use AuthorizesRequests;

    public MealPlan $mealPlan;

    public ?GroceryList $existingList = null;

    public bool $showConfirmation = true;

    public int $recipeCount = 0;

    public int $estimatedItemCount = 0;

    /** @var array<int, string> */
    public array $excludedCategories = [];

    /** @var array<string, int> */
    public array $categoryItemCounts = [];

    /** @var array<int, string> */
    public array $savedPreferences = [];

    /** @var array<int, array{name: string, quantity: mixed, unit: ?string, category: string, category_label: string}> */
    public array $ingredientPreview = [];

    /** @var array<int, string> */
    public array $excludedIngredients = [];

    /** @var array<int, string> */
    public array $savedPantry = [];

    public function mount(MealPlan $mealPlan): void
    {
        // Check if user owns this meal plan
        $this->authorize('view', $mealPlan);

        $this->mealPlan = $mealPlan;

        // Check if grocery list already exists for this meal plan
        $this->existingList = GroceryList::where('meal_plan_id', $mealPlan->id)->first();

        // Calculate recipe count
        $this->recipeCount = $mealPlan->mealAssignments()->count();

        // Load saved preferences
        $this->savedPreferences = auth()->user()->grocery_category_exclusions ?? [];

        // Pre-populate exclusions: existing list takes precedence over saved preferences
        $this->excludedCategories = $this->existingList?->excluded_categories
            ?? $this->savedPreferences;

        // Load category item counts and ingredient preview when recipes exist
        if ($this->recipeCount > 0) {
            $generator = app(GroceryListGenerator::class);
            $this->categoryItemCounts = $generator->getCategoryItemCounts($mealPlan, auth()->id());

            // Load ingredient preview (real data — replaces heuristic)
            $this->ingredientPreview = $generator->getIngredientPreview($mealPlan, auth()->id())
                ->toArray();
        }

        // Replace heuristic with real count from preview
        $this->estimatedItemCount = count($this->ingredientPreview);

        // Load pantry
        $this->savedPantry = auth()->user()->pantry_items ?? [];

        // Pre-populate excludedIngredients:
        // existing list's excluded_ingredients takes precedence over pantry
        $this->excludedIngredients = $this->existingList?->excluded_ingredients ?? $this->savedPantry;
    }

    public function generate(): mixed
    {
        // Authorize that user can create grocery lists
        $this->authorize('create', GroceryList::class);

        $generator = app(GroceryListGenerator::class);

        // Convert string values to IngredientCategory enum cases
        $excludedEnums = array_filter(array_map(
            fn (string $value) => IngredientCategory::tryFrom($value),
            $this->excludedCategories
        ));

        // If list exists, regenerate it
        if ($this->existingList) {
            $groceryList = $generator->regenerate($this->existingList, array_values($excludedEnums), $this->excludedIngredients);
            session()->flash('message', 'Grocery list regenerated successfully!');
        } else {
            // Generate new list
            $groceryList = $generator->generate($this->mealPlan, array_values($excludedEnums), $this->excludedIngredients);
            session()->flash('message', 'Grocery list generated successfully!');
        }

        // Redirect to the grocery list show page
        return redirect()->route('grocery-lists.show', $groceryList);
    }

    public function savePreferences(): void
    {
        auth()->user()->update([
            'grocery_category_exclusions' => $this->excludedCategories ?: null,
        ]);

        $this->savedPreferences = $this->excludedCategories;
    }

    public function clearPreferences(): void
    {
        auth()->user()->update([
            'grocery_category_exclusions' => null,
        ]);

        $this->savedPreferences = [];
    }

    public function savePantry(): void
    {
        auth()->user()->update([
            'pantry_items' => $this->excludedIngredients ?: null,
        ]);

        $this->savedPantry = $this->excludedIngredients;
    }

    public function clearPantry(): void
    {
        auth()->user()->update([
            'pantry_items' => null,
        ]);

        $this->savedPantry = [];
    }

    public function cancel(): mixed
    {
        // Return to meal plan
        return redirect()->route('meal-plans.show', $this->mealPlan);
    }

    public function render(): mixed
    {
        return view('livewire.grocery-lists.generate', [
            'categories' => IngredientCategory::cases(),
        ]);
    }
}
