<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CommonItemTemplate>
 */
class CommonItemTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'category' => fake()->randomElement(\App\Enums\IngredientCategory::cases()),
            'unit' => null,
            'default_quantity' => null,
            'search_keywords' => null,
            'usage_count' => 0,
        ];
    }
}
