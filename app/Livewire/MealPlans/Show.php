<?php

namespace App\Livewire\MealPlans;

use App\Enums\MealType;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public MealPlan $mealPlan;

    public ?string $selectedDate = null;

    public ?string $selectedMealType = null;

    public bool $showRecipeSelector = false;

    public string $recipeSearch = '';

    public function mount(MealPlan $mealPlan)
    {
        $this->authorize('view', $mealPlan);
        $this->mealPlan = $mealPlan;
    }

    public function openRecipeSelector($date, $mealType)
    {
        $this->selectedDate = $date;
        $this->selectedMealType = $mealType;
        $this->showRecipeSelector = true;
        $this->recipeSearch = '';
    }

    public function closeRecipeSelector()
    {
        $this->showRecipeSelector = false;
        $this->selectedDate = null;
        $this->selectedMealType = null;
        $this->recipeSearch = '';
    }

    public function assignRecipe(Recipe $recipe)
    {
        $this->authorize('update', $this->mealPlan);

        if (! $this->selectedDate || ! $this->selectedMealType) {
            return;
        }

        // Check if date is within meal plan range
        $date = \Carbon\Carbon::parse($this->selectedDate);
        if ($date->lt($this->mealPlan->start_date) || $date->gt($this->mealPlan->end_date)) {
            session()->flash('error', 'Selected date is outside the meal plan range.');

            return;
        }

        // Check if assignment already exists for this slot
        $existing = $this->mealPlan->mealAssignments()
            ->where('date', $this->selectedDate)
            ->where('meal_type', $this->selectedMealType)
            ->first();

        if ($existing) {
            // Update existing assignment
            $existing->update(['recipe_id' => $recipe->id]);
            session()->flash('success', 'Recipe updated successfully!');
        } else {
            // Create new assignment
            MealAssignment::create([
                'meal_plan_id' => $this->mealPlan->id,
                'recipe_id' => $recipe->id,
                'date' => $this->selectedDate,
                'meal_type' => $this->selectedMealType,
                'serving_multiplier' => 1.00,
            ]);
            session()->flash('success', 'Recipe assigned successfully!');
        }

        $this->closeRecipeSelector();
        $this->mealPlan->refresh();
    }

    public function removeAssignment(MealAssignment $assignment)
    {
        $this->authorize('update', $this->mealPlan);

        $assignment->delete();

        session()->flash('success', 'Recipe removed from meal plan.');

        $this->mealPlan->refresh();
    }

    public function delete()
    {
        $this->authorize('delete', $this->mealPlan);

        $this->mealPlan->delete();

        session()->flash('success', 'Meal plan deleted successfully.');

        return redirect()->route('meal-plans.index');
    }

    public function getRecipesProperty()
    {
        $query = Recipe::query()
            ->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', auth()->id());
            });

        if ($this->recipeSearch) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%'.$this->recipeSearch.'%')
                    ->orWhere('description', 'like', '%'.$this->recipeSearch.'%');
            });
        }

        return $query->limit(20)->get();
    }

    public function render()
    {
        $mealPlan = $this->mealPlan->load(['mealAssignments.recipe']);

        // Generate date range
        $dates = [];
        $current = $this->mealPlan->start_date->copy();
        while ($current->lte($this->mealPlan->end_date)) {
            $dates[] = $current->copy();
            $current->addDay();
        }

        // Group assignments by date and meal type
        $assignments = $mealPlan->mealAssignments->groupBy(function ($assignment) {
            return $assignment->date->format('Y-m-d').'_'.$assignment->meal_type->value;
        });

        return view('livewire.meal-plans.show', [
            'dates' => $dates,
            'assignments' => $assignments,
            'mealTypes' => MealType::cases(),
        ]);
    }
}
