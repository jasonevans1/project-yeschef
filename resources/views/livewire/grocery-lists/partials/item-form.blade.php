@props([
    'mode' => 'add', // 'add' or 'edit'
    'categories' => [],
    'compact' => false, // Use compact layout for inline editing
])

@php
    $gridClass = $compact ? 'grid grid-cols-1 md:grid-cols-3 gap-3' : 'grid grid-cols-1 md:grid-cols-2 gap-4';
    $inputSizeClass = $compact ? 'text-sm' : '';
    $errorSizeClass = $compact ? 'text-xs' : 'text-sm';
@endphp

<div class="{{ $gridClass }}">
    {{-- Item Name Field --}}
    <div class="{{ $compact ? '' : 'md:col-span-2' }}">
        <label for="itemName" class="block text-sm font-medium text-gray-700 mb-1">
            Item Name {{ $mode === 'add' ? '*' : '' }}
        </label>
        <input
            type="text"
            id="itemName"
            wire:model="itemName"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $inputSizeClass }}"
            placeholder="{{ $compact ? 'Item name' : 'e.g., Milk, Bread, Chicken' }}"
            required
        />
        @error('itemName')
            <span class="text-red-600 {{ $errorSizeClass }} mt-1 block">{{ $message }}</span>
        @enderror
    </div>

    @if($compact)
        {{-- Compact Layout: Quantity and Unit in one column --}}
        <div class="grid grid-cols-2 gap-2">
            <div>
                <label for="itemQuantity" class="block text-sm font-medium text-gray-700 mb-1">Qty</label>
                <input
                    type="number"
                    id="itemQuantity"
                    wire:model="itemQuantity"
                    step="0.01"
                    min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $inputSizeClass }}"
                    placeholder="Qty"
                />
                @error('itemQuantity')
                    <span class="text-red-600 {{ $errorSizeClass }} mt-1 block">{{ $message }}</span>
                @enderror
            </div>
            <div>
                <label for="itemUnit" class="block text-sm font-medium text-gray-700 mb-1">Unit</label>
                <input
                    type="text"
                    id="itemUnit"
                    wire:model="itemUnit"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $inputSizeClass }}"
                    placeholder="Unit"
                />
                @error('itemUnit')
                    <span class="text-red-600 {{ $errorSizeClass }} mt-1 block">{{ $message }}</span>
                @enderror
            </div>
        </div>
    @else
        {{-- Standard Layout: Separate columns for Quantity and Unit --}}
        <div>
            <label for="itemQuantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
            <input
                type="number"
                id="itemQuantity"
                wire:model="itemQuantity"
                step="0.01"
                min="0"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="e.g., 2"
            />
            @error('itemQuantity')
                <span class="text-red-600 {{ $errorSizeClass }} mt-1 block">{{ $message }}</span>
            @enderror
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
            @error('itemUnit')
                <span class="text-red-600 {{ $errorSizeClass }} mt-1 block">{{ $message }}</span>
            @enderror
        </div>
    @endif

    {{-- Category Dropdown --}}
    <div>
        <label for="itemCategory" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
        <select
            id="itemCategory"
            wire:model="itemCategory"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ $inputSizeClass }}"
        >
            <option value="">Select category...</option>
            @foreach($categories as $category)
                <option value="{{ $category->value }}">{{ ucfirst(str_replace('_', ' ', $category->value)) }}</option>
            @endforeach
        </select>
        @error('itemCategory')
            <span class="text-red-600 {{ $errorSizeClass }} mt-1 block">{{ $message }}</span>
        @enderror
    </div>

    @if(!$compact)
        {{-- Notes Field (only in non-compact mode) --}}
        <div>
            <label for="itemNotes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
            <input
                type="text"
                id="itemNotes"
                wire:model="itemNotes"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Optional notes"
            />
            @error('itemNotes')
                <span class="text-red-600 {{ $errorSizeClass }} mt-1 block">{{ $message }}</span>
            @enderror
        </div>
    @endif
</div>
