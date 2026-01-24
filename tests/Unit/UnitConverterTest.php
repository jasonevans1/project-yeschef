<?php

use App\Enums\MeasurementUnit;
use App\Services\UnitConverter;

test('converts cups to fluid ounces', function () {
    $converter = new UnitConverter;

    expect($converter->convert(2, MeasurementUnit::CUP, MeasurementUnit::FL_OZ))->toBe(16.0);
});

test('converts pints to cups', function () {
    $converter = new UnitConverter;

    expect($converter->convert(2, MeasurementUnit::PINT, MeasurementUnit::CUP))->toBe(4.0);
});

test('converts pounds to ounces', function () {
    $converter = new UnitConverter;

    expect($converter->convert(2, MeasurementUnit::LB, MeasurementUnit::OZ))->toBe(32.0);
});

test('converts metric units - liters to milliliters', function () {
    $converter = new UnitConverter;

    expect($converter->convert(2, MeasurementUnit::LITER, MeasurementUnit::ML))->toEqualWithDelta(2000.0, 0.01);
});

test('converts metric units - kilograms to grams', function () {
    $converter = new UnitConverter;

    expect($converter->convert(1.5, MeasurementUnit::KG, MeasurementUnit::GRAM))->toBe(1500.0);
});

test('handles same-unit conversion by returning original quantity', function () {
    $converter = new UnitConverter;

    expect($converter->convert(5, MeasurementUnit::CUP, MeasurementUnit::CUP))->toBe(5.0);
    expect($converter->convert(3.5, MeasurementUnit::OZ, MeasurementUnit::OZ))->toBe(3.5);
});

test('throws exception for incompatible unit types - volume to weight', function () {
    $converter = new UnitConverter;

    expect(fn () => $converter->convert(2, MeasurementUnit::CUP, MeasurementUnit::OZ))
        ->toThrow(InvalidArgumentException::class, 'Cannot convert between volume and weight units');
});

test('throws exception for incompatible unit types - weight to volume', function () {
    $converter = new UnitConverter;

    expect(fn () => $converter->convert(2, MeasurementUnit::LB, MeasurementUnit::FL_OZ))
        ->toThrow(InvalidArgumentException::class, 'Cannot convert between volume and weight units');
});

test('handles zero quantity', function () {
    $converter = new UnitConverter;

    expect($converter->convert(0, MeasurementUnit::CUP, MeasurementUnit::FL_OZ))->toBe(0.0);
});

test('handles negative quantity', function () {
    $converter = new UnitConverter;

    expect($converter->convert(-2, MeasurementUnit::CUP, MeasurementUnit::FL_OZ))->toBe(-16.0);
});

test('handles very large numbers', function () {
    $converter = new UnitConverter;

    expect($converter->convert(10000, MeasurementUnit::CUP, MeasurementUnit::FL_OZ))->toBe(80000.0);
});

test('converts tablespoons to teaspoons', function () {
    $converter = new UnitConverter;

    expect($converter->convert(2, MeasurementUnit::TBSP, MeasurementUnit::TSP))->toEqualWithDelta(6.0, 0.01);
});

test('converts teaspoons to fluid ounces', function () {
    $converter = new UnitConverter;

    expect($converter->convert(6, MeasurementUnit::TSP, MeasurementUnit::FL_OZ))->toEqualWithDelta(1.0, 0.01);
});

test('converts gallons to cups', function () {
    $converter = new UnitConverter;

    expect($converter->convert(1, MeasurementUnit::GALLON, MeasurementUnit::CUP))->toBe(16.0);
});

test('converts quarts to pints', function () {
    $converter = new UnitConverter;

    expect($converter->convert(2, MeasurementUnit::QUART, MeasurementUnit::PINT))->toBe(4.0);
});

test('throws exception when converting count units', function () {
    $converter = new UnitConverter;

    expect(fn () => $converter->convert(2, MeasurementUnit::WHOLE, MeasurementUnit::PIECE))
        ->toThrow(InvalidArgumentException::class, 'Cannot convert count-based units');
});

test('throws exception when converting non-standard units', function () {
    $converter = new UnitConverter;

    expect(fn () => $converter->convert(2, MeasurementUnit::PINCH, MeasurementUnit::DASH))
        ->toThrow(InvalidArgumentException::class, 'Cannot convert non-standard units');
});
