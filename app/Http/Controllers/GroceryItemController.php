<?php

namespace App\Http\Controllers;

use App\Enums\IngredientCategory;
use App\Enums\SourceType;
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
}
