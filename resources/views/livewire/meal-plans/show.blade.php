<div>
    {{-- Header --}}
    <div class="mb-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-4">
            <div>
                <flux:heading size="xl" level="1">{{ $mealPlan->name }}</flux:heading>
                <flux:text class="text-gray-600 dark:text-zinc-400">
                    {{ $mealPlan->start_date->format('M d, Y') }} - {{ $mealPlan->end_date->format('M d, Y') }}
                    ({{ $mealPlan->duration_days }} {{ Str::plural('day', $mealPlan->duration_days) }})
                </flux:text>
            </div>
            <div class="flex flex-wrap items-center gap-2 lg:gap-3">
                @can('update', $mealPlan)
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
                @endcan
                @can('share', $mealPlan)
                    <flux:button
                        wire:click="openShareModal"
                        variant="ghost"
                        icon="share"
                        class="flex-1 sm:flex-none"
                    >
                        Share
                    </flux:button>
                @endcan
                @can('update', $mealPlan)
                    <flux:button
                        href="{{ route('meal-plans.edit', $mealPlan) }}"
                        variant="ghost"
                        icon="pencil"
                        class="flex-1 sm:flex-none"
                    >
                        Edit
                    </flux:button>
                @endcan
                @can('delete', $mealPlan)
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
                @endcan
            </div>
        </div>

        @if($mealPlan->description)
            <div class="bg-gray-50 dark:bg-zinc-800 rounded-lg p-4">
                <flux:text>{{ $mealPlan->description }}</flux:text>
            </div>
        @endif
    </div>

    {{-- Meal Plan Calendar --}}
    <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-6">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b-2 border-gray-200 dark:border-zinc-700">
                        <th class="p-3 text-left font-semibold text-gray-700 dark:text-zinc-300 w-32">Date</th>
                        @foreach($mealTypes as $mealType)
                            <th class="p-3 text-center font-semibold text-gray-700 dark:text-zinc-300">
                                {{ ucfirst($mealType->value) }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($dates as $date)
                        <tr class="border-b border-gray-100 dark:border-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-800">
                            <td class="p-3 font-medium text-gray-700 dark:text-zinc-300">
                                <div>{{ $date->format('D') }}</div>
                                <div class="text-sm text-gray-500 dark:text-zinc-400">{{ $date->format('M d') }}</div>
                            </td>
                            @foreach($mealTypes as $mealType)
                                @php
                                    $key = $date->format('Y-m-d') . '_' . $mealType->value;
                                    $assignmentCollection = $assignments->get($key) ?? collect();
                                    $noteCollection = $notes->get($key) ?? collect();
                                    $hasItems = $assignmentCollection->isNotEmpty() || $noteCollection->isNotEmpty();
                                @endphp
                                <td
                                    class="p-2 text-center align-top border-l border-gray-100 dark:border-zinc-700"
                                    data-date="{{ $date->format('Y-m-d') }}"
                                    data-meal-type="{{ $mealType->value }}"
                                >
                                    <div class="flex flex-col gap-2">
                                        {{-- Recipe Assignments --}}
                                        @foreach($assignmentCollection as $assignment)
                                            <div class="bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-lg p-3 text-left relative group transition-colors cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700"
                                                 role="button"
                                                 tabindex="0"
                                                 wire:click="openRecipeDrawer({{ $assignment }})"
                                                 @keydown.enter="$wire.openRecipeDrawer({{ $assignment }})"
                                                 @keydown.space.prevent="$wire.openRecipeDrawer({{ $assignment }})">
                                                <div class="font-medium text-sm text-gray-900 dark:text-white mb-1">
                                                    {{ $assignment->recipe->name }}
                                                </div>
                                                @if($assignment->serving_multiplier != 1.00)
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-zinc-700 text-gray-800 dark:text-zinc-200 border border-gray-300 dark:border-zinc-600">
                                                            {{ $assignment->recipe->servings * $assignment->serving_multiplier }} servings
                                                        </span>
                                                        <span class="text-xs text-gray-600 dark:text-zinc-400">
                                                            ({{ $assignment->serving_multiplier }}x)
                                                        </span>
                                                    </div>
                                                @else
                                                    <div class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                                                        {{ $assignment->recipe->servings }} servings
                                                    </div>
                                                @endif
                                                @if($assignment->notes)
                                                    <div class="text-xs text-gray-600 dark:text-zinc-400 mt-1">
                                                        {{ Str::limit($assignment->notes, 50) }}
                                                    </div>
                                                @endif
                                                @can('update', $mealPlan)
                                                    <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <flux:button
                                                            wire:click.stop="removeAssignment({{ $assignment->id }})"
                                                            wire:confirm="Remove this recipe from the meal slot?"
                                                            variant="ghost"
                                                            size="xs"
                                                            icon="x-mark"
                                                            class="text-red-600"
                                                        />
                                                    </div>
                                                @endcan
                                            </div>
                                        @endforeach

                                        {{-- Notes (distinct amber styling) --}}
                                        @foreach($noteCollection as $note)
                                            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-3 text-left relative group transition-colors cursor-pointer hover:bg-amber-100 dark:hover:bg-amber-900/40"
                                                 role="button"
                                                 tabindex="0"
                                                 wire:click="openNoteDrawer({{ $note }})"
                                                 @keydown.enter="$wire.openNoteDrawer({{ $note }})"
                                                 @keydown.space.prevent="$wire.openNoteDrawer({{ $note }})">
                                                <div class="flex items-start gap-2">
                                                    <svg class="w-4 h-4 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="font-medium text-sm text-amber-900 dark:text-amber-100 truncate">
                                                            {{ Str::limit($note->title, 40) }}
                                                        </div>
                                                        @if($note->details)
                                                            <div class="text-xs text-amber-700 dark:text-amber-300 mt-1 truncate">
                                                                {{ Str::limit($note->details, 50) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                                @can('update', $mealPlan)
                                                    <div class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                        <flux:button
                                                            wire:click.stop="deleteNote({{ $note->id }})"
                                                            wire:confirm="Delete this note?"
                                                            variant="ghost"
                                                            size="xs"
                                                            icon="x-mark"
                                                            class="text-red-600"
                                                        />
                                                    </div>
                                                @endcan
                                            </div>
                                        @endforeach

                                        {{-- Empty state or Add Another button --}}
                                        @can('update', $mealPlan)
                                            @if(!$hasItems)
                                                <flux:dropdown>
                                                    <flux:button
                                                        variant="ghost"
                                                        icon="plus"
                                                        class="w-full h-full min-h-[60px] border-2 border-dashed border-gray-300 dark:border-zinc-600 rounded-lg hover:border-gray-400 dark:hover:border-zinc-400 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors"
                                                    />
                                                    <flux:menu>
                                                        <flux:menu.item wire:click="openRecipeSelector('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')" icon="book-open">
                                                            Add Recipe
                                                        </flux:menu.item>
                                                        <flux:menu.item wire:click="openNoteForm('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')" icon="document-text">
                                                            Add Note
                                                        </flux:menu.item>
                                                    </flux:menu>
                                                </flux:dropdown>
                                            @else
                                                <flux:dropdown>
                                                    <flux:button
                                                        variant="ghost"
                                                        icon="plus"
                                                        class="w-full border border-dashed border-gray-300 dark:border-zinc-600 rounded-lg hover:border-gray-400 dark:hover:border-zinc-400 hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors text-xs py-1"
                                                    >
                                                        Add Another
                                                    </flux:button>
                                                    <flux:menu>
                                                        <flux:menu.item wire:click="openRecipeSelector('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')" icon="book-open">
                                                            Add Recipe
                                                        </flux:menu.item>
                                                        <flux:menu.item wire:click="openNoteForm('{{ $date->format('Y-m-d') }}', '{{ $mealType->value }}')" icon="document-text">
                                                            Add Note
                                                        </flux:menu.item>
                                                    </flux:menu>
                                                </flux:dropdown>
                                            @endif
                                        @endcan
                                    </div>
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
            <div class="mb-4 p-4 bg-gray-50 dark:bg-zinc-800 rounded-lg border border-gray-200 dark:border-zinc-600">
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
                        <div class="text-xs text-gray-600 dark:text-zinc-400 mb-1">Multiplier</div>
                        <div class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ number_format($servingMultiplier, 2) }}x
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-xs text-gray-600 dark:text-zinc-400">
                    Adjust the serving multiplier to scale ingredient quantities (0.25x to 10x).
                </div>
            </div>

            <div class="max-h-96 overflow-y-auto space-y-2">
                @forelse($this->recipes as $recipe)
                    <button
                        wire:click="assignRecipe({{ $recipe }})"
                        class="w-full p-4 border border-gray-200 dark:border-zinc-700 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-800 hover:border-gray-300 dark:hover:border-zinc-500 transition text-left relative"
                        data-recipe-card
                    >
                        <div wire:loading wire:target="assignRecipe" class="absolute inset-0 bg-white/80 dark:bg-zinc-900/80 flex items-center justify-center rounded-lg">
                            <span class="text-sm text-gray-600 dark:text-zinc-400">Assigning...</span>
                        </div>
                        <div class="font-semibold text-gray-900 dark:text-white">{{ $recipe->name }}</div>
                        @if($recipe->description)
                            <div class="text-sm text-gray-600 dark:text-zinc-400 mt-1">{{ Str::limit($recipe->description, 100) }}</div>
                        @endif
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-500 dark:text-zinc-400">
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
                    <div class="text-center py-8 text-gray-500 dark:text-zinc-400">
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

    {{-- Note Form Modal --}}
    @if($showNoteForm)
        <flux:modal wire:model="showNoteForm" class="max-w-lg">
            <flux:heading size="lg" class="mb-4">
                {{ $editingNoteId ? 'Edit Note' : 'Add Note' }} for {{ \Carbon\Carbon::parse($selectedDate)->format('M d') }} - {{ ucfirst($selectedMealType) }}
            </flux:heading>

            <form wire:submit="saveNote" class="space-y-4">
                <flux:field>
                    <flux:label>Title</flux:label>
                    <flux:input
                        wire:model="noteTitle"
                        type="text"
                        placeholder="e.g., Eating out at Mom's house"
                        autofocus
                    />
                    <flux:error name="noteTitle" />
                </flux:field>

                <flux:field>
                    <flux:label>Details (optional)</flux:label>
                    <flux:textarea
                        wire:model="noteDetails"
                        rows="4"
                        placeholder="Add any additional details..."
                    />
                    <flux:error name="noteDetails" />
                </flux:field>

                <div class="flex justify-end gap-3 pt-4">
                    <flux:button wire:click="closeNoteForm" variant="ghost" type="button">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        <span wire:loading.remove wire:target="saveNote">{{ $editingNoteId ? 'Update Note' : 'Add Note' }}</span>
                        <span wire:loading wire:target="saveNote">Saving...</span>
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endif

    {{-- Recipe Drawer --}}
    @if($showRecipeDrawer && $this->selectedAssignment)
        <div
            x-data="{ show: @entangle('showRecipeDrawer') }"
            @keydown.escape.window="$wire.closeRecipeDrawer()"
            class="relative z-50"
        >
            {{-- Backdrop --}}
            <div
                x-show="show"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="$wire.closeRecipeDrawer()"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-zinc-900 dark:bg-opacity-75"
            ></div>

            {{-- Drawer Panel --}}
            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <div
                            x-show="show"
                            x-transition:enter="transform transition ease-in-out duration-300"
                            x-transition:enter-start="translate-x-full"
                            x-transition:enter-end="translate-x-0"
                            x-transition:leave="transform transition ease-in-out duration-200"
                            x-transition:leave-start="translate-x-0"
                            x-transition:leave-end="translate-x-full"
                            x-trap="show"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="drawer-title"
                            class="pointer-events-auto w-screen max-w-full sm:max-w-md lg:max-w-lg"
                        >
                            <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-zinc-900 shadow-xl">
                                {{-- Sticky Header --}}
                                <div class="sticky top-0 z-10 bg-white dark:bg-zinc-900 border-b border-gray-200 dark:border-zinc-700 px-4 py-6 sm:px-6">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h2 id="drawer-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                                                {{ $this->selectedAssignment->recipe->name }}
                                            </h2>
                                            <div class="mt-1 text-sm text-gray-500 dark:text-zinc-400">
                                                {{ \Carbon\Carbon::parse($this->selectedAssignment->date)->format('l, F j') }}@if($this->selectedAssignment->meal_type) - {{ ucfirst($this->selectedAssignment->meal_type->value) }}@endif
                                            </div>
                                        </div>
                                        <div class="ml-3 flex h-7 items-center">
                                            <flux:button
                                                wire:click="closeRecipeDrawer"
                                                variant="ghost"
                                                size="sm"
                                                icon="x-mark"
                                                class="text-gray-400 hover:text-gray-500 dark:text-zinc-500 dark:hover:text-gray-400"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {{-- Scrollable Content --}}
                                <div class="relative flex-1 px-4 py-6 sm:px-6">
                                    <div class="space-y-6">
                                        {{-- Servings Information --}}
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Servings</h3>
                                            @if($this->selectedAssignment->serving_multiplier != 1.00)
                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-800/50 text-blue-800 dark:text-blue-200 border border-blue-300 dark:border-blue-700">
                                                        {{ $this->selectedAssignment->recipe->servings * $this->selectedAssignment->serving_multiplier }} servings
                                                    </span>
                                                    <span class="text-sm text-blue-600 dark:text-blue-400">
                                                        ({{ $this->selectedAssignment->serving_multiplier }}x multiplier)
                                                    </span>
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-700 dark:text-zinc-300">{{ $this->selectedAssignment->recipe->servings }} servings</p>
                                            @endif
                                        </div>

                                        {{-- Time Information --}}
                                        @if($this->selectedAssignment->recipe->prep_time || $this->selectedAssignment->recipe->cook_time)
                                            <div>
                                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Time</h3>
                                                <div class="grid grid-cols-2 gap-4">
                                                    @if($this->selectedAssignment->recipe->prep_time)
                                                        <div class="text-sm">
                                                            <span class="text-gray-500 dark:text-zinc-400">Prep:</span>
                                                            <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $this->selectedAssignment->recipe->prep_time }} min</span>
                                                        </div>
                                                    @endif
                                                    @if($this->selectedAssignment->recipe->cook_time)
                                                        <div class="text-sm">
                                                            <span class="text-gray-500 dark:text-zinc-400">Cook:</span>
                                                            <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $this->selectedAssignment->recipe->cook_time }} min</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Scaled Ingredients --}}
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Ingredients</h3>
                                            @if(count($this->scaledIngredients) > 0)
                                                <ul class="space-y-2">
                                                    @foreach($this->scaledIngredients as $ingredient)
                                                        <li class="text-sm text-gray-700 dark:text-zinc-300 flex items-start">
                                                            <span class="mr-2 text-gray-400 dark:text-zinc-500">•</span>
                                                            <span>
                                                                <span class="font-medium">{{ $ingredient['quantity'] }} {{ $ingredient['unit'] }}</span>
                                                                <span class="ml-1">{{ $ingredient['name'] }}</span>
                                                            </span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="text-sm text-gray-500 dark:text-zinc-400 italic">No ingredients listed</p>
                                            @endif
                                        </div>

                                        {{-- Instructions --}}
                                        @if($this->selectedAssignment->recipe->instructions)
                                            <div>
                                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Instructions</h3>
                                                <div class="text-sm text-gray-700 dark:text-zinc-300 whitespace-pre-wrap">
                                                    {{ $this->selectedAssignment->recipe->instructions }}
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Notes --}}
                                        @if($this->selectedAssignment->notes)
                                            <div>
                                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Notes</h3>
                                                <div class="text-sm text-gray-700 dark:text-zinc-300 whitespace-pre-wrap bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-3">
                                                    {{ $this->selectedAssignment->notes }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Sticky Footer --}}
                                <div class="sticky bottom-0 z-10 bg-white dark:bg-zinc-900 border-t border-gray-200 dark:border-zinc-700 px-4 py-4 sm:px-6">
                                    <div class="flex gap-3">
                                        <flux:button
                                            href="{{ route('recipes.show', $this->selectedAssignment->recipe) }}"
                                            variant="primary"
                                            icon="arrow-top-right-on-square"
                                            class="flex-1"
                                        >
                                            View Full Recipe
                                        </flux:button>
                                        <flux:button
                                            wire:click="closeRecipeDrawer"
                                            variant="ghost"
                                        >
                                            Close
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Note Drawer --}}
    @if($showNoteDrawer && $this->selectedNote)
        <div
            x-data="{ show: @entangle('showNoteDrawer') }"
            @keydown.escape.window="$wire.closeNoteDrawer()"
            class="relative z-50"
        >
            {{-- Backdrop --}}
            <div
                x-show="show"
                x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="$wire.closeNoteDrawer()"
                class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-zinc-900 dark:bg-opacity-75"
            ></div>

            {{-- Drawer Panel --}}
            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
                        <div
                            x-show="show"
                            x-transition:enter="transform transition ease-in-out duration-300"
                            x-transition:enter-start="translate-x-full"
                            x-transition:enter-end="translate-x-0"
                            x-transition:leave="transform transition ease-in-out duration-200"
                            x-transition:leave-start="translate-x-0"
                            x-transition:leave-end="translate-x-full"
                            x-trap="show"
                            role="dialog"
                            aria-modal="true"
                            aria-labelledby="note-drawer-title"
                            class="pointer-events-auto w-screen max-w-full sm:max-w-md"
                        >
                            <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-zinc-900 shadow-xl">
                                {{-- Sticky Header --}}
                                <div class="sticky top-0 z-10 bg-amber-50 dark:bg-amber-900/30 border-b border-amber-200 dark:border-amber-700 px-4 py-6 sm:px-6">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <h2 id="note-drawer-title" class="text-lg font-semibold text-amber-900 dark:text-amber-100">
                                                    {{ $this->selectedNote->title }}
                                                </h2>
                                            </div>
                                            <div class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                                {{ \Carbon\Carbon::parse($this->selectedNote->date)->format('l, F j') }} - {{ ucfirst($this->selectedNote->meal_type->value) }}
                                            </div>
                                        </div>
                                        <div class="ml-3 flex h-7 items-center">
                                            <flux:button
                                                wire:click="closeNoteDrawer"
                                                variant="ghost"
                                                size="sm"
                                                icon="x-mark"
                                                class="text-amber-600 hover:text-amber-700 dark:text-amber-400 dark:hover:text-amber-300"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {{-- Scrollable Content --}}
                                <div class="relative flex-1 px-4 py-6 sm:px-6">
                                    <div class="space-y-6">
                                        {{-- Details --}}
                                        @if($this->selectedNote->details)
                                            <div>
                                                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Details</h3>
                                                <div class="text-sm text-gray-700 dark:text-zinc-300 whitespace-pre-wrap bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg p-4">
                                                    {{ $this->selectedNote->details }}
                                                </div>
                                            </div>
                                        @else
                                            <div class="text-sm text-gray-500 dark:text-zinc-400 italic">
                                                No additional details for this note.
                                            </div>
                                        @endif

                                        {{-- Metadata --}}
                                        <div class="text-xs text-gray-500 dark:text-zinc-400">
                                            <p>Created: {{ $this->selectedNote->created_at->format('M d, Y g:i A') }}</p>
                                            @if($this->selectedNote->updated_at->ne($this->selectedNote->created_at))
                                                <p>Updated: {{ $this->selectedNote->updated_at->format('M d, Y g:i A') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Sticky Footer --}}
                                <div class="sticky bottom-0 z-10 bg-white dark:bg-zinc-900 border-t border-gray-200 dark:border-zinc-700 px-4 py-4 sm:px-6">
                                    <div class="flex gap-3">
                                        <flux:button
                                            wire:click="editNote({{ $this->selectedNote }})"
                                            variant="primary"
                                            icon="pencil"
                                            class="flex-1"
                                        >
                                            Edit Note
                                        </flux:button>
                                        <flux:button
                                            wire:click="deleteNote({{ $this->selectedNote }})"
                                            wire:confirm="Are you sure you want to delete this note?"
                                            variant="ghost"
                                            icon="trash"
                                            class="text-red-600 hover:text-red-700"
                                        >
                                            Delete
                                        </flux:button>
                                        <flux:button
                                            wire:click="closeNoteDrawer"
                                            variant="ghost"
                                        >
                                            Close
                                        </flux:button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Share Modal --}}
    <x-share-modal :model="$mealPlan" title="Share &quot;{{ $mealPlan->name }}&quot;" />
</div>
