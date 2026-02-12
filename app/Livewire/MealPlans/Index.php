<?php

namespace App\Livewire\MealPlans;

use App\Models\MealPlan;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function render()
    {
        $mealPlans = MealPlan::accessibleBy(auth()->user())
            ->with('user')
            ->withCount('mealAssignments')
            ->latest()
            ->paginate(10);

        // Group meal plans by status
        $activePlans = $mealPlans->filter(fn ($plan) => $plan->is_active);
        $futurePlans = $mealPlans->filter(fn ($plan) => $plan->is_future);
        $pastPlans = $mealPlans->filter(fn ($plan) => $plan->is_past);

        return view('livewire.meal-plans.index', [
            'mealPlans' => $mealPlans,
            'activePlans' => $activePlans,
            'futurePlans' => $futurePlans,
            'pastPlans' => $pastPlans,
        ]);
    }
}
