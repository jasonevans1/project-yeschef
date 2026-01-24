<?php

namespace App\Observers;

use App\Enums\SourceType;
use App\Jobs\UpdateUserItemTemplate;
use App\Models\GroceryItem;

class GroceryItemObserver
{
    /**
     * Handle the GroceryItem "created" event.
     */
    public function created(GroceryItem $groceryItem): void
    {
        // Only track manually added items (not generated from recipes)
        if ($groceryItem->source_type !== SourceType::MANUAL) {
            return;
        }

        // Dispatch job to update user item template
        dispatch(new UpdateUserItemTemplate(
            userId: $groceryItem->groceryList->user_id,
            itemName: $groceryItem->name,
            category: $groceryItem->category?->value,
            unit: $groceryItem->unit?->value,
            defaultQuantity: (float) $groceryItem->quantity,
        ));
    }
}
