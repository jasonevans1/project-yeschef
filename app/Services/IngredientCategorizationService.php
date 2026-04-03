<?php

namespace App\Services;

use App\Enums\IngredientCategory;
use App\Models\CommonItemTemplate;
use App\Models\UserItemTemplate;

class IngredientCategorizationService
{
    /**
     * Keyword map for category resolution.
     * Wine/beer are mapped to WINE_BEER_AND_SPIRITS (not BEVERAGES).
     *
     * @var array<string, string[]>
     */
    private array $categoryKeywords = [
        'wine-beer-and-spirits' => ['wine', 'beer', 'spirits', 'liquor', 'whiskey', 'vodka', 'rum'],
        'beverages' => ['juice', 'soda', 'water', 'coffee', 'tea'],
        'produce' => ['lettuce', 'tomato', 'onion', 'garlic', 'pepper', 'carrot', 'celery', 'potato', 'spinach', 'broccoli', 'cucumber', 'apple', 'banana', 'orange', 'lemon', 'lime', 'herb', 'basil', 'parsley', 'cilantro'],
        'dairy' => ['milk', 'cheese', 'butter', 'cream', 'yogurt', 'sour cream', 'ricotta', 'mozzarella', 'parmesan', 'cheddar'],
        'meat' => ['chicken', 'beef', 'pork', 'turkey', 'lamb', 'bacon', 'sausage', 'ground beef', 'steak'],
        'seafood' => ['fish', 'salmon', 'tuna', 'shrimp', 'crab', 'lobster', 'cod', 'tilapia'],
        'bakery' => ['bread', 'bun', 'roll', 'tortilla', 'pita', 'bagel'],
        'cooking-and-baking' => ['flour', 'sugar', 'salt', 'oil', 'olive oil', 'vinegar', 'spice', 'cumin', 'paprika', 'oregano'],
        'grains-and-pasta' => ['rice', 'pasta', 'beans'],
        'soups-and-canned-goods' => ['sauce'],
        'frozen' => ['frozen'],
    ];

    /**
     * Resolve the IngredientCategory for a given ingredient name.
     *
     * Resolution order:
     * 1. UserItemTemplate (when $userId > 0)
     * 2. CommonItemTemplate
     * 3. Keyword matching
     * 4. IngredientCategory::OTHER
     *
     * @param  int  $userId  Pass auth()->id() for authenticated contexts; pass 0 as the no-user sentinel.
     */
    public function resolve(string $name, int $userId = 0): IngredientCategory
    {
        $normalized = strtolower($name);

        if ($userId > 0) {
            $userTemplate = UserItemTemplate::query()
                ->where('user_id', $userId)
                ->whereRaw('LOWER(name) = ?', [$normalized])
                ->first();

            if ($userTemplate !== null) {
                return $userTemplate->category;
            }
        }

        $commonTemplate = CommonItemTemplate::query()
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->first();

        if ($commonTemplate !== null) {
            return $commonTemplate->category;
        }

        foreach ($this->categoryKeywords as $categoryValue => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return IngredientCategory::from($categoryValue);
                }
            }
        }

        return IngredientCategory::OTHER;
    }
}
