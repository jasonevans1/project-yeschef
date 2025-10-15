<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cuisines = ['Italian', 'Mexican', 'Chinese', 'Japanese', 'Indian', 'Thai', 'French', 'Greek', 'American', 'Mediterranean'];
        $dietaryTags = ['vegetarian', 'vegan', 'gluten-free', 'dairy-free', 'keto', 'paleo', 'low-carb', 'high-protein'];

        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->words(rand(2, 4), true),
            'description' => fake()->optional(0.8)->paragraph(),
            'prep_time' => fake()->numberBetween(5, 60),
            'cook_time' => fake()->numberBetween(10, 120),
            'servings' => fake()->numberBetween(2, 8),
            'meal_type' => fake()->randomElement(['breakfast', 'lunch', 'dinner', 'snack']),
            'cuisine' => fake()->optional(0.9)->randomElement($cuisines),
            'difficulty' => fake()->optional(0.8)->randomElement(['easy', 'medium', 'hard']),
            'dietary_tags' => fake()->optional(0.6)->randomElements($dietaryTags, rand(1, 3)),
            'instructions' => fake()->paragraphs(rand(3, 6), true),
            'image_url' => fake()->optional(0.7)->imageUrl(),
        ];
    }

    /**
     * Indicate that the recipe is a system recipe (no user owner).
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the recipe should have ingredients attached.
     */
    public function withIngredients(?int $count = null): static
    {
        return $this->afterCreating(function (\App\Models\Recipe $recipe) use ($count) {
            $ingredientCount = $count ?? fake()->numberBetween(3, 10);
            $units = ['tsp', 'tbsp', 'cup', 'oz', 'lb', 'gram', 'whole', 'piece'];

            // Get all available ingredients, or create some if none exist
            $availableIngredients = \App\Models\Ingredient::all();
            if ($availableIngredients->isEmpty()) {
                // Create some if none exist (fallback)
                for ($j = 0; $j < 20; $j++) {
                    \App\Models\Ingredient::factory()->create();
                }
                $availableIngredients = \App\Models\Ingredient::all();
            }

            // Randomly select ingredients from available ones
            $selectedIngredients = $availableIngredients->random(min($ingredientCount, $availableIngredients->count()));

            foreach ($selectedIngredients as $i => $ingredient) {
                $recipe->recipeIngredients()->create([
                    'ingredient_id' => $ingredient->id,
                    'quantity' => fake()->randomFloat(2, 0.25, 10),
                    'unit' => fake()->randomElement($units),
                    'sort_order' => $i,
                    'notes' => fake()->optional(0.3)->words(rand(2, 4), true),
                ]);
            }
        });
    }
}
