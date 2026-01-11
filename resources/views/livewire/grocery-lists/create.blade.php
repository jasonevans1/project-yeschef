<div>
    <div class="max-w-2xl mx-auto">
        <flux:heading size="xl" level="1" class="mb-6">Create Standalone Grocery List</flux:heading>

        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
            <form wire:submit="save" class="space-y-6">
                {{-- Explanation --}}
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex gap-3">
                        <flux:icon.information-circle class="size-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                        <div class="text-sm text-blue-800 dark:text-blue-200">
                            <p class="font-medium mb-1">Standalone Shopping List</p>
                            <p>Create a shopping list not linked to any meal plan. Perfect for parties, special occasions, or general shopping trips.</p>
                        </div>
                    </div>
                </div>

                {{-- Name Field --}}
                <flux:input
                    wire:model="name"
                    label="List Name"
                    id="name"
                    name="name"
                    placeholder="e.g., Party Shopping, Weekend Essentials"
                    required
                />

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t dark:border-zinc-700">
                    <flux:button
                        href="{{ route('grocery-lists.index') }}"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button
                        type="submit"
                        variant="primary"
                    >
                        <span wire:loading.remove>Create List</span>
                        <span wire:loading>Creating...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
