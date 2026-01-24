<x-settings.layout
    :heading="$template ? 'Edit Item Template' : 'Create Item Template'"
    subheading="Customize autocomplete suggestions for grocery items"
>
    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
        <form wire:submit="save">
            <div class="space-y-6">
                {{-- Item Name --}}
                <div>
                    <flux:field>
                        <flux:label for="name">Item Name</flux:label>
                        <flux:input
                            wire:model="name"
                            name="name"
                            placeholder="e.g., Organic Honey"
                            required
                        />
                        <flux:error name="name" />
                    </flux:field>
                </div>

                {{-- Category --}}
                <div>
                    <flux:field>
                        <flux:label for="category">Category</flux:label>
                        <flux:select
                            wire:model="category"
                            name="category"
                            required
                        >
                            <option value="">Select a category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="category" />
                    </flux:field>
                </div>

                {{-- Unit --}}
                <div>
                    <flux:field>
                        <flux:label for="unit">Unit</flux:label>
                        <flux:select
                            wire:model="unit"
                            name="unit"
                            required
                        >
                            <option value="">Select a unit</option>
                            @foreach($units as $u)
                                <option value="{{ $u->value }}">{{ $u->label() }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="unit" />
                    </flux:field>
                </div>

                {{-- Default Quantity --}}
                <div>
                    <flux:field>
                        <flux:label for="default_quantity">Default Quantity</flux:label>
                        <flux:input
                            wire:model="default_quantity"
                            name="default_quantity"
                            type="number"
                            step="0.001"
                            min="0"
                            placeholder="1"
                        />
                        <flux:error name="default_quantity" />
                    </flux:field>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                    <flux:button
                        href="{{ route('settings.item-templates') }}"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ $template ? 'Update' : 'Create' }} Template
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</x-settings.layout>
