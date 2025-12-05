<div>
    <div class="mb-6">
        <flux:button variant="ghost" href="{{ route('recipes.index') }}" icon="arrow-left">
            Back to Recipes
        </flux:button>
    </div>

    <div class="max-w-4xl mx-auto">
        {{-- Recipe Header --}}
        <div class="mb-8">
            @if ($recipe->image_url)
                <img src="{{ $recipe->image_url }}" alt="{{ $recipe->name }}" class="w-full h-64 object-cover rounded-lg mb-4">
            @endif

            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <flux:heading size="xl" class="mb-2">{{ $recipe->name }}</flux:heading>

                    <div class="flex flex-wrap gap-2 mb-4">
                        @if ($this->isSystemRecipe)
                            <flux:badge color="blue" icon="star">System Recipe</flux:badge>
                        @else
                            <flux:badge color="green" icon="user">My Recipe</flux:badge>
                        @endif

                        @if ($recipe->meal_type)
                            <flux:badge>{{ ucfirst($recipe->meal_type->value) }}</flux:badge>
                        @endif

                        @if ($recipe->difficulty)
                            <flux:badge color="zinc">{{ ucfirst($recipe->difficulty) }}</flux:badge>
                        @endif

                        @if ($recipe->cuisine)
                            <flux:badge>{{ $recipe->cuisine }}</flux:badge>
                        @endif
                    </div>

                    @if ($recipe->description)
                        <flux:text class="text-gray-600 mb-4">{{ $recipe->description }}</flux:text>
                    @endif
                </div>

                <div class="flex gap-2">
                    @can('update', $recipe)
                        <flux:button variant="primary" href="{{ route('recipes.edit', $recipe) }}" icon="pencil">
                            Edit
                        </flux:button>
                    @endcan

                    @can('delete', $recipe)
                        <form action="{{ route('recipes.destroy', $recipe) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this recipe?');">
                            @csrf
                            @method('DELETE')
                            <flux:button type="submit" variant="danger" icon="trash">
                                Delete
                            </flux:button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>

        {{-- Recipe Info --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            @if ($recipe->prep_time)
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <flux:heading size="sm" class="text-gray-500 mb-1">Prep Time</flux:heading>
                    <flux:text class="text-xl font-semibold">{{ $recipe->prep_time }} min</flux:text>
                </div>
            @endif

            @if ($recipe->cook_time)
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <flux:heading size="sm" class="text-gray-500 mb-1">Cook Time</flux:heading>
                    <flux:text class="text-xl font-semibold">{{ $recipe->cook_time }} min</flux:text>
                </div>
            @endif

            @if ($this->totalTime)
                <div class="text-center p-4 bg-gray-50 rounded-lg">
                    <flux:heading size="sm" class="text-gray-500 mb-1">Total Time</flux:heading>
                    <flux:text class="text-xl font-semibold">{{ $this->totalTime }} min</flux:text>
                </div>
            @endif

            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <flux:heading size="sm" class="text-gray-500 mb-1">Servings</flux:heading>
                <flux:text class="text-xl font-semibold">{{ $recipe->servings }}</flux:text>
            </div>
        </div>

        {{-- Dietary Tags --}}
        @if ($recipe->dietary_tags && count($recipe->dietary_tags) > 0)
            <div class="mb-8">
                <flux:heading size="lg" class="mb-3">Dietary Information</flux:heading>
                <div class="flex flex-wrap gap-2">
                    @foreach ($recipe->dietary_tags as $tag)
                        <flux:badge color="green" icon="check-circle">{{ ucfirst($tag) }}</flux:badge>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Ingredients --}}
        <div class="mb-8">
            <flux:heading size="lg" class="mb-4">Ingredients</flux:heading>
            <div class="bg-gray-50 rounded-lg p-6">
                <ul class="space-y-2">
                    @foreach ($recipe->recipeIngredients->sortBy('sort_order') as $recipeIngredient)
                        <li class="flex items-start">
                            <span class="mr-2 text-gray-400">•</span>
                            <div class="flex-1">
                                @if ($recipeIngredient->quantity && $recipeIngredient->unit)
                                    <span class="font-medium">
                                        {{ $recipeIngredient->quantity }}
                                        {{ $recipeIngredient->unit->value }}
                                    </span>
                                    <span class="ml-1">{{ $recipeIngredient->ingredient->name }}</span>
                                @else
                                    <span>{{ $recipeIngredient->notes ?? $recipeIngredient->ingredient->name }}</span>
                                @endif
                                @if ($recipeIngredient->notes && $recipeIngredient->quantity && $recipeIngredient->unit)
                                    <span class="text-gray-500 text-sm ml-1">({{ $recipeIngredient->notes }})</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="mb-8">
            <flux:heading size="lg" class="mb-4">Instructions</flux:heading>
            <div class="prose max-w-none">
                <flux:text class="whitespace-pre-line">{{ $recipe->instructions }}</flux:text>
            </div>
        </div>
    </div>
</div>
