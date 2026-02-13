<?php

namespace App\Enums;

use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\Recipe;

enum ShareableType: string
{
    case Recipe = 'App\Models\Recipe';
    case MealPlan = 'App\Models\MealPlan';
    case GroceryList = 'App\Models\GroceryList';

    public function modelClass(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::Recipe => 'Recipe',
            self::MealPlan => 'Meal Plan',
            self::GroceryList => 'Grocery List',
        };
    }
}
