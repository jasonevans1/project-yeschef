<?php

use App\Services\ServingSizeScaler;
use Illuminate\Support\Collection;

test('scales quantity by multiplier - 1.5x', function () {
    $scaler = new ServingSizeScaler;

    expect($scaler->scale(2.0, 1.5))->toBe(3.0);
});

test('scales quantity by multiplier - 2x', function () {
    $scaler = new ServingSizeScaler;

    expect($scaler->scale(3.0, 2.0))->toBe(6.0);
});

test('scales quantity by multiplier - 0.5x', function () {
    $scaler = new ServingSizeScaler;

    expect($scaler->scale(4.0, 0.5))->toBe(2.0);
});

test('handles fractional results', function () {
    $scaler = new ServingSizeScaler;

    expect($scaler->scale(1.0, 0.33))->toEqualWithDelta(0.33, 0.01);
    expect($scaler->scale(3.0, 0.67))->toEqualWithDelta(2.01, 0.01);
});

test('preserves unit - scaling does not change unit', function () {
    $scaler = new ServingSizeScaler;

    // The scale method only scales quantity, not unit
    $result = $scaler->scale(2.0, 1.5);
    expect($result)->toBe(3.0);
});

test('handles zero quantity', function () {
    $scaler = new ServingSizeScaler;

    expect($scaler->scale(0.0, 2.0))->toBe(0.0);
});

test('handles negative quantity', function () {
    $scaler = new ServingSizeScaler;

    expect($scaler->scale(-2.0, 1.5))->toBe(-3.0);
});

test('scales collection of ingredients', function () {
    $scaler = new ServingSizeScaler;

    $ingredients = collect([
        ['name' => 'flour', 'quantity' => 2.0, 'unit' => 'cups'],
        ['name' => 'sugar', 'quantity' => 1.0, 'unit' => 'cup'],
        ['name' => 'eggs', 'quantity' => 3.0, 'unit' => 'whole'],
    ]);

    $scaled = $scaler->scaleIngredients($ingredients, 1.5);

    expect($scaled)->toBeInstanceOf(Collection::class);
    expect($scaled)->toHaveCount(3);
    expect($scaled[0]['quantity'])->toBe(3.0);
    expect($scaled[0]['name'])->toBe('flour');
    expect($scaled[0]['unit'])->toBe('cups');
    expect($scaled[1]['quantity'])->toBe(1.5);
    expect($scaled[2]['quantity'])->toBe(4.5);
});

test('scales empty collection returns empty collection', function () {
    $scaler = new ServingSizeScaler;

    $ingredients = collect([]);

    $scaled = $scaler->scaleIngredients($ingredients, 2.0);

    expect($scaled)->toBeInstanceOf(Collection::class);
    expect($scaled)->toHaveCount(0);
});

test('scaling with multiplier 1.0 returns original quantities', function () {
    $scaler = new ServingSizeScaler;

    $ingredients = collect([
        ['name' => 'flour', 'quantity' => 2.0, 'unit' => 'cups'],
        ['name' => 'sugar', 'quantity' => 1.0, 'unit' => 'cup'],
    ]);

    $scaled = $scaler->scaleIngredients($ingredients, 1.0);

    expect($scaled[0]['quantity'])->toBe(2.0);
    expect($scaled[1]['quantity'])->toBe(1.0);
});

test('scales very small quantities', function () {
    $scaler = new ServingSizeScaler;

    expect($scaler->scale(0.25, 2.0))->toBe(0.5);
    expect($scaler->scale(0.125, 4.0))->toBe(0.5);
});

test('scales very large quantities', function () {
    $scaler = new ServingSizeScaler;

    expect($scaler->scale(1000.0, 2.5))->toBe(2500.0);
});
