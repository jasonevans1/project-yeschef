<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroceryList>
 */
class GroceryListFactory extends Factory
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
            'meal_plan_id' => \App\Models\MealPlan::factory(),
            'name' => fake()->words(rand(2, 4), true),
            'generated_at' => now(),
        ];
    }

    /**
     * Indicate that the grocery list is standalone (not linked to a meal plan).
     */
    public function standalone(): static
    {
        return $this->state(fn (array $attributes) => [
            'meal_plan_id' => null,
        ]);
    }

    /**
     * Indicate that the grocery list should have items.
     */
    public function withItems(int $generatedCount = 5, int $manualCount = 3): static
    {
        return $this->afterCreating(function (\App\Models\GroceryList $groceryList) use ($generatedCount, $manualCount) {
            $categories = array_map(fn ($c) => $c->value, \App\Enums\IngredientCategory::cases());
            $units = ['cup', 'oz', 'lb', 'whole', 'piece'];

            for ($i = 0; $i < $generatedCount; $i++) {
                $groceryList->items()->create([
                    'name' => fake()->words(rand(1, 2), true),
                    'quantity' => fake()->randomFloat(2, 0.5, 10),
                    'unit' => fake()->randomElement($units),
                    'category' => fake()->randomElement($categories),
                    'source_type' => 'generated',
                    'purchased' => fake()->boolean(30),
                    'purchased_at' => fake()->boolean(30) ? now() : null,
                    'sort_order' => $i,
                ]);
            }

            for ($i = 0; $i < $manualCount; $i++) {
                $groceryList->items()->create([
                    'name' => fake()->words(rand(1, 3), true),
                    'quantity' => fake()->optional(0.7)->randomFloat(2, 1, 5),
                    'unit' => fake()->optional(0.7)->randomElement($units),
                    'category' => fake()->randomElement($categories),
                    'source_type' => 'manual',
                    'purchased' => fake()->boolean(20),
                    'purchased_at' => fake()->boolean(20) ? now() : null,
                    'notes' => fake()->optional(0.4)->sentence(),
                    'sort_order' => $generatedCount + $i,
                ]);
            }
        });
    }
}
