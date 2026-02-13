<?php

declare(strict_types=1);

use App\Enums\SharePermission;
use App\Models\ContentShare;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->recipient = User::factory()->create();
    $this->stranger = User::factory()->create();
});

// ──────────────────────────────────────────
// Recipe Policy — Share Permissions (T014)
// ──────────────────────────────────────────

test('owner can view their own recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    actingAs($this->owner);

    expect($this->owner->can('view', $recipe))->toBeTrue();
});

test('read-shared user can view a shared recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('view', $recipe))->toBeTrue();
});

test('write-shared user can view a shared recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forRecipe($recipe)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('view', $recipe))->toBeTrue();
});

test('unshared user cannot view another user\'s recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    actingAs($this->stranger);

    expect($this->stranger->can('view', $recipe))->toBeFalse();
});

test('share-all user can view all owner recipes', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('view', $recipe))->toBeTrue();
});

test('read-shared user cannot update a shared recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $recipe))->toBeFalse();
});

test('write-shared user can update a shared recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forRecipe($recipe)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $recipe))->toBeTrue();
});

test('shared user cannot delete a recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forRecipe($recipe)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('delete', $recipe))->toBeFalse();
});

test('only owner can share a recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    actingAs($this->owner);
    expect($this->owner->can('share', $recipe))->toBeTrue();

    actingAs($this->recipient);
    expect($this->recipient->can('share', $recipe))->toBeFalse();
});

test('share-all with write allows updating any owner recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->shareAll(Recipe::class)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $recipe))->toBeTrue();
});

test('share-all with read does not allow updating owner recipes', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $recipe))->toBeFalse();
});

// ──────────────────────────────────────────
// MealPlan Policy — Share Permissions (T015)
// ──────────────────────────────────────────

test('owner can view their own meal plan', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    actingAs($this->owner);

    expect($this->owner->can('view', $mealPlan))->toBeTrue();
});

test('read-shared user can view a shared meal plan', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forMealPlan($mealPlan)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('view', $mealPlan))->toBeTrue();
});

test('write-shared user can view a shared meal plan', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forMealPlan($mealPlan)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('view', $mealPlan))->toBeTrue();
});

test('unshared user cannot view another user\'s meal plan', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    actingAs($this->stranger);

    expect($this->stranger->can('view', $mealPlan))->toBeFalse();
});

test('share-all user can view all owner meal plans', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->shareAll(MealPlan::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('view', $mealPlan))->toBeTrue();
});

test('read-shared user cannot update a shared meal plan', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forMealPlan($mealPlan)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $mealPlan))->toBeFalse();
});

test('write-shared user can update a shared meal plan', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forMealPlan($mealPlan)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $mealPlan))->toBeTrue();
});

test('shared user cannot delete a meal plan', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forMealPlan($mealPlan)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('delete', $mealPlan))->toBeFalse();
});

test('only owner can share a meal plan', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    actingAs($this->owner);
    expect($this->owner->can('share', $mealPlan))->toBeTrue();

    actingAs($this->recipient);
    expect($this->recipient->can('share', $mealPlan))->toBeFalse();
});

test('share-all with write allows updating any owner meal plan', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->shareAll(MealPlan::class)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $mealPlan))->toBeTrue();
});

test('share-all with read does not allow updating owner meal plans', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->shareAll(MealPlan::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $mealPlan))->toBeFalse();
});

// ──────────────────────────────────────────
// GroceryList Policy — Share Permissions (T016)
// ──────────────────────────────────────────

test('owner can view their own grocery list', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    actingAs($this->owner);

    expect($this->owner->can('view', $groceryList))->toBeTrue();
});

test('read-shared user can view a shared grocery list', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forGroceryList($groceryList)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('view', $groceryList))->toBeTrue();
});

test('write-shared user can view a shared grocery list', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forGroceryList($groceryList)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('view', $groceryList))->toBeTrue();
});

test('unshared user cannot view another user\'s grocery list', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    actingAs($this->stranger);

    expect($this->stranger->can('view', $groceryList))->toBeFalse();
});

test('share-all user can view all owner grocery lists', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->shareAll(GroceryList::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('view', $groceryList))->toBeTrue();
});

test('read-shared user cannot update a shared grocery list', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forGroceryList($groceryList)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $groceryList))->toBeFalse();
});

test('write-shared user can update a shared grocery list', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forGroceryList($groceryList)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $groceryList))->toBeTrue();
});

test('shared user cannot delete a grocery list', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->forGroceryList($groceryList)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('delete', $groceryList))->toBeFalse();
});

test('only owner can share a grocery list', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    actingAs($this->owner);
    expect($this->owner->can('share', $groceryList))->toBeTrue();

    actingAs($this->recipient);
    expect($this->recipient->can('share', $groceryList))->toBeFalse();
});

test('share-all with write allows updating any owner grocery list', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->shareAll(GroceryList::class)->withWritePermission()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $groceryList))->toBeTrue();
});

test('share-all with read does not allow updating owner grocery lists', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    ContentShare::factory()->shareAll(GroceryList::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('update', $groceryList))->toBeFalse();
});

test('existing token-based viewShared still works on grocery list', function () {
    $groceryList = GroceryList::factory()->create([
        'user_id' => $this->owner->id,
        'share_token' => 'test-token',
        'share_expires_at' => now()->addDays(7),
    ]);

    actingAs($this->recipient);

    expect($this->recipient->can('viewShared', $groceryList))->toBeTrue();
});
