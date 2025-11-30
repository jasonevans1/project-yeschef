<?php

use App\Livewire\GroceryLists\Show;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\User;
use Livewire\Livewire;

test('owner can delete their grocery list', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $list])
        ->call('delete')
        ->assertRedirect(route('grocery-lists.index'))
        ->assertSessionHas('success');

    expect($list->fresh()->trashed())->toBeTrue();
});

test('non-owner cannot delete another user\'s list', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $list = GroceryList::factory()->for($owner)->create();

    // Attacker cannot view the grocery list show page (fails authorization)
    $this->actingAs($attacker)
        ->get(route('grocery-lists.show', $list))
        ->assertForbidden();

    expect($list->fresh()->trashed())->toBeFalse();
});

test('deleting list cascades to grocery items', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();
    $items = GroceryItem::factory()->count(3)->for($list)->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $list])
        ->call('delete');

    foreach ($items as $item) {
        expect($item->fresh()->trashed())->toBeTrue();
    }
});

test('deleted list returns 404 when accessed', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();

    // Delete the list
    $list->delete();

    // Try to access the deleted list - should get 404
    $this->actingAs($user)
        ->get(route('grocery-lists.show', $list))
        ->assertNotFound();
});

test('user can cancel deletion', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $list])
        ->call('confirmDelete')
        ->assertSet('showDeleteConfirm', true)
        ->call('cancelDelete')
        ->assertSet('showDeleteConfirm', false);

    // Verify list was not deleted
    expect($list->fresh()->trashed())->toBeFalse();
});

test('cancel deletion preserves all data', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();
    $items = GroceryItem::factory()->count(3)->for($list)->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $list])
        ->call('confirmDelete')
        ->call('cancelDelete');

    // Verify list still exists
    expect($list->fresh()->trashed())->toBeFalse();

    // Verify all items still exist
    foreach ($items as $item) {
        expect($item->fresh()->trashed())->toBeFalse();
    }
});
