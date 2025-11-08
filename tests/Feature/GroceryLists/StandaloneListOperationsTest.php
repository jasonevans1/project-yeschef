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

    // Create a standalone grocery list (meal_plan_id is null)
    $this->standaloneList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Standalone Shopping List',
        'meal_plan_id' => null,
    ]);
});

// Test: User can add manual items to standalone list
test('user can add manual items to standalone list', function () {
    actingAs($this->user);

    expect($this->standaloneList->is_standalone)->toBeTrue();

    $response = $this->post(route('grocery-lists.items.store', $this->standaloneList), [
        'name' => 'Paper Towels',
        'quantity' => 2,
        'unit' => MeasurementUnit::WHOLE->value,
        'category' => IngredientCategory::OTHER->value,
    ]);

    $response->assertRedirect();

    expect(GroceryItem::where('grocery_list_id', $this->standaloneList->id)->count())->toBe(1);

    $item = GroceryItem::where('grocery_list_id', $this->standaloneList->id)->first();
    expect($item->name)->toBe('Paper Towels')
        ->and($item->source_type)->toBe(SourceType::MANUAL)
        ->and((float) $item->quantity)->toBe(2.0)
        ->and($item->unit)->toBe(MeasurementUnit::WHOLE);
});

test('user can add multiple manual items to standalone list', function () {
    actingAs($this->user);

    // Add first item
    $this->post(route('grocery-lists.items.store', $this->standaloneList), [
        'name' => 'Milk',
        'category' => IngredientCategory::DAIRY->value,
    ]);

    // Add second item
    $this->post(route('grocery-lists.items.store', $this->standaloneList), [
        'name' => 'Bread',
        'category' => IngredientCategory::BAKERY->value,
    ]);

    // Add third item
    $this->post(route('grocery-lists.items.store', $this->standaloneList), [
        'name' => 'Apples',
        'quantity' => 5,
        'unit' => MeasurementUnit::WHOLE->value,
        'category' => IngredientCategory::PRODUCE->value,
    ]);

    expect($this->standaloneList->groceryItems()->count())->toBe(3);

    $items = $this->standaloneList->groceryItems;
    expect($items)->toHaveCount(3);

    // Verify all items are manual
    foreach ($items as $item) {
        expect($item->source_type)->toBe(SourceType::MANUAL);
    }
});

// Test: User can edit items
test('user can edit items in standalone list', function () {
    actingAs($this->user);

    $item = GroceryItem::factory()->for($this->standaloneList)->create([
        'name' => 'Original Name',
        'quantity' => 1.0,
        'unit' => MeasurementUnit::CUP->value,
        'category' => IngredientCategory::PRODUCE->value,
        'source_type' => SourceType::MANUAL->value,
    ]);

    $response = $this->put(route('grocery-lists.items.update', [$this->standaloneList, $item]), [
        'name' => 'Updated Name',
        'quantity' => 3.0,
        'unit' => MeasurementUnit::TBSP->value,
        'category' => IngredientCategory::PANTRY->value,
    ]);

    $response->assertRedirect();

    $item->refresh();
    expect($item->name)->toBe('Updated Name')
        ->and((float) $item->quantity)->toBe(3.0)
        ->and($item->unit)->toBe(MeasurementUnit::TBSP)
        ->and($item->category)->toBe(IngredientCategory::PANTRY);
});

test('user can edit item name only', function () {
    actingAs($this->user);

    $item = GroceryItem::factory()->for($this->standaloneList)->create([
        'name' => 'Old Item',
        'source_type' => SourceType::MANUAL->value,
    ]);

    $response = $this->put(route('grocery-lists.items.update', [$this->standaloneList, $item]), [
        'name' => 'New Item Name',
    ]);

    $response->assertRedirect();

    $item->refresh();
    expect($item->name)->toBe('New Item Name');
});

// Test: User can delete items
test('user can delete items from standalone list', function () {
    actingAs($this->user);

    $item = GroceryItem::factory()->for($this->standaloneList)->create([
        'name' => 'Item to Delete',
        'source_type' => SourceType::MANUAL->value,
    ]);

    expect($this->standaloneList->groceryItems()->count())->toBe(1);

    $response = $this->delete(route('grocery-lists.items.destroy', [$this->standaloneList, $item]));

    $response->assertRedirect();

    // Manual items are hard deleted
    expect(GroceryItem::withTrashed()->find($item->id))->toBeNull();
    expect($this->standaloneList->groceryItems()->count())->toBe(0);
});

test('deleting item from standalone list reduces item count', function () {
    actingAs($this->user);

    // Create 3 items
    $item1 = GroceryItem::factory()->for($this->standaloneList)->create(['source_type' => SourceType::MANUAL]);
    $item2 = GroceryItem::factory()->for($this->standaloneList)->create(['source_type' => SourceType::MANUAL]);
    $item3 = GroceryItem::factory()->for($this->standaloneList)->create(['source_type' => SourceType::MANUAL]);

    expect($this->standaloneList->groceryItems()->count())->toBe(3);

    // Delete one item
    $this->delete(route('grocery-lists.items.destroy', [$this->standaloneList, $item2]));

    expect($this->standaloneList->groceryItems()->count())->toBe(2);
    expect($this->standaloneList->groceryItems->pluck('id')->toArray())
        ->toContain($item1->id, $item3->id)
        ->not->toContain($item2->id);
});

// Test: User can mark items purchased
test('user can mark items as purchased in standalone list', function () {
    actingAs($this->user);

    $item = GroceryItem::factory()->for($this->standaloneList)->create([
        'name' => 'Milk',
        'source_type' => SourceType::MANUAL->value,
        'purchased' => false,
        'purchased_at' => null,
    ]);

    expect($item->purchased)->toBeFalse()
        ->and($item->purchased_at)->toBeNull();

    // Mark as purchased
    $item->update([
        'purchased' => true,
        'purchased_at' => now(),
    ]);

    $item->refresh();

    expect($item->purchased)->toBeTrue()
        ->and($item->purchased_at)->not->toBeNull()
        ->and($item->purchased_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('user can unmark items as purchased in standalone list', function () {
    actingAs($this->user);

    $item = GroceryItem::factory()->for($this->standaloneList)->create([
        'name' => 'Bread',
        'source_type' => SourceType::MANUAL->value,
        'purchased' => true,
        'purchased_at' => now(),
    ]);

    expect($item->purchased)->toBeTrue();

    // Unmark as purchased
    $item->update([
        'purchased' => false,
        'purchased_at' => null,
    ]);

    $item->refresh();

    expect($item->purchased)->toBeFalse()
        ->and($item->purchased_at)->toBeNull();
});

test('marking multiple items tracks completion percentage', function () {
    actingAs($this->user);

    // Create 5 items
    $items = collect();
    for ($i = 1; $i <= 5; $i++) {
        $items->push(
            GroceryItem::factory()->for($this->standaloneList)->create([
                'name' => "Item {$i}",
                'source_type' => SourceType::MANUAL->value,
                'purchased' => false,
            ])
        );
    }

    // Refresh list to get computed attributes
    $this->standaloneList->refresh();

    expect($this->standaloneList->total_items)->toBe(5)
        ->and($this->standaloneList->completed_items)->toBe(0)
        ->and($this->standaloneList->completion_percentage)->toBe(0.0);

    // Mark 2 items as purchased
    $items[0]->update(['purchased' => true, 'purchased_at' => now()]);
    $items[1]->update(['purchased' => true, 'purchased_at' => now()]);

    $this->standaloneList->refresh();

    expect($this->standaloneList->completed_items)->toBe(2)
        ->and($this->standaloneList->completion_percentage)->toBe(40.0);

    // Mark all items as purchased
    foreach ($items as $item) {
        $item->update(['purchased' => true, 'purchased_at' => now()]);
    }

    $this->standaloneList->refresh();

    expect($this->standaloneList->completed_items)->toBe(5)
        ->and($this->standaloneList->completion_percentage)->toBe(100.0);
});

// Test: Standalone list has no "Regenerate" option (meal_plan_id null)
test('standalone list has no regenerate option due to null meal_plan_id', function () {
    expect($this->standaloneList->meal_plan_id)->toBeNull()
        ->and($this->standaloneList->is_standalone)->toBeTrue()
        ->and($this->standaloneList->is_meal_plan_linked)->toBeFalse();

    // Verify list cannot be regenerated (no meal plan link)
    expect($this->standaloneList->mealPlan)->toBeNull();
});

test('standalone list does not have regenerate capability', function () {
    // Since standalone lists have no meal plan, they cannot be regenerated
    expect($this->standaloneList->meal_plan_id)->toBeNull();

    // Attempting to access the meal plan relationship returns null
    expect($this->standaloneList->mealPlan)->toBeNull();

    // This confirms that regeneration logic (which depends on meal plan) cannot apply
    expect($this->standaloneList->is_meal_plan_linked)->toBeFalse();
});

// Test: User can delete standalone list
test('user can delete standalone list using model deletion', function () {
    actingAs($this->user);

    // Add some items to the list
    GroceryItem::factory()->for($this->standaloneList)->count(3)->create([
        'source_type' => SourceType::MANUAL->value,
    ]);

    expect($this->standaloneList->groceryItems()->count())->toBe(3);
    expect(GroceryList::find($this->standaloneList->id))->not->toBeNull();

    $listId = $this->standaloneList->id;

    // Delete the list using model deletion (route implementation pending)
    $this->standaloneList->delete();

    // Verify list is deleted
    expect(GroceryList::find($listId))->toBeNull();

    // Verify all items are also deleted (cascade)
    expect(GroceryItem::where('grocery_list_id', $listId)->count())->toBe(0);
});

test('deleting standalone list removes all associated items via cascade', function () {
    actingAs($this->user);

    // Create items
    $item1 = GroceryItem::factory()->for($this->standaloneList)->create(['source_type' => SourceType::MANUAL]);
    $item2 = GroceryItem::factory()->for($this->standaloneList)->create(['source_type' => SourceType::MANUAL]);
    $item3 = GroceryItem::factory()->for($this->standaloneList)->create(['source_type' => SourceType::MANUAL]);

    $itemIds = [$item1->id, $item2->id, $item3->id];
    $listId = $this->standaloneList->id;

    expect(GroceryItem::whereIn('id', $itemIds)->count())->toBe(3);

    // Delete the list
    $this->standaloneList->delete();

    // All items should be gone (cascade delete)
    expect(GroceryItem::where('grocery_list_id', $listId)->count())->toBe(0);
});

test('standalone list can be identified for deletion permissions', function () {
    // Verify ownership check could be performed
    expect($this->standaloneList->user_id)->toBe($this->user->id)
        ->and($this->standaloneList->user_id)->not->toBe($this->otherUser->id);

    // This test verifies the data model supports authorization
    // The actual authorization will be tested when routes are implemented
});

test('standalone list only contains manual items', function () {
    actingAs($this->user);

    // Add manual items
    GroceryItem::factory()->for($this->standaloneList)->count(5)->create([
        'source_type' => SourceType::MANUAL->value,
    ]);

    $items = $this->standaloneList->groceryItems;

    expect($items)->toHaveCount(5);

    // All items should be manual
    foreach ($items as $item) {
        expect($item->source_type)->toBe(SourceType::MANUAL)
            ->and($item->is_manual)->toBeTrue()
            ->and($item->is_generated)->toBeFalse();
    }
});

test('standalone list operations are independent of meal plans', function () {
    actingAs($this->user);

    // Create items
    GroceryItem::factory()->for($this->standaloneList)->count(3)->create([
        'source_type' => SourceType::MANUAL->value,
    ]);

    // Verify the list is truly standalone
    expect($this->standaloneList->meal_plan_id)->toBeNull()
        ->and($this->standaloneList->is_standalone)->toBeTrue()
        ->and($this->standaloneList->mealPlan)->toBeNull();

    // Items can be added, edited, marked purchased without any meal plan interaction
    $item = $this->standaloneList->groceryItems->first();

    // Edit item
    $item->update(['name' => 'Updated Item']);
    expect($item->name)->toBe('Updated Item');

    // Mark as purchased
    $item->update(['purchased' => true, 'purchased_at' => now()]);
    expect($item->purchased)->toBeTrue();

    // Operations are completely independent of any meal plan
    expect($this->standaloneList->is_standalone)->toBeTrue();
});
