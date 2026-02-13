<?php

namespace App\Livewire\GroceryLists;

use App\Models\GroceryList;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $groceryLists = GroceryList::accessibleBy(auth()->user())
            ->with(['mealPlan', 'user'])
            ->withCount([
                'groceryItems as total_items',
                'groceryItems as completed_items' => function ($query) {
                    $query->where('purchased', true);
                },
            ])
            ->latest()
            ->paginate(15);

        return view('livewire.grocery-lists.index', [
            'groceryLists' => $groceryLists,
        ]);
    }
}
