<?php

namespace App\Livewire\MealPlans;

use App\Models\MealPlan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Delete extends Component
{
    use AuthorizesRequests;

    public function delete(MealPlan $mealPlan)
    {
        $this->authorize('delete', $mealPlan);

        $mealPlan->delete();

        session()->flash('success', 'Meal plan deleted successfully.');

        return redirect()->route('meal-plans.index');
    }

    public function render()
    {
        return view('livewire.meal-plans.delete');
    }
}
