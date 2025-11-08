<div>
    <div class="max-w-4xl mx-auto">
        <flux:heading size="xl" level="1" class="mb-6">Edit Recipe</flux:heading>

        <div class="bg-white rounded-lg shadow p-6">
            <form wire:submit="update" class="space-y-6">
                {{-- Basic Information --}}
                <div>
                    <flux:heading size="lg" class="mb-4">Basic Information</flux:heading>

                    {{-- Recipe Name --}}
                    <flux:input
                        wire:model="name"
                        label="Recipe Name"
                        id="name"
                        name="name"
                        placeholder="e.g., Mom's Lasagna"
                        required
                    />
                </div>

                {{-- Description --}}
                <flux:field>
                    <flux:label>Description (Optional)</flux:label>
                    <flux:textarea
                        wire:model="description"
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Brief description of the recipe..."
                    />
                    <flux:error name="description" />
                </flux:field>

                {{-- Time and Servings --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:field>
                        <flux:label>Prep Time (minutes)</flux:label>
                        <flux:input
                            wire:model="prep_time"
                            type="number"
                            id="prep_time"
                            name="prep_time"
                            min="0"
                            placeholder="30"
                        />
                        <flux:error name="prep_time" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Cook Time (minutes)</flux:label>
                        <flux:input
                            wire:model="cook_time"
                            type="number"
                            id="cook_time"
                            name="cook_time"
                            min="0"
                            placeholder="45"
                        />
                        <flux:error name="cook_time" />
                    </flux:field>

                    <flux:input
                        wire:model="servings"
                        label="Servings"
                        type="number"
                        id="servings"
                        name="servings"
                        min="1"
                        max="100"
                        placeholder="4"
                        required
                    />
                </div>

                {{-- Additional Details --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <flux:field>
                        <flux:label>Meal Type (Optional)</flux:label>
                        <flux:select wire:model="meal_type" id="meal_type" name="meal_type">
                            <option value="">Select...</option>
                            @foreach($mealTypes as $type)
                                <option value="{{ $type->value }}">{{ ucfirst($type->value) }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="meal_type" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Cuisine (Optional)</flux:label>
                        <flux:input
                            wire:model="cuisine"
                            id="cuisine"
                            name="cuisine"
                            placeholder="e.g., Italian"
                        />
                        <flux:error name="cuisine" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Difficulty (Optional)</flux:label>
                        <flux:select wire:model="difficulty" id="difficulty" name="difficulty">
                            <option value="">Select...</option>
                            <option value="easy">Easy</option>
                            <option value="medium">Medium</option>
                            <option value="hard">Hard</option>
                        </flux:select>
                        <flux:error name="difficulty" />
                    </flux:field>
                </div>

                {{-- Dietary Tags --}}
                <flux:field>
                    <flux:label>Dietary Tags (Optional)</flux:label>
                    <div class="flex flex-wrap gap-3 mt-2">
                        <flux:checkbox wire:model="dietary_tags" value="vegetarian" label="Vegetarian" />
                        <flux:checkbox wire:model="dietary_tags" value="vegan" label="Vegan" />
                        <flux:checkbox wire:model="dietary_tags" value="gluten-free" label="Gluten-Free" />
                        <flux:checkbox wire:model="dietary_tags" value="dairy-free" label="Dairy-Free" />
                        <flux:checkbox wire:model="dietary_tags" value="nut-free" label="Nut-Free" />
                        <flux:checkbox wire:model="dietary_tags" value="low-carb" label="Low-Carb" />
                        <flux:checkbox wire:model="dietary_tags" value="keto" label="Keto" />
                    </div>
                    <flux:error name="dietary_tags" />
                </flux:field>

                {{-- Ingredients --}}
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <flux:heading size="lg">Ingredients</flux:heading>
                        <flux:button type="button" wire:click="addIngredient" variant="ghost" size="sm">
                            <flux:icon.plus class="size-4" /> Add Ingredient
                        </flux:button>
                    </div>

                    <div class="space-y-3">
                        @foreach($ingredients as $index => $ingredient)
                            <x-ingredient-input
                                :index="$index"
                                :ingredient="$ingredient"
                                :showRemove="count($ingredients) > 1"
                            />
                        @endforeach
                    </div>

                    @error('ingredients')
                        <span class="text-red-600 text-sm mt-2 block">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Instructions --}}
                <flux:field>
                    <flux:label>Cooking Instructions</flux:label>
                    <flux:textarea
                        wire:model="instructions"
                        id="instructions"
                        name="instructions"
                        rows="8"
                        placeholder="Step-by-step cooking instructions...&#10;&#10;1. Prepare ingredients&#10;2. Heat pan&#10;3. Cook until done..."
                        required
                    />
                    <flux:description>Write step-by-step instructions for preparing this recipe</flux:description>
                    <flux:error name="instructions" />
                </flux:field>

                {{-- Image URL (Optional) --}}
                <flux:field>
                    <flux:label>Image URL (Optional)</flux:label>
                    <flux:input
                        wire:model="image_url"
                        type="url"
                        id="image_url"
                        name="image_url"
                        placeholder="https://example.com/recipe-image.jpg"
                    />
                    <flux:description>Link to an image of the finished dish</flux:description>
                    <flux:error name="image_url" />
                </flux:field>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 pt-4 border-t">
                    <flux:button
                        href="{{ route('recipes.show', $recipe) }}"
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>
                    <flux:button
                        type="submit"
                        variant="primary"
                    >
                        <span wire:loading.remove>Update Recipe</span>
                        <span wire:loading>Updating...</span>
                    </flux:button>
                </div>
            </form>
        </div>
    </div>
</div>
