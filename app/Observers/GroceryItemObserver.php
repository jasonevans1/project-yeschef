<?php

namespace App\Observers;

use App\Models\GroceryItem;

class GroceryItemObserver
{
    /**
     * Handle the GroceryItem "created" event.
     */
    public function created(GroceryItem $groceryItem): void
    {
        // TODO: Implement in Phase 4 (User Story 2)
        // Will dispatch UpdateUserItemTemplate job for MANUAL items only
    }
}
