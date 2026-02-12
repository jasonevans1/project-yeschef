<?php

namespace App\Policies;

use App\Enums\SharePermission;
use App\Models\ContentShare;
use App\Models\Recipe;
use App\Models\User;

class RecipePolicy
{
    /**
     * Determine whether the user can view the recipe.
     *
     * System recipes (user_id = null) can be viewed by anyone.
     * Personal recipes can be viewed by their owner or shared recipients.
     */
    public function view(User $user, Recipe $recipe): bool
    {
        // System recipes are viewable by all authenticated users
        if ($recipe->user_id === null) {
            return true;
        }

        // Owner can always view
        if ($recipe->user_id === $user->id) {
            return true;
        }

        // Shared recipients can view (read or write)
        return $this->hasShareAccess($user, $recipe);
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
     * Only the owner or write-shared recipients can update personal recipes.
     * System recipes cannot be updated.
     */
    public function update(User $user, Recipe $recipe): bool
    {
        // System recipes cannot be updated
        if ($recipe->user_id === null) {
            return false;
        }

        // Owner can always update
        if ($recipe->user_id === $user->id) {
            return true;
        }

        // Write-shared recipients can update
        return $this->hasWriteShareAccess($user, $recipe);
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

    /**
     * Determine whether the user can share the recipe.
     *
     * Only the owner can share their recipe.
     */
    public function share(User $user, Recipe $recipe): bool
    {
        return $recipe->user_id === $user->id;
    }

    private function hasShareAccess(User $user, Recipe $recipe): bool
    {
        return ContentShare::where('recipient_id', $user->id)
            ->where('shareable_type', Recipe::class)
            ->where(function ($query) use ($recipe) {
                $query->where('shareable_id', $recipe->id)
                    ->orWhere(function ($q) use ($recipe) {
                        $q->where('share_all', true)
                            ->where('owner_id', $recipe->user_id);
                    });
            })
            ->exists();
    }

    private function hasWriteShareAccess(User $user, Recipe $recipe): bool
    {
        return ContentShare::where('recipient_id', $user->id)
            ->where('shareable_type', Recipe::class)
            ->where('permission', SharePermission::Write)
            ->where(function ($query) use ($recipe) {
                $query->where('shareable_id', $recipe->id)
                    ->orWhere(function ($q) use ($recipe) {
                        $q->where('share_all', true)
                            ->where('owner_id', $recipe->user_id);
                    });
            })
            ->exists();
    }
}
