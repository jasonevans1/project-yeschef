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
