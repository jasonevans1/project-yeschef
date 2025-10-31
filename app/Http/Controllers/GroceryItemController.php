<?php

namespace App\Http\Controllers;

use App\Enums\IngredientCategory;
use App\Enums\SourceType;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class GroceryItemController extends Controller
{
    use AuthorizesRequests;

    /**
     * Store a newly created manual grocery item in storage.
     */
    public function store(Request $request, GroceryList $groceryList)
    {
        $this->authorize('update', $groceryList);

        $validated = $request->validate([
            'name' => 'required|string|min:1|max:255',
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string',
            'category' => 'nullable|string|in:'.implode(',', array_map(fn ($c) => $c->value, IngredientCategory::cases())),
            'notes' => 'nullable|string|max:500',
        ]);

        $groceryList->groceryItems()->create([
            'name' => $validated['name'],
            'quantity' => $validated['quantity'] ?? null,
            'unit' => $validated['unit'] ?? null,
            'category' => $validated['category'] ?? IngredientCategory::OTHER->value,
            'source_type' => SourceType::MANUAL->value,
            'notes' => $validated['notes'] ?? null,
            'sort_order' => $groceryList->groceryItems()->max('sort_order') + 1,
        ]);

        return redirect()->route('grocery-lists.show', $groceryList);
    }

    /**
     * Update the specified grocery item in storage.
     */
    public function update(Request $request, GroceryList $groceryList, GroceryItem $item)
    {
        $this->authorize('update', $groceryList);

        // Verify item belongs to this list
        if ($item->grocery_list_id !== $groceryList->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|min:1|max:255',
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string',
            'category' => 'nullable|string|in:'.implode(',', array_map(fn ($c) => $c->value, IngredientCategory::cases())),
            'notes' => 'nullable|string|max:500',
        ]);

        // Store original values if this is a generated item being edited for the first time
        $originalValues = $item->original_values;
        if ($item->is_generated && $originalValues === null) {
            $originalValues = [
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit' => $item->unit?->value,
                'category' => $item->category?->value,
                'notes' => $item->notes,
            ];
        }

        $item->update([
            'name' => $validated['name'],
            'quantity' => $validated['quantity'] ?? null,
            'unit' => $validated['unit'] ?? null,
            'category' => $validated['category'] ?? IngredientCategory::OTHER->value,
            'notes' => $validated['notes'] ?? null,
            'original_values' => $originalValues,
        ]);

        return redirect()->route('grocery-lists.show', $groceryList);
    }
}
