<?php

namespace App\Enums;

enum IngredientCategory: string
{
    case PRODUCE = 'produce';
    case DAIRY = 'dairy';
    case MEAT = 'meat';
    case SEAFOOD = 'seafood';
    case PANTRY = 'pantry';
    case FROZEN = 'frozen';
    case BAKERY = 'bakery';
    case DELI = 'deli';
    case BEVERAGES = 'beverages';
    case OTHER = 'other';
}
