<div>
    <div class="max-w-2xl mx-auto">
        <flux:heading size="xl" level="1" class="mb-6">Import Recipe from URL</flux:heading>

        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
            <form wire:submit="import" class="space-y-6">
                <flux:field>
                    <flux:label>Recipe URL</flux:label>
                    <flux:input
                        wire:model="url"
                        type="url"
                        id="url"
                        name="url"
                        placeholder="https://example.com/recipe"
                        required
                    />
                    <flux:description>
                        Enter the URL of a recipe from a major recipe site (AllRecipes, Food Network, etc.)
                    </flux:description>
                    <flux:error name="url" />
                </flux:field>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                    <flux:button
                        href="{{ route('recipes.index') }}"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button
                        type="submit"
                        variant="primary"
                    >
                        <span wire:loading.remove>Import Recipe</span>
                        <span wire:loading>Fetching recipe...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
