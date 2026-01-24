<div>
    <div class="max-w-4xl mx-auto">
        <flux:heading size="xl" level="1" class="mb-6">Preview Recipe Import</flux:heading>

        @if($recipeData)
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6 space-y-6">
            {{-- Recipe Name --}}
            <div>
                <h2 class="text-2xl font-bold dark:text-white">{{ $recipeData['name'] }}</h2>
            </div>

            {{-- Image --}}
            @if($recipeData['image_url'] ?? null)
            <div>
                <img src="{{ $recipeData['image_url'] }}" alt="{{ $recipeData['name'] }}" class="w-full h-64 object-cover rounded-lg">
            </div>
            @endif

            {{-- Description --}}
            @if($recipeData['description'] ?? null)
            <div>
                <h3 class="font-semibold dark:text-white mb-2">Description</h3>
                <p class="text-gray-700 dark:text-zinc-300">{{ $recipeData['description'] }}</p>
            </div>
            @endif

            {{-- Recipe Details --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @if($recipeData['prep_time'] ?? null)
                <div>
                    <span class="text-sm text-gray-500 dark:text-zinc-400">Prep Time</span>
                    <p class="font-semibold dark:text-white">{{ $recipeData['prep_time'] }} min</p>
                </div>
                @endif
                @if($recipeData['cook_time'] ?? null)
                <div>
                    <span class="text-sm text-gray-500 dark:text-zinc-400">Cook Time</span>
                    <p class="font-semibold dark:text-white">{{ $recipeData['cook_time'] }} min</p>
                </div>
                @endif
                <div>
                    <span class="text-sm text-gray-500 dark:text-zinc-400">Servings</span>
                    <p class="font-semibold dark:text-white">{{ $recipeData['servings'] ?? 4 }}</p>
                </div>
                @if($recipeData['cuisine'] ?? null)
                <div>
                    <span class="text-sm text-gray-500 dark:text-zinc-400">Cuisine</span>
                    <p class="font-semibold dark:text-white">{{ $recipeData['cuisine'] }}</p>
                </div>
                @endif
            </div>

            {{-- Ingredients --}}
            @if(!empty($recipeData['recipeIngredient']))
            <div>
                <h3 class="font-semibold dark:text-white mb-2">Ingredients</h3>
                <ul class="list-disc list-inside space-y-1">
                    @foreach($recipeData['recipeIngredient'] as $ingredient)
                    <li class="text-gray-700 dark:text-zinc-300">{{ $ingredient }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            {{-- Instructions --}}
            <div>
                <h3 class="font-semibold dark:text-white mb-2">Instructions</h3>
                <div class="text-gray-700 dark:text-zinc-300 whitespace-pre-line">{{ $recipeData['instructions'] }}</div>
            </div>

            {{-- Source URL --}}
            @if($recipeData['source_url'] ?? null)
            <div class="text-sm text-gray-500 dark:text-zinc-400">
                <strong>Source:</strong>
                <a href="{{ $recipeData['source_url'] }}" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline">
                    {{ $recipeData['source_url'] }}
                </a>
            </div>
            @endif

            {{-- Error Display --}}
            <flux:error name="import" />

            {{-- Action Buttons --}}
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-zinc-700">
                <flux:button
                    wire:click="cancel"
                    variant="ghost"
                >
                    Cancel
                </flux:button>
                <flux:button
                    wire:click="confirmImport"
                    variant="primary"
                >
                    <span wire:loading.remove>Confirm & Save Recipe</span>
                    <span wire:loading>Saving...</span>
                </flux:button>
            </div>
        </div>
        @endif
    </div>
</div>
