<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MealAssignment>
 */
class MealAssignmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meal_plan_id' => \App\Models\MealPlan::factory(),
            'recipe_id' => \App\Models\Recipe::factory(),
            'date' => now()->addDays(fake()->numberBetween(0, 6)),
            'meal_type' => fake()->randomElement(['breakfast', 'lunch', 'dinner', 'snack']),
            'serving_multiplier' => 1.0,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
