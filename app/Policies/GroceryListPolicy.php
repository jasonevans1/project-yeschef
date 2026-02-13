<?php

namespace App\Policies;

use App\Enums\SharePermission;
use App\Models\ContentShare;
use App\Models\GroceryList;
use App\Models\User;

class GroceryListPolicy
{
    /**
     * Determine whether the user can view any grocery lists.
     *
     * Users can only view their own grocery lists.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the grocery list.
     *
     * Users can view their own grocery lists or shared ones.
     */
    public function view(User $user, GroceryList $groceryList): bool
    {
        if ($groceryList->user_id === $user->id) {
            return true;
        }

        return $this->hasShareAccess($user, $groceryList);
    }

    /**
     * Determine whether the user can create grocery lists.
     *
     * All authenticated users can create grocery lists.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the grocery list.
     *
     * Users can update their own grocery lists or write-shared ones.
     */
    public function update(User $user, GroceryList $groceryList): bool
    {
        if ($groceryList->user_id === $user->id) {
            return true;
        }

        return $this->hasWriteShareAccess($user, $groceryList);
    }

    /**
     * Determine whether the user can delete the grocery list.
     *
     * Users can only delete their own grocery lists.
     */
    public function delete(User $user, GroceryList $groceryList): bool
    {
        return $groceryList->user_id === $user->id;
    }

    /**
     * Determine whether the user can share the grocery list.
     *
     * Only the owner can share their grocery list.
     */
    public function share(User $user, GroceryList $groceryList): bool
    {
        return $groceryList->user_id === $user->id;
    }

    /**
     * Determine whether the user can view a shared grocery list.
     *
     * Requires authentication and valid share token with non-expired link.
     */
    public function viewShared(User $user, GroceryList $groceryList): bool
    {
        // Check if the list has a share token
        if ($groceryList->share_token === null) {
            return false;
        }

        // Check if the share link has expired
        if ($groceryList->share_expires_at !== null && $groceryList->share_expires_at->isPast()) {
            return false;
        }

        // User is authenticated and share link is valid
        return true;
    }

    private function hasShareAccess(User $user, GroceryList $groceryList): bool
    {
        return ContentShare::where('recipient_id', $user->id)
            ->where('shareable_type', GroceryList::class)
            ->where(function ($query) use ($groceryList) {
                $query->where('shareable_id', $groceryList->id)
                    ->orWhere(function ($q) use ($groceryList) {
                        $q->where('share_all', true)
                            ->where('owner_id', $groceryList->user_id);
                    });
            })
            ->exists();
    }

    private function hasWriteShareAccess(User $user, GroceryList $groceryList): bool
    {
        return ContentShare::where('recipient_id', $user->id)
            ->where('shareable_type', GroceryList::class)
            ->where('permission', SharePermission::Write)
            ->where(function ($query) use ($groceryList) {
                $query->where('shareable_id', $groceryList->id)
                    ->orWhere(function ($q) use ($groceryList) {
                        $q->where('share_all', true)
                            ->where('owner_id', $groceryList->user_id);
                    });
            })
            ->exists();
    }
}
