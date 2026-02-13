<?php

declare(strict_types=1);

use App\Models\ContentShare;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->recipient = User::factory()->create();
    $this->stranger = User::factory()->create();
});

// ──────────────────────────────────────────
// ContentShare Model Relationships
// ──────────────────────────────────────────

test('content share belongs to owner', function () {
    $share = ContentShare::factory()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    expect($share->owner->id)->toBe($this->owner->id);
});

test('content share belongs to recipient', function () {
    $share = ContentShare::factory()->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    expect($share->recipient->id)->toBe($this->recipient->id);
});

test('content share has morphTo shareable for recipe', function () {
    $recipe = Recipe::factory()->create(['user_id' => $this->owner->id]);

    $share = ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    expect($share->shareable)->toBeInstanceOf(Recipe::class);
    expect($share->shareable->id)->toBe($recipe->id);
});

test('content share has morphTo shareable for meal plan', function () {
    $mealPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);

    $share = ContentShare::factory()->forMealPlan($mealPlan)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    expect($share->shareable)->toBeInstanceOf(MealPlan::class);
    expect($share->shareable->id)->toBe($mealPlan->id);
});

test('content share has morphTo shareable for grocery list', function () {
    $groceryList = GroceryList::factory()->create(['user_id' => $this->owner->id]);

    $share = ContentShare::factory()->forGroceryList($groceryList)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    expect($share->shareable)->toBeInstanceOf(GroceryList::class);
    expect($share->shareable->id)->toBe($groceryList->id);
});

test('share all has null shareable', function () {
    $share = ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    expect($share->shareable_id)->toBeNull();
    expect($share->share_all)->toBeTrue();
});

test('pending share has null recipient_id', function () {
    $share = ContentShare::factory()->pending('invited@example.com')->create([
        'owner_id' => $this->owner->id,
    ]);

    expect($share->recipient_id)->toBeNull();
    expect($share->recipient_email)->toBe('invited@example.com');
    expect($share->is_pending)->toBeTrue();
});

// ──────────────────────────────────────────
// User Relationships
// ──────────────────────────────────────────

test('user has outgoing shares', function () {
    ContentShare::factory()->count(3)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    expect($this->owner->outgoingShares)->toHaveCount(3);
});

test('user has incoming shares', function () {
    ContentShare::factory()->count(2)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    expect($this->recipient->incomingShares)->toHaveCount(2);
});

// ──────────────────────────────────────────
// Recipe::accessibleBy Scope
// ──────────────────────────────────────────

test('recipe accessibleBy returns owned recipes', function () {
    $ownedRecipe = Recipe::factory()->create(['user_id' => $this->owner->id]);
    Recipe::factory()->create(['user_id' => $this->stranger->id]);

    $results = Recipe::accessibleBy($this->owner)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($ownedRecipe->id);
});

test('recipe accessibleBy returns specifically shared recipes', function () {
    $sharedRecipe = Recipe::factory()->create(['user_id' => $this->owner->id]);
    Recipe::factory()->create(['user_id' => $this->owner->id]); // not shared

    ContentShare::factory()->forRecipe($sharedRecipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    $results = Recipe::accessibleBy($this->recipient)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($sharedRecipe->id);
});

test('recipe accessibleBy returns share-all recipes', function () {
    $recipe1 = Recipe::factory()->create(['user_id' => $this->owner->id]);
    $recipe2 = Recipe::factory()->create(['user_id' => $this->owner->id]);
    Recipe::factory()->create(['user_id' => $this->stranger->id]); // different owner

    ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    $results = Recipe::accessibleBy($this->recipient)->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id')->sort()->values()->all())->toBe(
        collect([$recipe1->id, $recipe2->id])->sort()->values()->all()
    );
});

test('recipe accessibleBy returns no results for unshared user', function () {
    Recipe::factory()->create(['user_id' => $this->owner->id]);

    $results = Recipe::accessibleBy($this->stranger)->get();

    expect($results)->toHaveCount(0);
});

test('recipe accessibleBy includes system recipes', function () {
    $systemRecipe = Recipe::factory()->create(['user_id' => null]);
    Recipe::factory()->create(['user_id' => $this->owner->id]); // not accessible

    $results = Recipe::accessibleBy($this->recipient)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($systemRecipe->id);
});

// ──────────────────────────────────────────
// MealPlan::accessibleBy Scope
// ──────────────────────────────────────────

test('meal plan accessibleBy returns owned meal plans', function () {
    $ownedPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);
    MealPlan::factory()->create(['user_id' => $this->stranger->id]);

    $results = MealPlan::accessibleBy($this->owner)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($ownedPlan->id);
});

test('meal plan accessibleBy returns specifically shared meal plans', function () {
    $sharedPlan = MealPlan::factory()->create(['user_id' => $this->owner->id]);
    MealPlan::factory()->create(['user_id' => $this->owner->id]); // not shared

    ContentShare::factory()->forMealPlan($sharedPlan)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    $results = MealPlan::accessibleBy($this->recipient)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($sharedPlan->id);
});

test('meal plan accessibleBy returns share-all meal plans', function () {
    $plan1 = MealPlan::factory()->create(['user_id' => $this->owner->id]);
    $plan2 = MealPlan::factory()->create(['user_id' => $this->owner->id]);
    MealPlan::factory()->create(['user_id' => $this->stranger->id]); // different owner

    ContentShare::factory()->shareAll(MealPlan::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    $results = MealPlan::accessibleBy($this->recipient)->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id')->sort()->values()->all())->toBe(
        collect([$plan1->id, $plan2->id])->sort()->values()->all()
    );
});

test('meal plan accessibleBy returns no results for unshared user', function () {
    MealPlan::factory()->create(['user_id' => $this->owner->id]);

    $results = MealPlan::accessibleBy($this->stranger)->get();

    expect($results)->toHaveCount(0);
});

// ──────────────────────────────────────────
// GroceryList::accessibleBy Scope
// ──────────────────────────────────────────

test('grocery list accessibleBy returns owned grocery lists', function () {
    $ownedList = GroceryList::factory()->create(['user_id' => $this->owner->id]);
    GroceryList::factory()->create(['user_id' => $this->stranger->id]);

    $results = GroceryList::accessibleBy($this->owner)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($ownedList->id);
});

test('grocery list accessibleBy returns specifically shared grocery lists', function () {
    $sharedList = GroceryList::factory()->create(['user_id' => $this->owner->id]);
    GroceryList::factory()->create(['user_id' => $this->owner->id]); // not shared

    ContentShare::factory()->forGroceryList($sharedList)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    $results = GroceryList::accessibleBy($this->recipient)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($sharedList->id);
});

test('grocery list accessibleBy returns share-all grocery lists', function () {
    $list1 = GroceryList::factory()->create(['user_id' => $this->owner->id]);
    $list2 = GroceryList::factory()->create(['user_id' => $this->owner->id]);
    GroceryList::factory()->create(['user_id' => $this->stranger->id]); // different owner

    ContentShare::factory()->shareAll(GroceryList::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
    ]);

    $results = GroceryList::accessibleBy($this->recipient)->get();

    expect($results)->toHaveCount(2);
    expect($results->pluck('id')->sort()->values()->all())->toBe(
        collect([$list1->id, $list2->id])->sort()->values()->all()
    );
});

test('grocery list accessibleBy returns no results for unshared user', function () {
    GroceryList::factory()->create(['user_id' => $this->owner->id]);

    $results = GroceryList::accessibleBy($this->stranger)->get();

    expect($results)->toHaveCount(0);
});
