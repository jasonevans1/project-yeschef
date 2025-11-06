<?php

declare(strict_types=1);

use App\Models\Recipe;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

// RecipePolicy::view() tests

test('user can view system recipes', function () {
    actingAs($this->user);

    $systemRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'System Recipe',
        'instructions' => 'System instructions',
    ]);

    expect($this->user->can('view', $systemRecipe))->toBeTrue();
});

test('user can view own personal recipe', function () {
    actingAs($this->user);

    $personalRecipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My Recipe',
        'instructions' => 'My instructions',
    ]);

    expect($this->user->can('view', $personalRecipe))->toBeTrue();
});

test('user cannot view another user\'s personal recipe', function () {
    actingAs($this->user);

    $otherRecipe = Recipe::factory()->create([
        'user_id' => $this->otherUser->id,
        'name' => 'Other User Recipe',
        'instructions' => 'Other instructions',
    ]);

    expect($this->user->can('view', $otherRecipe))->toBeFalse();
});

test('all authenticated users can view system recipes', function () {
    $systemRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Shared System Recipe',
        'instructions' => 'Public instructions',
    ]);

    // Test with first user
    actingAs($this->user);
    expect($this->user->can('view', $systemRecipe))->toBeTrue();

    // Test with other user
    actingAs($this->otherUser);
    expect($this->otherUser->can('view', $systemRecipe))->toBeTrue();
});

// RecipePolicy::create() tests

test('authenticated user can create recipes', function () {
    actingAs($this->user);

    expect($this->user->can('create', Recipe::class))->toBeTrue();
});

test('all authenticated users can create recipes', function () {
    actingAs($this->user);
    expect($this->user->can('create', Recipe::class))->toBeTrue();

    actingAs($this->otherUser);
    expect($this->otherUser->can('create', Recipe::class))->toBeTrue();
});

// RecipePolicy::update() tests

test('user can update own personal recipe', function () {
    actingAs($this->user);

    $personalRecipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My Recipe',
        'instructions' => 'My instructions',
    ]);

    expect($this->user->can('update', $personalRecipe))->toBeTrue();
});

test('user cannot update another user\'s recipe', function () {
    actingAs($this->user);

    $otherRecipe = Recipe::factory()->create([
        'user_id' => $this->otherUser->id,
        'name' => 'Other Recipe',
        'instructions' => 'Other instructions',
    ]);

    expect($this->user->can('update', $otherRecipe))->toBeFalse();
});

test('user cannot update system recipes', function () {
    actingAs($this->user);

    $systemRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'System Recipe',
        'instructions' => 'System instructions',
    ]);

    expect($this->user->can('update', $systemRecipe))->toBeFalse();
});

test('system recipes cannot be updated by any user', function () {
    $systemRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Locked System Recipe',
        'instructions' => 'Protected instructions',
    ]);

    actingAs($this->user);
    expect($this->user->can('update', $systemRecipe))->toBeFalse();

    actingAs($this->otherUser);
    expect($this->otherUser->can('update', $systemRecipe))->toBeFalse();
});

// RecipePolicy::delete() tests

test('user can delete own personal recipe', function () {
    actingAs($this->user);

    $personalRecipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'My Recipe to Delete',
        'instructions' => 'Instructions',
    ]);

    expect($this->user->can('delete', $personalRecipe))->toBeTrue();
});

test('user cannot delete another user\'s recipe', function () {
    actingAs($this->user);

    $otherRecipe = Recipe::factory()->create([
        'user_id' => $this->otherUser->id,
        'name' => 'Other Recipe',
        'instructions' => 'Other instructions',
    ]);

    expect($this->user->can('delete', $otherRecipe))->toBeFalse();
});

test('user cannot delete system recipes', function () {
    actingAs($this->user);

    $systemRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'System Recipe',
        'instructions' => 'System instructions',
    ]);

    expect($this->user->can('delete', $systemRecipe))->toBeFalse();
});

test('system recipes cannot be deleted by any user', function () {
    $systemRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'Protected System Recipe',
        'instructions' => 'Cannot be deleted',
    ]);

    actingAs($this->user);
    expect($this->user->can('delete', $systemRecipe))->toBeFalse();

    actingAs($this->otherUser);
    expect($this->otherUser->can('delete', $systemRecipe))->toBeFalse();
});

// Edge cases and combined scenarios

test('ownership is strictly enforced for all operations', function () {
    actingAs($this->user);

    $userRecipe = Recipe::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'User Recipe',
        'instructions' => 'User instructions',
    ]);

    $otherRecipe = Recipe::factory()->create([
        'user_id' => $this->otherUser->id,
        'name' => 'Other Recipe',
        'instructions' => 'Other instructions',
    ]);

    // User can manage their own recipe
    expect($this->user->can('view', $userRecipe))->toBeTrue();
    expect($this->user->can('update', $userRecipe))->toBeTrue();
    expect($this->user->can('delete', $userRecipe))->toBeTrue();

    // User cannot manage other user's recipe
    expect($this->user->can('view', $otherRecipe))->toBeFalse();
    expect($this->user->can('update', $otherRecipe))->toBeFalse();
    expect($this->user->can('delete', $otherRecipe))->toBeFalse();
});

test('system recipes have read-only access for all users', function () {
    $systemRecipe = Recipe::factory()->create([
        'user_id' => null,
        'name' => 'System Recipe',
        'instructions' => 'System instructions',
    ]);

    actingAs($this->user);

    // Can view
    expect($this->user->can('view', $systemRecipe))->toBeTrue();

    // Cannot modify
    expect($this->user->can('update', $systemRecipe))->toBeFalse();
    expect($this->user->can('delete', $systemRecipe))->toBeFalse();
});

test('policy correctly handles null user_id for system recipes', function () {
    actingAs($this->user);

    $systemRecipe = Recipe::factory()->create(['user_id' => null]);

    // Verify it's truly a system recipe
    expect($systemRecipe->user_id)->toBeNull();

    // Test all permissions
    expect($this->user->can('view', $systemRecipe))->toBeTrue();
    expect($this->user->can('update', $systemRecipe))->toBeFalse();
    expect($this->user->can('delete', $systemRecipe))->toBeFalse();
});

test('policy correctly identifies recipe ownership by user_id match', function () {
    actingAs($this->user);

    $ownRecipe = Recipe::factory()->create(['user_id' => $this->user->id]);
    $otherRecipe = Recipe::factory()->create(['user_id' => $this->otherUser->id]);

    // Verify user IDs
    expect($ownRecipe->user_id)->toBe($this->user->id);
    expect($otherRecipe->user_id)->toBe($this->otherUser->id);

    // Test ownership detection
    expect($this->user->can('update', $ownRecipe))->toBeTrue();
    expect($this->user->can('update', $otherRecipe))->toBeFalse();
});
