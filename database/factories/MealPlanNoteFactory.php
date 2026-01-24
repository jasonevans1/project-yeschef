<?php

namespace Database\Factories;

use App\Enums\MealType;
use App\Models\MealPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MealPlanNote>
 */
class MealPlanNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meal_plan_id' => MealPlan::factory(),
            'date' => fake()->dateTimeBetween('now', '+7 days'),
            'meal_type' => fake()->randomElement(MealType::cases()),
            'title' => fake()->sentence(3),
            'details' => fake()->optional(0.7)->paragraph(),
        ];
    }
}
