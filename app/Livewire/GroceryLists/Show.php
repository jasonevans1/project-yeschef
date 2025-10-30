<?php

namespace App\Livewire\GroceryLists;

use App\Enums\IngredientCategory;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Services\GroceryListGenerator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public GroceryList $groceryList;

    // Properties for manual item management (US4)
    public bool $showAddItemForm = false;

    public ?int $editingItemId = null;

    // Form properties for adding/editing items
    public string $itemName = '';

    public ?string $itemQuantity = null;

    public ?string $itemUnit = null;

    public ?string $itemCategory = null;

    public string $itemNotes = '';

    protected function rules()
    {
        return [
            'itemName' => 'required|string|min:1|max:255',
            'itemQuantity' => 'nullable|numeric|min:0',
            'itemUnit' => 'nullable|string',
            'itemCategory' => 'nullable|string|in:'.implode(',', array_map(fn ($c) => $c->value, IngredientCategory::cases())),
            'itemNotes' => 'nullable|string|max:500',
        ];
    }

    public function mount(GroceryList $groceryList)
    {
        // Check if user owns this grocery list
        $this->authorize('view', $groceryList);

        $this->groceryList = $groceryList;
    }

    /**
     * Toggle purchased status of an item
     */
    public function togglePurchased(GroceryItem $item)
    {
        // Verify item belongs to this list
        if ($item->grocery_list_id !== $this->groceryList->id) {
            abort(403);
        }

        // Toggle purchased status
        $item->update([
            'purchased' => ! $item->purchased,
            'purchased_at' => ! $item->purchased ? now() : null,
        ]);

        // Refresh the grocery list to update computed attributes
        $this->groceryList->refresh();

        session()->flash('message', $item->purchased ? 'Item marked as purchased' : 'Item unmarked');
    }

    /**
     * Show the add item form (US4)
     */
    public function showAddItemForm()
    {
        $this->authorize('update', $this->groceryList);

        $this->resetForm();
        $this->showAddItemForm = true;
    }

    /**
     * Cancel adding/editing item
     */
    public function cancelItemForm()
    {
        $this->resetForm();
        $this->showAddItemForm = false;
        $this->editingItemId = null;
    }

    /**
     * Add a manual item to the list (US4)
     */
    public function addManualItem()
    {
        $this->authorize('update', $this->groceryList);

        $this->validate();

        $this->groceryList->groceryItems()->create([
            'name' => $this->itemName,
            'quantity' => $this->itemQuantity,
            'unit' => $this->itemUnit,
            'category' => $this->itemCategory ?? IngredientCategory::OTHER->value,
            'source_type' => 'manual',
            'notes' => $this->itemNotes,
            'sort_order' => $this->groceryList->groceryItems()->max('sort_order') + 1,
        ]);

        session()->flash('message', 'Item added successfully');

        $this->resetForm();
        $this->showAddItemForm = false;
        $this->groceryList->refresh();
    }

    /**
     * Start editing an item (US4)
     */
    public function startEditing(GroceryItem $item)
    {
        $this->authorize('update', $this->groceryList);

        // Verify item belongs to this list
        if ($item->grocery_list_id !== $this->groceryList->id) {
            abort(403);
        }

        $this->editingItemId = $item->id;
        $this->itemName = $item->name;
        $this->itemQuantity = $item->quantity;
        $this->itemUnit = $item->unit?->value;
        $this->itemCategory = $item->category?->value;
        $this->itemNotes = $item->notes ?? '';
        $this->showAddItemForm = false;
    }

    /**
     * Save edited item (US4)
     */
    public function saveEdit()
    {
        $this->authorize('update', $this->groceryList);

        $this->validate();

        $item = GroceryItem::findOrFail($this->editingItemId);

        // Verify item belongs to this list
        if ($item->grocery_list_id !== $this->groceryList->id) {
            abort(403);
        }

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
            'name' => $this->itemName,
            'quantity' => $this->itemQuantity,
            'unit' => $this->itemUnit,
            'category' => $this->itemCategory ?? IngredientCategory::OTHER->value,
            'notes' => $this->itemNotes,
            'original_values' => $originalValues,
        ]);

        session()->flash('message', 'Item updated successfully');

        $this->resetForm();
        $this->editingItemId = null;
        $this->groceryList->refresh();
    }

    /**
     * Delete an item (US4)
     */
    public function deleteItem(GroceryItem $item)
    {
        $this->authorize('update', $this->groceryList);

        // Verify item belongs to this list
        if ($item->grocery_list_id !== $this->groceryList->id) {
            abort(403);
        }

        if ($item->is_manual) {
            // Hard delete manual items
            $item->forceDelete();
        } else {
            // Soft delete generated items
            $item->delete();
        }

        session()->flash('message', 'Item deleted successfully');

        $this->groceryList->refresh();
    }

    /**
     * Regenerate the grocery list from meal plan (US4)
     */
    public function regenerate()
    {
        $this->authorize('update', $this->groceryList);

        // Only meal-plan-linked lists can be regenerated
        if (! $this->groceryList->is_meal_plan_linked) {
            session()->flash('error', 'Only meal plan lists can be regenerated');

            return;
        }

        $generator = app(GroceryListGenerator::class);
        $generator->regenerate($this->groceryList);

        session()->flash('message', 'Grocery list regenerated successfully');

        $this->groceryList->refresh();
    }

    /**
     * Reset form properties
     */
    private function resetForm()
    {
        $this->itemName = '';
        $this->itemQuantity = null;
        $this->itemUnit = null;
        $this->itemCategory = null;
        $this->itemNotes = '';
        $this->resetValidation();
    }

    /**
     * Get items grouped by category for display
     */
    public function getItemsByCategoryProperty(): Collection
    {
        // Load items with soft deletes excluded, ordered by category and sort_order
        $items = $this->groceryList->groceryItems()
            ->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Group by category
        return $items->groupBy(function ($item) {
            return $item->category->value;
        });
    }

    public function render()
    {
        return view('livewire.grocery-lists.show', [
            'itemsByCategory' => $this->itemsByCategory,
            'categories' => IngredientCategory::cases(),
        ]);
    }
}
