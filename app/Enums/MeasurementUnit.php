<?php

namespace App\Enums;

enum MeasurementUnit: string
{
    // Volume
    case TSP = 'tsp';
    case TBSP = 'tbsp';
    case FL_OZ = 'fl_oz';
    case CUP = 'cup';
    case PINT = 'pint';
    case QUART = 'quart';
    case GALLON = 'gallon';
    case ML = 'ml';
    case LITER = 'liter';

    // Weight
    case OZ = 'oz';
    case LB = 'lb';
    case GRAM = 'gram';
    case KG = 'kg';

    // Count
    case WHOLE = 'whole';
    case CLOVE = 'clove';
    case SLICE = 'slice';
    case PIECE = 'piece';

    // Non-standard
    case PINCH = 'pinch';
    case DASH = 'dash';
    case TO_TASTE = 'to_taste';

    case JAR = 'jar';
    case CAN = 'can';
    case BOX = 'box';
    case BAG = 'bag';
    case BOTTLE = 'bottle';
    case PACKAGE = 'package';
    case CONTAINER = 'container';

    public function label(): string
    {
        return match ($this) {
            self::TSP => 'tsp',
            self::TBSP => 'tbsp',
            self::FL_OZ => 'fl oz',
            self::CUP => 'cup',
            self::PINT => 'pint',
            self::QUART => 'quart',
            self::GALLON => 'gallon',
            self::ML => 'ml',
            self::LITER => 'liter',
            self::OZ => 'oz',
            self::LB => 'lb',
            self::GRAM => 'gram',
            self::KG => 'kg',
            self::WHOLE => 'whole',
            self::CLOVE => 'clove',
            self::SLICE => 'slice',
            self::PIECE => 'piece',
            self::PINCH => 'pinch',
            self::DASH => 'dash',
            self::TO_TASTE => 'to taste',
            self::JAR => 'jar',
            self::CAN => 'can',
            self::BOX => 'box',
            self::BAG => 'bag',
            self::BOTTLE => 'bottle',
            self::PACKAGE => 'package',
            self::CONTAINER => 'container',
        };
    }
}
