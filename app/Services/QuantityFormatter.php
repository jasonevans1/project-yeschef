<?php

namespace App\Services;

class QuantityFormatter
{
    private const EPSILON = 0.02;

    /**
     * Fraction map: eighths numerator => unicode character
     * Key is the numerator when denominator is 8 (e.g., 1 => 1/8, 2 => 2/8 = 1/4)
     *
     * @var array<int, string>
     */
    private const FRACTIONS = [
        1 => '⅛',
        2 => '¼',
        3 => '⅜',
        4 => '½',
        5 => '⅝',
        6 => '¾',
        7 => '⅞',
    ];

    /**
     * Unicode fraction characters mapped to their float values.
     *
     * @var array<string, float>
     */
    private const UNICODE_FRACTIONS = [
        '⅛' => 0.125,
        '¼' => 0.25,
        '⅓' => 1 / 3,
        '⅜' => 0.375,
        '½' => 0.5,
        '⅝' => 0.625,
        '⅔' => 2 / 3,
        '¾' => 0.75,
        '⅞' => 0.875,
    ];

    /**
     * Parse a quantity string (fraction, mixed number, unicode fraction, or decimal) to a float.
     */
    public static function parse(mixed $input): ?float
    {
        if ($input === null || $input === '') {
            return null;
        }

        $input = trim((string) $input);

        if ($input === '') {
            return null;
        }

        // Check for unicode fraction characters (with optional whole number prefix)
        foreach (self::UNICODE_FRACTIONS as $char => $value) {
            if (str_contains($input, $char)) {
                $prefix = str_replace($char, '', $input);
                $whole = $prefix === '' ? 0.0 : (float) $prefix;

                return $whole + $value;
            }
        }

        // ASCII fraction: "1/2" or mixed "1 1/2"
        if (str_contains($input, '/')) {
            $parts = explode('/', $input, 2);
            $denominator = (float) trim($parts[1]);

            if ($denominator == 0) {
                return null;
            }

            $numeratorPart = trim($parts[0]);
            $numeratorTokens = preg_split('/\s+/', $numeratorPart);

            if (count($numeratorTokens) === 2) {
                $whole = (float) $numeratorTokens[0];
                $numerator = (float) $numeratorTokens[1];

                return $whole + ($numerator / $denominator);
            }

            $numerator = (float) $numeratorPart;

            return $numerator / $denominator;
        }

        // Plain numeric
        if (is_numeric($input)) {
            return (float) $input;
        }

        return null;
    }

    /**
     * Format a decimal quantity as a human-readable fraction string.
     */
    public static function format(?float $quantity): ?string
    {
        if ($quantity === null) {
            return null;
        }

        $whole = (int) floor($quantity);
        $fractional = $quantity - $whole;

        $fractionString = self::resolveFraction($fractional);

        if ($fractionString === null) {
            return (string) $quantity;
        }

        if ($fractionString === '') {
            return (string) $whole;
        }

        if ($whole === 0) {
            return $fractionString;
        }

        return $whole.$fractionString;
    }

    /**
     * Resolve the fractional part of a quantity to a unicode fraction string.
     * Returns empty string for whole numbers, null if no clean fraction exists.
     */
    private static function resolveFraction(float $fractional): ?string
    {
        if ($fractional < self::EPSILON) {
            return '';
        }

        if (abs($fractional - 1.0 / 3.0) < self::EPSILON) {
            return '⅓';
        }

        if (abs($fractional - 2.0 / 3.0) < self::EPSILON) {
            return '⅔';
        }

        $roundedEighths = (int) round($fractional * 8);

        if (isset(self::FRACTIONS[$roundedEighths])) {
            $nearestFraction = $roundedEighths / 8.0;
            if (abs($fractional - $nearestFraction) < self::EPSILON) {
                return self::FRACTIONS[$roundedEighths];
            }
        }

        return null;
    }
}
