<?php

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Services\IngredientAggregator;
use App\Services\UnitConverter;

test('aggregates identical ingredients with same unit', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY],
        ['name' => 'milk', 'quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1);
    expect($result->first()['name'])->toBe('milk');
    expect($result->first()['quantity'])->toBe(3.0);
    expect($result->first()['unit'])->toBe(MeasurementUnit::CUP);
});

test('aggregates identical ingredients with different compatible units', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY],
        ['name' => 'milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::PINT, 'category' => IngredientCategory::DAIRY],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1);
    expect($result->first()['name'])->toBe('milk');
    // 1 cup + 1 pint = 1 cup + 2 cups = 3 cups
    expect($result->first()['quantity'])->toBe(3.0);
    expect($result->first()['unit'])->toBe(MeasurementUnit::CUP);
});

test('keeps separate ingredients with incompatible units', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'flour', 'quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::PANTRY],
        ['name' => 'flour', 'quantity' => 4.0, 'unit' => MeasurementUnit::OZ, 'category' => IngredientCategory::PANTRY],
    ]);

    $result = $aggregator->aggregate($items);

    // Should keep separate because volume and weight are incompatible
    expect($result)->toHaveCount(2);
});

test('handles non-standard measurements', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'salt', 'quantity' => 1.0, 'unit' => MeasurementUnit::PINCH, 'category' => IngredientCategory::PANTRY],
        ['name' => 'salt', 'quantity' => 2.0, 'unit' => MeasurementUnit::PINCH, 'category' => IngredientCategory::PANTRY],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1);
    expect($result->first()['quantity'])->toBe(3.0);
    expect($result->first()['unit'])->toBe(MeasurementUnit::PINCH);
});

test('preserves category information', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'chicken', 'quantity' => 1.0, 'unit' => MeasurementUnit::LB, 'category' => IngredientCategory::MEAT],
        ['name' => 'chicken', 'quantity' => 8.0, 'unit' => MeasurementUnit::OZ, 'category' => IngredientCategory::MEAT],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1);
    expect($result->first()['category'])->toBe(IngredientCategory::MEAT);
    // 1 lb + 8 oz = 16 oz + 8 oz = 24 oz = 1.5 lb
    expect($result->first()['quantity'])->toBe(1.5);
    expect($result->first()['unit'])->toBe(MeasurementUnit::LB);
});

test('handles empty input', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(0);
});

test('handles single item', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'eggs', 'quantity' => 2.0, 'unit' => MeasurementUnit::WHOLE, 'category' => IngredientCategory::DAIRY],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1);
    expect($result->first()['quantity'])->toBe(2.0);
});

test('aggregates multiple different ingredients', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY],
        ['name' => 'flour', 'quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::PANTRY],
        ['name' => 'eggs', 'quantity' => 3.0, 'unit' => MeasurementUnit::WHOLE, 'category' => IngredientCategory::DAIRY],
        ['name' => 'milk', 'quantity' => 0.5, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(3);

    $milk = $result->firstWhere('name', 'milk');
    expect($milk['quantity'])->toBe(1.5);

    $flour = $result->firstWhere('name', 'flour');
    expect($flour['quantity'])->toBe(2.0);

    $eggs = $result->firstWhere('name', 'eggs');
    expect($eggs['quantity'])->toBe(3.0);
});

test('handles case-insensitive ingredient names', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'Milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY],
        ['name' => 'milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY],
        ['name' => 'MILK', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1);
    expect($result->first()['quantity'])->toBe(3.0);
});

test('aggregates complex unit conversions', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'broth', 'quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::PANTRY],
        ['name' => 'broth', 'quantity' => 1.0, 'unit' => MeasurementUnit::PINT, 'category' => IngredientCategory::PANTRY],
        ['name' => 'broth', 'quantity' => 8.0, 'unit' => MeasurementUnit::FL_OZ, 'category' => IngredientCategory::PANTRY],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1);
    // 2 cups + 1 pint + 8 fl oz = 2 cups + 2 cups + 1 cup = 5 cups
    expect($result->first()['quantity'])->toBe(5.0);
    expect($result->first()['unit'])->toBe(MeasurementUnit::CUP);
});
