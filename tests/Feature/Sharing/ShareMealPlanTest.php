<?php

declare(strict_types=1);

use App\Enums\SharePermission;
use App\Livewire\MealPlans\Show;
use App\Models\ContentShare;
use App\Models\MealPlan;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->recipient = User::factory()->create();
    $this->mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);
});

// ──────────────────────────────────────────
// Share Meal Plan — Happy Paths (T023)
// ──────────────────────────────────────────

test('owner can share a meal plan with a valid email', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['mealPlan' => $this->mealPlan])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal');

    expect(ContentShare::where([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'shareable_type' => MealPlan::class,
        'shareable_id' => $this->mealPlan->id,
        'share_all' => false,
    ])->exists())->toBeTrue();
});

test('owner can share a meal plan with write permission', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['mealPlan' => $this->mealPlan])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'write')
        ->call('shareWith')
        ->assertHasNoErrors();

    $share = ContentShare::where('shareable_id', $this->mealPlan->id)
        ->where('shareable_type', MealPlan::class)
        ->first();

    expect($share->permission)->toBe(SharePermission::Write);
});

test('sharing meal plan with unregistered email creates pending share', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['mealPlan' => $this->mealPlan])
        ->set('shareEmail', 'newuser@example.com')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors();

    $share = ContentShare::where('recipient_email', 'newuser@example.com')->first();

    expect($share)->not->toBeNull()
        ->and($share->recipient_id)->toBeNull()
        ->and($share->shareable_type)->toBe(MealPlan::class);
});

// ──────────────────────────────────────────
// Share Meal Plan — Upsert Behavior (T023)
// ──────────────────────────────────────────

test('sharing same meal plan again with same email updates permission', function () {
    ContentShare::factory()->forMealPlan($this->mealPlan)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->owner)
        ->test(Show::class, ['mealPlan' => $this->mealPlan])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'write')
        ->call('shareWith')
        ->assertHasNoErrors();

    $shares = ContentShare::where([
        'owner_id' => $this->owner->id,
        'recipient_email' => $this->recipient->email,
        'shareable_id' => $this->mealPlan->id,
        'shareable_type' => MealPlan::class,
    ])->get();

    expect($shares)->toHaveCount(1)
        ->and($shares->first()->permission)->toBe(SharePermission::Write);
});

// ──────────────────────────────────────────
// Share Meal Plan — Validation & Authorization (T023)
// ──────────────────────────────────────────

test('owner cannot share meal plan with themselves', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['mealPlan' => $this->mealPlan])
        ->set('shareEmail', $this->owner->email)
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasErrors(['shareEmail']);
});

test('non-owner cannot share meal plan', function () {
    $stranger = User::factory()->create();

    ContentShare::factory()->forMealPlan($this->mealPlan)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $stranger->id,
        'recipient_email' => $stranger->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($stranger)
        ->test(Show::class, ['mealPlan' => $this->mealPlan])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertForbidden();
});

test('share email is required for meal plan', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['mealPlan' => $this->mealPlan])
        ->set('shareEmail', '')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasErrors(['shareEmail']);
});

test('share email must be valid for meal plan', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['mealPlan' => $this->mealPlan])
        ->set('shareEmail', 'not-an-email')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasErrors(['shareEmail']);
});

test('share permission must be valid for meal plan', function () {
    Livewire::actingAs($this->owner)
        ->test(Show::class, ['mealPlan' => $this->mealPlan])
        ->set('shareEmail', $this->recipient->email)
        ->set('sharePermission', 'admin')
        ->call('shareWith')
        ->assertHasErrors(['sharePermission']);
});
