<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Enums\SourceType;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->groceryList = GroceryList::factory()->for($this->user)->create();
});

test('user can add item with name only', function () {
    actingAs($this->user);

    $response = $this->post(route('grocery-lists.items.store', $this->groceryList), [
        'name' => 'Paper Towels',
    ]);

    $response->assertRedirect();

    expect(GroceryItem::where('grocery_list_id', $this->groceryList->id)->count())->toBe(1);

    $item = GroceryItem::where('grocery_list_id', $this->groceryList->id)->first();
    expect($item->name)->toBe('Paper Towels');
    expect($item->quantity)->toBeNull();
    expect($item->unit)->toBeNull();
    expect($item->category)->toBeInstanceOf(IngredientCategory::class);
});

test('user can add item with name quantity unit and category', function () {
    actingAs($this->user);

    $response = $this->post(route('grocery-lists.items.store', $this->groceryList), [
        'name' => 'Whole Milk',
        'quantity' => 2,
        'unit' => MeasurementUnit::GALLON->value,
        'category' => IngredientCategory::DAIRY->value,
    ]);

    $response->assertRedirect();

    $item = GroceryItem::where('grocery_list_id', $this->groceryList->id)->first();
    expect($item->name)->toBe('Whole Milk');
    expect((float) $item->quantity)->toBe(2.0);
    expect($item->unit)->toBe(MeasurementUnit::GALLON);
    expect($item->category)->toBe(IngredientCategory::DAIRY);
});

test('validation requires name', function () {
    actingAs($this->user);

    $response = $this->post(route('grocery-lists.items.store', $this->groceryList), [
        'quantity' => 2,
        'unit' => MeasurementUnit::CUP->value,
    ]);

    $response->assertSessionHasErrors('name');
});

test('validation allows optional quantity and unit', function () {
    actingAs($this->user);

    $response = $this->post(route('grocery-lists.items.store', $this->groceryList), [
        'name' => 'Bread',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();
});

test('item saved with source_type manual', function () {
    actingAs($this->user);

    $this->post(route('grocery-lists.items.store', $this->groceryList), [
        'name' => 'Eggs',
        'quantity' => 12,
        'unit' => MeasurementUnit::WHOLE->value,
    ]);

    $item = GroceryItem::where('grocery_list_id', $this->groceryList->id)->first();
    expect($item->source_type)->toBe(SourceType::MANUAL);
    expect($item->is_manual)->toBeTrue();
});

test('item appears in correct category', function () {
    actingAs($this->user);

    $this->post(route('grocery-lists.items.store', $this->groceryList), [
        'name' => 'Carrots',
        'category' => IngredientCategory::PRODUCE->value,
    ]);

    $item = GroceryItem::where('grocery_list_id', $this->groceryList->id)->first();
    expect($item->category)->toBe(IngredientCategory::PRODUCE);
});

test('user cannot add to another users list', function () {
    actingAs($this->otherUser);

    $response = $this->post(route('grocery-lists.items.store', $this->groceryList), [
        'name' => 'Unauthorized Item',
    ]);

    $response->assertForbidden();

    expect(GroceryItem::where('name', 'Unauthorized Item')->exists())->toBeFalse();
});
