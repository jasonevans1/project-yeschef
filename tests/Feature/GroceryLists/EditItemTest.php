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

test('user can edit manual item (all fields)', function () {
    actingAs($this->user);

    // Create a manual item
    $item = GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Original Name',
        'quantity' => 1.0,
        'unit' => MeasurementUnit::CUP->value,
        'category' => IngredientCategory::PRODUCE->value,
        'source_type' => SourceType::MANUAL->value,
        'notes' => 'Original notes',
    ]);

    $response = $this->put(route('grocery-lists.items.update', [$this->groceryList, $item]), [
        'name' => 'Updated Name',
        'quantity' => 2.5,
        'unit' => MeasurementUnit::TBSP->value,
        'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS->value,
        'notes' => 'Updated notes',
    ]);

    $response->assertRedirect();

    $item->refresh();
    expect($item->name)->toBe('Updated Name')
        ->and((float) $item->quantity)->toBe(2.5)
        ->and($item->unit)->toBe(MeasurementUnit::TBSP)
        ->and($item->category)->toBe(IngredientCategory::SOUPS_AND_CANNED_GOODS)
        ->and($item->notes)->toBe('Updated notes')
        ->and($item->source_type)->toBe(SourceType::MANUAL);
});

test('user can edit generated item (tracks original_values in JSON)', function () {
    actingAs($this->user);

    // Create a generated item
    $item = GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Generated Item',
        'quantity' => 3.0,
        'unit' => MeasurementUnit::CUP->value,
        'category' => IngredientCategory::PRODUCE->value,
        'source_type' => SourceType::GENERATED->value,
        'original_values' => null, // Not yet edited
    ]);

    $response = $this->put(route('grocery-lists.items.update', [$this->groceryList, $item]), [
        'name' => 'Edited Generated Item',
        'quantity' => 5.0,
        'unit' => MeasurementUnit::TBSP->value,
        'category' => IngredientCategory::SOUPS_AND_CANNED_GOODS->value,
    ]);

    $response->assertRedirect();

    $item->refresh();
    expect($item->name)->toBe('Edited Generated Item')
        ->and((float) $item->quantity)->toBe(5.0)
        ->and($item->unit)->toBe(MeasurementUnit::TBSP)
        ->and($item->category)->toBe(IngredientCategory::SOUPS_AND_CANNED_GOODS)
        ->and($item->source_type)->toBe(SourceType::GENERATED);

    // Verify original values were stored
    expect($item->original_values)->not->toBeNull()
        ->and($item->original_values['name'])->toBe('Generated Item')
        ->and((float) $item->original_values['quantity'])->toBe(3.0)
        ->and($item->original_values['unit'])->toBe(MeasurementUnit::CUP->value)
        ->and($item->original_values['category'])->toBe(IngredientCategory::PRODUCE->value);
});

test('edited generated item marked as edited (original_values not null)', function () {
    actingAs($this->user);

    // Create a generated item
    $item = GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Original',
        'source_type' => SourceType::GENERATED->value,
        'original_values' => null,
    ]);

    expect($item->is_edited)->toBeFalse();

    $this->put(route('grocery-lists.items.update', [$this->groceryList, $item]), [
        'name' => 'Edited',
    ]);

    $item->refresh();
    expect($item->is_edited)->toBeTrue()
        ->and($item->original_values)->not->toBeNull();
});

test('user cannot edit item in another users list', function () {
    actingAs($this->otherUser);

    $item = GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Original',
        'source_type' => SourceType::MANUAL->value,
    ]);

    $response = $this->put(route('grocery-lists.items.update', [$this->groceryList, $item]), [
        'name' => 'Unauthorized Edit',
    ]);

    $response->assertForbidden();

    $item->refresh();
    expect($item->name)->toBe('Original');
});

test('validation requires name', function () {
    actingAs($this->user);

    $item = GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Original',
        'source_type' => SourceType::MANUAL->value,
    ]);

    $response = $this->put(route('grocery-lists.items.update', [$this->groceryList, $item]), [
        'name' => '',
        'quantity' => 2,
    ]);

    $response->assertSessionHasErrors('name');
});

test('validation requires positive quantity', function () {
    actingAs($this->user);

    $item = GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Original',
        'quantity' => 5.0,
        'source_type' => SourceType::MANUAL->value,
    ]);

    $response = $this->put(route('grocery-lists.items.update', [$this->groceryList, $item]), [
        'name' => 'Test Item',
        'quantity' => -1,
    ]);

    $response->assertSessionHasErrors('quantity');
});
