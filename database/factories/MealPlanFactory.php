<?php

namespace Database\Factories;

use App\Models\MealPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MealPlan>
 */
class MealPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+30 days');
        $durationDays = fake()->numberBetween(1, 28); // 1 day to 4 weeks (28 days)
        $endDate = (clone $startDate)->modify("+{$durationDays} days");

        return [
            'user_id' => User::factory(),
            'name' => fake()->words(rand(2, 4), true),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => fake()->optional(0.7)->sentence(),
        ];
    }
}
