<?php

declare(strict_types=1);

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

    expect($result)->toHaveCount(1)
        ->and($result->first()['name'])->toBe('milk')
        ->and($result->first()['quantity'])->toBe(3.0)
        ->and($result->first()['unit'])->toBe(MeasurementUnit::CUP);
});

test('aggregates identical ingredients with different compatible units', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY],
        ['name' => 'milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::PINT, 'category' => IngredientCategory::DAIRY],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1)
        ->and($result->first()['name'])->toBe('milk')
        // 1 cup + 1 pint = 1 cup + 2 cups = 3 cups
        ->and($result->first()['quantity'])->toBe(3.0)
        ->and($result->first()['unit'])->toBe(MeasurementUnit::CUP);
});

test('keeps separate ingredients with incompatible units', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'flour', 'quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS],
        ['name' => 'flour', 'quantity' => 4.0, 'unit' => MeasurementUnit::OZ, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS],
    ]);

    $result = $aggregator->aggregate($items);

    // Should keep separate because volume and weight are incompatible
    expect($result)->toHaveCount(2);
});

test('handles non-standard measurements', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'salt', 'quantity' => 1.0, 'unit' => MeasurementUnit::PINCH, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS],
        ['name' => 'salt', 'quantity' => 2.0, 'unit' => MeasurementUnit::PINCH, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1)
        ->and($result->first()['quantity'])->toBe(3.0)
        ->and($result->first()['unit'])->toBe(MeasurementUnit::PINCH);
});

test('preserves category information', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'chicken', 'quantity' => 1.0, 'unit' => MeasurementUnit::LB, 'category' => IngredientCategory::MEAT],
        ['name' => 'chicken', 'quantity' => 8.0, 'unit' => MeasurementUnit::OZ, 'category' => IngredientCategory::MEAT],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1)
        ->and($result->first()['category'])->toBe(IngredientCategory::MEAT)
        // 1 lb + 8 oz = 16 oz + 8 oz = 24 oz = 1.5 lb
        ->and($result->first()['quantity'])->toBe(1.5)
        ->and($result->first()['unit'])->toBe(MeasurementUnit::LB);
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

    expect($result)->toHaveCount(1)
        ->and($result->first()['quantity'])->toBe(2.0);
});

test('aggregates multiple different ingredients', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY],
        ['name' => 'flour', 'quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS],
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

    expect($result)->toHaveCount(1)
        ->and($result->first()['quantity'])->toBe(3.0);
});

test('aggregates complex unit conversions', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'broth', 'quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS],
        ['name' => 'broth', 'quantity' => 1.0, 'unit' => MeasurementUnit::PINT, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS],
        ['name' => 'broth', 'quantity' => 8.0, 'unit' => MeasurementUnit::FL_OZ, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1)
        // 2 cups + 1 pint + 8 fl oz = 2 cups + 2 cups + 1 cup = 5 cups
        ->and($result->first()['quantity'])->toBe(5.0)
        ->and($result->first()['unit'])->toBe(MeasurementUnit::CUP);
});

it('strips the singular recipe_id input key from the output (only recipe_ids should remain)', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    // Test all three output paths: single-item group, single-subgroup, multi-item subgroup
    $items = collect([
        // Single-item group (path 1)
        ['name' => 'pepper', 'quantity' => 1.0, 'unit' => MeasurementUnit::TSP, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS, 'recipe_id' => 100],
        // Multi-item same-name incompatible units → single-subgroup path (path 2)
        ['name' => 'flour', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS, 'recipe_id' => 101],
        ['name' => 'flour', 'quantity' => 4.0, 'unit' => MeasurementUnit::OZ, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS, 'recipe_id' => 102],
        // Multi-item same-unit → aggregateCompatibleItems path (path 3)
        ['name' => 'milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY, 'recipe_id' => 103],
        ['name' => 'milk', 'quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY, 'recipe_id' => 104],
    ]);

    $result = $aggregator->aggregate($items);

    foreach ($result as $item) {
        expect($item)->toHaveKey('recipe_ids')
            ->and($item)->not->toHaveKey('recipe_id');
    }
});

it('handles items without a recipe_id key alongside items that have one', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'sugar', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS, 'recipe_id' => 7],
        ['name' => 'sugar', 'quantity' => 0.5, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS], // no recipe_id key
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1)
        ->and($result->first())->toHaveKey('recipe_ids')
        ->and($result->first()['recipe_ids'])->toBe([7])
        ->and($result->first())->not->toHaveKey('recipe_id');
});

it('produces an empty recipe_ids array when no recipe_id keys are present in the input', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'butter', 'quantity' => 2.0, 'unit' => MeasurementUnit::TBSP, 'category' => IngredientCategory::DAIRY],
        ['name' => 'butter', 'quantity' => 1.0, 'unit' => MeasurementUnit::TBSP, 'category' => IngredientCategory::DAIRY],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1)
        ->and($result->first())->toHaveKey('recipe_ids')
        ->and($result->first()['recipe_ids'])->toBe([]);
});

it('deduplicates recipe_ids when the same recipe appears multiple times', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'salt', 'quantity' => 1.0, 'unit' => MeasurementUnit::TSP, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS, 'recipe_id' => 5],
        ['name' => 'salt', 'quantity' => 0.5, 'unit' => MeasurementUnit::TSP, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS, 'recipe_id' => 5],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1)
        ->and($result->first()['recipe_ids'])->toBe([5]);
});

it('merges recipe_ids across multiple unit-compatible subgroups of the same name (e.g., milk in cups + milk in grams becomes two output rows, each with its own merged recipe_ids)', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    // milk in cups (volume subgroup) and milk in grams (weight subgroup) — incompatible
    $items = collect([
        ['name' => 'milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY, 'recipe_id' => 1],
        ['name' => 'milk', 'quantity' => 0.5, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY, 'recipe_id' => 2],
        ['name' => 'milk', 'quantity' => 100.0, 'unit' => MeasurementUnit::GRAM, 'category' => IngredientCategory::DAIRY, 'recipe_id' => 3],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(2);

    $cupRow = $result->firstWhere('unit', MeasurementUnit::CUP);
    expect($cupRow['recipe_ids'])->toBe([1, 2]);

    $gramRow = $result->firstWhere('unit', MeasurementUnit::GRAM);
    expect($gramRow['recipe_ids'])->toBe([3]);
});

it('merges recipe_ids from multiple compatible items into one array', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'milk', 'quantity' => 1.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY, 'recipe_id' => 1],
        ['name' => 'milk', 'quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::DAIRY, 'recipe_id' => 2],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1)
        ->and($result->first())->toHaveKey('recipe_ids')
        ->and($result->first()['recipe_ids'])->toBe([1, 2])
        ->and($result->first())->not->toHaveKey('recipe_id');
});

it('passes through recipe_id as recipe_ids array for a single compatible-unit subgroup within a multi-item group', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    // milk (cup) and flour (cup) grouped under the same name won't happen,
    // but milk (cup) and milk (oz) will produce two subgroups, each with one item
    $items = collect([
        ['name' => 'flour', 'quantity' => 2.0, 'unit' => MeasurementUnit::CUP, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS, 'recipe_id' => 10],
        ['name' => 'flour', 'quantity' => 4.0, 'unit' => MeasurementUnit::OZ, 'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS, 'recipe_id' => 20],
    ]);

    $result = $aggregator->aggregate($items);

    // Two output rows (incompatible units), each should have recipe_ids
    expect($result)->toHaveCount(2);

    $cupRow = $result->firstWhere('unit', MeasurementUnit::CUP);
    expect($cupRow)->toHaveKey('recipe_ids')
        ->and($cupRow['recipe_ids'])->toBe([10])
        ->and($cupRow)->not->toHaveKey('recipe_id');

    $ozRow = $result->firstWhere('unit', MeasurementUnit::OZ);
    expect($ozRow)->toHaveKey('recipe_ids')
        ->and($ozRow['recipe_ids'])->toBe([20])
        ->and($ozRow)->not->toHaveKey('recipe_id');
});

it('passes through recipe_id as recipe_ids array for a single-item group', function () {
    $converter = new UnitConverter;
    $aggregator = new IngredientAggregator($converter);

    $items = collect([
        ['name' => 'eggs', 'quantity' => 2.0, 'unit' => MeasurementUnit::WHOLE, 'category' => IngredientCategory::DAIRY, 'recipe_id' => 42],
    ]);

    $result = $aggregator->aggregate($items);

    expect($result)->toHaveCount(1)
        ->and($result->first())->toHaveKey('recipe_ids')
        ->and($result->first()['recipe_ids'])->toBe([42])
        ->and($result->first())->not->toHaveKey('recipe_id');
});
