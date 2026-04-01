<?php

namespace Database\Seeders;

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Enums\SourceType;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    /**
     * Seed the application's database with comprehensive development data.
     *
     * This seeder creates:
     * - 5 test users
     * - 100 system recipes
     * - 10 personal recipes per user (50 total)
     * - 3 meal plans per user with assigned recipes (15 total)
     * - 5 grocery lists (3 from meal plans, 2 standalone)
     */
    public function run(): void
    {
        $this->call([
            RecipeSeeder::class,
        ]);

        $users = collect();

        $users->push(User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        ));

        for ($i = 1; $i <= 4; $i++) {
            $users->push(User::firstOrCreate(
                ['email' => "test{$i}@example.com"],
                [
                    'name' => "Test User {$i}",
                    'email_verified_at' => now(),
                    'password' => bcrypt('password'),
                ]
            ));
        }

        $this->command->info("Created {$users->count()} test users.");

        $users->each(function (User $user, int $index) {
            $this->seedUserData($user, $index);
        });

        $this->command->info('Development data seeding complete!');
    }

    /**
     * Seed data for a specific user.
     */
    private function seedUserData(User $user, int $userIndex): void
    {
        $personalRecipes = Recipe::factory()
            ->count(10)
            ->state(['user_id' => $user->id])
            ->withIngredients(rand(3, 8))
            ->create();

        $this->command->info("Created 10 personal recipes for {$user->name}.");

        $allRecipes = Recipe::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->get();

        for ($planIndex = 0; $planIndex < 3; $planIndex++) {
            $mealPlan = $this->createMealPlanWithAssignments($user, $allRecipes, $planIndex);

            if ($userIndex < 3) {
                $this->createGroceryListFromMealPlan($user, $mealPlan, $planIndex);
            }
        }

        $this->command->info("Created 3 meal plans with assignments for {$user->name}.");

        if ($userIndex < 2) {
            for ($i = 0; $i < 2; $i++) {
                $this->createStandaloneGroceryList($user, $i);
            }
            $this->command->info("Created 2 standalone grocery lists for {$user->name}.");
        }
    }

    /**
     * Create a meal plan with recipe assignments.
     */
    private function createMealPlanWithAssignments(User $user, Collection $recipes, int $planIndex): MealPlan
    {
        $startDate = match ($planIndex) {
            0 => now()->addDays(1),
            1 => now()->subDays(7),
            2 => now()->addDays(7),
        };

        $endDate = $startDate->copy()->addDays(6);

        $mealPlan = MealPlan::factory()->create([
            'user_id' => $user->id,
            'name' => match ($planIndex) {
                0 => 'This Week\'s Meals',
                1 => 'Last Week\'s Meals',
                2 => 'Next Week\'s Meals',
            },
            'start_date' => $startDate,
            'end_date' => $endDate,
            'description' => "Meal plan {$planIndex} for {$user->name}",
        ]);

        $mealTypes = ['breakfast', 'lunch', 'dinner', 'snack'];
        $assignmentCount = rand(5, 10);
        $usedSlots = [];

        $attempts = 0;
        $maxAttempts = 100;

        while (count($usedSlots) < $assignmentCount && $attempts < $maxAttempts) {
            $date = $startDate->copy()->addDays(rand(0, 6));
            $mealType = $mealTypes[array_rand($mealTypes)];
            $slotKey = $date->format('Y-m-d').'-'.$mealType;

            if (in_array($slotKey, $usedSlots)) {
                $attempts++;

                continue;
            }

            $recipe = $recipes->random();

            MealAssignment::create([
                'meal_plan_id' => $mealPlan->id,
                'recipe_id' => $recipe->id,
                'date' => $date,
                'meal_type' => $mealType,
                'serving_multiplier' => rand(1, 3),
                'notes' => rand(0, 1) ? 'Double the vegetables' : null,
            ]);

            $usedSlots[] = $slotKey;
            $attempts++;
        }

        return $mealPlan;
    }

    /**
     * Create a grocery list from a meal plan.
     */
    private function createGroceryListFromMealPlan(User $user, MealPlan $mealPlan, int $planIndex): void
    {
        $groceryList = GroceryList::create([
            'user_id' => $user->id,
            'meal_plan_id' => $mealPlan->id,
            'name' => "Grocery List for {$mealPlan->name}",
            'generated_at' => now(),
        ]);

        $recipes = $mealPlan->recipes()->with('recipeIngredients.ingredient')->get();

        foreach ($recipes as $recipe) {
            foreach ($recipe->recipeIngredients->take(3) as $recipeIngredient) {
                GroceryItem::create([
                    'grocery_list_id' => $groceryList->id,
                    'name' => $recipeIngredient->ingredient->name,
                    'quantity' => $recipeIngredient->quantity,
                    'unit' => $recipeIngredient->unit,
                    'category' => $recipeIngredient->ingredient->category ?? IngredientCategory::OTHER,
                    'source_type' => SourceType::GENERATED,
                    'purchased' => rand(0, 1) === 1,
                    'notes' => rand(0, 1) ? 'Organic preferred' : null,
                ]);
            }
        }
    }

    /**
     * Create a standalone grocery list (not linked to a meal plan).
     */
    private function createStandaloneGroceryList(User $user, int $listIndex): void
    {
        $groceryList = GroceryList::create([
            'user_id' => $user->id,
            'meal_plan_id' => null,
            'name' => $listIndex === 0 ? 'Quick Shopping Trip' : 'Weekly Staples',
            'generated_at' => now(),
        ]);

        $categories = [
            IngredientCategory::PRODUCE->value => ['apples', 'bananas', 'oranges', 'lettuce', 'tomatoes'],
            IngredientCategory::DAIRY->value => ['milk', 'eggs', 'cheese', 'yogurt', 'butter'],
            IngredientCategory::GRAINS_AND_PASTA->value => ['bread', 'rice', 'pasta'],
            IngredientCategory::COOKING_AND_BAKING->value => ['olive oil', 'flour'],
            IngredientCategory::MEAT->value => ['chicken breast', 'ground beef', 'bacon'],
        ];

        $units = [MeasurementUnit::LB, MeasurementUnit::OZ, MeasurementUnit::CUP, MeasurementUnit::WHOLE];

        $itemCount = rand(5, 10);
        for ($i = 0; $i < $itemCount; $i++) {
            $categoryValue = array_rand($categories);
            $items = $categories[$categoryValue];
            $item = $items[array_rand($items)];

            GroceryItem::create([
                'grocery_list_id' => $groceryList->id,
                'name' => $item,
                'quantity' => rand(1, 5),
                'unit' => $units[array_rand($units)],
                'category' => IngredientCategory::from($categoryValue),
                'source_type' => SourceType::MANUAL,
                'purchased' => rand(0, 1) === 1,
                'notes' => null,
            ]);
        }
    }
}
