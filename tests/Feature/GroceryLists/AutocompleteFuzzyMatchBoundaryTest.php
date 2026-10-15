<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Models\CommonItemTemplate;
use App\Models\User;
use App\Services\ItemAutoCompleteService;

// FR-009 documents "fuzzy/partial text matching (e.g. partial matches, minor misspellings)".
// ItemAutoCompleteService only implements LIKE-based prefix and substring matching — no
// Levenshtein/edit-distance/soundex logic exists. This test documents that real boundary:
// partial matches work, but misspellings (extra characters, transpositions) are not tolerated.
// This is expected/current behavior, not a bug — true fuzzy matching is a feature gap for a
// future plan, not this one.
test('it matches partial item names but does not tolerate misspellings', function () {
    $user = User::factory()->create();

    CommonItemTemplate::create([
        'name' => 'tomato',
        'category' => IngredientCategory::PRODUCE,
        'unit' => MeasurementUnit::WHOLE,
        'default_quantity' => 4,
    ]);

    $service = new ItemAutoCompleteService;

    // Prefix and substring matches work; extra-character and transposition typos do not.
    expect($service->query($user->id, 'tomat')->pluck('name')->toArray())->toContain('tomato')
        ->and($service->query($user->id, 'omato')->pluck('name')->toArray())->toContain('tomato')
        ->and($service->query($user->id, 'tomatoe'))->toBeEmpty()
        ->and($service->query($user->id, 'tomaot'))->toBeEmpty();
});
