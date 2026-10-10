<div>
    <div class="max-w-2xl mx-auto">
        <flux:heading size="xl" level="1" class="mb-6">Import Recipe</flux:heading>

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
                        <span wire:loading.remove wire:target="import">Import Recipe</span>
                        <span wire:loading wire:target="import">Fetching recipe...</span>
                    </flux:button>
                </div>
            </form>

            <div class="flex items-center gap-3 my-6 text-sm text-gray-500 dark:text-zinc-400">
                <div class="flex-1 border-t border-gray-200 dark:border-zinc-700"></div>
                <span>or</span>
                <div class="flex-1 border-t border-gray-200 dark:border-zinc-700"></div>
            </div>

            <form wire:submit="importFile" class="space-y-6">
                <flux:field>
                    <flux:label>Recipe file</flux:label>
                    <input
                        type="file"
                        id="recipeFile"
                        accept=".json,application/json"
                        wire:model="recipeFile"
                        class="block w-full text-sm text-gray-900 dark:text-zinc-100 border border-gray-300 dark:border-zinc-700 rounded-lg bg-white dark:bg-zinc-800 file:mr-4 file:py-2 file:px-4 file:border-0 file:bg-gray-100 dark:file:bg-zinc-700 file:text-gray-700 dark:file:text-zinc-200"
                    />
                    <flux:description>
                        Upload a schema.org Recipe as a .json file (max 1MB).
                    </flux:description>
                    <flux:error name="recipeFile" />
                </flux:field>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                    <flux:button
                        type="submit"
                        variant="primary"
                        wire:loading.attr="disabled"
                        wire:target="recipeFile,importFile"
                    >
                        <span wire:loading.remove wire:target="recipeFile,importFile">Upload File</span>
                        <span wire:loading wire:target="recipeFile,importFile">Reading file...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
