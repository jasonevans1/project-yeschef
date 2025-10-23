<?php

namespace App\Http\Controllers;

use App\Models\MealPlan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class MealPlanController extends Controller
{
    use AuthorizesRequests;

    /**
     * Store a newly created meal plan in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:1000',
        ]);

        // Custom validation: max 28 days duration
        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end = \Carbon\Carbon::parse($validated['end_date']);

        if ($start->diffInDays($end) > 28) {
            return back()->withErrors(['end_date' => 'The meal plan duration cannot exceed 28 days.']);
        }

        // Ensure dates are stored in Y-m-d format
        $validated['start_date'] = $start->format('Y-m-d');
        $validated['end_date'] = $end->format('Y-m-d');

        $mealPlan = auth()->user()->mealPlans()->create($validated);

        session()->flash('success', 'Meal plan created successfully!');

        return redirect()->route('meal-plans.show', $mealPlan);
    }

    /**
     * Update the specified meal plan in storage.
     */
    public function update(Request $request, MealPlan $mealPlan)
    {
        $this->authorize('update', $mealPlan);

        $validated = $request->validate([
            'name' => 'required|string|min:3|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:1000',
        ]);

        // Custom validation: max 28 days duration
        $start = \Carbon\Carbon::parse($validated['start_date']);
        $end = \Carbon\Carbon::parse($validated['end_date']);

        if ($start->diffInDays($end) > 28) {
            return back()->withErrors(['end_date' => 'The meal plan duration cannot exceed 28 days.']);
        }

        // Ensure dates are stored in Y-m-d format
        $validated['start_date'] = $start->format('Y-m-d');
        $validated['end_date'] = $end->format('Y-m-d');

        $mealPlan->update($validated);

        session()->flash('success', 'Meal plan updated successfully!');

        return redirect()->route('meal-plans.show', $mealPlan);
    }

    /**
     * Remove the specified meal plan from storage.
     */
    public function destroy(MealPlan $mealPlan)
    {
        $this->authorize('delete', $mealPlan);

        $mealPlan->delete();

        session()->flash('success', 'Meal plan deleted successfully.');

        return redirect()->route('meal-plans.index');
    }
}
