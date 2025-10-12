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
}
