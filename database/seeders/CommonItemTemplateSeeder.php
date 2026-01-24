<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CommonItemTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Produce
            ['name' => 'apple', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 6],
            ['name' => 'banana', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 6],
            ['name' => 'orange', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 6],
            ['name' => 'tomato', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 4],
            ['name' => 'lettuce', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 1],
            ['name' => 'carrot', 'category' => 'produce', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'onion', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 3],
            ['name' => 'potato', 'category' => 'produce', 'unit' => 'lb', 'default_quantity' => 5],
            ['name' => 'bell pepper', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 2],
            ['name' => 'cucumber', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 2],
            ['name' => 'celery', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 1],
            ['name' => 'broccoli', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 1],
            ['name' => 'cauliflower', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 1],
            ['name' => 'spinach', 'category' => 'produce', 'unit' => 'oz', 'default_quantity' => 10],
            ['name' => 'mushroom', 'category' => 'produce', 'unit' => 'oz', 'default_quantity' => 8],
            ['name' => 'garlic', 'category' => 'produce', 'unit' => 'clove', 'default_quantity' => 6],
            ['name' => 'lemon', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 3],
            ['name' => 'lime', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 3],
            ['name' => 'strawberry', 'category' => 'produce', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'blueberry', 'category' => 'produce', 'unit' => 'oz', 'default_quantity' => 16],
            ['name' => 'grape', 'category' => 'produce', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'watermelon', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 1],
            ['name' => 'avocado', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 2],
            ['name' => 'sweet potato', 'category' => 'produce', 'unit' => 'lb', 'default_quantity' => 2],
            ['name' => 'zucchini', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 2],
            ['name' => 'corn', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 4],
            ['name' => 'green beans', 'category' => 'produce', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'kale', 'category' => 'produce', 'unit' => 'oz', 'default_quantity' => 8],
            ['name' => 'basil', 'category' => 'produce', 'unit' => 'oz', 'default_quantity' => 1],
            ['name' => 'cilantro', 'category' => 'produce', 'unit' => 'oz', 'default_quantity' => 1],

            // Dairy
            ['name' => 'milk', 'category' => 'dairy', 'unit' => 'gallon', 'default_quantity' => 1],
            ['name' => 'eggs', 'category' => 'dairy', 'unit' => 'whole', 'default_quantity' => 12],
            ['name' => 'butter', 'category' => 'dairy', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'cheddar cheese', 'category' => 'dairy', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'mozzarella cheese', 'category' => 'dairy', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'parmesan cheese', 'category' => 'dairy', 'unit' => 'oz', 'default_quantity' => 8],
            ['name' => 'cream cheese', 'category' => 'dairy', 'unit' => 'oz', 'default_quantity' => 8],
            ['name' => 'sour cream', 'category' => 'dairy', 'unit' => 'oz', 'default_quantity' => 16],
            ['name' => 'yogurt', 'category' => 'dairy', 'unit' => 'oz', 'default_quantity' => 32],
            ['name' => 'greek yogurt', 'category' => 'dairy', 'unit' => 'oz', 'default_quantity' => 32],
            ['name' => 'heavy cream', 'category' => 'dairy', 'unit' => 'pint', 'default_quantity' => 1],
            ['name' => 'half and half', 'category' => 'dairy', 'unit' => 'pint', 'default_quantity' => 1],
            ['name' => 'cottage cheese', 'category' => 'dairy', 'unit' => 'oz', 'default_quantity' => 16],
            ['name' => 'whipped cream', 'category' => 'dairy', 'unit' => 'oz', 'default_quantity' => 8],

            // Meat
            ['name' => 'chicken breast', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 2],
            ['name' => 'chicken thigh', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 2],
            ['name' => 'ground beef', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'ground turkey', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'pork chop', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 1.5],
            ['name' => 'bacon', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'sausage', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'steak', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 1.5],
            ['name' => 'pork tenderloin', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 1.5],
            ['name' => 'ham', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'hot dog', 'category' => 'meat', 'unit' => 'whole', 'default_quantity' => 8],

            // Seafood
            ['name' => 'salmon', 'category' => 'seafood', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'tuna', 'category' => 'seafood', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'shrimp', 'category' => 'seafood', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'tilapia', 'category' => 'seafood', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'cod', 'category' => 'seafood', 'unit' => 'lb', 'default_quantity' => 1],

            // Pantry
            ['name' => 'pasta', 'category' => 'pantry', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'rice', 'category' => 'pantry', 'unit' => 'lb', 'default_quantity' => 2],
            ['name' => 'flour', 'category' => 'pantry', 'unit' => 'lb', 'default_quantity' => 5],
            ['name' => 'sugar', 'category' => 'pantry', 'unit' => 'lb', 'default_quantity' => 4],
            ['name' => 'brown sugar', 'category' => 'pantry', 'unit' => 'lb', 'default_quantity' => 2],
            ['name' => 'olive oil', 'category' => 'pantry', 'unit' => 'liter', 'default_quantity' => 1],
            ['name' => 'vegetable oil', 'category' => 'pantry', 'unit' => 'liter', 'default_quantity' => 1],
            ['name' => 'canola oil', 'category' => 'pantry', 'unit' => 'liter', 'default_quantity' => 1],
            ['name' => 'salt', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 26],
            ['name' => 'black pepper', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 4],
            ['name' => 'honey', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 12],
            ['name' => 'peanut butter', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 16],
            ['name' => 'jelly', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 18],
            ['name' => 'canned tomato', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 28],
            ['name' => 'tomato sauce', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 15],
            ['name' => 'tomato paste', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 6],
            ['name' => 'chicken broth', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 32],
            ['name' => 'beef broth', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 32],
            ['name' => 'vegetable broth', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 32],
            ['name' => 'soy sauce', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 10],
            ['name' => 'worcestershire sauce', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 10],
            ['name' => 'ketchup', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 20],
            ['name' => 'mustard', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 12],
            ['name' => 'mayonnaise', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 30],
            ['name' => 'vinegar', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 16],
            ['name' => 'balsamic vinegar', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 8.5],
            ['name' => 'cereal', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 18],
            ['name' => 'oatmeal', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 42],
            ['name' => 'crackers', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 14],
            ['name' => 'chips', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 13],
            ['name' => 'popcorn', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 10],
            ['name' => 'canned beans', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 15],
            ['name' => 'canned corn', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 15],
            ['name' => 'canned tuna', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 5],
            ['name' => 'coffee', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 12],
            ['name' => 'tea', 'category' => 'pantry', 'unit' => 'whole', 'default_quantity' => 20],
            ['name' => 'baking powder', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 10],
            ['name' => 'baking soda', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 16],
            ['name' => 'vanilla extract', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 2],
            ['name' => 'cinnamon', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 2.37],
            ['name' => 'garlic powder', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 3],
            ['name' => 'onion powder', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 3],
            ['name' => 'paprika', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 2.5],
            ['name' => 'cumin', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 2.2],
            ['name' => 'chili powder', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 2.5],
            ['name' => 'oregano', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 0.75],
            ['name' => 'thyme', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 0.7],
            ['name' => 'rosemary', 'category' => 'pantry', 'unit' => 'oz', 'default_quantity' => 1.25],

            // Frozen
            ['name' => 'frozen pizza', 'category' => 'frozen', 'unit' => 'whole', 'default_quantity' => 1],
            ['name' => 'frozen vegetables', 'category' => 'frozen', 'unit' => 'oz', 'default_quantity' => 16],
            ['name' => 'frozen french fries', 'category' => 'frozen', 'unit' => 'oz', 'default_quantity' => 32],
            ['name' => 'ice cream', 'category' => 'frozen', 'unit' => 'pint', 'default_quantity' => 1],
            ['name' => 'frozen berries', 'category' => 'frozen', 'unit' => 'oz', 'default_quantity' => 16],
            ['name' => 'frozen chicken nuggets', 'category' => 'frozen', 'unit' => 'oz', 'default_quantity' => 24],
            ['name' => 'frozen waffles', 'category' => 'frozen', 'unit' => 'oz', 'default_quantity' => 12.3],
            ['name' => 'frozen burrito', 'category' => 'frozen', 'unit' => 'whole', 'default_quantity' => 4],

            // Bakery
            ['name' => 'bread', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 1],
            ['name' => 'bagel', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 6],
            ['name' => 'english muffin', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 6],
            ['name' => 'croissant', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 6],
            ['name' => 'hamburger bun', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 8],
            ['name' => 'hot dog bun', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 8],
            ['name' => 'tortilla', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 10],
            ['name' => 'pita bread', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 6],
            ['name' => 'donut', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 6],
            ['name' => 'muffin', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 6],
            ['name' => 'cake', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 1],
            ['name' => 'cookie', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 12],

            // Deli
            ['name' => 'deli turkey', 'category' => 'deli', 'unit' => 'lb', 'default_quantity' => 0.5],
            ['name' => 'deli ham', 'category' => 'deli', 'unit' => 'lb', 'default_quantity' => 0.5],
            ['name' => 'deli roast beef', 'category' => 'deli', 'unit' => 'lb', 'default_quantity' => 0.5],
            ['name' => 'deli salami', 'category' => 'deli', 'unit' => 'lb', 'default_quantity' => 0.5],
            ['name' => 'deli chicken', 'category' => 'deli', 'unit' => 'lb', 'default_quantity' => 0.5],
            ['name' => 'rotisserie chicken', 'category' => 'deli', 'unit' => 'whole', 'default_quantity' => 1],

            // Beverages
            ['name' => 'orange juice', 'category' => 'beverages', 'unit' => 'gallon', 'default_quantity' => 1],
            ['name' => 'apple juice', 'category' => 'beverages', 'unit' => 'gallon', 'default_quantity' => 1],
            ['name' => 'cranberry juice', 'category' => 'beverages', 'unit' => 'oz', 'default_quantity' => 64],
            ['name' => 'soda', 'category' => 'beverages', 'unit' => 'liter', 'default_quantity' => 2],
            ['name' => 'water', 'category' => 'beverages', 'unit' => 'gallon', 'default_quantity' => 1],
            ['name' => 'sparkling water', 'category' => 'beverages', 'unit' => 'oz', 'default_quantity' => 12],
            ['name' => 'lemonade', 'category' => 'beverages', 'unit' => 'oz', 'default_quantity' => 64],
            ['name' => 'iced tea', 'category' => 'beverages', 'unit' => 'oz', 'default_quantity' => 64],
            ['name' => 'beer', 'category' => 'beverages', 'unit' => 'oz', 'default_quantity' => 12],
            ['name' => 'wine', 'category' => 'beverages', 'unit' => 'ml', 'default_quantity' => 750],
            ['name' => 'sports drink', 'category' => 'beverages', 'unit' => 'oz', 'default_quantity' => 32],
            ['name' => 'energy drink', 'category' => 'beverages', 'unit' => 'oz', 'default_quantity' => 16],
            ['name' => 'almond milk', 'category' => 'beverages', 'unit' => 'gallon', 'default_quantity' => 0.5],
            ['name' => 'soy milk', 'category' => 'beverages', 'unit' => 'gallon', 'default_quantity' => 0.5],
            ['name' => 'oat milk', 'category' => 'beverages', 'unit' => 'gallon', 'default_quantity' => 0.5],
        ];

        foreach ($items as $item) {
            \App\Models\CommonItemTemplate::create($item);
        }

        $this->command->info('Seeded '.count($items).' common item templates');
    }
}
