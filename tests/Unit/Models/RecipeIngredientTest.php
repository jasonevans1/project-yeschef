<?php

declare(strict_types=1);

use App\Models\RecipeIngredient;

// User Story 1: Whole Number Quantities Tests

test('display_quantity formats whole numbers without decimals', function () {
    $ingredient = new RecipeIngredient(['quantity' => 2.000]);
    expect($ingredient->display_quantity)->toBe('2');
});

test('display_quantity formats single decimal zero', function () {
    $ingredient = new RecipeIngredient(['quantity' => 5.0]);
    expect($ingredient->display_quantity)->toBe('5');
});

test('display_quantity formats large whole numbers', function () {
    $ingredient = new RecipeIngredient(['quantity' => 1000.000]);
    expect($ingredient->display_quantity)->toBe('1000');
});

// User Story 2: Fractional Quantities Tests

test('display_quantity formats fractional with trailing zeros', function () {
    $ingredient = new RecipeIngredient(['quantity' => 1.500]);
    expect($ingredient->display_quantity)->toBe('1.5');
});

test('display_quantity formats fractional with two decimals', function () {
    $ingredient = new RecipeIngredient(['quantity' => 0.750]);
    expect($ingredient->display_quantity)->toBe('0.75');
});

test('display_quantity preserves precise decimals', function () {
    $ingredient = new RecipeIngredient(['quantity' => 0.333]);
    expect($ingredient->display_quantity)->toBe('0.333');
});

test('display_quantity formats mixed precision', function () {
    $ingredient = new RecipeIngredient(['quantity' => 2.125]);
    expect($ingredient->display_quantity)->toBe('2.125');
});

// User Story 3: Edge Case Tests

test('display_quantity returns null for null quantity', function () {
    $ingredient = new RecipeIngredient(['quantity' => null]);
    expect($ingredient->display_quantity)->toBeNull();
});

test('display_quantity formats zero as "0"', function () {
    $ingredient = new RecipeIngredient(['quantity' => 0.000]);
    expect($ingredient->display_quantity)->toBe('0');
});

test('display_quantity preserves very small quantities', function () {
    $ingredient = new RecipeIngredient(['quantity' => 0.001]);
    expect($ingredient->display_quantity)->toBe('0.001');
});

// User Story 1 (009-recipe-servings-multiplier): Scaling Calculation Tests
// Note: These tests verify the formatting pattern that will be used in Alpine.js

test('scaling with multiplier 2.0 doubles quantity correctly', function () {
    $ingredient = new RecipeIngredient(['quantity' => 2.000]);
    $scaled = $ingredient->quantity * 2.0;

    // Verify scaled value
    expect($scaled)->toBe(4.0);

    // Verify formatting (simulating Alpine.js formatQuantity)
    $formatted = (string) number_format($scaled, 3, '.', '');
    $formatted = rtrim($formatted, '0');
    $formatted = rtrim($formatted, '.');
    expect($formatted)->toBe('4');
});

test('scaling with multiplier 0.5 halves quantity correctly', function () {
    $ingredient = new RecipeIngredient(['quantity' => 2.000]);
    $scaled = $ingredient->quantity * 0.5;

    expect($scaled)->toBe(1.0);

    $formatted = (string) number_format($scaled, 3, '.', '');
    $formatted = rtrim($formatted, '0');
    $formatted = rtrim($formatted, '.');
    expect($formatted)->toBe('1');
});

test('scaling null quantity returns null', function () {
    $ingredient = new RecipeIngredient(['quantity' => null]);
    $scaled = $ingredient->quantity === null ? null : $ingredient->quantity * 2.0;

    expect($scaled)->toBeNull();
});

test('scaling with 1.5 multiplier calculates fractional values correctly', function () {
    $ingredient = new RecipeIngredient(['quantity' => 2.000]);
    $scaled = $ingredient->quantity * 1.5;

    expect($scaled)->toBe(3.0);

    $formatted = (string) number_format($scaled, 3, '.', '');
    $formatted = rtrim($formatted, '0');
    $formatted = rtrim($formatted, '.');
    expect($formatted)->toBe('3');
});

test('display formatting removes trailing zeros from scaled values', function () {
    // Test with various scaled results
    $testCases = [
        ['original' => 1.5, 'multiplier' => 2.0, 'expected' => '3'],
        ['original' => 0.75, 'multiplier' => 2.0, 'expected' => '1.5'],
        ['original' => 0.333, 'multiplier' => 3.0, 'expected' => '0.999'],
        ['original' => 2.5, 'multiplier' => 0.5, 'expected' => '1.25'],
    ];

    foreach ($testCases as $case) {
        $scaled = $case['original'] * $case['multiplier'];
        $formatted = (string) number_format($scaled, 3, '.', '');
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, '.');

        expect($formatted)->toBe($case['expected']);
    }
});
