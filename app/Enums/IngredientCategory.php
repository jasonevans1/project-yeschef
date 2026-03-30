<?php

namespace App\Enums;

enum IngredientCategory: string
{
    case BAKERY = 'bakery';
    case BREAKFAST = 'breakfast';
    case BEVERAGES = 'beverages';
    case CONDIMENTS_AND_DRESSINGS = 'condiments-and-dressings';
    case COOKING_AND_BAKING = 'cooking-and-baking';
    case DAIRY = 'dairy';
    case DELI = 'deli';
    case FROZEN = 'frozen';
    case GRAINS_AND_PASTA = 'grains-and-pasta';
    case HEALTH_AND_PERSONAL_CARE = 'health-and-personal-care';
    case HOUSEHOLD_AND_CLEANING = 'household-and-cleaning';
    case MEAT = 'meat';
    case OTHER = 'other';
    case PET_SUPPLIES = 'pet-supplies';
    case PRODUCE = 'produce';
    case SEAFOOD = 'seafood';
    case SNACKS = 'snacks';
    case SOUPS_AND_CANNED_GOODS = 'soups-and-canned-goods';
    case WINE_BEER_AND_SPIRITS = 'wine-beer-and-spirits';

    public function label(): string
    {
        return implode(' ', array_map('ucfirst', explode('-', $this->value)));
    }
}
