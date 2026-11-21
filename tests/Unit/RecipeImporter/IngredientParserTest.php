<?php

declare(strict_types=1);

use App\Services\RecipeImporter\IngredientParser;

beforeEach(function () {
    $this->parser = new IngredientParser;
});

test('strips single trailing parenthetical from name', function () {
    $result = $this->parser->parse('1 cup spaghetti (broken in half)');

    expect($result['name'])->toBe('spaghetti')
        ->and($result['original'])->toBe('1 cup spaghetti (broken in half)');
});

test('strips multiple parentheticals from name', function () {
    $result = $this->parser->parse('2 tbsp olive oil (extra virgin) (or butter)');

    expect($result['name'])->toBe('olive oil')
        ->and($result['original'])->toBe('2 tbsp olive oil (extra virgin) (or butter)');
});

test('leaves name unchanged when no parenthetical present', function () {
    $result = $this->parser->parse('3 eggs');

    expect($result['name'])->toBe('eggs');
});

test('strips parenthetical when no quantity present', function () {
    $result = $this->parser->parse('garlic (peeled)');

    expect($result['name'])->toBe('garlic')
        ->and($result['original'])->toBe('garlic (peeled)');
});

test('strips parenthetical mid-name and keeps trailing words', function () {
    $result = $this->parser->parse('2 lbs chicken breast (bone-in), sliced');

    expect($result['name'])->toBe('chicken breast, sliced');
});

test('preserves original string unchanged', function () {
    $result = $this->parser->parse('1 tsp vanilla extract (pure)');

    expect($result['original'])->toBe('1 tsp vanilla extract (pure)')
        ->and($result['name'])->toBe('vanilla extract');
});

dataset('unit variations', function () {
    $map = [
        'teaspoon' => 'tsp', 'teaspoons' => 'tsp', 'tsp' => 'tsp', 't' => 'tsp',
        'tablespoon' => 'tbsp', 'tablespoons' => 'tbsp', 'tbsp' => 'tbsp', 'tbs' => 'tbsp', 'tb' => 'tbsp',
        'fluid ounce' => 'fl_oz', 'fluid ounces' => 'fl_oz', 'fl oz' => 'fl_oz', 'fl. oz' => 'fl_oz', 'floz' => 'fl_oz',
        'cup' => 'cup', 'cups' => 'cup', 'c' => 'cup',
        'pint' => 'pint', 'pints' => 'pint', 'pt' => 'pint',
        'quart' => 'quart', 'quarts' => 'quart', 'qt' => 'quart',
        'gallon' => 'gallon', 'gallons' => 'gallon', 'gal' => 'gallon',
        'milliliter' => 'ml', 'milliliters' => 'ml', 'ml' => 'ml',
        'liter' => 'liter', 'liters' => 'liter', 'l' => 'liter',
        'ounce' => 'oz', 'ounces' => 'oz', 'oz' => 'oz',
        'pound' => 'lb', 'pounds' => 'lb', 'lb' => 'lb', 'lbs' => 'lb',
        'gram' => 'gram', 'grams' => 'gram', 'g' => 'gram',
        'kilogram' => 'kg', 'kilograms' => 'kg', 'kg' => 'kg',
        'whole' => 'whole',
        'clove' => 'clove', 'cloves' => 'clove',
        'slice' => 'slice', 'slices' => 'slice',
        'piece' => 'piece', 'pieces' => 'piece',
        'pinch' => 'pinch', 'pinches' => 'pinch',
        'dash' => 'dash', 'dashes' => 'dash',
        'to taste' => 'to_taste',
    ];

    foreach ($map as $text => $value) {
        yield $text => [$text, $value];
    }
});

test('recognises every unit variation', function (string $text, string $value) {
    $result = $this->parser->parse("2 {$text} item");

    expect($result['unit']?->value)->toBe($value)
        ->and($result['name'])->toBe('item')
        ->and($result['quantity'])->toBe(2.0);
})->with('unit variations');

test('recognises units case-insensitively', function () {
    $result = $this->parser->parse('2 CUPS Flour');

    expect($result['unit']?->value)->toBe('cup')
        ->and($result['name'])->toBe('Flour');
});

test('does not treat a unit prefix as a unit', function () {
    $result = $this->parser->parse('2 cupboards');

    expect($result['unit'])->toBeNull()
        ->and($result['name'])->toBe('cupboards');
});

test('unit at end of text falls back to original as name', function () {
    $result = $this->parser->parse('2 cups');

    expect($result['unit']?->value)->toBe('cup')
        ->and($result['name'])->toBe('2 cups');
});

test('trims surrounding whitespace from original', function () {
    $result = $this->parser->parse("  2 cups flour \n");

    expect($result['original'])->toBe('2 cups flour')
        ->and($result['name'])->toBe('flour');

    $result = $this->parser->parse('  garlic ');
    expect($result['original'])->toBe('garlic')
        ->and($result['quantity'])->toBeNull()
        ->and($result['unit'])->toBeNull();
});

test('parses mixed numbers', function () {
    expect($this->parser->parse('2 3/4 cups flour')['quantity'])->toBe(2.75)
        ->and($this->parser->parse('1 1/2 cups flour')['quantity'])->toBe(1.5);
});

test('parses fractions, decimals and whole numbers', function () {
    expect($this->parser->parse('3/4 cup milk')['quantity'])->toBe(0.75)
        ->and($this->parser->parse('2.5 cups milk')['quantity'])->toBe(2.5)
        ->and($this->parser->parse('7 eggs')['quantity'])->toBe(7.0);
});

test('zero denominators give null quantity', function () {
    $fraction = $this->parser->parse('1/0 cup milk');
    $mixed = $this->parser->parse('1 1/0 cup milk');

    expect($fraction['quantity'])->toBeNull()
        ->and($mixed['quantity'])->toBeNull()
        ->and($mixed['unit']?->value)->toBe('cup');
});

test('invalid utf-8 input does not throw', function () {
    $result = $this->parser->parse("2 cups \xB1");

    expect($result['name'])->toBeString();
});
