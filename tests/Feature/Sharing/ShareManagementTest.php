<?php

declare(strict_types=1);

use App\Enums\SharePermission;
use App\Livewire\Settings\Sharing;
use App\Models\ContentShare;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create(['name' => 'Alice Owner']);
    $this->recipient = User::factory()->create(['name' => 'Bob Recipient']);
});

// ──────────────────────────────────────────
// List Outgoing Shares (T046)
// ──────────────────────────────────────────

test('owner sees all outgoing shares on settings sharing page', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id, 'name' => 'Shared Recipe']);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    ContentShare::factory()->shareAll(MealPlan::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Write,
    ]);

    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->assertSee('Shared Recipe')
        ->assertSee('All Meal Plans')
        ->assertSee($this->recipient->email);
});

test('user does not see shares from other owners', function () {
    $otherOwner = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $otherOwner->id, 'name' => 'Other Owner Recipe']);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $otherOwner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->assertDontSee('Other Owner Recipe');
});

// ──────────────────────────────────────────
// Update Permission Level (T046)
// ──────────────────────────────────────────

test('owner can update share permission from read to write', function () {
    $share = ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->call('updatePermission', $share->id, 'write')
        ->assertHasNoErrors();

    expect($share->fresh()->permission)->toBe(SharePermission::Write);
});

test('owner can update share permission from write to read', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    $share = ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Write,
    ]);

    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->call('updatePermission', $share->id, 'read')
        ->assertHasNoErrors();

    expect($share->fresh()->permission)->toBe(SharePermission::Read);
});

test('non-owner cannot update share permission', function () {
    $share = ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->recipient)
        ->test(Sharing::class)
        ->call('updatePermission', $share->id, 'write')
        ->assertForbidden();

    expect($share->fresh()->permission)->toBe(SharePermission::Read);
});

test('update permission validates permission value', function () {
    $share = ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->call('updatePermission', $share->id, 'admin')
        ->assertHasErrors();

    expect($share->fresh()->permission)->toBe(SharePermission::Read);
});

// ──────────────────────────────────────────
// Revoke Share (T046)
// ──────────────────────────────────────────

test('owner can revoke a specific share', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    $share = ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->call('revokeShare', $share->id)
        ->assertHasNoErrors();

    expect(ContentShare::find($share->id))->toBeNull();
});

test('owner can revoke a share-all', function () {
    $share = ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->call('revokeShare', $share->id)
        ->assertHasNoErrors();

    expect(ContentShare::find($share->id))->toBeNull();
});

test('non-owner cannot revoke a share', function () {
    $share = ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->recipient)
        ->test(Sharing::class)
        ->call('revokeShare', $share->id)
        ->assertForbidden();

    expect(ContentShare::find($share->id))->not->toBeNull();
});

// ──────────────────────────────────────────
// Revoke Share-All Removes Access (T046)
// ──────────────────────────────────────────

test('revoking share-all removes access to all items of that type', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    $share = ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    // Verify recipient has access before revoke
    expect(Recipe::accessibleBy($this->recipient)->pluck('id'))->toContain($recipe->id);

    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->call('revokeShare', $share->id)
        ->assertHasNoErrors();

    // Verify recipient loses access after revoke
    expect(Recipe::accessibleBy($this->recipient)->pluck('id'))->not->toContain($recipe->id);
});

// ──────────────────────────────────────────
// Specific Revoke Under Share-All (T046)
// ──────────────────────────────────────────

test('revoking specific share keeps access via share-all', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    // Create both a specific share and a share-all
    $specificShare = ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Write,
    ]);

    ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    // Revoke the specific share
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->call('revokeShare', $specificShare->id)
        ->assertHasNoErrors();

    // Recipient still has access via share-all
    expect(Recipe::accessibleBy($this->recipient)->pluck('id'))->toContain($recipe->id);
});

// ──────────────────────────────────────────
// UI Updates After Actions (T046)
// ──────────────────────────────────────────

test('share disappears from list after revoke', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => $this->owner->id,
        'name' => 'Revocable Recipe',
    ]);

    $share = ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->assertSee('Revocable Recipe')
        ->call('revokeShare', $share->id)
        ->assertDontSee('Revocable Recipe');
});

test('permission dropdown reflects updated permission after change', function () {
    $share = ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->call('updatePermission', $share->id, 'write')
        ->assertHasNoErrors();

    // Verify the write option is now selected in the re-rendered dropdown
    expect($component->html())->toContain('value="write" selected');
});
