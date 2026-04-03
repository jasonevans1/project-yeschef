<?php

declare(strict_types=1);

use App\Models\GroceryList;
use App\Models\User;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('stores and retrieves pantry_items as an array on User', function () {
    $user = User::factory()->create([
        'pantry_items' => ['milk', 'eggs', 'butter'],
    ]);

    $fresh = User::find($user->id);

    expect($fresh->pantry_items)->toBe(['milk', 'eggs', 'butter']);
});

it('defaults pantry_items to null when not set', function () {
    $user = User::factory()->create();

    $fresh = User::find($user->id);

    expect($fresh->pantry_items)->toBeNull();
});

it('stores and retrieves excluded_ingredients as an array on GroceryList', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create([
        'excluded_ingredients' => ['gluten', 'peanuts', 'dairy'],
    ]);

    $fresh = GroceryList::find($list->id);

    expect($fresh->excluded_ingredients)->toBe(['gluten', 'peanuts', 'dairy']);
});

it('defaults excluded_ingredients to null when not set', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();

    $fresh = GroceryList::find($list->id);

    expect($fresh->excluded_ingredients)->toBeNull();
});

it('casts pantry_items null to empty on User model', function () {
    $user = User::factory()->create(['pantry_items' => null]);

    $user->pantry_items = $user->pantry_items ?? [];

    expect($user->pantry_items)->toBe([]);
});
