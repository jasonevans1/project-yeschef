<?php

declare(strict_types=1);

namespace App\Services\RecipeImporter;

use App\Enums\MeasurementUnit;

class IngredientParser
{
    /**
     * Parse an ingredient string into quantity, unit, and name components.
     *
     * @return array{quantity: float|null, unit: MeasurementUnit|null, name: string, original: string}
     */
    public function parse(string $ingredientText): array
    {
        $original = trim($ingredientText);

        // Pattern to match quantity at the start
        // Handles: mixed numbers (1 1/2), fractions (1/2), decimals (2.5), whole numbers (3)
        $quantityPattern = '/^(\d+\s+\d+\/\d+|\d+\/\d+|\d+\.\d+|\d+)/';

        if (! preg_match($quantityPattern, $original, $quantityMatch)) {
            // No quantity found, return original as name
            return [
                'quantity' => null,
                'unit' => null,
                'name' => $original,
                'original' => $original,
            ];
        }

        $quantityString = trim($quantityMatch[1]);
        $remainingText = trim(substr($original, strlen($quantityString)));

        // Try to extract unit from the beginning of remaining text
        $unit = $this->extractUnit($remainingText);

        if ($unit) {
            // Remove the matched unit from remaining text to get the ingredient name
            $unitPattern = '/^'.preg_quote($unit['matched'], '/').'\s*/i';
            $name = trim(preg_replace($unitPattern, '', $remainingText));
        } else {
            // No unit found, remaining text is the ingredient name
            $name = $remainingText;
        }

        return [
            'quantity' => $this->parseQuantity($quantityString),
            'unit' => $unit ? $unit['enum'] : null,
            'name' => $name ?: $original,
            'original' => $original,
        ];
    }

    /**
     * Extract unit from the beginning of text.
     *
     * @return array{matched: string, enum: MeasurementUnit}|null
     */
    protected function extractUnit(string $text): ?array
    {
        $text = trim($text);

        // Get all unit variations sorted by length (longest first to match "tablespoon" before "table")
        $unitMap = $this->getUnitMap();
        $sortedUnits = array_keys($unitMap);
        usort($sortedUnits, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($sortedUnits as $unitVariation) {
            // Check if text starts with this unit variation (case-insensitive)
            if (stripos($text, $unitVariation) === 0) {
                // Make sure it's followed by a space or is at the end
                $afterUnit = substr($text, strlen($unitVariation));
                if ($afterUnit === '' || preg_match('/^\s/', $afterUnit)) {
                    return [
                        'matched' => $unitVariation,
                        'enum' => $unitMap[$unitVariation],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Get the unit mapping array.
     *
     * @return array<string, MeasurementUnit>
     */
    protected function getUnitMap(): array
    {
        return [
            // Volume
            'teaspoon' => MeasurementUnit::TSP,
            'teaspoons' => MeasurementUnit::TSP,
            'tsp' => MeasurementUnit::TSP,
            't' => MeasurementUnit::TSP,
            'tablespoon' => MeasurementUnit::TBSP,
            'tablespoons' => MeasurementUnit::TBSP,
            'tbsp' => MeasurementUnit::TBSP,
            'tbs' => MeasurementUnit::TBSP,
            'tb' => MeasurementUnit::TBSP,
            'fluid ounce' => MeasurementUnit::FL_OZ,
            'fluid ounces' => MeasurementUnit::FL_OZ,
            'fl oz' => MeasurementUnit::FL_OZ,
            'fl. oz' => MeasurementUnit::FL_OZ,
            'floz' => MeasurementUnit::FL_OZ,
            'cup' => MeasurementUnit::CUP,
            'cups' => MeasurementUnit::CUP,
            'c' => MeasurementUnit::CUP,
            'pint' => MeasurementUnit::PINT,
            'pints' => MeasurementUnit::PINT,
            'pt' => MeasurementUnit::PINT,
            'quart' => MeasurementUnit::QUART,
            'quarts' => MeasurementUnit::QUART,
            'qt' => MeasurementUnit::QUART,
            'gallon' => MeasurementUnit::GALLON,
            'gallons' => MeasurementUnit::GALLON,
            'gal' => MeasurementUnit::GALLON,
            'milliliter' => MeasurementUnit::ML,
            'milliliters' => MeasurementUnit::ML,
            'ml' => MeasurementUnit::ML,
            'liter' => MeasurementUnit::LITER,
            'liters' => MeasurementUnit::LITER,
            'l' => MeasurementUnit::LITER,

            // Weight
            'ounce' => MeasurementUnit::OZ,
            'ounces' => MeasurementUnit::OZ,
            'oz' => MeasurementUnit::OZ,
            'pound' => MeasurementUnit::LB,
            'pounds' => MeasurementUnit::LB,
            'lb' => MeasurementUnit::LB,
            'lbs' => MeasurementUnit::LB,
            'gram' => MeasurementUnit::GRAM,
            'grams' => MeasurementUnit::GRAM,
            'g' => MeasurementUnit::GRAM,
            'kilogram' => MeasurementUnit::KG,
            'kilograms' => MeasurementUnit::KG,
            'kg' => MeasurementUnit::KG,

            // Count
            'whole' => MeasurementUnit::WHOLE,
            'clove' => MeasurementUnit::CLOVE,
            'cloves' => MeasurementUnit::CLOVE,
            'slice' => MeasurementUnit::SLICE,
            'slices' => MeasurementUnit::SLICE,
            'piece' => MeasurementUnit::PIECE,
            'pieces' => MeasurementUnit::PIECE,

            // Non-standard
            'pinch' => MeasurementUnit::PINCH,
            'pinches' => MeasurementUnit::PINCH,
            'dash' => MeasurementUnit::DASH,
            'dashes' => MeasurementUnit::DASH,
            'to taste' => MeasurementUnit::TO_TASTE,
        ];
    }

    /**
     * Parse quantity string (handles fractions, decimals, and mixed numbers).
     */
    protected function parseQuantity(string $quantity): ?float
    {
        $quantity = trim($quantity);

        // Handle mixed numbers (e.g., "1 1/2")
        if (preg_match('/^(\d+)\s+(\d+)\/(\d+)$/', $quantity, $matches)) {
            $whole = (int) $matches[1];
            $numerator = (int) $matches[2];
            $denominator = (int) $matches[3];

            if ($denominator === 0) {
                return null;
            }

            return $whole + ($numerator / $denominator);
        }

        // Handle fractions (e.g., "1/2")
        if (preg_match('/^(\d+)\/(\d+)$/', $quantity, $matches)) {
            $numerator = (int) $matches[1];
            $denominator = (int) $matches[2];

            if ($denominator === 0) {
                return null;
            }

            return $numerator / $denominator;
        }

        // Handle decimals and whole numbers
        if (is_numeric($quantity)) {
            return (float) $quantity;
        }

        return null;
    }
}
