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
                        <flux:text class="text-gray-600 dark:text-gray-400 mb-4">{{ $recipe->description }}</flux:text>
                    @endif
                </div>

                <div class="flex gap-2">
                    {{-- Add To Meal Plan Button --}}
                    <flux:button
                        wire:click="openMealPlanModal"
                        variant="ghost"
                        icon="calendar"
                    >
                        Add To Meal Plan
                    </flux:button>

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

        {{-- Recipe Info and Ingredients (shared Alpine.js scope for multiplier) --}}
        <div x-data="recipeShowPage()" x-init="originalServings = {{ $recipe->servings }}">
            {{-- Recipe Info --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                @if ($recipe->prep_time)
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <flux:heading size="sm" class="text-gray-500 dark:text-gray-400 mb-1">Prep Time</flux:heading>
                        <flux:text class="text-xl font-semibold dark:text-white">{{ $recipe->prep_time }} min</flux:text>
                    </div>
                @endif

                @if ($recipe->cook_time)
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <flux:heading size="sm" class="text-gray-500 dark:text-gray-400 mb-1">Cook Time</flux:heading>
                        <flux:text class="text-xl font-semibold dark:text-white">{{ $recipe->cook_time }} min</flux:text>
                    </div>
                @endif

                @if ($this->totalTime)
                    <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <flux:heading size="sm" class="text-gray-500 dark:text-gray-400 mb-1">Total Time</flux:heading>
                        <flux:text class="text-xl font-semibold dark:text-white">{{ $this->totalTime }} min</flux:text>
                    </div>
                @endif

                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <flux:heading size="sm" id="servings-heading" class="text-gray-500 dark:text-gray-400 mb-1">Servings</flux:heading>

                {{-- Multiplier Controls --}}
                <div role="group" aria-labelledby="servings-heading" class="flex items-center justify-center gap-2 mb-2">
                    <flux:button
                        @click="multiplier = Math.max(0.25, multiplier - 0.25)"
                        variant="ghost"
                        icon="minus"
                        size="sm"
                        aria-label="Decrease serving size"
                    ></flux:button>

                    <flux:input
                        type="number"
                        x-model.number="multiplier"
                        min="0.25"
                        max="10"
                        step="0.25"
                        @input="setMultiplier($event.target.value)"
                        class="w-20 text-center text-xl font-semibold"
                        aria-label="Serving size multiplier"
                        aria-describedby="servings-result"
                    />

                    <flux:button
                        @click="multiplier = Math.min(10, multiplier + 0.25)"
                        variant="ghost"
                        icon="plus"
                        size="sm"
                        aria-label="Increase serving size"
                    ></flux:button>
                </div>

                {{-- Servings Display --}}
                <div id="servings-result">
                    <template x-if="multiplier === 1">
                        <flux:text class="text-xl font-semibold dark:text-white">{{ $recipe->servings }}</flux:text>
                    </template>
                    <template x-if="multiplier !== 1">
                        <div>
                            <flux:text class="text-xl font-semibold dark:text-white" x-text="scaledServings()"></flux:text>
                            <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                                (from <span x-text="originalServings"></span>)
                            </flux:text>
                        </div>
                    </template>
                </div>

                {{-- ARIA Live Region for Screen Readers --}}
                <div
                    class="sr-only"
                    aria-live="polite"
                    aria-atomic="true"
                    x-text="multiplier !== 1 ? 'Recipe scaled to ' + multiplier + ' times original, making ' + scaledServings() + ' servings' : ''"
                ></div>
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
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                <div>
                    <ul class="space-y-3">
                        @foreach ($recipe->recipeIngredients->sortBy('sort_order') as $recipeIngredient)
                            <li class="flex items-start gap-3">
                                <div class="flex-shrink-0 pt-0.5">
                                    <input
                                        type="checkbox"
                                        x-model="checkedIngredients"
                                        value="{{ $recipeIngredient->id }}"
                                        :aria-label="'Mark {{ $recipeIngredient->ingredient->name }} as used'"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                    />
                                </div>
                                <div
                                    class="flex-1 transition-all duration-150"
                                    :class="isChecked({{ $recipeIngredient->id }}) ? 'line-through opacity-50' : ''"
                                >
                                    @if ($recipeIngredient->quantity)
                                        <span class="font-medium">
                                            <span x-text="scaleQuantity({{ $recipeIngredient->quantity }}) || '{{ $recipeIngredient->display_quantity }}'"></span>
                                            @if ($recipeIngredient->unit)
                                                {{ $recipeIngredient->unit->value }}
                                            @endif
                                        </span>
                                        <span class="ml-1">{{ $recipeIngredient->ingredient->name }}</span>
                                    @else
                                        <span>{{ $recipeIngredient->notes ?? $recipeIngredient->ingredient->name }}</span>
                                    @endif
                                    @if ($recipeIngredient->notes && $recipeIngredient->quantity)
                                        <span class="text-gray-500 dark:text-gray-400 text-sm ml-1">({{ $recipeIngredient->notes }})</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
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

    {{-- Add To Meal Plan Modal --}}
    <flux:modal wire:model="showMealPlanModal" class="max-w-2xl">
        <flux:heading size="lg" class="mb-4">Add "{{ $recipe->name }}" to Meal Plan</flux:heading>

        @if ($this->mealPlans->isEmpty())
            <div class="mb-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <flux:text class="text-yellow-800 dark:text-yellow-200">
                    You don't have any meal plans yet.
                    <a href="{{ route('meal-plans.create') }}" class="underline font-medium">Create a meal plan</a> first.
                </flux:text>
            </div>

            <div class="flex justify-end">
                <flux:button wire:click="closeMealPlanModal" variant="ghost">Close</flux:button>
            </div>
        @else
            <form wire:submit="addToMealPlan">
                <div class="space-y-4">
                    {{-- Meal Plan Selection --}}
                    <flux:field>
                        <flux:label>Meal Plan *</flux:label>
                        <flux:select wire:model="selectedMealPlanId" required>
                            <option value="">Select a meal plan...</option>
                            @foreach ($this->mealPlans as $mealPlan)
                                <option value="{{ $mealPlan->id }}">
                                    {{ $mealPlan->name }}
                                    ({{ $mealPlan->start_date->format('M j') }} - {{ $mealPlan->end_date->format('M j, Y') }})
                                </option>
                            @endforeach
                        </flux:select>
                        <flux:error name="selectedMealPlanId" />
                    </flux:field>

                    {{-- Date Selection --}}
                    <flux:field>
                        <flux:label>Date *</flux:label>
                        <flux:input
                            wire:model="assignmentDate"
                            type="date"
                            required
                            min="{{ now()->format('Y-m-d') }}"
                        />
                        <flux:description>Select when you plan to make this recipe</flux:description>
                        <flux:error name="assignmentDate" />
                    </flux:field>

                    {{-- Meal Type Selection --}}
                    <flux:field>
                        <flux:label>Meal Type *</flux:label>
                        <flux:select wire:model="assignmentMealType" required>
                            <option value="breakfast">Breakfast</option>
                            <option value="lunch">Lunch</option>
                            <option value="dinner">Dinner</option>
                            <option value="snack">Snack</option>
                        </flux:select>
                        <flux:error name="assignmentMealType" />
                    </flux:field>

                    {{-- Serving Multiplier --}}
                    <flux:field>
                        <flux:label>Serving Multiplier *</flux:label>
                        <flux:input
                            wire:model="servingMultiplier"
                            type="number"
                            min="0.25"
                            max="10"
                            step="0.25"
                            required
                        />
                        <flux:description>
                            Scale the recipe (1.0 = original {{ $recipe->servings }} servings)
                        </flux:description>
                        <flux:error name="servingMultiplier" />
                    </flux:field>

                    {{-- Notes (Optional) --}}
                    <flux:field>
                        <flux:label>Notes (Optional)</flux:label>
                        <flux:textarea
                            wire:model="notes"
                            rows="3"
                            placeholder="Add any notes about this meal..."
                        />
                        <flux:error name="notes" />
                    </flux:field>
                </div>

                <div class="flex gap-2 justify-end mt-6">
                    <flux:button type="button" wire:click="closeMealPlanModal" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Add to Meal Plan
                    </flux:button>
                </div>
            </form>
        @endif
    </flux:modal>
</div>
