<div>
    <div class="flex items-center justify-between mb-6">
        <flux:heading size="xl">Browse Recipes</flux:heading>
        <flux:button href="{{ route('recipes.create') }}" variant="primary" icon="plus">
            Create New Recipe
        </flux:button>
    </div>

    <div class="mb-6 space-y-4">
        {{-- Search Input --}}
        <flux:input
            wire:model.live.debounce.300ms="search"
            type="search"
            placeholder="Search recipes by name or description..."
            class="w-full"
        />

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
        </div>
    </div>

    {{-- Recipe Grid --}}
    <div class="grid grid-cols-2 gap-6 mb-6">
        @forelse ($recipes as $recipe)
            <x-recipe-card :recipe="$recipe" />
        @empty
            <div class="col-span-full text-center py-12">
                <flux:heading size="lg" class="text-gray-500 mb-2">No recipes found</flux:heading>
                <flux:text class="text-gray-400">Try adjusting your search or filters</flux:text>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $recipes->links() }}
    </div>
</div>
