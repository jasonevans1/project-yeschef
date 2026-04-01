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
        $this->createCommonIngredients();

        $recipeCount = rand(50, 100);

        for ($i = 0; $i < $recipeCount; $i++) {
            Recipe::factory()
                ->system()
                ->withIngredients(rand(3, 12))
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

            ['name' => 'chicken breast', 'category' => 'meat'],
            ['name' => 'chicken thighs', 'category' => 'meat'],
            ['name' => 'ground beef', 'category' => 'meat'],
            ['name' => 'beef steak', 'category' => 'meat'],
            ['name' => 'pork chops', 'category' => 'meat'],
            ['name' => 'bacon', 'category' => 'meat'],
            ['name' => 'sausage', 'category' => 'meat'],
            ['name' => 'ground turkey', 'category' => 'meat'],

            ['name' => 'salmon', 'category' => 'seafood'],
            ['name' => 'shrimp', 'category' => 'seafood'],
            ['name' => 'tuna', 'category' => 'seafood'],
            ['name' => 'cod', 'category' => 'seafood'],
            ['name' => 'tilapia', 'category' => 'seafood'],

            ['name' => 'olive oil', 'category' => 'cooking-and-baking'],
            ['name' => 'vegetable oil', 'category' => 'cooking-and-baking'],
            ['name' => 'salt', 'category' => 'cooking-and-baking'],
            ['name' => 'black pepper', 'category' => 'cooking-and-baking'],
            ['name' => 'flour', 'category' => 'cooking-and-baking'],
            ['name' => 'sugar', 'category' => 'cooking-and-baking'],
            ['name' => 'brown sugar', 'category' => 'cooking-and-baking'],
            ['name' => 'cumin', 'category' => 'cooking-and-baking'],
            ['name' => 'paprika', 'category' => 'cooking-and-baking'],
            ['name' => 'chili powder', 'category' => 'cooking-and-baking'],
            ['name' => 'cayenne pepper', 'category' => 'cooking-and-baking'],
            ['name' => 'oregano', 'category' => 'cooking-and-baking'],
            ['name' => 'thyme', 'category' => 'cooking-and-baking'],
            ['name' => 'rosemary', 'category' => 'cooking-and-baking'],
            ['name' => 'bay leaves', 'category' => 'cooking-and-baking'],
            ['name' => 'cinnamon', 'category' => 'cooking-and-baking'],
            ['name' => 'nutmeg', 'category' => 'cooking-and-baking'],
            ['name' => 'ginger', 'category' => 'cooking-and-baking'],
            ['name' => 'garlic powder', 'category' => 'cooking-and-baking'],
            ['name' => 'onion powder', 'category' => 'cooking-and-baking'],
            ['name' => 'italian seasoning', 'category' => 'cooking-and-baking'],

            ['name' => 'rice', 'category' => 'grains-and-pasta'],
            ['name' => 'pasta', 'category' => 'grains-and-pasta'],
            ['name' => 'beans', 'category' => 'grains-and-pasta'],
            ['name' => 'chickpeas', 'category' => 'grains-and-pasta'],
            ['name' => 'lentils', 'category' => 'grains-and-pasta'],

            ['name' => 'soy sauce', 'category' => 'condiments-and-dressings'],
            ['name' => 'worcestershire sauce', 'category' => 'condiments-and-dressings'],
            ['name' => 'balsamic vinegar', 'category' => 'condiments-and-dressings'],
            ['name' => 'red wine vinegar', 'category' => 'condiments-and-dressings'],

            ['name' => 'chicken broth', 'category' => 'soups-and-canned-goods'],
            ['name' => 'beef broth', 'category' => 'soups-and-canned-goods'],
            ['name' => 'vegetable broth', 'category' => 'soups-and-canned-goods'],
            ['name' => 'canned tomatoes', 'category' => 'soups-and-canned-goods'],
            ['name' => 'tomato paste', 'category' => 'soups-and-canned-goods'],
            ['name' => 'tomato sauce', 'category' => 'soups-and-canned-goods'],

            ['name' => 'bread', 'category' => 'bakery'],
            ['name' => 'tortillas', 'category' => 'bakery'],
            ['name' => 'buns', 'category' => 'bakery'],
            ['name' => 'pita bread', 'category' => 'bakery'],

            ['name' => 'frozen peas', 'category' => 'frozen'],
            ['name' => 'frozen corn', 'category' => 'frozen'],
            ['name' => 'frozen mixed vegetables', 'category' => 'frozen'],

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
