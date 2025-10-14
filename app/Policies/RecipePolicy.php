<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

class RecipePolicy
{
    /**
     * Determine whether the user can view the recipe.
     *
     * System recipes (user_id = null) can be viewed by anyone.
     * Personal recipes can only be viewed by their owner.
     */
    public function view(User $user, Recipe $recipe): bool
    {
        // System recipes are viewable by all authenticated users
        if ($recipe->user_id === null) {
            return true;
        }

        // Personal recipes can only be viewed by their owner
        return $recipe->user_id === $user->id;
    }

    /**
     * Determine whether the user can create recipes.
     *
     * All authenticated users can create personal recipes.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the recipe.
     *
     * Only the owner can update their personal recipes.
     * System recipes cannot be updated.
     */
    public function update(User $user, Recipe $recipe): bool
    {
        // System recipes cannot be updated
        if ($recipe->user_id === null) {
            return false;
        }

        // Only the owner can update their recipe
        return $recipe->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the recipe.
     *
     * Only the owner can delete their personal recipes.
     * System recipes cannot be deleted.
     */
    public function delete(User $user, Recipe $recipe): bool
    {
        // System recipes cannot be deleted
        if ($recipe->user_id === null) {
            return false;
        }

        // Only the owner can delete their recipe
        return $recipe->user_id === $user->id;
    }
}
