<?php

namespace App\Livewire\MealPlans;

use App\Enums\MealType;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public MealPlan $mealPlan;

    public ?string $selectedDate = null;

    public ?string $selectedMealType = null;

    public bool $showRecipeSelector = false;

    public string $recipeSearch = '';

    #[Validate('required|numeric|min:0.25|max:10')]
    public float $servingMultiplier = 1.0;

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
        $this->servingMultiplier = 1.0;
    }

    public function closeRecipeSelector()
    {
        $this->showRecipeSelector = false;
        $this->selectedDate = null;
        $this->selectedMealType = null;
        $this->recipeSearch = '';
        $this->servingMultiplier = 1.0;
    }

    public function assignRecipe(Recipe $recipe)
    {
        $this->authorize('update', $this->mealPlan);

        // Validate serving multiplier
        $this->validate();

        if (! $this->selectedDate || ! $this->selectedMealType) {
            return;
        }

        // Check if date is within meal plan range
        $date = \Carbon\Carbon::parse($this->selectedDate);
        if ($date->lt($this->mealPlan->start_date) || $date->gt($this->mealPlan->end_date)) {
            session()->flash('error', 'Selected date is outside the meal plan range.');

            return;
        }

        // Always create new assignment (supports multiple recipes per slot)
        MealAssignment::create([
            'meal_plan_id' => $this->mealPlan->id,
            'recipe_id' => $recipe->id,
            'date' => $this->selectedDate,
            'meal_type' => $this->selectedMealType,
            'serving_multiplier' => $this->servingMultiplier,
        ]);
        session()->flash('success', 'Recipe assigned successfully!');

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

        // Group assignments by date and meal type, sort by creation time
        $assignments = $mealPlan->mealAssignments
            ->groupBy(function ($assignment) {
                return $assignment->date->format('Y-m-d').'_'.$assignment->meal_type->value;
            })
            ->map(fn ($group) => $group->sortBy('created_at'));

        return view('livewire.meal-plans.show', [
            'dates' => $dates,
            'assignments' => $assignments,
            'mealTypes' => MealType::cases(),
        ]);
    }
}
