{{-- Recipe Selector Modal Partial --}}
{{-- This partial is used within the MealPlans\Show component --}}

<flux:modal wire:model="showRecipeSelector" class="max-w-4xl">
    <flux:heading size="lg" class="mb-4">
        Select Recipe
        @if($selectedDate && $selectedMealType)
            for {{ \Carbon\Carbon::parse($selectedDate)->format('M d') }} - {{ ucfirst($selectedMealType) }}
        @endif
    </flux:heading>

    {{-- Search Input --}}
    <div class="mb-4">
        <flux:input
            wire:model.live.debounce.300ms="recipeSearch"
            type="search"
            placeholder="Search recipes by name or description..."
            icon="search"
        />
    </div>

    {{-- Recipe List --}}
    <div class="max-h-96 overflow-y-auto space-y-2">
        @forelse($this->recipes as $recipe)
            <button
                wire:click="assignRecipe({{ $recipe->id }})"
                class="w-full p-4 border border-gray-200 rounded-lg hover:bg-blue-50 hover:border-blue-300 transition text-left"
                data-recipe-card
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="font-semibold text-gray-900">{{ $recipe->name }}</div>
                        @if($recipe->description)
                            <div class="text-sm text-gray-600 mt-1">
                                {{ Str::limit($recipe->description, 100) }}
                            </div>
                        @endif
                    </div>
                    @if($recipe->user_id)
                        <flux:badge size="sm" variant="primary">My Recipe</flux:badge>
                    @endif
                </div>

                {{-- Recipe Metadata --}}
                <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
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
                        <flux:badge size="sm" variant="ghost">
                            {{ ucfirst($recipe->meal_type->value) }}
                        </flux:badge>
                    @endif

                    @if($recipe->difficulty)
                        <flux:badge size="sm" variant="ghost">
                            {{ ucfirst($recipe->difficulty->value) }}
                        </flux:badge>
                    @endif
                </div>

                {{-- Dietary Tags --}}
                @if($recipe->dietary_tags && count($recipe->dietary_tags) > 0)
                    <div class="flex flex-wrap gap-1 mt-2">
                        @foreach($recipe->dietary_tags as $tag)
                            <span class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded">
                                {{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </button>
        @empty
            <div class="text-center py-12 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="mt-2 text-sm">No recipes found</p>
                @if($recipeSearch)
                    <p class="text-xs mt-1">Try a different search term</p>
                @else
                    <p class="text-xs mt-1">Start typing to search for recipes</p>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Modal Actions --}}
    <div class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-500">
            @if($this->recipes->isNotEmpty())
                Showing {{ $this->recipes->count() }} {{ Str::plural('recipe', $this->recipes->count()) }}
            @endif
        </div>
        <flux:button wire:click="closeRecipeSelector" variant="ghost">
            Cancel
        </flux:button>
    </div>
</flux:modal>
