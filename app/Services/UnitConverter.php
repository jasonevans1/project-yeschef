<?php

namespace App\Services;

use App\Enums\MeasurementUnit;
use InvalidArgumentException;

class UnitConverter
{
    /**
     * Conversion tables to base units
     * - Volume: fluid ounces (fl_oz)
     * - Weight: ounces (oz)
     */
    private const VOLUME_TO_FL_OZ = [
        'tsp' => 0.166667,  // 1 tsp = 1/6 fl oz
        'tbsp' => 0.5,      // 1 tbsp = 0.5 fl oz
        'fl_oz' => 1.0,     // Base unit
        'cup' => 8.0,       // 1 cup = 8 fl oz
        'pint' => 16.0,     // 1 pint = 16 fl oz
        'quart' => 32.0,    // 1 quart = 32 fl oz
        'gallon' => 128.0,  // 1 gallon = 128 fl oz
        'ml' => 0.033814,   // 1 ml ≈ 0.034 fl oz
        'liter' => 33.814,  // 1 liter ≈ 33.8 fl oz
    ];

    private const WEIGHT_TO_OZ = [
        'oz' => 1.0,        // Base unit
        'lb' => 16.0,       // 1 lb = 16 oz
        'gram' => 0.035274, // 1 gram ≈ 0.035 oz
        'kg' => 35.274,     // 1 kg ≈ 35.3 oz
    ];

    private const COUNT_UNITS = ['whole', 'clove', 'slice', 'piece'];

    private const NON_STANDARD_UNITS = ['pinch', 'dash', 'to_taste'];

    /**
     * Convert quantity from one unit to another
     *
     * @param  float  $quantity  The quantity to convert
     * @param  MeasurementUnit  $from  The source unit
     * @param  MeasurementUnit  $to  The target unit
     * @return float The converted quantity
     *
     * @throws InvalidArgumentException If units are incompatible
     */
    public function convert(float $quantity, MeasurementUnit $from, MeasurementUnit $to): float
    {
        // Same unit - no conversion needed
        if ($from === $to) {
            return $quantity;
        }

        $fromValue = $from->value;
        $toValue = $to->value;

        // Check for count units
        if (in_array($fromValue, self::COUNT_UNITS) || in_array($toValue, self::COUNT_UNITS)) {
            throw new InvalidArgumentException('Cannot convert count-based units');
        }

        // Check for non-standard units
        if (in_array($fromValue, self::NON_STANDARD_UNITS) || in_array($toValue, self::NON_STANDARD_UNITS)) {
            throw new InvalidArgumentException('Cannot convert non-standard units');
        }

        // Check if both units are volume
        $fromIsVolume = isset(self::VOLUME_TO_FL_OZ[$fromValue]);
        $toIsVolume = isset(self::VOLUME_TO_FL_OZ[$toValue]);

        // Check if both units are weight
        $fromIsWeight = isset(self::WEIGHT_TO_OZ[$fromValue]);
        $toIsWeight = isset(self::WEIGHT_TO_OZ[$toValue]);

        // Ensure units are of the same type
        if ($fromIsVolume && $toIsWeight) {
            throw new InvalidArgumentException('Cannot convert between volume and weight units');
        }

        if ($fromIsWeight && $toIsVolume) {
            throw new InvalidArgumentException('Cannot convert between volume and weight units');
        }

        // Perform conversion
        if ($fromIsVolume && $toIsVolume) {
            return $this->convertVolume($quantity, $fromValue, $toValue);
        }

        if ($fromIsWeight && $toIsWeight) {
            return $this->convertWeight($quantity, $fromValue, $toValue);
        }

        throw new InvalidArgumentException("Cannot convert from {$fromValue} to {$toValue}");
    }

    /**
     * Convert volume units via base unit (fluid ounces)
     */
    private function convertVolume(float $quantity, string $from, string $to): float
    {
        // Convert to base unit (fl oz)
        $baseQuantity = $quantity * self::VOLUME_TO_FL_OZ[$from];

        // Convert from base unit to target unit
        return $baseQuantity / self::VOLUME_TO_FL_OZ[$to];
    }

    /**
     * Convert weight units via base unit (ounces)
     */
    private function convertWeight(float $quantity, string $from, string $to): float
    {
        // Convert to base unit (oz)
        $baseQuantity = $quantity * self::WEIGHT_TO_OZ[$from];

        // Convert from base unit to target unit
        return $baseQuantity / self::WEIGHT_TO_OZ[$to];
    }
}
