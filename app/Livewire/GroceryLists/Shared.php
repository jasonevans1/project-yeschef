<?php

namespace App\Livewire\GroceryLists;

use App\Enums\IngredientCategory;
use App\Models\GroceryList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;

class Shared extends Component
{
    use AuthorizesRequests;

    public GroceryList $groceryList;

    public string $token;

    public function mount(string $token)
    {
        $this->token = $token;

        // Find grocery list by share token
        $this->groceryList = GroceryList::where('share_token', $token)->firstOrFail();

        // Check authorization using the viewShared policy
        $this->authorize('viewShared', $this->groceryList);

        // Check if share link has expired
        if ($this->groceryList->share_expires_at && $this->groceryList->share_expires_at->isPast()) {
            abort(403, 'This share link has expired.');
        }

        // Check if share token is null
        if ($this->groceryList->share_token === null) {
            abort(403, 'This grocery list is not shared.');
        }
    }

    /**
     * Get items grouped by category for display
     */
    public function getItemsByCategoryProperty(): Collection
    {
        // Load items with soft deletes excluded, ordered by category and sort_order
        $items = $this->groceryList->groceryItems()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Group by category
        return $items->groupBy(function ($item) {
            return $item->category->value;
        });
    }

    public function render()
    {
        return view('livewire.grocery-lists.shared', [
            'itemsByCategory' => $this->itemsByCategory,
            'categories' => IngredientCategory::cases(),
        ]);
    }
}
