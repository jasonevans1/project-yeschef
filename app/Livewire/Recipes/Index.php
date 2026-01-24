<?php

namespace App\Livewire\Recipes;

use App\Models\Recipe;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public array $mealTypes = [];

    #[Url]
    public array $dietaryTags = [];

    #[Url]
    public string $sortBy = 'newest';

    #[Url]
    public bool $myRecipesOnly = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedMealTypes(): void
    {
        $this->resetPage();
    }

    public function updatedDietaryTags(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function updatedMyRecipesOnly(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Recipe::query()
            ->with(['recipeIngredients.ingredient']);

        // Filter by ownership
        if ($this->myRecipesOnly) {
            // Show only user's own recipes
            $query->where('user_id', auth()->id());
        } else {
            // Show system recipes and user's own recipes (default behavior)
            $query->where(function ($q) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', auth()->id());
            });
        }

        // Full-text search on name, description, and ingredients (fallback to LIKE for SQLite)
        if ($this->search) {
            // Check if the database supports fulltext search
            $useFulltext = config('database.default') !== 'sqlite';

            $query->where(function ($q) use ($useFulltext) {
                if ($useFulltext) {
                    $q->whereFullText(['name', 'description'], $this->search);
                } else {
                    // Fallback to LIKE search for databases that don't support fulltext (e.g., SQLite)
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                }

                // Also search by ingredient name
                $q->orWhereHas('ingredients', function ($ingredientQuery) {
                    $ingredientQuery->whereRaw('LOWER(ingredients.name) LIKE ?', ['%'.strtolower($this->search).'%']);
                });
            });
        }

        // Filter by meal types
        if (! empty($this->mealTypes)) {
            $query->whereIn('meal_type', $this->mealTypes);
        }

        // Filter by dietary tags
        if (! empty($this->dietaryTags)) {
            foreach ($this->dietaryTags as $tag) {
                $query->whereJsonContains('dietary_tags', $tag);
            }
        }

        // Apply sorting
        $query = match ($this->sortBy) {
            'oldest' => $query->orderBy('created_at', 'asc'),
            'name_asc' => $query->orderBy('name', 'asc'),
            'name_desc' => $query->orderBy('name', 'desc'),
            default => $query->latest(), // 'newest' or invalid values
        };

        $recipes = $query->paginate(24);

        return view('livewire.recipes.index', [
            'recipes' => $recipes,
        ]);
    }
}
