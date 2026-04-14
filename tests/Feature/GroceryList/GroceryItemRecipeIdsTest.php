<?php

declare(strict_types=1);

use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

it('adds a nullable recipe_ids JSON column to the grocery_items table', function () {
    expect(Schema::hasColumn('grocery_items', 'recipe_ids'))->toBeTrue();
});

it('mass-assigns recipe_ids via GroceryItem::create() (verifies $fillable)', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();

    $item = GroceryItem::create([
        'grocery_list_id' => $list->id,
        'name' => 'Test Item',
        'recipe_ids' => [1, 2, 3],
    ]);

    expect($item->recipe_ids)->toBe([1, 2, 3]);
});

it('stores and retrieves recipe_ids as a PHP array (verifies array cast)', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();

    $item = GroceryItem::create([
        'grocery_list_id' => $list->id,
        'name' => 'Test Item',
        'recipe_ids' => [10, 20, 30],
    ]);

    $fresh = $item->fresh();

    expect($fresh->recipe_ids)->toBeArray()
        ->and($fresh->recipe_ids)->toBe([10, 20, 30]);
});

it('allows recipe_ids to be null', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();

    $item = GroceryItem::create([
        'grocery_list_id' => $list->id,
        'name' => 'Test Item',
        'recipe_ids' => null,
    ]);

    expect($item->fresh()->recipe_ids)->toBeNull();
});

it('defaults recipe_ids to null when not provided', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();

    $item = GroceryItem::create([
        'grocery_list_id' => $list->id,
        'name' => 'Test Item',
    ]);

    expect($item->fresh()->recipe_ids)->toBeNull();
});

it('stores an empty array as [] (not null) when explicitly passed', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();

    $item = GroceryItem::create([
        'grocery_list_id' => $list->id,
        'name' => 'Test Item',
        'recipe_ids' => [],
    ]);

    $fresh = $item->fresh();

    expect($fresh->recipe_ids)->toBeArray()
        ->and($fresh->recipe_ids)->toBe([])
        ->and($fresh->recipe_ids)->not->toBeNull();
});
