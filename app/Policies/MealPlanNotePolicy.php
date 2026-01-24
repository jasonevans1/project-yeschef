<?php

namespace App\Policies;

use App\Models\MealPlanNote;
use App\Models\User;

class MealPlanNotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MealPlanNote $mealPlanNote): bool
    {
        return $user->can('view', $mealPlanNote->mealPlan);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MealPlanNote $mealPlanNote): bool
    {
        return $user->can('update', $mealPlanNote->mealPlan);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MealPlanNote $mealPlanNote): bool
    {
        return $user->can('delete', $mealPlanNote->mealPlan);
    }
}
