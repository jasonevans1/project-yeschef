<div>
    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <flux:text class="text-green-800">{{ session('message') }}</flux:text>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <flux:text class="text-red-800">{{ session('error') }}</flux:text>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <flux:heading size="xl" level="1" class="mb-2">{{ $groceryList->name }}</flux:heading>

                {{-- Source Information --}}
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    @if($groceryList->is_meal_plan_linked && $groceryList->mealPlan)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span>From
                            <a href="{{ route('meal-plans.show', $groceryList->mealPlan) }}" class="text-blue-600 hover:underline font-medium">
                                {{ $groceryList->mealPlan->name }}
                            </a>
                        </span>
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <span class="font-medium">Standalone List</span>
                    @endif

                    <span class="text-gray-400">•</span>

                    @if($groceryList->generated_at)
                        <span>Generated {{ $groceryList->generated_at->diffForHumans() }}</span>
                    @else
                        <span>Created {{ $groceryList->created_at->diffForHumans() }}</span>
                    @endif

                    @if($groceryList->regenerated_at)
                        <span class="text-gray-400">•</span>
                        <span>Last updated {{ $groceryList->regenerated_at->diffForHumans() }}</span>
                    @endif
                </div>

                {{-- Completion Progress Bar --}}
                @if($groceryList->total_items > 0)
                    <div class="mt-4">
                        <div class="flex items-center justify-between mb-2">
                            <flux:text class="text-sm font-medium text-gray-700">
                                {{ $groceryList->completed_items }} of {{ $groceryList->total_items }} items completed
                            </flux:text>
                            <flux:text class="text-sm font-semibold text-gray-900">
                                {{ round($groceryList->completion_percentage) }}%
                            </flux:text>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                                 style="width: {{ $groceryList->completion_percentage }}%">
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-2 ml-4">
                @can('update', $groceryList)
                    <flux:button
                        wire:click="showAddItemForm"
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
                            Regenerate
                        </flux:button>
                    @endif
                @endcan

                <a href="{{ route('grocery-lists.index') }}" class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
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
        <div class="bg-white rounded-lg shadow p-6 mb-6 border-2 border-blue-200">
            <flux:heading size="lg" class="mb-4">Add New Item</flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="md:col-span-2">
                    <label for="itemName" class="block text-sm font-medium text-gray-700 mb-1">Item Name *</label>
                    <input
                        type="text"
                        id="itemName"
                        wire:model="itemName"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g., Milk, Bread, Chicken"
                    />
                    @error('itemName') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="itemQuantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                    <input
                        type="number"
                        id="itemQuantity"
                        wire:model="itemQuantity"
                        step="0.01"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g., 2"
                    />
                    @error('itemQuantity') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="itemUnit" class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                    <input
                        type="text"
                        id="itemUnit"
                        wire:model="itemUnit"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g., lbs, cups, whole"
                    />
                    @error('itemUnit') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="itemCategory" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                    <select
                        id="itemCategory"
                        wire:model="itemCategory"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option value="">Select category...</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->value }}">{{ ucfirst($category->value) }}</option>
                        @endforeach
                    </select>
                    @error('itemCategory') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="itemNotes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <input
                        type="text"
                        id="itemNotes"
                        wire:model="itemNotes"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Optional notes"
                    />
                    @error('itemNotes') <span class="text-red-600 text-sm mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <flux:button wire:click="cancelItemForm" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button wire:click="addManualItem" variant="primary">
                    Add Item
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Empty State --}}
    @if($groceryList->total_items === 0)
        <div class="bg-white rounded-lg shadow p-6">
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
                    <flux:button wire:click="showAddItemForm" variant="primary" class="mt-4">
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
                />
            @endforeach
        </div>
    @endif

    {{-- Loading State --}}
    <div wire:loading class="fixed bottom-4 right-4 bg-blue-600 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2">
        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-sm font-medium">Processing...</span>
    </div>
</div>
