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
            ->latest()
            ->paginate(15);

        // Separate lists into meal-plan-linked and standalone
        $mealPlanLists = $groceryLists->filter(fn ($list) => $list->meal_plan_id !== null);
        $standaloneLists = $groceryLists->filter(fn ($list) => $list->meal_plan_id === null);

        return view('livewire.grocery-lists.index', [
            'groceryLists' => $groceryLists,
            'mealPlanLists' => $mealPlanLists,
            'standaloneLists' => $standaloneLists,
        ]);
    }
}
