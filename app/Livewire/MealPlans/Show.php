<?php

namespace App\Livewire\MealPlans;

use App\Enums\MealType;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\MealPlanNote;
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

    public ?int $selectedAssignmentId = null;

    public bool $showRecipeDrawer = false;

    // Note-related properties
    public bool $showNoteForm = false;

    #[Validate('required|string|max:255')]
    public string $noteTitle = '';

    #[Validate('nullable|string|max:2000')]
    public string $noteDetails = '';

    public ?int $editingNoteId = null;

    // Note drawer properties
    public bool $showNoteDrawer = false;

    public ?int $selectedNoteId = null;

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

        // Validate serving multiplier only
        $this->validateOnly('servingMultiplier');

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
        $this->closeRecipeSelector();

        // Force fresh load of relationships
        $this->mealPlan = MealPlan::with(['mealAssignments.recipe', 'mealPlanNotes'])
            ->findOrFail($this->mealPlan->id);

        session()->flash('success', 'Recipe assigned successfully!');
    }

    public function removeAssignment(MealAssignment $assignment)
    {
        $this->authorize('update', $this->mealPlan);

        $assignment->delete();

        // Force fresh load of relationships
        $this->mealPlan = MealPlan::with(['mealAssignments.recipe', 'mealPlanNotes'])
            ->findOrFail($this->mealPlan->id);

        session()->flash('success', 'Recipe removed from meal plan.');
    }

    public function openRecipeDrawer(MealAssignment $assignment)
    {
        $this->authorize('view', $this->mealPlan);

        // Eager load recipe and ingredients
        $assignment->load('recipe.recipeIngredients.ingredient');

        $this->selectedAssignmentId = $assignment->id;
        $this->showRecipeDrawer = true;
    }

    public function closeRecipeDrawer()
    {
        $this->showRecipeDrawer = false;
        $this->selectedAssignmentId = null;
    }

    // Note methods

    public function openNoteForm($date, $mealType)
    {
        $this->authorize('update', $this->mealPlan);

        $this->selectedDate = $date;
        $this->selectedMealType = $mealType;
        $this->noteTitle = '';
        $this->noteDetails = '';
        $this->editingNoteId = null;
        $this->showNoteForm = true;
    }

    public function closeNoteForm()
    {
        $this->showNoteForm = false;
        $this->selectedDate = null;
        $this->selectedMealType = null;
        $this->noteTitle = '';
        $this->noteDetails = '';
        $this->editingNoteId = null;
        $this->resetValidation(['noteTitle', 'noteDetails']);
    }

    public function saveNote()
    {
        $this->authorize('update', $this->mealPlan);

        $this->validate([
            'noteTitle' => 'required|string|max:255',
            'noteDetails' => 'nullable|string|max:2000',
        ]);

        if (! $this->selectedDate || ! $this->selectedMealType) {
            return;
        }

        $noteData = [
            'meal_plan_id' => $this->mealPlan->id,
            'date' => $this->selectedDate,
            'meal_type' => $this->selectedMealType,
            'title' => $this->noteTitle,
            'details' => $this->noteDetails ?: null,
        ];

        if ($this->editingNoteId) {
            $note = MealPlanNote::find($this->editingNoteId);
            if ($note) {
                $note->update($noteData);
                session()->flash('success', 'Note updated successfully!');
            }
        } else {
            MealPlanNote::create($noteData);
            session()->flash('success', 'Note added successfully!');
        }

        $this->closeNoteForm();

        // Force fresh load of relationships
        $this->mealPlan = MealPlan::with(['mealAssignments.recipe', 'mealPlanNotes'])
            ->findOrFail($this->mealPlan->id);
    }

    public function openNoteDrawer(MealPlanNote $note)
    {
        $this->authorize('view', $this->mealPlan);

        $this->selectedNoteId = $note->id;
        $this->showNoteDrawer = true;
    }

    public function closeNoteDrawer()
    {
        $this->showNoteDrawer = false;
        $this->selectedNoteId = null;
    }

    public function editNote(MealPlanNote $note)
    {
        $this->authorize('update', $this->mealPlan);

        $this->selectedDate = $note->date->format('Y-m-d');
        $this->selectedMealType = $note->meal_type->value;
        $this->noteTitle = $note->title;
        $this->noteDetails = $note->details ?? '';
        $this->editingNoteId = $note->id;
        $this->showNoteDrawer = false;
        $this->showNoteForm = true;
    }

    public function getSelectedNoteProperty()
    {
        if (! $this->selectedNoteId) {
            return null;
        }

        return MealPlanNote::find($this->selectedNoteId);
    }

    public function deleteNote(MealPlanNote $note)
    {
        $this->authorize('update', $this->mealPlan);

        $note->delete();

        $this->closeNoteDrawer();

        // Force fresh load of relationships
        $this->mealPlan = MealPlan::with(['mealAssignments.recipe', 'mealPlanNotes'])
            ->findOrFail($this->mealPlan->id);

        session()->flash('success', 'Note deleted successfully.');
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

    public function getSelectedAssignmentProperty()
    {
        if (! $this->selectedAssignmentId) {
            return null;
        }

        return MealAssignment::with('recipe.recipeIngredients.ingredient')
            ->find($this->selectedAssignmentId);
    }

    public function getScaledIngredientsProperty()
    {
        $assignment = $this->selectedAssignment;

        if (! $assignment || ! $assignment->recipe) {
            return [];
        }

        $multiplier = $assignment->serving_multiplier;

        return $assignment->recipe->recipeIngredients->map(function ($recipeIngredient) use ($multiplier) {
            $scaledQuantity = $recipeIngredient->quantity * $multiplier;

            // Format quantity: max 3 decimals, remove trailing zeros
            $formattedQuantity = rtrim(rtrim(number_format($scaledQuantity, 3, '.', ''), '0'), '.');

            return [
                'quantity' => $formattedQuantity,
                'unit' => $recipeIngredient->unit?->value ?? '',
                'name' => $recipeIngredient->ingredient->name,
            ];
        })->toArray();
    }

    public function render()
    {
        $mealPlan = $this->mealPlan->load(['mealAssignments.recipe', 'mealPlanNotes']);

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

        // Group notes by date and meal type, sort by creation time
        $notes = $mealPlan->mealPlanNotes
            ->groupBy(function ($note) {
                return $note->date->format('Y-m-d').'_'.$note->meal_type->value;
            })
            ->map(fn ($group) => $group->sortBy('created_at'));

        return view('livewire.meal-plans.show', [
            'dates' => $dates,
            'assignments' => $assignments,
            'notes' => $notes,
            'mealTypes' => MealType::cases(),
        ]);
    }
}
