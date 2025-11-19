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
        // First, seed common ingredients and system recipes
        $this->call([
            RecipeSeeder::class,
        ]);

        // Create 5 test users
        $users = collect();

        // Create the main test user (or get if exists)
        $users->push(User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
            ]
        ));

        // Create 4 additional test users
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

        // For each user, create personal recipes, meal plans, and grocery lists
        $users->each(function (User $user, $index) {
            $this->seedUserData($user, $index);
        });

        $this->command->info('Development data seeding complete!');
    }

    /**
     * Seed data for a specific user.
     */
    private function seedUserData(User $user, int $userIndex): void
    {
        // Create 10 personal recipes for this user
        $personalRecipes = Recipe::factory()
            ->count(10)
            ->state(['user_id' => $user->id])
            ->withIngredients(rand(3, 8))
            ->create();

        $this->command->info("Created 10 personal recipes for {$user->name}.");

        // Get all available recipes (system + personal) for meal plan assignments
        $allRecipes = Recipe::query()
            ->where(function ($query) use ($user) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->get();

        // Create 3 meal plans for this user
        for ($planIndex = 0; $planIndex < 3; $planIndex++) {
            $mealPlan = $this->createMealPlanWithAssignments($user, $allRecipes, $planIndex);

            // Create a grocery list for the first 3 meal plans (1 per user from different users)
            if ($userIndex < 3) {
                $this->createGroceryListFromMealPlan($user, $mealPlan, $planIndex);
            }
        }

        $this->command->info("Created 3 meal plans with assignments for {$user->name}.");

        // Create 2 standalone grocery lists for the first 2 users
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
    private function createMealPlanWithAssignments(User $user, $recipes, int $planIndex): MealPlan
    {
        // Create meal plans with different time ranges
        $startDate = match ($planIndex) {
            0 => now()->addDays(1), // Tomorrow (future)
            1 => now()->subDays(7), // Last week (past)
            2 => now()->addDays(7), // Next week (future)
        };

        $endDate = $startDate->copy()->addDays(6); // 7-day meal plans

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

        // Assign recipes to various meal slots (ensuring no duplicates)
        $mealTypes = ['breakfast', 'lunch', 'dinner', 'snack'];
        $assignmentCount = rand(5, 10); // Random number of assignments per plan
        $usedSlots = []; // Track used date+mealType combinations

        $attempts = 0;
        $maxAttempts = 100; // Prevent infinite loop

        while (count($usedSlots) < $assignmentCount && $attempts < $maxAttempts) {
            $date = $startDate->copy()->addDays(rand(0, 6));
            $mealType = $mealTypes[array_rand($mealTypes)];
            $slotKey = $date->format('Y-m-d').'-'.$mealType;

            // Skip if this slot is already assigned
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

        // Add items from the meal plan's recipes
        $recipes = $mealPlan->recipes()->with('recipeIngredients.ingredient')->get();

        foreach ($recipes as $recipe) {
            foreach ($recipe->recipeIngredients->take(3) as $recipeIngredient) {
                GroceryItem::create([
                    'grocery_list_id' => $groceryList->id,
                    'name' => $recipeIngredient->ingredient->name,
                    'quantity' => $recipeIngredient->quantity,
                    'unit' => $recipeIngredient->unit, // Already a MeasurementUnit enum
                    'category' => $recipeIngredient->ingredient->category ?? IngredientCategory::OTHER,
                    'source_type' => SourceType::GENERATED,
                    'purchased' => rand(0, 1) === 1, // Random purchased status
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
            'meal_plan_id' => null, // Standalone
            'name' => $listIndex === 0 ? 'Quick Shopping Trip' : 'Weekly Staples',
            'generated_at' => now(),
        ]);

        // Add some random grocery items
        $categories = [
            IngredientCategory::PRODUCE->value => ['apples', 'bananas', 'oranges', 'lettuce', 'tomatoes'],
            IngredientCategory::DAIRY->value => ['milk', 'eggs', 'cheese', 'yogurt', 'butter'],
            IngredientCategory::PANTRY->value => ['bread', 'rice', 'pasta', 'olive oil', 'flour'],
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
