@props(['category', 'items', 'groceryList', 'editingItemId' => null, 'categories' => []])

@php
    use App\Enums\IngredientCategory;

    // Ensure category is an IngredientCategory enum instance
    if (is_string($category)) {
        $category = IngredientCategory::from($category);
    }

    $categoryName = ucfirst($category->value);
    $itemCount = $items->count();
    $completedCount = $items->where('purchased', true)->count();
@endphp

<div class="bg-white rounded-lg shadow">
    {{-- Category Header --}}
    <div class="bg-gray-50 border-b border-gray-200 px-6 py-4 rounded-t-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                </svg>
                <flux:heading size="md">{{ $categoryName }}</flux:heading>
                <flux:badge variant="ghost">
                    {{ $completedCount }} / {{ $itemCount }}
                </flux:badge>
            </div>
        </div>
    </div>

    {{-- Category Items --}}
    <div class="divide-y divide-gray-100">
        @foreach($items as $item)
            <div class="px-6 py-4 hover:bg-gray-50 transition {{ $item->purchased ? 'bg-gray-50/50' : '' }}">
                <div class="flex items-start gap-4">
                    {{-- Checkbox --}}
                    <div class="flex-shrink-0 pt-1">
                        <button
                            wire:click="togglePurchased({{ $item->id }})"
                            class="w-5 h-5 rounded border-2 {{ $item->purchased ? 'bg-blue-600 border-blue-600' : 'border-gray-300 hover:border-blue-500' }} flex items-center justify-center transition"
                        >
                            @if($item->purchased)
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            @endif
                        </button>
                    </div>

                    {{-- Item Details --}}
                    @if($editingItemId === $item->id)
                        {{-- Edit Form --}}
                        <div class="flex-1 space-y-3">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <div>
                                    <input
                                        type="text"
                                        wire:model.live="itemName"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        placeholder="Item name"
                                    />
                                    @error('itemName') <span class="text-red-600 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <input
                                        type="number"
                                        wire:model.live="itemQuantity"
                                        step="0.01"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        placeholder="Qty"
                                    />
                                    <input
                                        type="text"
                                        wire:model.live="itemUnit"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        placeholder="Unit"
                                    />
                                </div>
                                <select
                                    wire:model.live="itemCategory"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                >
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->value }}">{{ ucfirst($cat->value) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:button wire:click="saveEdit" variant="primary" size="sm">
                                    Save
                                </flux:button>
                                <flux:button wire:click="cancelItemForm" variant="ghost" size="sm">
                                    Cancel
                                </flux:button>
                            </div>
                        </div>
                    @else
                        {{-- Display Mode --}}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <flux:text class="font-medium {{ $item->purchased ? 'line-through text-gray-500' : 'text-gray-900' }}">
                                        {{ $item->name }}
                                    </flux:text>

                                    <div class="flex items-center gap-2 mt-1">
                                        @if($item->display_quantity)
                                            <flux:text class="text-sm {{ $item->purchased ? 'text-gray-400' : 'text-gray-600' }}">
                                                {{ $item->display_quantity }}
                                            </flux:text>
                                        @endif

                                        @if($item->notes)
                                            <span class="text-gray-400">•</span>
                                            <flux:text class="text-sm {{ $item->purchased ? 'text-gray-400' : 'text-gray-600' }} italic">
                                                {{ $item->notes }}
                                            </flux:text>
                                        @endif

                                        @if($item->is_edited)
                                            <flux:badge variant="warning" size="sm">Edited</flux:badge>
                                        @elseif($item->is_manual)
                                            <flux:badge variant="info" size="sm">Manual</flux:badge>
                                        @endif
                                    </div>
                                </div>

                                {{-- Edit/Delete Icons (US4) --}}
                                @can('update', $groceryList)
                                    <div class="flex items-center gap-2 ml-4">
                                        <button
                                            wire:click="startEditing({{ $item->id }})"
                                            class="p-1 text-gray-400 hover:text-blue-600 transition"
                                            title="Edit item"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button
                                            wire:click="deleteItem({{ $item->id }})"
                                            wire:confirm="Are you sure you want to delete this item?"
                                            class="p-1 text-gray-400 hover:text-red-600 transition"
                                            title="Delete item"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                @endcan
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
