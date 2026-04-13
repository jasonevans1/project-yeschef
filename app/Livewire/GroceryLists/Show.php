<?php

namespace App\Livewire\GroceryLists;

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Enums\SharePermission;
use App\Mail\ShareInvitation;
use App\Models\ContentShare;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use App\Services\GroceryListGenerator;
use App\Services\ItemAutoCompleteService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
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

    // Properties for autocomplete (US1)
    public string $searchQuery = '';

    /** @var array<int, array<string, mixed>> */
    public array $suggestions = [];

    // Properties for regeneration confirmation (US4 - T090)
    public bool $showRegenerateConfirm = false;

    /** @var array<string, int> */
    public array $regenerateDiff = [];

    /** @var array<int, string> */
    public array $regenerateExcludedCategories = [];

    /** @var array<int, string> */
    public array $regenerateExcludedIngredients = [];

    // Properties for share dialog (US8 - T131)
    public bool $showShareDialog = false;

    // Properties for user-based sharing (011-share-content)
    public bool $showShareModal = false;

    public string $shareEmail = '';

    public string $sharePermission = 'read';

    // Properties for delete confirmation (US1)
    public bool $showDeleteConfirm = false;

    protected function rules(): array
    {
        return [
            'itemName' => 'required|string|min:1|max:255',
            'itemQuantity' => 'nullable|numeric|min:0',
            'itemUnit' => 'nullable|string|in:'.implode(',', array_map(fn ($u) => $u->value, MeasurementUnit::cases())),
            'itemCategory' => 'nullable|string|in:'.implode(',', array_map(fn ($c) => $c->value, IngredientCategory::cases())),
            'itemNotes' => 'nullable|string|max:500',
        ];
    }

    public function mount(GroceryList $groceryList): void
    {
        // Check if user owns this grocery list
        $this->authorize('view', $groceryList);

        $this->groceryList = $groceryList;
    }

    /**
     * Toggle purchased status of an item
     */
    public function togglePurchased(GroceryItem $item): void
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
    public function openAddItemForm(): void
    {
        $this->authorize('update', $this->groceryList);

        $this->resetForm();
        $this->showAddItemForm = true;
    }

    /**
     * Cancel adding/editing item
     */
    public function cancelItemForm(): void
    {
        $this->resetForm();
        $this->showAddItemForm = false;
        $this->editingItemId = null;
    }

    /**
     * Add a manual item to the list (US4)
     */
    public function addManualItem(): void
    {
        $this->authorize('update', $this->groceryList);

        // If itemName is empty but searchQuery has a value, copy it over
        // This handles the case where user types a custom item without selecting autocomplete
        if (empty($this->itemName) && ! empty($this->searchQuery)) {
            $this->itemName = $this->searchQuery;
        }

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
    public function startEditing(GroceryItem $item): void
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
    public function saveEdit(): void
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
    public function deleteItem(GroceryItem $item): void
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
     * Show regenerate confirmation dialog with diff preview (US4 - T090)
     */
    public function showRegenerateConfirmation(): void
    {
        $this->authorize('update', $this->groceryList);

        // Only meal-plan-linked lists can be regenerated
        if (! $this->groceryList->is_meal_plan_linked) {
            session()->flash('error', 'Only meal plan lists can be regenerated');

            return;
        }

        // Pre-populate exclusions from the existing list
        $this->regenerateExcludedCategories = $this->groceryList->excluded_categories ?? [];
        $this->regenerateExcludedIngredients = $this->groceryList->excluded_ingredients ?? [];

        // Calculate diff preview
        $this->regenerateDiff = $this->calculateRegenerateDiff();
        $this->showRegenerateConfirm = true;
    }

    /**
     * Cancel regeneration
     */
    public function cancelRegenerate(): void
    {
        $this->showRegenerateConfirm = false;
        $this->regenerateDiff = [];
        $this->regenerateExcludedCategories = [];
        $this->regenerateExcludedIngredients = [];
    }

    /**
     * Calculate the diff preview for regeneration (US4 - T090)
     * Returns counts of items that will be added, updated, or removed
     */
    private function calculateRegenerateDiff(): array
    {
        $mealPlan = $this->groceryList->mealPlan;

        // Get existing items (excluding soft-deleted)
        $existingItems = $this->groceryList->groceryItems()->get();

        // Get what would be generated from current meal plan
        $generator = app(GroceryListGenerator::class);
        $freshIngredients = $generator->processIngredients(
            $this->collectIngredientsFromMealPlan($mealPlan),
            1.0
        );
        $aggregatedIngredients = $generator->aggregateIngredients($freshIngredients);

        // Separate existing items by type
        $manualItems = $existingItems->where('source_type', 'manual');
        $editedGeneratedItems = $existingItems->where('source_type', 'generated')
            ->whereNotNull('original_values');
        $unmodifiedGeneratedItems = $existingItems->where('source_type', 'generated')
            ->whereNull('original_values');

        // Count items that will be added (new ingredients not in list)
        $itemsToAdd = 0;
        foreach ($aggregatedIngredients as $ingredient) {
            $ingredientName = strtolower($ingredient['name']);
            $exists = $existingItems->contains(function ($item) use ($ingredientName) {
                return strtolower($item->name) === $ingredientName;
            });

            if (! $exists) {
                $itemsToAdd++;
            }
        }

        // Count items that will be updated (unmodified generated items)
        $itemsToUpdate = $unmodifiedGeneratedItems->count();

        // Count items that will be removed (unmodified generated items no longer in meal plan)
        $itemsToRemove = 0;
        foreach ($unmodifiedGeneratedItems as $item) {
            $ingredientName = strtolower($item->name);
            $stillExists = collect($aggregatedIngredients)->contains(function ($ingredient) use ($ingredientName) {
                return strtolower($ingredient['name']) === $ingredientName;
            });

            if (! $stillExists) {
                $itemsToRemove++;
            }
        }

        return [
            'added' => $itemsToAdd,
            'updated' => $itemsToUpdate,
            'removed' => $itemsToRemove,
            'preserved_manual' => $manualItems->count(),
            'preserved_edited' => $editedGeneratedItems->count(),
        ];
    }

    /**
     * Helper method to collect ingredients from meal plan
     */
    private function collectIngredientsFromMealPlan(MealPlan $mealPlan): Collection
    {
        $allIngredients = collect();

        $mealAssignments = $mealPlan->mealAssignments()
            ->with('recipe.recipeIngredients.ingredient')
            ->get();

        foreach ($mealAssignments as $assignment) {
            $recipe = $assignment->recipe;
            $servingMultiplier = $assignment->serving_multiplier ?? 1.0;

            foreach ($recipe->recipeIngredients as $recipeIngredient) {
                $allIngredients->push([
                    'name' => $recipeIngredient->ingredient->name,
                    'quantity' => $recipeIngredient->quantity * $servingMultiplier,
                    'unit' => $recipeIngredient->unit,
                    'category' => $recipeIngredient->ingredient->category,
                ]);
            }
        }

        return $allIngredients;
    }

    /**
     * Regenerate the grocery list from meal plan (US4 - T090)
     */
    public function regenerate(): void
    {
        $this->authorize('update', $this->groceryList);

        // Only meal-plan-linked lists can be regenerated
        if (! $this->groceryList->is_meal_plan_linked) {
            session()->flash('error', 'Only meal plan lists can be regenerated');

            return;
        }

        // Convert string values to IngredientCategory enum cases
        $excludedEnums = array_filter(array_map(
            fn (string $value) => IngredientCategory::tryFrom($value),
            $this->regenerateExcludedCategories
        ));

        $generator = app(GroceryListGenerator::class);
        $generator->regenerate($this->groceryList, array_values($excludedEnums), $this->regenerateExcludedIngredients);

        session()->flash('message', 'Grocery list regenerated successfully');

        $this->showRegenerateConfirm = false;
        $this->regenerateDiff = [];
        $this->regenerateExcludedCategories = [];
        $this->regenerateExcludedIngredients = [];
        $this->groceryList->refresh();
    }

    /**
     * Open share dialog (US8 - T131)
     */
    public function openShareDialog(): void
    {
        $this->authorize('update', $this->groceryList);

        // Generate share link if not already shared
        if (! $this->groceryList->is_shared) {
            $this->share();
        }

        $this->showShareDialog = true;
    }

    /**
     * Close share dialog (US8 - T131)
     */
    public function closeShareDialog(): void
    {
        $this->showShareDialog = false;
    }

    /**
     * Generate shareable link for grocery list (US8 - T128)
     */
    public function share(): void
    {
        $this->authorize('update', $this->groceryList);

        // Generate UUID token
        $this->groceryList->share_token = Str::uuid()->toString();

        // Set expiration to 7 days from now
        $this->groceryList->share_expires_at = now()->addDays(7);

        // Save the grocery list
        $this->groceryList->save();

        // Refresh to get the updated values
        $this->groceryList->refresh();

        session()->flash('message', 'Shareable link generated successfully');
    }

    /**
     * Revoke share access by clearing the token (US8 - Optional)
     */
    public function revokeShare(): void
    {
        $this->authorize('update', $this->groceryList);

        // Clear the share token and expiration
        $this->groceryList->share_token = null;
        $this->groceryList->share_expires_at = null;

        // Save the grocery list
        $this->groceryList->save();

        // Refresh to get the updated values
        $this->groceryList->refresh();

        session()->flash('message', 'Share access revoked successfully');
    }

    /**
     * Open user-based share modal (011-share-content)
     */
    public function openShareModal(): void
    {
        $this->showShareModal = true;
        $this->shareEmail = '';
        $this->sharePermission = 'read';
        $this->resetValidation(['shareEmail', 'sharePermission']);
    }

    /**
     * Close user-based share modal (011-share-content)
     */
    public function closeShareModal(): void
    {
        $this->showShareModal = false;
        $this->shareEmail = '';
        $this->sharePermission = 'read';
        $this->resetValidation(['shareEmail', 'sharePermission']);
    }

    /**
     * Share grocery list with a user by email (011-share-content)
     */
    public function shareWith(): void
    {
        $this->authorize('share', $this->groceryList);

        $this->validate([
            'shareEmail' => ['required', 'email', 'max:255'],
            'sharePermission' => ['required', 'in:read,write'],
        ]);

        // Prevent self-sharing
        if ($this->shareEmail === auth()->user()->email) {
            $this->addError('shareEmail', 'You cannot share with yourself.');

            return;
        }

        $recipient = User::where('email', $this->shareEmail)->first();

        $share = ContentShare::updateOrCreate(
            [
                'owner_id' => auth()->id(),
                'recipient_email' => $this->shareEmail,
                'shareable_type' => GroceryList::class,
                'shareable_id' => $this->groceryList->id,
            ],
            [
                'recipient_id' => $recipient?->id,
                'permission' => SharePermission::from($this->sharePermission),
                'share_all' => false,
            ]
        );

        if (! $recipient && $share->wasRecentlyCreated) {
            Mail::to($this->shareEmail)->send(new ShareInvitation(
                ownerName: auth()->user()->name,
                contentDescription: "a grocery list: \"{$this->groceryList->name}\"",
                registerUrl: route('register'),
            ));
        }

        session()->flash('success', "Shared with {$this->shareEmail}");

        $this->closeShareModal();
        $this->dispatch('close-modal');
    }

    /**
     * Show delete confirmation modal (US1)
     */
    public function confirmDelete(): void
    {
        $this->authorize('delete', $this->groceryList);

        $this->showDeleteConfirm = true;
    }

    /**
     * Cancel delete operation (US2)
     */
    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
    }

    /**
     * Delete the grocery list (US1)
     */
    public function delete(): mixed
    {
        $this->authorize('delete', $this->groceryList);

        // Soft delete the grocery list (cascades to items via model event)
        $this->groceryList->delete();

        session()->flash('success', 'Grocery list deleted successfully');

        return redirect()->route('grocery-lists.index');
    }

    /**
     * Reset form properties
     */
    private function resetForm(): void
    {
        $this->itemName = '';
        $this->itemQuantity = null;
        $this->itemUnit = null;
        $this->itemCategory = null;
        $this->itemNotes = '';
        $this->searchQuery = '';
        $this->resetValidation();
    }

    /**
     * Update suggestions when search query changes (US1)
     */
    public function updatedSearchQuery(): void
    {
        if (empty(trim($this->searchQuery))) {
            $this->suggestions = [];

            return;
        }

        $service = app(ItemAutoCompleteService::class);
        $results = $service->query(auth()->id(), $this->searchQuery, 10);

        $this->suggestions = $results->map(function ($template) use ($service) {
            return $service->formatSuggestion($template);
        })->toArray();
    }

    /**
     * Select an item from autocomplete suggestions and populate form fields (US1)
     */
    public function selectGroceryItem(array $item): void
    {
        $this->itemName = $item['name'];
        $this->itemCategory = $item['category'];
        $this->itemUnit = $item['unit'];
        $this->itemQuantity = $item['default_quantity'] ? (string) $item['default_quantity'] : null;

        // Show selected item name in the visible search input
        $this->searchQuery = $item['name'];

        // Clear suggestions after selection
        $this->suggestions = [];
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

    public function render(): mixed
    {
        $itemsByCategory = $this->itemsByCategory;

        $recipeIds = $itemsByCategory
            ->flatten(1)
            ->pluck('recipe_ids')
            ->filter()
            ->flatten()
            ->unique()
            ->values();

        $recipesById = Recipe::whereIn('id', $recipeIds)
            ->get()
            ->filter(fn (Recipe $r) => auth()->user()?->can('view', $r) ?? false)
            ->keyBy('id');

        return view('livewire.grocery-lists.show', [
            'itemsByCategory' => $itemsByCategory,
            'recipesById' => $recipesById,
            'categories' => IngredientCategory::cases(),
            'units' => MeasurementUnit::cases(),
        ]);
    }
}
