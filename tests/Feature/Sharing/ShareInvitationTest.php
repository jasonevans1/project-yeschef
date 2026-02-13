<?php

declare(strict_types=1);

use App\Enums\SharePermission;
use App\Livewire\GroceryLists\Show as GroceryListsShow;
use App\Livewire\MealPlans\Show as MealPlansShow;
use App\Livewire\Recipes\Show as RecipesShow;
use App\Livewire\Settings\Sharing;
use App\Mail\ShareInvitation;
use App\Models\ContentShare;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create(['name' => 'Alice Owner']);
    Mail::fake();
});

// ──────────────────────────────────────────
// Invitation Email Sent for Non-Registered Email (T051)
// ──────────────────────────────────────────

test('sharing a recipe with non-registered email sends invitation', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    Livewire::actingAs($this->owner)
        ->test(RecipesShow::class, ['recipe' => $recipe])
        ->set('shareEmail', 'newuser@example.com')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors();

    Mail::assertSent(ShareInvitation::class, function (ShareInvitation $mail) {
        return $mail->hasTo('newuser@example.com');
    });
});

test('sharing a meal plan with non-registered email sends invitation', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    Livewire::actingAs($this->owner)
        ->test(MealPlansShow::class, ['mealPlan' => $mealPlan])
        ->set('shareEmail', 'newuser@example.com')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors();

    Mail::assertSent(ShareInvitation::class, function (ShareInvitation $mail) {
        return $mail->hasTo('newuser@example.com');
    });
});

test('sharing a grocery list with non-registered email sends invitation', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    Livewire::actingAs($this->owner)
        ->test(GroceryListsShow::class, ['groceryList' => $groceryList])
        ->set('shareEmail', 'newuser@example.com')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors();

    Mail::assertSent(ShareInvitation::class, function (ShareInvitation $mail) {
        return $mail->hasTo('newuser@example.com');
    });
});

test('share-all with non-registered email sends invitation', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', 'newuser@example.com')
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasNoErrors();

    Mail::assertSent(ShareInvitation::class, function (ShareInvitation $mail) {
        return $mail->hasTo('newuser@example.com');
    });
});

test('invitation email contains owner name and content description', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => $this->owner->id,
        'name' => 'Test Pasta Recipe',
    ]);

    Livewire::actingAs($this->owner)
        ->test(RecipesShow::class, ['recipe' => $recipe])
        ->set('shareEmail', 'newuser@example.com')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors();

    Mail::assertSent(ShareInvitation::class, function (ShareInvitation $mail) {
        return $mail->ownerName === 'Alice Owner'
            && str_contains($mail->contentDescription, 'Test Pasta Recipe');
    });
});

test('share-all invitation describes content type', function () {
    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', 'newuser@example.com')
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasNoErrors();

    Mail::assertSent(ShareInvitation::class, function (ShareInvitation $mail) {
        return $mail->ownerName === 'Alice Owner'
            && str_contains($mail->contentDescription, 'all Recipes');
    });
});

// ──────────────────────────────────────────
// No Email Sent for Registered User (T051)
// ──────────────────────────────────────────

test('sharing with registered email does not send invitation', function () {
    $recipient = User::factory()->create();
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    Livewire::actingAs($this->owner)
        ->test(RecipesShow::class, ['recipe' => $recipe])
        ->set('shareEmail', $recipient->email)
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors();

    Mail::assertNotSent(ShareInvitation::class);
});

test('share-all with registered email does not send invitation', function () {
    $recipient = User::factory()->create();

    Livewire::actingAs($this->owner)
        ->test(Sharing::class)
        ->set('shareAllEmail', $recipient->email)
        ->set('shareAllType', 'recipe')
        ->set('shareAllPermission', 'read')
        ->call('shareAll')
        ->assertHasNoErrors();

    Mail::assertNotSent(ShareInvitation::class);
});

// ──────────────────────────────────────────
// Pending Shares Resolved on Registration (T051)
// ──────────────────────────────────────────

test('pending shares are resolved when user registers', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => null,
        'recipient_email' => 'newuser@example.com',
        'permission' => SharePermission::Read,
    ]);

    // Simulate registration
    $newUser = User::factory()->create(['email' => 'newuser@example.com']);
    event(new Registered($newUser));

    $share = ContentShare::where('recipient_email', 'newuser@example.com')->first();

    expect($share->recipient_id)->toBe($newUser->id);
});

test('multiple pending shares all resolve on registration', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => null,
        'recipient_email' => 'newuser@example.com',
        'permission' => SharePermission::Read,
    ]);

    ContentShare::factory()->forMealPlan($mealPlan)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => null,
        'recipient_email' => 'newuser@example.com',
        'permission' => SharePermission::Write,
    ]);

    ContentShare::factory()->shareAll(GroceryList::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => null,
        'recipient_email' => 'newuser@example.com',
        'permission' => SharePermission::Read,
    ]);

    $newUser = User::factory()->create(['email' => 'newuser@example.com']);
    event(new Registered($newUser));

    $shares = ContentShare::where('recipient_email', 'newuser@example.com')->get();

    expect($shares)->toHaveCount(3);
    $shares->each(fn ($share) => expect($share->recipient_id)->toBe($newUser->id));
});

// ──────────────────────────────────────────
// Registration with Different Email (T051)
// ──────────────────────────────────────────

test('registration with different email does not resolve pending shares', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => null,
        'recipient_email' => 'specific@example.com',
        'permission' => SharePermission::Read,
    ]);

    $newUser = User::factory()->create(['email' => 'different@example.com']);
    event(new Registered($newUser));

    $share = ContentShare::where('recipient_email', 'specific@example.com')->first();

    expect($share->recipient_id)->toBeNull();
});

// ──────────────────────────────────────────
// Duplicate Invitation Prevention (T051)
// ──────────────────────────────────────────

test('re-sharing with same non-registered email does not send duplicate invitation', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    // First share creates pending + sends email
    Livewire::actingAs($this->owner)
        ->test(RecipesShow::class, ['recipe' => $recipe])
        ->set('shareEmail', 'newuser@example.com')
        ->set('sharePermission', 'read')
        ->call('shareWith')
        ->assertHasNoErrors();

    // Second share (upsert) should not send another email
    Livewire::actingAs($this->owner)
        ->test(RecipesShow::class, ['recipe' => $recipe])
        ->set('shareEmail', 'newuser@example.com')
        ->set('sharePermission', 'write')
        ->call('shareWith')
        ->assertHasNoErrors();

    Mail::assertSent(ShareInvitation::class, 1);
});
