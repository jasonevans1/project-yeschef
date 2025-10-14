<?php

namespace App\Policies;

use App\Models\MealPlan;
use App\Models\User;

class MealPlanPolicy
{
    /**
     * Determine whether the user can view any meal plans.
     *
     * Users can only view their own meal plans.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the meal plan.
     *
     * Users can only view their own meal plans.
     */
    public function view(User $user, MealPlan $mealPlan): bool
    {
        return $mealPlan->user_id === $user->id;
    }

    /**
     * Determine whether the user can create meal plans.
     *
     * All authenticated users can create meal plans.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the meal plan.
     *
     * Users can only update their own meal plans.
     */
    public function update(User $user, MealPlan $mealPlan): bool
    {
        return $mealPlan->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the meal plan.
     *
     * Users can only delete their own meal plans.
     */
    public function delete(User $user, MealPlan $mealPlan): bool
    {
        return $mealPlan->user_id === $user->id;
    }
}
