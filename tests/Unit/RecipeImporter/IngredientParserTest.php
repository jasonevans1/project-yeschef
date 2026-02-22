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
