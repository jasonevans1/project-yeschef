<?php

namespace Database\Factories;

use App\Enums\SharePermission;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContentShare>
 */
class ContentShareFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $recipient = User::factory();

        return [
            'owner_id' => User::factory(),
            'recipient_id' => $recipient,
            'recipient_email' => fn (array $attributes) => User::find($attributes['recipient_id'])?->email ?? fake()->safeEmail(),
            'shareable_type' => Recipe::class,
            'shareable_id' => Recipe::factory(),
            'permission' => SharePermission::Read,
            'share_all' => false,
        ];
    }

    /**
     * Share a specific recipe.
     */
    public function forRecipe(?Recipe $recipe = null): static
    {
        return $this->state(fn (array $attributes) => [
            'shareable_type' => Recipe::class,
            'shareable_id' => $recipe?->id ?? Recipe::factory(),
            'share_all' => false,
        ]);
    }

    /**
     * Share a specific meal plan.
     */
    public function forMealPlan(?MealPlan $mealPlan = null): static
    {
        return $this->state(fn (array $attributes) => [
            'shareable_type' => MealPlan::class,
            'shareable_id' => $mealPlan?->id ?? MealPlan::factory(),
            'share_all' => false,
        ]);
    }

    /**
     * Share a specific grocery list.
     */
    public function forGroceryList(?GroceryList $groceryList = null): static
    {
        return $this->state(fn (array $attributes) => [
            'shareable_type' => GroceryList::class,
            'shareable_id' => $groceryList?->id ?? GroceryList::factory(),
            'share_all' => false,
        ]);
    }

    /**
     * Share all items of a content type.
     */
    public function shareAll(string $shareableType = Recipe::class): static
    {
        return $this->state(fn (array $attributes) => [
            'shareable_type' => $shareableType,
            'shareable_id' => null,
            'share_all' => true,
        ]);
    }

    /**
     * Pending share (recipient not yet registered).
     */
    public function pending(string $email = null): static
    {
        return $this->state(fn (array $attributes) => [
            'recipient_id' => null,
            'recipient_email' => $email ?? fake()->safeEmail(),
        ]);
    }

    /**
     * Write permission.
     */
    public function withWritePermission(): static
    {
        return $this->state(fn (array $attributes) => [
            'permission' => SharePermission::Write,
        ]);
    }
}
