<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Models\CommonItemTemplate;
use App\Models\User;
use App\Models\UserItemTemplate;
use App\Services\ItemAutoCompleteService;

// T035: Test personal history prioritized over common defaults
test('personal history suggestions are prioritized over common defaults', function () {
    $user = User::factory()->create();

    // Create a common template for "almond milk" (dairy)
    CommonItemTemplate::create([
        'name' => 'almond milk',
        'category' => IngredientCategory::DAIRY,
        'unit' => MeasurementUnit::GALLON,
        'default_quantity' => 1,
    ]);

    // Create user's personal template for "almond milk" with different category (beverages)
    UserItemTemplate::create([
        'user_id' => $user->id,
        'name' => 'almond milk',
        'category' => IngredientCategory::BEVERAGES,
        'unit' => MeasurementUnit::QUART,
        'default_quantity' => 2,
        'usage_count' => 5,
        'last_used_at' => now(),
    ]);

    $service = new ItemAutoCompleteService;
    $results = $service->query($user->id, 'alm');

    // User's "almond milk" template should appear (NOT common template due to deduplication)
    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('almond milk')
        ->and($results->first()->category)->toBe(IngredientCategory::BEVERAGES) // User's category
        ->and((float) $results->first()->default_quantity)->toBe(2.0); // User's quantity, not common's
});

// T036: Test most frequent category wins (5x produce > 2x pantry)
test('most frequently used category is suggested for item', function () {
    $user = User::factory()->create();

    // User added "tomato" 5 times as PRODUCE
    UserItemTemplate::create([
        'user_id' => $user->id,
        'name' => 'tomato',
        'category' => IngredientCategory::PRODUCE,
        'unit' => MeasurementUnit::WHOLE,
        'default_quantity' => 4,
        'usage_count' => 5,
        'last_used_at' => now()->subDays(1),
    ]);

    $service = new ItemAutoCompleteService;
    $results = $service->query($user->id, 'tomato');

    expect($results)->toHaveCount(1)
        ->and($results->first()->category)->toBe(IngredientCategory::PRODUCE)
        ->and($results->first()->usage_count)->toBe(5);
});

test('user templates appear before common templates in search results', function () {
    $user = User::factory()->create();

    // Create common templates
    CommonItemTemplate::create([
        'name' => 'carrots',
        'category' => IngredientCategory::PRODUCE,
        'unit' => MeasurementUnit::LB,
        'default_quantity' => 2,
    ]);

    CommonItemTemplate::create([
        'name' => 'celery',
        'category' => IngredientCategory::PRODUCE,
        'unit' => MeasurementUnit::WHOLE,
        'default_quantity' => 1,
    ]);

    // Create user template for "cauliflower" (starts with 'c')
    UserItemTemplate::create([
        'user_id' => $user->id,
        'name' => 'cauliflower',
        'category' => IngredientCategory::PRODUCE,
        'unit' => MeasurementUnit::WHOLE,
        'default_quantity' => 1,
        'usage_count' => 10,
        'last_used_at' => now(),
    ]);

    $service = new ItemAutoCompleteService;
    $results = $service->query($user->id, 'cau');

    // User's cauliflower should be the only result (prefix match)
    expect($results->count())->toBe(1)
        ->and($results->first()->name)->toBe('cauliflower'); // User template
});

test('higher usage count ranks suggestions higher', function () {
    $user = User::factory()->create();

    // Create multiple user templates with different usage counts
    UserItemTemplate::create([
        'user_id' => $user->id,
        'name' => 'apple',
        'category' => IngredientCategory::PRODUCE,
        'unit' => MeasurementUnit::WHOLE,
        'default_quantity' => 6,
        'usage_count' => 2,
        'last_used_at' => now()->subDays(1),
    ]);

    UserItemTemplate::create([
        'user_id' => $user->id,
        'name' => 'avocado',
        'category' => IngredientCategory::PRODUCE,
        'unit' => MeasurementUnit::WHOLE,
        'default_quantity' => 3,
        'usage_count' => 10, // Used more frequently
        'last_used_at' => now()->subDays(2),
    ]);

    $service = new ItemAutoCompleteService;
    $results = $service->query($user->id, 'a');

    // Avocado (usage_count: 10) should rank before apple (usage_count: 2)
    expect($results->first()->name)->toBe('avocado')
        ->and($results->first()->usage_count)->toBe(10);
});

test('more recent items rank higher when usage counts are equal', function () {
    $user = User::factory()->create();

    // Create templates with same usage count but different last_used_at
    UserItemTemplate::create([
        'user_id' => $user->id,
        'name' => 'chicken breast',
        'category' => IngredientCategory::MEAT,
        'unit' => MeasurementUnit::LB,
        'default_quantity' => 2,
        'usage_count' => 3,
        'last_used_at' => now()->subDays(5), // Older
    ]);

    UserItemTemplate::create([
        'user_id' => $user->id,
        'name' => 'cheddar cheese',
        'category' => IngredientCategory::DAIRY,
        'unit' => MeasurementUnit::LB,
        'default_quantity' => 1,
        'usage_count' => 3,
        'last_used_at' => now(), // More recent
    ]);

    $service = new ItemAutoCompleteService;
    $results = $service->query($user->id, 'c');

    // Cheddar cheese (more recent) should rank before chicken breast
    expect($results->first()->name)->toBe('cheddar cheese')
        ->and($results->get(1)->name)->toBe('chicken breast');
});

test('user templates are isolated per user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // User 1's template
    UserItemTemplate::create([
        'user_id' => $user1->id,
        'name' => 'oat milk',
        'category' => IngredientCategory::BEVERAGES,
        'unit' => MeasurementUnit::GALLON,
        'default_quantity' => 1,
        'usage_count' => 5,
        'last_used_at' => now(),
    ]);

    // User 2's template
    UserItemTemplate::create([
        'user_id' => $user2->id,
        'name' => 'soy milk',
        'category' => IngredientCategory::BEVERAGES,
        'unit' => MeasurementUnit::GALLON,
        'default_quantity' => 1,
        'usage_count' => 3,
        'last_used_at' => now(),
    ]);

    $service = new ItemAutoCompleteService;

    // User 1 should only see their templates
    $resultsUser1 = $service->query($user1->id, 'mil');
    expect($resultsUser1->pluck('name')->toArray())->toContain('oat milk')
        ->and($resultsUser1->pluck('name')->toArray())->not->toContain('soy milk');

    // User 2 should only see their templates
    $resultsUser2 = $service->query($user2->id, 'mil');
    expect($resultsUser2->pluck('name')->toArray())->toContain('soy milk')
        ->and($resultsUser2->pluck('name')->toArray())->not->toContain('oat milk');
});
