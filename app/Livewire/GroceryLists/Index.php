<?php

namespace App\Livewire\GroceryLists;

use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $groceryLists = auth()->user()
            ->groceryLists()
            ->with('mealPlan')
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
