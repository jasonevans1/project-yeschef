<?php

declare(strict_types=1);

use App\Services\QuantityFormatter;

it('formats zero point five as one half unicode fraction', function () {
    expect(QuantityFormatter::format(0.5))->toBe('½');
});

it('formats zero point two five as one quarter unicode fraction', function () {
    expect(QuantityFormatter::format(0.25))->toBe('¼');
});

it('formats zero point three three three as one third unicode fraction', function () {
    expect(QuantityFormatter::format(0.333))->toBe('⅓');
});

it('formats zero point seven five as three quarters unicode fraction', function () {
    expect(QuantityFormatter::format(0.75))->toBe('¾');
});

it('formats zero point six six seven as two thirds unicode fraction', function () {
    expect(QuantityFormatter::format(0.667))->toBe('⅔');
});

it('formats one point five as mixed number one and one half', function () {
    expect(QuantityFormatter::format(1.5))->toBe('1½');
});

it('formats two point three three three as mixed number two and one third', function () {
    expect(QuantityFormatter::format(2.333))->toBe('2⅓');
});

it('formats whole numbers without fraction part', function () {
    expect(QuantityFormatter::format(2.0))->toBe('2');
    expect(QuantityFormatter::format(3.0))->toBe('3');
});

it('formats zero point one two five as one eighth', function () {
    expect(QuantityFormatter::format(0.125))->toBe('⅛');
});

it('formats zero point eight seven five as seven eighths', function () {
    expect(QuantityFormatter::format(0.875))->toBe('⅞');
});

it('returns decimal string for values that do not map to clean fractions', function () {
    expect(QuantityFormatter::format(0.1))->toBe('0.1');
});

it('returns null for null input', function () {
    expect(QuantityFormatter::format(null))->toBeNull();
});

it('handles floating point imprecision near fraction boundaries', function () {
    expect(QuantityFormatter::format(0.4999999))->toBe('½');
    expect(QuantityFormatter::format(0.2499999))->toBe('¼');
    expect(QuantityFormatter::format(0.7499999))->toBe('¾');
});
