<?php

namespace Database\Factories;

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Models\User;
use App\Models\UserItemTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserItemTemplate>
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
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'category' => fake()->randomElement(IngredientCategory::cases()),
            'unit' => fake()->randomElement(MeasurementUnit::cases()),
            'default_quantity' => fake()->randomFloat(3, 0.25, 10),
            'usage_count' => fake()->numberBetween(0, 20),
            'last_used_at' => now(),
        ];
    }
}
