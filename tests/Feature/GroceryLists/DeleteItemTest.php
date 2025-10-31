<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\SourceType;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

test('user can delete manual item with hard delete', function () {
    $groceryList = GroceryList::factory()
        ->for($this->user)
        ->create();

    $manualItem = GroceryItem::factory()
        ->for($groceryList)
        ->create([
            'name' => 'Paper Towels',
            'source_type' => SourceType::MANUAL,
        ]);

    actingAs($this->user)
        ->delete(route('grocery-lists.items.destroy', [$groceryList, $manualItem]))
        ->assertRedirect();

    // Manual item should be hard deleted (not exist in database at all)
    expect(GroceryItem::withTrashed()->find($manualItem->id))->toBeNull();
});

test('user can delete generated item with soft delete', function () {
    $groceryList = GroceryList::factory()
        ->for($this->user)
        ->create();

    $generatedItem = GroceryItem::factory()
        ->for($groceryList)
        ->create([
            'name' => 'Milk',
            'source_type' => SourceType::GENERATED,
            'original_values' => [
                'quantity' => 2,
                'unit' => 'cup',
            ],
        ]);

    actingAs($this->user)
        ->delete(route('grocery-lists.items.destroy', [$groceryList, $generatedItem]))
        ->assertRedirect();

    // Generated item should be soft deleted (exists but has deleted_at)
    $deletedItem = GroceryItem::withTrashed()->find($generatedItem->id);
    expect($deletedItem)->not->toBeNull();
    expect($deletedItem->deleted_at)->not->toBeNull();
    expect($deletedItem->trashed())->toBeTrue();
});

test('deleted generated item not shown in list view', function () {
    $groceryList = GroceryList::factory()
        ->for($this->user)
        ->create(['name' => 'My Shopping List']);

    $activeItem = GroceryItem::factory()
        ->for($groceryList)
        ->create([
            'name' => 'Eggs',
            'source_type' => SourceType::GENERATED,
        ]);

    $deletedItem = GroceryItem::factory()
        ->for($groceryList)
        ->create([
            'name' => 'Milk',
            'source_type' => SourceType::GENERATED,
        ]);
    $deletedItem->delete(); // Soft delete

    actingAs($this->user)
        ->get(route('grocery-lists.show', $groceryList))
        ->assertSuccessful()
        ->assertSee('Eggs')
        ->assertDontSee('Milk');
});

test('user cannot delete item from another users list', function () {
    $groceryList = GroceryList::factory()
        ->for($this->otherUser)
        ->create();

    $item = GroceryItem::factory()
        ->for($groceryList)
        ->create([
            'name' => 'Unauthorized Item',
            'source_type' => SourceType::MANUAL,
        ]);

    actingAs($this->user)
        ->delete(route('grocery-lists.items.destroy', [$groceryList, $item]))
        ->assertForbidden();

    // Item should still exist
    expect(GroceryItem::find($item->id))->not->toBeNull();
});

test('deleting manual item removes it completely from database', function () {
    $groceryList = GroceryList::factory()
        ->for($this->user)
        ->create();

    $manualItem = GroceryItem::factory()
        ->for($groceryList)
        ->create([
            'name' => 'Snacks',
            'quantity' => 3,
            'category' => IngredientCategory::PANTRY,
            'source_type' => SourceType::MANUAL,
        ]);

    $itemId = $manualItem->id;

    actingAs($this->user)
        ->delete(route('grocery-lists.items.destroy', [$groceryList, $manualItem]))
        ->assertRedirect();

    // Verify hard delete - even withTrashed() should not find it
    expect(GroceryItem::withTrashed()->find($itemId))->toBeNull();

    // Verify the grocery list still exists
    expect(GroceryList::find($groceryList->id))->not->toBeNull();
});

test('deleting generated item preserves data with soft delete', function () {
    $groceryList = GroceryList::factory()
        ->for($this->user)
        ->create();

    $generatedItem = GroceryItem::factory()
        ->for($groceryList)
        ->create([
            'name' => 'Chicken Breast',
            'quantity' => 2.5,
            'category' => IngredientCategory::MEAT,
            'source_type' => SourceType::GENERATED,
            'original_values' => [
                'quantity' => 2,
                'unit' => 'lb',
            ],
        ]);

    $itemId = $generatedItem->id;

    actingAs($this->user)
        ->delete(route('grocery-lists.items.destroy', [$groceryList, $generatedItem]))
        ->assertRedirect();

    // Retrieve with trashed
    $deletedItem = GroceryItem::withTrashed()->find($itemId);

    // Verify all data is preserved
    expect($deletedItem)->not->toBeNull();
    expect($deletedItem->deleted_at)->not->toBeNull();
    expect($deletedItem->name)->toBe('Chicken Breast');
    expect((float) $deletedItem->quantity)->toBe(2.5);
    expect($deletedItem->category)->toBe(IngredientCategory::MEAT);
    expect($deletedItem->source_type)->toBe(SourceType::GENERATED);
    expect($deletedItem->original_values)->toBeArray();
    expect($deletedItem->original_values['quantity'])->toBe(2);
});

test('multiple items can be deleted from same list', function () {
    $groceryList = GroceryList::factory()
        ->for($this->user)
        ->create();

    $manualItem = GroceryItem::factory()
        ->for($groceryList)
        ->create(['name' => 'Manual Item', 'source_type' => SourceType::MANUAL]);

    $generatedItem1 = GroceryItem::factory()
        ->for($groceryList)
        ->create(['name' => 'Generated Item 1', 'source_type' => SourceType::GENERATED]);

    $generatedItem2 = GroceryItem::factory()
        ->for($groceryList)
        ->create(['name' => 'Generated Item 2', 'source_type' => SourceType::GENERATED]);

    // Delete all items
    actingAs($this->user)
        ->delete(route('grocery-lists.items.destroy', [$groceryList, $manualItem]))
        ->assertRedirect();

    actingAs($this->user)
        ->delete(route('grocery-lists.items.destroy', [$groceryList, $generatedItem1]))
        ->assertRedirect();

    actingAs($this->user)
        ->delete(route('grocery-lists.items.destroy', [$groceryList, $generatedItem2]))
        ->assertRedirect();

    // Verify deletion behavior
    expect(GroceryItem::withTrashed()->find($manualItem->id))->toBeNull(); // Hard deleted
    expect(GroceryItem::withTrashed()->find($generatedItem1->id)->trashed())->toBeTrue(); // Soft deleted
    expect(GroceryItem::withTrashed()->find($generatedItem2->id)->trashed())->toBeTrue(); // Soft deleted

    // Verify none show in normal queries
    expect(GroceryItem::find($manualItem->id))->toBeNull();
    expect(GroceryItem::find($generatedItem1->id))->toBeNull();
    expect(GroceryItem::find($generatedItem2->id))->toBeNull();
});
