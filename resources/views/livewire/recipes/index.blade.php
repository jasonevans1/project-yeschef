<div>
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <flux:heading size="xl">Browse Recipes</flux:heading>
        <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
            <flux:button href="{{ route('recipes.import') }}" variant="outline" icon="arrow-down-tray" class="w-full sm:w-auto">
                Import Recipe
            </flux:button>
            <flux:button href="{{ route('recipes.create') }}" variant="primary" icon="plus" class="w-full sm:w-auto">
                Create New Recipe
            </flux:button>
        </div>
    </div>

    @php
        $hasFilters = !empty($search) || !empty($mealTypes) || !empty($dietaryTags) || $myRecipesOnly;
        $totalRecipes = $recipes->total();
    @endphp

    @if($totalRecipes === 0 && !$hasFilters)
        {{-- Empty State: No recipes exist at all --}}
        <div class="bg-white rounded-lg shadow p-6">
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
                <flux:heading size="lg" class="mt-4">No recipes yet</flux:heading>
                <flux:text class="mt-2">Get started by creating your first recipe or browse our system recipes</flux:text>
                <flux:button href="{{ route('recipes.create') }}" variant="primary" class="mt-4">
                    Create Your First Recipe
                </flux:button>
            </div>
        </div>
    @else
        <div class="mb-6 space-y-4">
            {{-- Search and Sort --}}
            <div class="flex flex-col sm:flex-row gap-3">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Search recipes by name or description..."
                    class="flex-1"
                />

                <flux:field class="w-full sm:w-48">
                    <flux:select wire:model.live="sortBy" id="sortBy" name="sortBy">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                    </flux:select>
                </flux:field>
            </div>

            {{-- Filters --}}
            <div class="flex flex-wrap gap-4">
                {{-- Meal Type Filter --}}
                <div class="flex-1 min-w-[250px]">
                    <flux:label>Meal Type</flux:label>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <flux:checkbox wire:model.live="mealTypes" value="breakfast" label="Breakfast" />
                        <flux:checkbox wire:model.live="mealTypes" value="lunch" label="Lunch" />
                        <flux:checkbox wire:model.live="mealTypes" value="dinner" label="Dinner" />
                        <flux:checkbox wire:model.live="mealTypes" value="snack" label="Snack" />
                    </div>
                </div>

                {{-- Dietary Tags Filter --}}
                <div class="flex-1 min-w-[250px]">
                    <flux:label>Dietary Preferences</flux:label>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <flux:checkbox wire:model.live="dietaryTags" value="vegetarian" label="Vegetarian" />
                        <flux:checkbox wire:model.live="dietaryTags" value="vegan" label="Vegan" />
                        <flux:checkbox wire:model.live="dietaryTags" value="gluten-free" label="Gluten-Free" />
                        <flux:checkbox wire:model.live="dietaryTags" value="dairy-free" label="Dairy-Free" />
                    </div>
                </div>

                {{-- Recipe Source Filter --}}
                <div class="flex-1 min-w-[250px]">
                    <flux:label>Recipe Source</flux:label>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <flux:checkbox wire:model.live="myRecipesOnly" label="My Recipes Only" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Recipe Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            @forelse ($recipes as $recipe)
                <x-recipe-card :recipe="$recipe" />
            @empty
                {{-- Empty State: No recipes match current filters --}}
                <div class="col-span-full text-center py-12">
                    <svg class="mx-auto h-10 w-10 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <flux:heading size="lg" class="text-gray-500 mb-2">No recipes found</flux:heading>
                    <flux:text class="text-gray-400">Try adjusting your search or filters</flux:text>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($recipes->hasPages())
            <div class="mt-6">
                {{ $recipes->links() }}
            </div>
        @endif
    @endif
</div>
