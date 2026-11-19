<?php

namespace Database\Factories;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeIngredient>
 */
class RecipeIngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'ingredient_id' => Ingredient::factory(),
            'quantity' => fake()->randomFloat(2, 0.25, 10),
            'unit' => fake()->randomElement(['tsp', 'tbsp', 'cup', 'oz', 'lb', 'gram', 'whole', 'piece']),
            'sort_order' => 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
