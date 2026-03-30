<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;

it('has exactly 19 cases', function () {
    expect(IngredientCategory::cases())->toHaveCount(19);
});

it('does not have a PANTRY case', function () {
    $values = array_map(fn ($case) => $case->value, IngredientCategory::cases());
    expect($values)->not->toContain('pantry');
});

it('has a SOUPS_AND_CANNED_GOODS case with value soups-and-canned-goods', function () {
    expect(IngredientCategory::SOUPS_AND_CANNED_GOODS->value)->toBe('soups-and-canned-goods');
});

it('has a WINE_BEER_AND_SPIRITS case with value wine-beer-and-spirits', function () {
    expect(IngredientCategory::WINE_BEER_AND_SPIRITS->value)->toBe('wine-beer-and-spirits');
});

it('returns a human readable label for single word values', function () {
    expect(IngredientCategory::PRODUCE->label())->toBe('Produce')
        ->and(IngredientCategory::DAIRY->label())->toBe('Dairy')
        ->and(IngredientCategory::MEAT->label())->toBe('Meat');
});

it('returns a human readable label for hyphenated multi-word values', function () {
    expect(IngredientCategory::SOUPS_AND_CANNED_GOODS->label())->toBe('Soups And Canned Goods')
        ->and(IngredientCategory::WINE_BEER_AND_SPIRITS->label())->toBe('Wine Beer And Spirits')
        ->and(IngredientCategory::CONDIMENTS_AND_DRESSINGS->label())->toBe('Condiments And Dressings');
});
