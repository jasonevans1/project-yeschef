<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserItemTemplate>
 */
class UserItemTemplateFactory extends Factory
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
            'name' => fake()->words(2, true),
            'category' => fake()->randomElement(\App\Enums\IngredientCategory::cases()),
            'unit' => fake()->randomElement(\App\Enums\MeasurementUnit::cases()),
            'default_quantity' => fake()->randomFloat(3, 0.25, 10),
            'usage_count' => fake()->numberBetween(0, 20),
            'last_used_at' => now(),
        ];
    }
}
