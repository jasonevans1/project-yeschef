<?php

namespace App\Livewire\GroceryLists;

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

    public function mount(MealPlan $mealPlan)
    {
        // Check if user owns this meal plan
        $this->authorize('view', $mealPlan);

        $this->mealPlan = $mealPlan;

        // Check if grocery list already exists for this meal plan
        $this->existingList = GroceryList::where('meal_plan_id', $mealPlan->id)->first();

        // Calculate recipe count
        $this->recipeCount = $mealPlan->mealAssignments()->count();

        // Estimate item count (rough estimate based on average ingredients per recipe)
        $this->estimatedItemCount = $this->recipeCount * 8; // Assume ~8 ingredients per recipe
    }

    public function generate()
    {
        // Authorize that user can create grocery lists
        $this->authorize('create', GroceryList::class);

        $generator = app(GroceryListGenerator::class);

        // If list exists, regenerate it
        if ($this->existingList) {
            $groceryList = $generator->regenerate($this->existingList);
            session()->flash('message', 'Grocery list regenerated successfully!');
        } else {
            // Generate new list
            $groceryList = $generator->generate($this->mealPlan);
            session()->flash('message', 'Grocery list generated successfully!');
        }

        // Redirect to the grocery list show page
        return redirect()->route('grocery-lists.show', $groceryList);
    }

    public function cancel()
    {
        // Return to meal plan
        return redirect()->route('meal-plans.show', $this->mealPlan);
    }

    public function render()
    {
        return view('livewire.grocery-lists.generate');
    }
}
