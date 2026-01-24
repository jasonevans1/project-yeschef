<?php

namespace App\Livewire\Recipes;

use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Recipe $recipe;

    public bool $showMealPlanModal = false;

    public ?int $selectedMealPlanId = null;

    #[Validate('required|date|after_or_equal:today')]
    public string $assignmentDate = '';

    #[Validate('required|in:breakfast,lunch,dinner,snack')]
    public string $assignmentMealType = 'dinner';

    #[Validate('required|numeric|min:0.25|max:10')]
    public ?float $servingMultiplier = 1.0;

    #[Validate('nullable|string|max:500')]
    public ?string $notes = null;

    public function mount(Recipe $recipe): void
    {
        $this->authorize('view', $recipe);

        // Eager load relationships
        $this->recipe->load(['recipeIngredients.ingredient', 'user']);

        // Set default assignment date to today
        $this->assignmentDate = now()->format('Y-m-d');
    }

    public function getTotalTimeProperty(): ?int
    {
        if ($this->recipe->prep_time === null && $this->recipe->cook_time === null) {
            return null;
        }

        return ($this->recipe->prep_time ?? 0) + ($this->recipe->cook_time ?? 0);
    }

    public function getIsSystemRecipeProperty(): bool
    {
        return $this->recipe->user_id === null;
    }

    public function openMealPlanModal(): void
    {
        $this->showMealPlanModal = true;
        $this->assignmentDate = now()->format('Y-m-d');
        $this->assignmentMealType = 'dinner';
        $this->servingMultiplier = 1.0;
        $this->notes = null;
        $this->selectedMealPlanId = null;
    }

    public function closeMealPlanModal(): void
    {
        $this->showMealPlanModal = false;
        $this->reset(['selectedMealPlanId', 'assignmentDate', 'assignmentMealType', 'servingMultiplier', 'notes']);
    }

    public function addToMealPlan(): void
    {
        // Validate all fields
        $this->validate([
            'selectedMealPlanId' => 'required|exists:meal_plans,id',
            'assignmentDate' => 'required|date|after_or_equal:today',
            'assignmentMealType' => 'required|in:breakfast,lunch,dinner,snack',
            'servingMultiplier' => 'required|numeric|min:0.25|max:10',
            'notes' => 'nullable|string|max:500',
        ]);

        // Get meal plan and authorize
        $mealPlan = MealPlan::findOrFail($this->selectedMealPlanId);
        $this->authorize('update', $mealPlan);

        // Create meal assignment
        MealAssignment::create([
            'meal_plan_id' => $this->selectedMealPlanId,
            'recipe_id' => $this->recipe->id,
            'date' => $this->assignmentDate,
            'meal_type' => $this->assignmentMealType,
            'serving_multiplier' => $this->servingMultiplier,
            'notes' => $this->notes,
        ]);

        session()->flash('success_with_link', [
            'message' => 'Recipe added to ',
            'link_text' => $mealPlan->name,
            'link_url' => route('meal-plans.show', $mealPlan),
            'message_after' => ' successfully!',
        ]);

        $this->redirect(route('recipes.show', $this->recipe), navigate: true);
    }

    public function getMealPlansProperty()
    {
        return MealPlan::where('user_id', auth()->id())
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.recipes.show');
    }
}
