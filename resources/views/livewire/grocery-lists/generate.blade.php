<div>
    {{-- Header --}}
    <div class="mb-6">
        <flux:heading size="xl" class="mb-2">Generate Grocery List</flux:heading>
        <flux:text class="text-gray-600 dark:text-gray-400">
            Create a grocery list from your meal plan
        </flux:text>
    </div>

    {{-- Confirmation Dialog --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-2xl">
        @if($existingList)
            {{-- Existing list warning --}}
            <div class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div>
                        <flux:heading size="md" class="text-yellow-800 dark:text-yellow-100 mb-1">List Already Exists</flux:heading>
                        <flux:text class="text-yellow-700 dark:text-yellow-300 text-sm">
                            A grocery list already exists for this meal plan. Regenerating will update the list while preserving your manual changes and edits.
                        </flux:text>
                    </div>
                </div>
            </div>
        @endif

        {{-- Meal Plan Summary --}}
        <div class="mb-6">
            <flux:heading size="lg" class="mb-4">
                {{ $existingList ? 'Regenerate' : 'Generate' }} grocery list for "{{ $mealPlan->name }}"?
            </flux:heading>

            <div class="space-y-3">
                {{-- Meal Plan Details --}}
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <div>
                        <flux:text class="font-medium text-gray-900 dark:text-white">
                            {{ $mealPlan->start_date->format('M d, Y') }} - {{ $mealPlan->end_date->format('M d, Y') }}
                        </flux:text>
                        <flux:text class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $mealPlan->duration_days }} {{ Str::plural('day', $mealPlan->duration_days) }}
                        </flux:text>
                    </div>
                </div>

                {{-- Recipe Count --}}
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <div>
                        <flux:text class="font-medium text-gray-900 dark:text-white">
                            {{ $recipeCount }} {{ Str::plural('recipe', $recipeCount) }}
                        </flux:text>
                        <flux:text class="text-sm text-gray-600 dark:text-gray-400">
                            In your meal plan
                        </flux:text>
                    </div>
                </div>

                {{-- Estimated Items --}}
                @if($recipeCount > 0)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <div>
                            <flux:text class="font-medium text-gray-900 dark:text-white">
                                ~{{ $estimatedItemCount }} {{ Str::plural('item', $estimatedItemCount) }}
                            </flux:text>
                            <flux:text class="text-sm text-gray-600 dark:text-gray-400">
                                Estimated (after aggregation)
                            </flux:text>
                        </div>
                    </div>
                @else
                    {{-- Empty meal plan warning --}}
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <flux:text class="text-amber-800 dark:text-amber-100 font-medium">No recipes assigned</flux:text>
                                <flux:text class="text-amber-700 dark:text-amber-300 text-sm">
                                    Your meal plan doesn't have any recipes yet. Add some recipes to your meal plan before generating a grocery list.
                                </flux:text>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
            <flux:button
                wire:click="cancel"
                variant="ghost"
            >
                Cancel
            </flux:button>

            @if($recipeCount > 0)
                <flux:button
                    wire:click="generate"
                    variant="primary"
                    icon="check"
                >
                    {{ $existingList ? 'Regenerate List' : 'Generate List' }}
                </flux:button>
            @else
                <flux:button
                    href="{{ route('meal-plans.show', $mealPlan) }}"
                    variant="primary"
                >
                    Add Recipes to Meal Plan
                </flux:button>
            @endif
        </div>

        {{-- Loading State --}}
        <div wire:loading wire:target="generate" class="mt-4 flex items-center justify-center gap-2 text-blue-600 dark:text-blue-400">
            <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <flux:text>{{ $existingList ? 'Regenerating' : 'Generating' }} grocery list...</flux:text>
        </div>
    </div>
</div>
