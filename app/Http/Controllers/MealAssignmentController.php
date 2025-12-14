<?php

namespace App\Http\Controllers;

use App\Models\MealAssignment;
use App\Models\MealPlan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class MealAssignmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Store a newly created meal assignment in storage.
     */
    public function store(Request $request, MealPlan $mealPlan)
    {
        // Authorize through meal plan ownership
        $this->authorize('update', $mealPlan);

        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'date' => 'required|date',
            'meal_type' => 'required|string',
            'serving_multiplier' => 'nullable|numeric|min:0.25|max:10',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Validate date is within meal plan range
        $date = \Carbon\Carbon::parse($validated['date']);
        if ($date->lt($mealPlan->start_date) || $date->gt($mealPlan->end_date)) {
            return back()->withErrors(['date' => 'The date must be within the meal plan range.']);
        }

        // Ensure date is stored in Y-m-d format
        $validated['date'] = $date->format('Y-m-d');

        // Create the assignment (supports multiple recipes per slot)
        $assignment = $mealPlan->mealAssignments()->create([
            'recipe_id' => $validated['recipe_id'],
            'date' => $validated['date'],
            'meal_type' => $validated['meal_type'],
            'serving_multiplier' => $validated['serving_multiplier'] ?? 1.0,
            'notes' => $validated['notes'] ?? null,
        ]);

        session()->flash('success', 'Recipe assigned successfully!');

        return redirect()->route('meal-plans.show', $mealPlan);
    }

    /**
     * Update the specified meal assignment in storage.
     */
    public function update(Request $request, MealPlan $mealPlan, MealAssignment $assignment)
    {
        // Authorize through meal plan ownership
        $this->authorize('update', $mealPlan);

        // Ensure the assignment belongs to this meal plan
        if ($assignment->meal_plan_id !== $mealPlan->id) {
            abort(404);
        }

        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        $assignment->update($validated);

        session()->flash('success', 'Meal assignment updated successfully!');

        return redirect()->route('meal-plans.show', $mealPlan);
    }

    /**
     * Remove the specified meal assignment from storage.
     */
    public function destroy(MealPlan $mealPlan, MealAssignment $assignment)
    {
        // Authorize through meal plan ownership
        $this->authorize('delete', $mealPlan);

        // Ensure the assignment belongs to this meal plan
        if ($assignment->meal_plan_id !== $mealPlan->id) {
            abort(404);
        }

        $assignment->delete();

        session()->flash('success', 'Meal assignment removed successfully!');

        return redirect()->route('meal-plans.show', $mealPlan);
    }
}
