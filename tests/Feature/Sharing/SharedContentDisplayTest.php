<?php

declare(strict_types=1);

use App\Enums\SharePermission;
use App\Livewire\Dashboard;
use App\Livewire\GroceryLists\Index as GroceryListsIndex;
use App\Livewire\GroceryLists\Show as GroceryListsShow;
use App\Livewire\MealPlans\Index as MealPlansIndex;
use App\Livewire\MealPlans\Show as MealPlansShow;
use App\Livewire\Recipes\Index as RecipesIndex;
use App\Livewire\Recipes\Show as RecipesShow;
use App\Models\ContentShare;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->owner = User::factory()->create(['name' => 'Alice Owner']);
    $this->recipient = User::factory()->create(['name' => 'Bob Recipient']);
});

// ──────────────────────────────────────────
// Shared Items in Index Views (T036)
// ──────────────────────────────────────────

test('shared recipe appears in recipient recipe index', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => $this->owner->id,
        'name' => 'Shared Pasta Recipe',
    ]);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->recipient)
        ->test(RecipesIndex::class)
        ->assertSee('Shared Pasta Recipe')
        ->assertSee('Alice Owner');
});

test('share-all recipes appear in recipient recipe index', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => $this->owner->id,
        'name' => 'All-Shared Recipe',
    ]);

    ContentShare::factory()->shareAll(Recipe::class)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->recipient)
        ->test(RecipesIndex::class)
        ->assertSee('All-Shared Recipe')
        ->assertSee('Alice Owner');
});

test('shared meal plan appears in recipient meal plan index', function () {
    $mealPlan = MealPlan::factory()->create([
        'user_id' => $this->owner->id,
        'name' => 'Shared Weekly Plan',
    ]);

    ContentShare::factory()->forMealPlan($mealPlan)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->recipient)
        ->test(MealPlansIndex::class)
        ->assertSee('Shared Weekly Plan')
        ->assertSee('Alice Owner');
});

test('shared grocery list appears in recipient grocery list index', function () {
    $groceryList = GroceryList::factory()->create([
        'user_id' => $this->owner->id,
        'name' => 'Shared Shopping List',
    ]);

    ContentShare::factory()->forGroceryList($groceryList)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->recipient)
        ->test(GroceryListsIndex::class)
        ->assertSee('Shared Shopping List')
        ->assertSee('Alice Owner');
});

// ──────────────────────────────────────────
// Owner Badge Display (T036)
// ──────────────────────────────────────────

test('shared recipe shows shared-by badge with owner name', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => $this->owner->id,
        'name' => 'Owner Recipe',
    ]);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->recipient)
        ->test(RecipesIndex::class)
        ->assertSee('Shared by Alice Owner');
});

test('own recipes do not show shared-by badge', function () {
    Recipe::factory()->create([
        'user_id' => $this->recipient->id,
        'name' => 'My Own Recipe',
    ]);

    Livewire::actingAs($this->recipient)
        ->test(RecipesIndex::class)
        ->assertSee('My Own Recipe')
        ->assertDontSee('Shared by');
});

// ──────────────────────────────────────────
// Show Page — Permission-Based Controls (T036)
// ──────────────────────────────────────────

test('read-only recipient cannot see edit or delete buttons on recipe show', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => $this->owner->id,
    ]);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    $component = Livewire::actingAs($this->recipient)
        ->test(RecipesShow::class, ['recipe' => $recipe]);

    // Verify share/edit/delete buttons are not rendered (use wire attributes to avoid matching badge text)
    expect($component->html())
        ->not->toContain('wire:click="openShareModal"')
        ->not->toContain('route(\'recipes.edit\'')
        ->not->toContain('route(\'recipes.destroy\'');
});

test('write-shared recipient can see edit button but not delete or share on recipe show', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => $this->owner->id,
    ]);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Write,
    ]);

    $component = Livewire::actingAs($this->recipient)
        ->test(RecipesShow::class, ['recipe' => $recipe])
        ->assertSee('Edit');

    expect($component->html())
        ->not->toContain('wire:click="openShareModal"')
        ->not->toContain('route(\'recipes.destroy\'');
});

test('owner sees all controls on recipe show', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => $this->owner->id,
    ]);

    $component = Livewire::actingAs($this->owner)
        ->test(RecipesShow::class, ['recipe' => $recipe])
        ->assertSee('Edit')
        ->assertSee('Delete');

    expect($component->html())->toContain('wire:click="openShareModal"');
});

test('read-only recipient cannot see edit or delete on meal plan show', function () {
    $mealPlan = MealPlan::factory()->create([
        'user_id' => $this->owner->id,
    ]);

    ContentShare::factory()->forMealPlan($mealPlan)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    $component = Livewire::actingAs($this->recipient)
        ->test(MealPlansShow::class, ['mealPlan' => $mealPlan]);

    expect($component->html())
        ->not->toContain('wire:click="openShareModal"')
        ->not->toContain('route(\'meal-plans.edit\'')
        ->not->toContain('wire:click="delete"');
});

test('read-only recipient cannot see delete on grocery list show', function () {
    $groceryList = GroceryList::factory()->create([
        'user_id' => $this->owner->id,
    ]);

    ContentShare::factory()->forGroceryList($groceryList)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    $component = Livewire::actingAs($this->recipient)
        ->test(GroceryListsShow::class, ['groceryList' => $groceryList]);

    expect($component->html())
        ->not->toContain('wire:click="confirmDelete"')
        ->not->toContain('wire:click="openShareModal"');
});

// ──────────────────────────────────────────
// Recipients Cannot Delete or Re-Share (T036)
// ──────────────────────────────────────────

test('recipient with write permission cannot delete recipe', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => $this->owner->id,
    ]);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Write,
    ]);

    $component = Livewire::actingAs($this->recipient)
        ->test(RecipesShow::class, ['recipe' => $recipe]);

    expect($component->html())->not->toContain('route(\'recipes.destroy\'');
});

test('recipient with write permission cannot re-share recipe', function () {
    $recipe = Recipe::factory()->create([
        'user_id' => $this->owner->id,
    ]);

    ContentShare::factory()->forRecipe($recipe)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Write,
    ]);

    $component = Livewire::actingAs($this->recipient)
        ->test(RecipesShow::class, ['recipe' => $recipe]);

    expect($component->html())->not->toContain('wire:click="openShareModal"');
});

test('recipient with write permission cannot delete meal plan', function () {
    $mealPlan = MealPlan::factory()->create([
        'user_id' => $this->owner->id,
    ]);

    ContentShare::factory()->forMealPlan($mealPlan)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Write,
    ]);

    $component = Livewire::actingAs($this->recipient)
        ->test(MealPlansShow::class, ['mealPlan' => $mealPlan]);

    expect($component->html())->not->toContain('wire:click="delete"');
});

// ──────────────────────────────────────────
// Dashboard — Shared Content (T036)
// ──────────────────────────────────────────

test('shared meal plans appear on recipient dashboard', function () {
    $mealPlan = MealPlan::factory()->create([
        'user_id' => $this->owner->id,
        'name' => 'Dashboard Shared Plan',
        'start_date' => now(),
        'end_date' => now()->addDays(7),
    ]);

    ContentShare::factory()->forMealPlan($mealPlan)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->recipient)
        ->test(Dashboard::class)
        ->assertSee('Dashboard Shared Plan');
});

test('shared grocery lists appear on recipient dashboard', function () {
    $groceryList = GroceryList::factory()->create([
        'user_id' => $this->owner->id,
        'name' => 'Dashboard Shared Groceries',
    ]);

    ContentShare::factory()->forGroceryList($groceryList)->create([
        'owner_id' => $this->owner->id,
        'recipient_id' => $this->recipient->id,
        'recipient_email' => $this->recipient->email,
        'permission' => SharePermission::Read,
    ]);

    Livewire::actingAs($this->recipient)
        ->test(Dashboard::class)
        ->assertSee('Dashboard Shared Groceries');
});
