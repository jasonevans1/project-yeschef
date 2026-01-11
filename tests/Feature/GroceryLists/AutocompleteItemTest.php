<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Models\CommonItemTemplate;
use App\Models\User;
use App\Services\ItemAutoCompleteService;

beforeEach(function () {
    // Seed common item templates
    CommonItemTemplate::create([
        'name' => 'milk',
        'category' => IngredientCategory::DAIRY,
        'unit' => MeasurementUnit::GALLON,
        'default_quantity' => 1,
    ]);

    CommonItemTemplate::create([
        'name' => 'banana',
        'category' => IngredientCategory::PRODUCE,
        'unit' => MeasurementUnit::WHOLE,
        'default_quantity' => 6,
    ]);

    CommonItemTemplate::create([
        'name' => 'bread',
        'category' => IngredientCategory::BAKERY,
        'unit' => MeasurementUnit::WHOLE,
        'default_quantity' => 1,
    ]);

    CommonItemTemplate::create([
        'name' => 'almond milk',
        'category' => IngredientCategory::DAIRY,
        'unit' => MeasurementUnit::GALLON,
        'default_quantity' => 1,
    ]);
});

// T015: Test autocomplete query returning common templates
test('autocomplete query returns common templates', function () {
    $user = User::factory()->create();
    $service = new ItemAutoCompleteService;

    $results = $service->query($user->id, 'mil');

    expect($results)->toHaveCount(2)
        ->and($results->pluck('name')->toArray())->toContain('milk', 'almond milk');
});

// T016: Test partial name matching
test('partial name matching works correctly', function () {
    $user = User::factory()->create();
    $service = new ItemAutoCompleteService;

    // Test prefix matching
    $results = $service->query($user->id, 'banan');
    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('banana');

    // Test contains matching
    $results = $service->query($user->id, 'anan');
    expect($results)->toHaveCount(1)
        ->and($results->first()->name)->toBe('banana');
});

// T017: Test category auto-population on selection
test('category auto-populates on selection', function () {
    $user = User::factory()->create();
    $service = new ItemAutoCompleteService;

    $results = $service->query($user->id, 'milk');
    $template = $results->first();

    expect($template->category)->toBe(IngredientCategory::DAIRY)
        ->and($template->unit)->toBe(MeasurementUnit::GALLON)
        ->and($template->default_quantity)->toBe(1.0);
});

// T018: Test user can override suggested values
test('user can override suggested values', function () {
    $user = User::factory()->create();
    $groceryList = \App\Models\GroceryList::factory()->for($user)->create();

    $this->actingAs($user);

    // Simulate selecting a template but overriding values
    $groceryList->groceryItems()->create([
        'name' => 'milk', // From template
        'quantity' => 2, // Override default_quantity (was 1)
        'unit' => MeasurementUnit::QUART, // Override unit (was gallon)
        'category' => IngredientCategory::BEVERAGES, // Override category (was dairy)
        'source_type' => 'manual',
        'sort_order' => 1,
    ]);

    $item = $groceryList->groceryItems()->first();

    expect($item->name)->toBe('milk')
        ->and($item->quantity)->toBe(2.0)
        ->and($item->unit)->toBe(MeasurementUnit::QUART)
        ->and($item->category)->toBe(IngredientCategory::BEVERAGES);
});

// Test: User can add custom item not in autocomplete
test('user can add custom item that does not match autocomplete', function () {
    $user = User::factory()->create();
    $groceryList = \App\Models\GroceryList::factory()->for($user)->create();

    $this->actingAs($user);

    // Use Livewire to test the component
    \Livewire\Livewire::test(\App\Livewire\GroceryLists\Show::class, ['groceryList' => $groceryList])
        ->call('openAddItemForm')
        ->set('searchQuery', 'My Custom Item')
        ->set('itemQuantity', 5)
        ->set('itemUnit', MeasurementUnit::WHOLE->value)
        ->set('itemCategory', IngredientCategory::OTHER->value)
        ->call('addManualItem')
        ->assertHasNoErrors()
        ->assertSet('showAddItemForm', false);

    // Verify item was created with searchQuery value
    $item = $groceryList->groceryItems()->first();
    expect($item)->not->toBeNull()
        ->and($item->name)->toBe('My Custom Item')
        ->and($item->quantity)->toBe(5.0)
        ->and($item->unit)->toBe(MeasurementUnit::WHOLE)
        ->and($item->category)->toBe(IngredientCategory::OTHER);
});
