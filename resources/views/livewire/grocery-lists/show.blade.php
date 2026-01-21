<div>
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4 mb-4">
            <div class="flex-1">
                <flux:heading size="xl" level="1" class="mb-2">{{ $groceryList->name }}</flux:heading>

                {{-- Source Information --}}
                <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600 dark:text-zinc-400">
                    @if($groceryList->is_meal_plan_linked && $groceryList->mealPlan)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>From
                            <a href="{{ route('meal-plans.show', $groceryList->mealPlan) }}" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                {{ $groceryList->mealPlan->name }}
                            </a>
                        </span>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span class="font-medium">Standalone List</span>
                    @endif

                    <span class="text-gray-400 dark:text-zinc-500">•</span>

                    @if($groceryList->generated_at)
                        <span>Generated {{ $groceryList->generated_at->diffForHumans() }}</span>
                    @else
                        <span>Created {{ $groceryList->created_at->diffForHumans() }}</span>
                    @endif

                    @if($groceryList->regenerated_at)
                        <span class="text-gray-400 dark:text-zinc-500">•</span>
                        <span>Last updated {{ $groceryList->regenerated_at->diffForHumans() }}</span>
                    @endif
                </div>

                {{-- Completion Progress Bar --}}
                @if($groceryList->total_items > 0)
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <flux:text class="text-sm font-medium text-gray-700 dark:text-zinc-300">
                                {{ $groceryList->completed_items }} of {{ $groceryList->total_items }} items completed
                            </flux:text>
                            <flux:text class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ round($groceryList->completion_percentage) }}%
                            </flux:text>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-zinc-800 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                                 style="width: {{ $groceryList->completion_percentage }}%">
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-2 lg:ml-4">
                {{-- Export Dropdown (US8 - T131) --}}
                <flux:dropdown>
                    <flux:button variant="ghost" size="sm" icon="arrow-down-tray">
                        Export
                    </flux:button>

                    <flux:menu>
                        <flux:menu.item href="{{ route('grocery-lists.export.pdf', $groceryList) }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Download PDF
                        </flux:menu.item>
                        <flux:menu.item href="{{ route('grocery-lists.export.text', $groceryList) }}">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download Text
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>

                {{-- Share Button (US8 - T131) --}}
                @can('update', $groceryList)
                    <flux:button
                        wire:click="openShareDialog"
                        variant="ghost"
                        size="sm"
                        icon="share"
                    >
                        Share
                    </flux:button>
                @endcan

                @can('delete', $groceryList)
                    <flux:button
                        wire:click="confirmDelete"
                        variant="ghost"
                        size="sm"
                        icon="trash"
                    >
                        Delete
                    </flux:button>
                @endcan

                @can('update', $groceryList)
                    <flux:button
                        wire:click="openAddItemForm"
                        variant="primary"
                        size="sm"
                        icon="plus"
                    >
                        Add Item
                    </flux:button>

                    @if($groceryList->is_meal_plan_linked)
                        <flux:button
                            wire:click="regenerate"
                            wire:confirm="Regenerate grocery list? This will update items from the meal plan while preserving your manual changes."
                            variant="ghost"
                            size="sm"
                            icon="arrow-path"
                        >
                            <span wire:loading.remove wire:target="regenerate">Regenerate</span>
                            <span wire:loading wire:target="regenerate">Regenerating...</span>
                        </flux:button>
                    @endif
                @endcan

                <a href="{{ route('grocery-lists.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-zinc-300 bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-600 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 transition">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Lists
                </a>
            </div>
        </div>
    </div>

    {{-- Add Item Form --}}
    @if($showAddItemForm)
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6 mb-6 border-2 border-gray-200 dark:border-zinc-700">
            <flux:heading size="lg" class="mb-4">Add New Item</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2" x-data="groceryAutocomplete()">
                    <label for="searchQuery" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Item Name *</label>
                    <div class="relative">
                        <input
                            type="text"
                            id="searchQuery"
                            wire:model.live.debounce.300ms="searchQuery"
                            @focus="isOpen = true"
                            @blur="setTimeout(() => isOpen = false, 150)"
                            @keydown.arrow-down.prevent="selectNext()"
                            @keydown.arrow-up.prevent="selectPrevious()"
                            @keydown.enter.prevent="selectCurrent()"
                            @keydown.escape="closeDropdown()"
                            role="combobox"
                            aria-autocomplete="list"
                            aria-controls="suggestions-list"
                            :aria-expanded="isOpen && $wire.suggestions.length > 0"
                            :aria-activedescendant="activeIndex !== null ? `suggestion-${activeIndex}` : ''"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500"
                            placeholder="Start typing to search (e.g., Milk, Bread, Chicken)"
                        />

                        {{-- Autocomplete Dropdown --}}
                        <div
                            x-show="isOpen && $wire.suggestions.length > 0"
                            x-cloak
                            id="suggestions-list"
                            role="listbox"
                            aria-label="Available grocery items"
                            class="absolute z-50 w-full mt-1 bg-white dark:bg-zinc-900 border border-gray-300 dark:border-zinc-600 rounded-lg shadow-lg max-h-64 overflow-y-auto"
                        >
                            <template x-for="(item, index) in $wire.suggestions" :key="item.id">
                                <div
                                    :id="`suggestion-${index}`"
                                    @click="selectItem(item)"
                                    @mouseenter="activeIndex = index"
                                    role="option"
                                    :aria-selected="activeIndex === index"
                                    :class="activeIndex === index ? 'bg-gray-100 dark:bg-zinc-700' : 'hover:bg-gray-100 dark:hover:bg-zinc-800'"
                                    class="px-4 py-3 cursor-pointer transition-colors touch-manipulation min-h-[44px] flex items-center justify-between"
                                >
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900 dark:text-white" x-text="item.name"></div>
                                        <div class="text-sm text-gray-500 dark:text-zinc-400">
                                            <span x-text="item.category ? item.category.charAt(0).toUpperCase() + item.category.slice(1) : ''"></span>
                                            <span x-show="item.default_quantity && item.unit"> • </span>
                                            <span x-show="item.default_quantity && item.unit">
                                                <span x-text="item.default_quantity"></span>
                                                <span x-text="item.unit ? ' ' + item.unit.replace('_', ' ') : ''"></span>
                                            </span>
                                        </div>
                                    </div>
                                    <div x-show="item.is_user_template" class="ml-2">
                                        <span class="text-xs bg-gray-100 dark:bg-zinc-700 text-gray-800 dark:text-zinc-200 px-2 py-1 rounded">
                                            Personal
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Hidden input for actual itemName value --}}
                    <input type="hidden" wire:model="itemName" />

                    @error('itemName') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="itemQuantity" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Quantity</label>
                    <input
                        type="number"
                        id="itemQuantity"
                        wire:model.live="itemQuantity"
                        step="0.01"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500"
                        placeholder="e.g., 2"
                    />
                    @error('itemQuantity') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="itemUnit" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Unit</label>
                    <select
                        id="itemUnit"
                        wire:model.live="itemUnit"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500"
                    >
                        <option value="">Select unit...</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->value }}">{{ ucfirst(str_replace('_', ' ', $unit->value)) }}</option>
                        @endforeach
                    </select>
                    @error('itemUnit') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="itemCategory" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Category</label>
                    <select
                        id="itemCategory"
                        wire:model.live="itemCategory"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500"
                    >
                        <option value="">Select category...</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->value }}">{{ ucfirst($category->value) }}</option>
                        @endforeach
                    </select>
                    @error('itemCategory') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="itemNotes" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Notes</label>
                    <input
                        type="text"
                        id="itemNotes"
                        wire:model.live="itemNotes"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:placeholder-gray-400 rounded-lg focus:ring-2 focus:ring-zinc-500 focus:border-zinc-500"
                        placeholder="Optional notes"
                    />
                    @error('itemNotes') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                <flux:button wire:click="cancelItemForm" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="addManualItem" variant="primary">
                    Save Item
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Empty State --}}
    @if($groceryList->total_items === 0)
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <flux:heading size="lg" class="mt-4">No items in this list</flux:heading>
                <flux:text class="mt-2">
                    @if($groceryList->is_meal_plan_linked)
                        Your meal plan doesn't have any recipes yet. Add recipes to your meal plan and regenerate the list.
                    @else
                        Start adding items to your grocery list
                    @endif
                </flux:text>
                @can('update', $groceryList)
                    <flux:button wire:click="openAddItemForm" variant="primary" class="mt-4">
                        Add First Item
                    </flux:button>
                @endcan
            </div>
        </div>
    @else
        {{-- Grocery Items by Category --}}
        <div class="space-y-6">
            @foreach($itemsByCategory as $categoryValue => $items)
                <x-grocery-category
                    :category="$categoryValue"
                    :items="$items"
                    :groceryList="$groceryList"
                    :editingItemId="$editingItemId"
                    :categories="$categories"
                    :units="$units"
                />
            @endforeach
        </div>
    @endif

    {{-- Loading State --}}
    <div wire:loading class="fixed bottom-4 right-4 bg-zinc-700 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2">
        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm font-medium">Processing...</span>
    </div>

    {{-- Share Dialog Modal (US8 - T132) --}}
    @if($showShareDialog)
        @include('livewire.grocery-lists.partials.share-dialog')
    @endif

    {{-- Delete Confirmation Modal (US1) --}}
    <flux:modal wire:model="showDeleteConfirm">
        <flux:heading size="lg" class="mb-4">Delete Grocery List?</flux:heading>

        <flux:text class="mb-6">
            Are you sure you want to delete "{{ $groceryList->name }}"?
            This will also delete all {{ $groceryList->total_items }} item(s) in this list.
            This action cannot be undone.
        </flux:text>

        <div class="flex items-center justify-end gap-3">
            <flux:button wire:click="cancelDelete" variant="ghost">
                Cancel
            </flux:button>
            <flux:button wire:click="delete" variant="danger">
                <span wire:loading.remove wire:target="delete">Delete List</span>
                <span wire:loading wire:target="delete">Deleting...</span>
            </flux:button>
        </div>
    </flux:modal>
</div>

{{-- Alpine.js Autocomplete Script --}}
<script>
function groceryAutocomplete() {
    return {
        isOpen: false,
        activeIndex: null,

        selectNext() {
            if (!this.$wire.suggestions || this.$wire.suggestions.length === 0) return;

            this.activeIndex = (this.activeIndex ?? -1) + 1;
            if (this.activeIndex >= this.$wire.suggestions.length) {
                this.activeIndex = 0;
            }
            this.scrollIntoView();
        },

        selectPrevious() {
            if (!this.$wire.suggestions || this.$wire.suggestions.length === 0) return;

            this.activeIndex = (this.activeIndex ?? 0) - 1;
            if (this.activeIndex < 0) {
                this.activeIndex = this.$wire.suggestions.length - 1;
            }
            this.scrollIntoView();
        },

        selectCurrent() {
            if (this.activeIndex !== null && this.$wire.suggestions[this.activeIndex]) {
                this.selectItem(this.$wire.suggestions[this.activeIndex]);
            }
        },

        selectItem(item) {
            this.$wire.selectGroceryItem(item);
            this.closeDropdown();
        },

        closeDropdown() {
            this.isOpen = false;
            this.activeIndex = null;
        },

        scrollIntoView() {
            this.$nextTick(() => {
                const active = document.getElementById(`suggestion-${this.activeIndex}`);
                if (active) {
                    active.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                }
            });
        }
    };
}
</script>
