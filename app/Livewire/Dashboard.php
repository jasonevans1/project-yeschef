<?php

namespace App\Livewire;

use App\Models\GroceryList;
use App\Models\MealPlan;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    /**
     * Get upcoming meal plans for the next 7 days.
     */
    #[Computed]
    public function upcomingMealPlans()
    {
        $today = now()->startOfDay();
        $nextWeek = now()->addDays(7)->endOfDay();

        return MealPlan::query()
            ->accessibleBy(auth()->user())
            ->where(function ($query) use ($today, $nextWeek) {
                // Plans that start in the next 7 days, or are currently active
                $query->whereBetween('start_date', [$today, $nextWeek])
                    ->orWhere(function ($q) use ($today, $nextWeek) {
                        // Or plans that are active during this period
                        $q->where('start_date', '<=', $nextWeek)
                            ->where('end_date', '>=', $today);
                    });
            })
            ->withCount('mealAssignments')
            ->orderBy('start_date')
            ->limit(5)
            ->get();
    }

    /**
     * Get recent grocery lists (most recently created/updated).
     */
    #[Computed]
    public function recentGroceryLists()
    {
        return GroceryList::query()
            ->accessibleBy(auth()->user())
            ->withCount([
                'groceryItems as total_items',
                'groceryItems as completed_items' => function ($query) {
                    $query->where('purchased', true);
                },
            ])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
