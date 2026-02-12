<?php

declare(strict_types=1);

use App\Enums\SharePermission;
use App\Livewire\Recipes\Show;
use App\Models\ContentShare;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->recipient = User::factory()->create();
    $this->recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);
});

// ──────────────────────────────────────────
// Share Recipe — Happy Paths (T022)
// ──────────────────────────────────────────

test('owner can share a recipe with a valid email', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['recipe' => $this->recipe])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal');

    expect(ContentShare::where([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'shareable_type' => Recipe::class,
        'shareable_id' => $this->recipe->id,
        'share_all' => false,
    ])->exists())->toBeTrue();
});

test('owner can share a recipe with write permission', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['recipe' => $this->recipe])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'write')
        ->call('shareWith')
        ->assertHasNoErrors();

    $share = ContentShare::where('shareable_id', $this->recipe->id)->first();

    expect($share->permission)->toBe(SharePermission::Write);
});

test('sharing with unregistered email creates pending share', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['recipe' => $this->recipe])
        ->set('shareEmail', 'newuser@example.com')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors();

    $share = ContentShare::where('recipient_email', 'newuser@example.com')->first();

    expect($share)->not->toBeNull()
        ->and($share->recipient_id)->toBeNull()
        ->and($share->owner_id)->toBe($this->owner->id)
        ->and($share->shareable_id)->toBe($this->recipe->id);
});

// ──────────────────────────────────────────
// Share Recipe — Upsert Behavior (T022)
// ──────────────────────────────────────────

test('sharing same recipe again with same email updates permission', function () {
    // Create initial share with read permission
    ContentShare::factory()->forRecipe($this->recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    // Share again with write permission
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['recipe' => $this->recipe])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'write')
        ->call('shareWith')
        ->assertHasNoErrors();

    // Should only have one share record, with updated permission
    $shares = ContentShare::where([
        'owner_id' => $this->owner->id,
        'recipient_email' => $this->recipient->email,
        'shareable_id' => $this->recipe->id,
    ])->get();

    expect($shares)->toHaveCount(1)
        ->and($shares->first()->permission)->toBe(SharePermission::Write);
});

// ──────────────────────────────────────────
// Share Recipe — Validation & Authorization (T022)
// ──────────────────────────────────────────

test('owner cannot share recipe with themselves', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['recipe' => $this->recipe])
        ->set('shareEmail', $this->owner->email)
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasErrors(['shareEmail']);

    expect(ContentShare::count())->toBe(0);
});

test('non-owner cannot share recipe', function () {
    $stranger = User::factory()->create();

    // Grant stranger view access so they can load the page
    ContentShare::factory()->forRecipe($this->recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $stranger->id,
        'recipient_email' => $stranger->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($stranger)
        ->test(Show::class, ['recipe' => $this->recipe])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertForbidden();
});

test('share email is required', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['recipe' => $this->recipe])
        ->set('shareEmail', '')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasErrors(['shareEmail']);
});

test('share email must be valid', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['recipe' => $this->recipe])
        ->set('shareEmail', 'not-an-email')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasErrors(['shareEmail']);
});

test('share permission must be valid', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['recipe' => $this->recipe])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'admin')
        ->call('shareWith')
        ->assertHasErrors(['sharePermission']);
});
