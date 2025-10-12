<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroceryItem>
 */
class GroceryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grocery_list_id' => \App\Models\GroceryList::factory(),
            'name' => fake()->word(),
            'quantity' => fake()->randomFloat(2, 0.5, 10),
            'unit' => fake()->randomElement(['tsp', 'tbsp', 'cup', 'oz', 'lb', 'gram', 'whole', 'piece']),
            'category' => fake()->randomElement(['produce', 'dairy', 'meat', 'seafood', 'pantry', 'frozen', 'bakery', 'deli', 'beverages', 'other']),
            'source_type' => 'manual',
            'original_values' => null,
            'purchased' => false,
            'purchased_at' => null,
            'notes' => fake()->optional()->sentence(),
            'sort_order' => 0,
        ];
    }
}
