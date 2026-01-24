<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Recipe;
use Illuminate\Database\Seeder;

class RecipeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Seed 50-100 system recipes (user_id = null) with diverse cuisines,
     * meal types, difficulties, dietary tags, and realistic ingredients.
     */
    public function run(): void
    {
        // Create common ingredients first to avoid duplicates
        $this->createCommonIngredients();

        // Seed system recipes with realistic data
        $recipeCount = rand(50, 100);

        for ($i = 0; $i < $recipeCount; $i++) {
            Recipe::factory()
                ->system() // user_id = null
                ->withIngredients(rand(3, 12)) // 3-12 ingredients per recipe
                ->create();
        }

        $this->command->info("Seeded {$recipeCount} system recipes with ingredients.");
    }

    /**
     * Create common ingredients that will be reused across recipes.
     */
    private function createCommonIngredients(): void
    {
        $commonIngredients = [
            // Produce
            ['name' => 'onion', 'category' => 'produce'],
            ['name' => 'garlic', 'category' => 'produce'],
            ['name' => 'tomato', 'category' => 'produce'],
            ['name' => 'potato', 'category' => 'produce'],
            ['name' => 'carrot', 'category' => 'produce'],
            ['name' => 'celery', 'category' => 'produce'],
            ['name' => 'bell pepper', 'category' => 'produce'],
            ['name' => 'spinach', 'category' => 'produce'],
            ['name' => 'lettuce', 'category' => 'produce'],
            ['name' => 'broccoli', 'category' => 'produce'],
            ['name' => 'mushroom', 'category' => 'produce'],
            ['name' => 'zucchini', 'category' => 'produce'],
            ['name' => 'lemon', 'category' => 'produce'],
            ['name' => 'lime', 'category' => 'produce'],
            ['name' => 'cilantro', 'category' => 'produce'],
            ['name' => 'parsley', 'category' => 'produce'],
            ['name' => 'basil', 'category' => 'produce'],

            // Dairy
            ['name' => 'butter', 'category' => 'dairy'],
            ['name' => 'milk', 'category' => 'dairy'],
            ['name' => 'heavy cream', 'category' => 'dairy'],
            ['name' => 'sour cream', 'category' => 'dairy'],
            ['name' => 'cheddar cheese', 'category' => 'dairy'],
            ['name' => 'mozzarella cheese', 'category' => 'dairy'],
            ['name' => 'parmesan cheese', 'category' => 'dairy'],
            ['name' => 'cream cheese', 'category' => 'dairy'],
            ['name' => 'yogurt', 'category' => 'dairy'],
            ['name' => 'eggs', 'category' => 'dairy'],

            // Meat
            ['name' => 'chicken breast', 'category' => 'meat'],
            ['name' => 'chicken thighs', 'category' => 'meat'],
            ['name' => 'ground beef', 'category' => 'meat'],
            ['name' => 'beef steak', 'category' => 'meat'],
            ['name' => 'pork chops', 'category' => 'meat'],
            ['name' => 'bacon', 'category' => 'meat'],
            ['name' => 'sausage', 'category' => 'meat'],
            ['name' => 'ground turkey', 'category' => 'meat'],

            // Seafood
            ['name' => 'salmon', 'category' => 'seafood'],
            ['name' => 'shrimp', 'category' => 'seafood'],
            ['name' => 'tuna', 'category' => 'seafood'],
            ['name' => 'cod', 'category' => 'seafood'],
            ['name' => 'tilapia', 'category' => 'seafood'],

            // Pantry
            ['name' => 'olive oil', 'category' => 'pantry'],
            ['name' => 'vegetable oil', 'category' => 'pantry'],
            ['name' => 'salt', 'category' => 'pantry'],
            ['name' => 'black pepper', 'category' => 'pantry'],
            ['name' => 'flour', 'category' => 'pantry'],
            ['name' => 'sugar', 'category' => 'pantry'],
            ['name' => 'brown sugar', 'category' => 'pantry'],
            ['name' => 'rice', 'category' => 'pantry'],
            ['name' => 'pasta', 'category' => 'pantry'],
            ['name' => 'soy sauce', 'category' => 'pantry'],
            ['name' => 'worcestershire sauce', 'category' => 'pantry'],
            ['name' => 'balsamic vinegar', 'category' => 'pantry'],
            ['name' => 'red wine vinegar', 'category' => 'pantry'],
            ['name' => 'chicken broth', 'category' => 'pantry'],
            ['name' => 'beef broth', 'category' => 'pantry'],
            ['name' => 'vegetable broth', 'category' => 'pantry'],
            ['name' => 'canned tomatoes', 'category' => 'pantry'],
            ['name' => 'tomato paste', 'category' => 'pantry'],
            ['name' => 'tomato sauce', 'category' => 'pantry'],
            ['name' => 'beans', 'category' => 'pantry'],
            ['name' => 'chickpeas', 'category' => 'pantry'],
            ['name' => 'lentils', 'category' => 'pantry'],

            // Spices (Pantry)
            ['name' => 'cumin', 'category' => 'pantry'],
            ['name' => 'paprika', 'category' => 'pantry'],
            ['name' => 'chili powder', 'category' => 'pantry'],
            ['name' => 'cayenne pepper', 'category' => 'pantry'],
            ['name' => 'oregano', 'category' => 'pantry'],
            ['name' => 'thyme', 'category' => 'pantry'],
            ['name' => 'rosemary', 'category' => 'pantry'],
            ['name' => 'bay leaves', 'category' => 'pantry'],
            ['name' => 'cinnamon', 'category' => 'pantry'],
            ['name' => 'nutmeg', 'category' => 'pantry'],
            ['name' => 'ginger', 'category' => 'pantry'],
            ['name' => 'garlic powder', 'category' => 'pantry'],
            ['name' => 'onion powder', 'category' => 'pantry'],
            ['name' => 'italian seasoning', 'category' => 'pantry'],

            // Bakery
            ['name' => 'bread', 'category' => 'bakery'],
            ['name' => 'tortillas', 'category' => 'bakery'],
            ['name' => 'buns', 'category' => 'bakery'],
            ['name' => 'pita bread', 'category' => 'bakery'],

            // Frozen
            ['name' => 'frozen peas', 'category' => 'frozen'],
            ['name' => 'frozen corn', 'category' => 'frozen'],
            ['name' => 'frozen mixed vegetables', 'category' => 'frozen'],

            // Beverages
            ['name' => 'white wine', 'category' => 'beverages'],
            ['name' => 'red wine', 'category' => 'beverages'],
            ['name' => 'beer', 'category' => 'beverages'],
        ];

        foreach ($commonIngredients as $ingredient) {
            Ingredient::firstOrCreate(
                ['name' => $ingredient['name']],
                ['category' => $ingredient['category']]
            );
        }

        $this->command->info('Created '.count($commonIngredients).' common ingredients.');
    }
}
