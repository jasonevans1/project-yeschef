<?php

declare(strict_types=1);

use App\Enums\SharePermission;
use App\Livewire\Settings\Sharing;
use App\Models\ContentShare;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->recipient = User::factory()->create();
});

// ──────────────────────────────────────────
// Share All — Happy Paths (T030)
// ──────────────────────────────────────────

test('user can share all recipes with a valid email', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasNoErrors();

    expect(ContentShare::where([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'shareable_type' => Recipe::class,
        'share_all' => true,
    ])->whereNull('shareable_id')->exists())->toBeTrue();
});

test('user can share all meal plans with a valid email', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', 'meal_plan')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasNoErrors();

    expect(ContentShare::where([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'shareable_type' => MealPlan::class,
        'share_all' => true,
    ])->whereNull('shareable_id')->exists())->toBeTrue();
});

test('user can share all grocery lists with a valid email', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', 'grocery_list')
        ->set('shareAllPermission', 'write')
        ->call('shareAll')
        ->assertHasNoErrors();

    expect(ContentShare::where([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'shareable_type' => GroceryList::class,
        'share_all' => true,
        'permission' => SharePermission::Write,
    ])->whereNull('shareable_id')->exists())->toBeTrue();
});

test('future items are included via share-all through accessibleBy scope', function () {
    // Create share-all for recipes
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasNoErrors();

    // Create a new recipe AFTER the share-all was created
    $newRecipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    // Verify the recipient can see it via accessibleBy scope
    $accessibleRecipes = Recipe::accessibleBy($this->recipient)->get();

    expect($accessibleRecipes->pluck('id'))->toContain($newRecipe->id);
});

test('sharing with unregistered email creates pending share', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', 'newuser@example.com')
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasNoErrors();

    $share = ContentShare::where('recipient_email', 'newuser@example.com')->first();

    expect($share)->not->toBeNull()
        ->and($share->recipient_id)->toBeNull()
        ->and($share->owner_id)->toBe($this->owner->id)
        ->and($share->share_all)->toBeTrue()
        ->and($share->shareable_id)->toBeNull();
});

// ──────────────────────────────────────────
// Share All — Self-Share Prevention (T030)
// ──────────────────────────────────────────

test('user cannot share all with themselves', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->owner->email)
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasErrors(['shareAllEmail']);

    expect(ContentShare::count())->toBe(0);
});

// ──────────────────────────────────────────
// Share All — Upsert Behavior (T030)
// ──────────────────────────────────────────

test('sharing same type with same email updates permission', function () {
    // Create initial share-all with read permission
    ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    // Share again with write permission
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'write')
        ->call('shareAll')
        ->assertHasNoErrors();

    // Should only have one share record, with updated permission
    $shares = ContentShare::where('owner_id', $this->owner->id)
        ->where('recipient_email', $this->recipient->email)
        ->where('shareable_type', Recipe::class)
        ->whereNull('shareable_id')
        ->get();

    expect($shares)->toHaveCount(1)
        ->and($shares->first()->permission)->toBe(SharePermission::Write);
});

// ──────────────────────────────────────────
// Share All — Coexistence (T030)
// ──────────────────────────────────────────

test('share-all coexists with specific-item shares', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    // Create specific-item share
    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Write,
    ]);

    // Create share-all with read permission
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasNoErrors();

    // Both shares should exist
    $shares = ContentShare::where('owner_id', $this->owner->id)
        ->where('recipient_email', $this->recipient->email)
        ->where('shareable_type', Recipe::class)
        ->get();

    expect($shares)->toHaveCount(2);

    // One specific, one share-all
    expect($shares->where('share_all', true)->count())->toBe(1);
    expect($shares->where('share_all', false)->count())->toBe(1);
});

// ──────────────────────────────────────────
// Share All — Validation (T030)
// ──────────────────────────────────────────

test('share all email is required', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', '')
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasErrors(['shareAllEmail']);
});

test('share all email must be valid', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', 'not-an-email')
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasErrors(['shareAllEmail']);
});

test('share all permission is required', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', '')
        ->call('shareAll')
        ->assertHasErrors(['shareAllPermission']);
});

test('share all permission must be valid', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'admin')
        ->call('shareAll')
        ->assertHasErrors(['shareAllPermission']);
});

test('share all type is required', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', '')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasErrors(['shareAllType']);
});

test('share all type must be valid', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', 'invalid_type')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasErrors(['shareAllType']);
});

// ──────────────────────────────────────────
// Share All — Outgoing Shares Display (T030)
// ──────────────────────────────────────────

test('outgoing shares list displays correctly for authenticated user', function () {
    // Create some outgoing shares
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

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
        ->assertSee($this->recipient->email)
        ->assertSee($recipe->name)
        ->assertSee('All Meal Plans');
});

test('success message displays after sharing', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasNoErrors()
        ->assertSee($this->recipient->email);
});

test('form fields reset after successful share', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $this->recipient->email)
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'write')
        ->call('shareAll')
        ->assertHasNoErrors()
        ->assertSet('shareAllEmail', '')
        ->assertSet('shareAllType', '')
        ->assertSet('shareAllPermission', 'read');
});
