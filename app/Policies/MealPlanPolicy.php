<?php

namespace App\Policies;

use App\Enums\SharePermission;
use App\Models\ContentShare;
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
     * Users can view their own meal plans or shared ones.
     */
    public function view(User $user, MealPlan $mealPlan): bool
    {
        if ($mealPlan->user_id === $user->id) {
            return true;
        }

        return $this->hasShareAccess($user, $mealPlan);
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
     * Users can update their own meal plans or write-shared ones.
     */
    public function update(User $user, MealPlan $mealPlan): bool
    {
        if ($mealPlan->user_id === $user->id) {
            return true;
        }

        return $this->hasWriteShareAccess($user, $mealPlan);
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

    /**
     * Determine whether the user can share the meal plan.
     *
     * Only the owner can share their meal plan.
     */
    public function share(User $user, MealPlan $mealPlan): bool
    {
        return $mealPlan->user_id === $user->id;
    }

    private function hasShareAccess(User $user, MealPlan $mealPlan): bool
    {
        return ContentShare::where('recipient_id', $user->id)
            ->where('shareable_type', MealPlan::class)
            ->where(function ($query) use ($mealPlan) {
                $query->where('shareable_id', $mealPlan->id)
                    ->orWhere(function ($q) use ($mealPlan) {
                        $q->where('share_all', true)
                            ->where('owner_id', $mealPlan->user_id);
                    });
            })
            ->exists();
    }

    private function hasWriteShareAccess(User $user, MealPlan $mealPlan): bool
    {
        return ContentShare::where('recipient_id', $user->id)
            ->where('shareable_type', MealPlan::class)
            ->where('permission', SharePermission::Write)
            ->where(function ($query) use ($mealPlan) {
                $query->where('shareable_id', $mealPlan->id)
                    ->orWhere(function ($q) use ($mealPlan) {
                        $q->where('share_all', true)
                            ->where('owner_id', $mealPlan->user_id);
                    });
            })
            ->exists();
    }
}
