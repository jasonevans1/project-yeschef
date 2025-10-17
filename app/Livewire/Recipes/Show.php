<?php

namespace App\Livewire\Recipes;

use App\Models\Recipe;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Recipe $recipe;

    public function mount(Recipe $recipe): void
    {
        $this->authorize('view', $recipe);

        // Eager load relationships
        $this->recipe->load(['recipeIngredients.ingredient', 'user']);
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

    public function render(): View
    {
        return view('livewire.recipes.show');
    }
}
