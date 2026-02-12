<?php

declare(strict_types=1);

use App\Enums\SharePermission;
use App\Livewire\GroceryLists\Show;
use App\Models\ContentShare;
use App\Models\GroceryList;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->recipient = User::factory()->create();
    $this->groceryList = GroceryList::factory()->standalone()->create(['user_id' => $this->owner->id]);
});

// ──────────────────────────────────────────
// Share Grocery List — Happy Paths (T024)
// ──────────────────────────────────────────

test('owner can share a grocery list with a valid email', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal');

    expect(ContentShare::where([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'shareable_type' => GroceryList::class,
        'shareable_id' => $this->groceryList->id,
        'share_all' => false,
    ])->exists())->toBeTrue();
});

test('owner can share a grocery list with write permission', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'write')
        ->call('shareWith')
        ->assertHasNoErrors();

    $share = ContentShare::where('shareable_id', $this->groceryList->id)
        ->where('shareable_type', GroceryList::class)
        ->first();

    expect($share->permission)->toBe(SharePermission::Write);
});

test('sharing grocery list with unregistered email creates pending share', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->set('shareEmail', 'newuser@example.com')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors();

    $share = ContentShare::where('recipient_email', 'newuser@example.com')->first();

    expect($share)->not->toBeNull()
        ->and($share->recipient_id)->toBeNull()
        ->and($share->shareable_type)->toBe(GroceryList::class);
});

// ──────────────────────────────────────────
// Share Grocery List — Upsert Behavior (T024)
// ──────────────────────────────────────────

test('sharing same grocery list again with same email updates permission', function () {
    ContentShare::factory()->forGroceryList($this->groceryList)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->owner)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'write')
        ->call('shareWith')
        ->assertHasNoErrors();

    $shares = ContentShare::where([
        'owner_id' => $this->owner->id,
        'recipient_email' => $this->recipient->email,
        'shareable_id' => $this->groceryList->id,
        'shareable_type' => GroceryList::class,
    ])->get();

    expect($shares)->toHaveCount(1)
        ->and($shares->first()->permission)->toBe(SharePermission::Write);
});

// ──────────────────────────────────────────
// Share Grocery List — Validation & Authorization (T024)
// ──────────────────────────────────────────

test('owner cannot share grocery list with themselves', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->set('shareEmail', $this->owner->email)
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasErrors(['shareEmail']);
});

test('non-owner cannot share grocery list', function () {
    $stranger = User::factory()->create();

    ContentShare::factory()->forGroceryList($this->groceryList)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $stranger->id,
        'recipient_email' => $stranger->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($stranger)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertForbidden();
});

test('share email is required for grocery list', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->set('shareEmail', '')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasErrors(['shareEmail']);
});

test('share email must be valid for grocery list', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->set('shareEmail', 'not-an-email')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasErrors(['shareEmail']);
});

test('share permission must be valid for grocery list', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'admin')
        ->call('shareWith')
        ->assertHasErrors(['sharePermission']);
});
