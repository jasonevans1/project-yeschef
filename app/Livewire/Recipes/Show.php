<?php

namespace App\Livewire\Recipes;

use App\Enums\SharePermission;
use App\Mail\ShareInvitation;
use App\Models\ContentShare;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Recipe $recipe;

    public bool $showMealPlanModal = false;

    // Share modal properties
    public bool $showShareModal = false;

    #[Validate('required|email|max:255')]
    public string $shareEmail = '';

    #[Validate('required|in:read,write')]
    public string $sharePermission = 'read';

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

    public function openShareModal(): void
    {
        $this->showShareModal = true;
        $this->shareEmail = '';
        $this->sharePermission = 'read';
        $this->resetValidation(['shareEmail', 'sharePermission']);
    }

    public function closeShareModal(): void
    {
        $this->showShareModal = false;
        $this->reset(['shareEmail', 'sharePermission']);
        $this->resetValidation(['shareEmail', 'sharePermission']);
    }

    public function shareWith(): void
    {
        $this->authorize('share', $this->recipe);

        $this->validate([
            'shareEmail' => ['required', 'email', 'max:255'],
            'sharePermission' => ['required', 'in:read,write'],
        ]);

        // Prevent self-sharing
        if ($this->shareEmail === auth()->user()->email) {
            $this->addError('shareEmail', 'You cannot share with yourself.');

            return;
        }

        $recipient = User::where('email', $this->shareEmail)->first();

        $share = ContentShare::updateOrCreate(
            [
                'owner_id' => auth()->id(),
                'recipient_email' => $this->shareEmail,
                'shareable_type' => Recipe::class,
                'shareable_id' => $this->recipe->id,
            ],
            [
                'recipient_id' => $recipient?->id,
                'permission' => SharePermission::from($this->sharePermission),
                'share_all' => false,
            ]
        );

        if (! $recipient && $share->wasRecentlyCreated) {
            Mail::to($this->shareEmail)->send(new ShareInvitation(
                ownerName: auth()->user()->name,
                contentDescription: "a recipe: \"{$this->recipe->name}\"",
                registerUrl: route('register'),
            ));
        }

        session()->flash('success', "Shared with {$this->shareEmail}");

        $this->closeShareModal();
        $this->dispatch('close-modal');
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
