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
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'prep_time' => fake()->numberBetween(5, 60),
            'cook_time' => fake()->numberBetween(10, 120),
            'servings' => fake()->numberBetween(2, 8),
            'meal_type' => fake()->randomElement(['breakfast', 'lunch', 'dinner', 'snack']),
            'cuisine' => fake()->optional()->word(),
            'difficulty' => fake()->optional()->randomElement(['easy', 'medium', 'hard']),
            'dietary_tags' => fake()->optional()->randomElements(['vegetarian', 'vegan', 'gluten-free'], rand(0, 3)),
            'instructions' => fake()->paragraphs(3, true),
            'image_url' => fake()->optional()->imageUrl(),
        ];
    }
}
