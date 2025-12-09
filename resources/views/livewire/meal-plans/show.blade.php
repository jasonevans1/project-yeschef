<div>
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
            <div>
                <flux:heading size="xl" level="1">{{ $mealPlan->name }}</flux:heading>
                <flux:text class="text-gray-600 dark:text-gray-400">
                    {{ $mealPlan->start_date->format('M d, Y') }} - {{ $mealPlan->end_date->format('M d, Y') }}
                    ({{ $mealPlan->duration_days }} {{ Str::plural('day', $mealPlan->duration_days) }})
                </flux:text>
            </div>
            <div class="flex flex-wrap items-center gap-2 lg:gap-3">
                @if($mealPlan->mealAssignments->isNotEmpty() && Route::has('grocery-lists.generate'))
                    <flux:button
                        href="{{ route('grocery-lists.generate', $mealPlan) }}"
                        variant="primary"
                        icon="shopping-cart"
                        class="flex-1 sm:flex-none"
                    >
                        <span class="hidden sm:inline">Generate Grocery List</span>
                        <span class="sm:hidden">Grocery List</span>
                    </flux:button>
                @endif
                <flux:button
                    href="{{ route('meal-plans.edit', $mealPlan) }}"
                    variant="ghost"
                    icon="pencil"
                    class="flex-1 sm:flex-none"
                >
                    Edit
                </flux:button>
                <flux:button
                    wire:click="delete"
                    wire:confirm="Are you sure you want to delete this meal plan? This action cannot be undone."
                    variant="ghost"
                    icon="trash"
                    class="text-red-600 hover:text-red-700 flex-1 sm:flex-none"
                >
                    <span wire:loading.remove wire:target="delete">Delete</span>
                    <span wire:loading wire:target="delete">Deleting...</span>
                </flux:button>
            </div>
        </div>

        @if($mealPlan->description)
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <flux:text>{{ $mealPlan->description }}</flux:text>
            </div>
        @endif
    </div>

    {{-- Meal Plan Calendar --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                        <th class="p-3 text-left font-semibold text-gray-700 dark:text-gray-300 w-32">Date</th>
                        @foreach($mealTypes as $mealType)
                            <th class="p-3 text-center font-semibold text-gray-700 dark:text-gray-300">
                                {{ ucfirst($mealType->value) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($dates as $date)
                        <tr class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="p-3 font-medium text-gray-700 dark:text-gray-300">
                                <div>{{ $date->format('D') }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $date->format('M d') }}</div>
                            </td>
                            @foreach($mealTypes as $mealType)
                                @php
                                    $key = $date->format('Y-m-d') . '_' . $mealType->value;
                                    $assignment = $assignments->get($key)?->first();
                                @endphp
                                <td
                                    class="p-2 text-center align-top border-l border-gray-100 dark:border-gray-700"
                                    data-date="{{ $date->format('Y-m-d') }}"
                                    data-meal-type="{{ $mealType->value }}"
                                >
                                    @if($assignment)
                                        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 text-left relative group cursor-pointer hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors"
                                             wire:click="openRecipeSelector('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')"
                                             role="button"
                                             tabindex="0"
                                             title="Click to change recipe">
                                            <div class="font-medium text-sm text-blue-900 dark:text-blue-100 mb-1">
                                                {{ $assignment->recipe->name }}
                                            </div>
                                            @if($assignment->serving_multiplier != 1.00)
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-800/50 text-blue-800 dark:text-blue-200 border border-blue-300 dark:border-blue-700">
                                                        {{ $assignment->recipe->servings * $assignment->serving_multiplier }} servings
                                                    </span>
                                                    <span class="text-xs text-blue-600 dark:text-blue-400">
                                                        ({{ $assignment->serving_multiplier }}x)
                                                    </span>
                                                </div>
                                            @else
                                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $assignment->recipe->servings }} servings
                                                </div>
                                            @endif
                                            @if($assignment->notes)
                                                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                                    {{ Str::limit($assignment->notes, 50) }}
                                                </div>
                                            @endif
                                            <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <flux:button
                                                    wire:click.stop="removeAssignment({{ $assignment->id }})"
                                                    variant="ghost"
                                                    size="xs"
                                                    icon="x-mark"
                                                    class="text-red-600"
                                                />
                                            </div>
                                        </div>
                                    @else
                                        <flux:button
                                            wire:click="openRecipeSelector('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')"
                                            variant="ghost"
                                            icon="plus"
                                            class="w-full h-full min-h-[60px] border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                        />
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recipe Selector Modal --}}
    @if($showRecipeSelector)
        <flux:modal wire:model="showRecipeSelector" class="max-w-4xl">
            <flux:heading size="lg" class="mb-4">
                Select Recipe for {{ \Carbon\Carbon::parse($selectedDate)->format('M d') }} - {{ ucfirst($selectedMealType) }}
            </flux:heading>

            <div class="mb-4">
                <flux:input
                    wire:model.live.debounce.300ms="recipeSearch"
                    type="search"
                    placeholder="Search recipes..."
                />
            </div>

            {{-- Serving Size Adjustment --}}
            <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <flux:field>
                            <flux:label>Servings</flux:label>
                            <flux:input
                                wire:model.live="servingMultiplier"
                                type="number"
                                step="0.25"
                                min="0.25"
                                max="10"
                                name="servings"
                            />
                            <flux:error name="servingMultiplier" />
                        </flux:field>
                    </div>
                    <div class="text-center px-4">
                        <div class="text-xs text-gray-600 dark:text-gray-400 mb-1">Multiplier</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ number_format($servingMultiplier, 2) }}x
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                    Adjust the serving multiplier to scale ingredient quantities (0.25x to 10x).
                </div>
            </div>

            <div class="max-h-96 overflow-y-auto space-y-2">
                @forelse($this->recipes as $recipe)
                    <button
                        wire:click="assignRecipe({{ $recipe->id }})"
                        class="w-full p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-300 dark:hover:border-blue-600 transition text-left relative"
                        data-recipe-card
                    >
                        <div wire:loading wire:target="assignRecipe" class="absolute inset-0 bg-white/80 dark:bg-gray-800/80 flex items-center justify-center rounded-lg">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Assigning...</span>
                        </div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $recipe->name }}</div>
                        @if($recipe->description)
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ Str::limit($recipe->description, 100) }}</div>
                        @endif
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                            @if($recipe->prep_time || $recipe->cook_time)
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ ($recipe->prep_time ?? 0) + ($recipe->cook_time ?? 0) }} min
                                </span>
                            @endif
                            @if($recipe->servings)
                                <span>{{ $recipe->servings }} servings</span>
                            @endif
                            @if($recipe->meal_type)
                                <flux:badge size="sm" variant="ghost">{{ ucfirst($recipe->meal_type->value) }}</flux:badge>
                            @endif
                        </div>
                    </button>
                @empty
                    <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                        No recipes found. Try a different search term.
                    </div>
                @endforelse
            </div>

            <div class="mt-4 flex justify-end">
                <flux:button wire:click="closeRecipeSelector" variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </flux:modal>
    @endif
</div>
