<?php

namespace App\Livewire\GroceryLists;

use Livewire\Attributes\Validate;
use Livewire\Component;

class Create extends Component
{
    #[Validate('required|string|min:3|max:255')]
    public string $name = '';

    public function save()
    {
        $this->validate();

        $groceryList = auth()->user()->groceryLists()->create([
            'name' => $this->name,
            'meal_plan_id' => null,
        ]);

        session()->flash('success', 'Standalone grocery list created successfully!');

        return redirect()->route('grocery-lists.show', $groceryList);
    }

    public function render()
    {
        return view('livewire.grocery-lists.create');
    }
}
