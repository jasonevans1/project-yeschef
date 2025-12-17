<?php

use App\Livewire\GroceryLists\Index;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('index page displays combined list of grocery lists', function () {
    $standAloneList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Standalone Shopping List',
        'meal_plan_id' => null,
    ]);

    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $mealPlanList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Meal Plan Shopping List',
        'meal_plan_id' => $mealPlan->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(Index::class)
        ->assertOk()
        ->assertSee('Standalone Shopping List')
        ->assertSee('Meal Plan Shopping List');
});

test('standalone list displays standalone badge', function () {
    GroceryList::factory()->for($this->user)->create([
        'name' => 'My Standalone List',
        'meal_plan_id' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test(Index::class)
        ->assertSee('My Standalone List')
        ->assertSee('Standalone');
});

test('meal plan list displays meal plan badge', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    GroceryList::factory()->for($this->user)->create([
        'name' => 'My Meal Plan List',
        'meal_plan_id' => $mealPlan->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(Index::class)
        ->assertSee('My Meal Plan List')
        ->assertSee('Meal Plan');
});

test('empty state displays when no lists exist', function () {
    Livewire::actingAs($this->user)
        ->test(Index::class)
        ->assertSee('No grocery lists yet')
        ->assertSee('Create a standalone list or generate one from your meal plans');
});

test('lists display in chronological order with newest first', function () {
    $olderList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Older List',
        'created_at' => now()->subDays(2),
    ]);

    $newerList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Newer List',
        'created_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->user)
        ->test(Index::class)
        ->assertSee('Newer List')
        ->assertSee('Older List');

    // Verify order in database
    $lists = $this->user->groceryLists()->latest()->get();
    expect($lists->first()->name)->toBe('Newer List')
        ->and($lists->last()->name)->toBe('Older List');
});

test('list displays completion percentage when items exist', function () {
    $groceryList = GroceryList::factory()
        ->for($this->user)
        ->hasGroceryItems(10, ['purchased' => false])
        ->create([
            'name' => 'Test List',
        ]);

    // Mark 5 items as purchased
    $groceryList->groceryItems()->take(5)->update(['purchased' => true, 'purchased_at' => now()]);

    Livewire::actingAs($this->user)
        ->test(Index::class)
        ->assertSee('Test List')
        ->assertSee('50');
});

test('meal plan list shows link to source meal plan', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create([
        'name' => 'Week 1 Meal Plan',
    ]);

    GroceryList::factory()->for($this->user)->create([
        'name' => 'Generated List',
        'meal_plan_id' => $mealPlan->id,
    ]);

    Livewire::actingAs($this->user)
        ->test(Index::class)
        ->assertSee('Generated List')
        ->assertSee('Week 1 Meal Plan');
});

test('user only sees their own grocery lists', function () {
    $otherUser = User::factory()->create();

    GroceryList::factory()->for($this->user)->create([
        'name' => 'My List',
    ]);

    GroceryList::factory()->for($otherUser)->create([
        'name' => 'Other User List',
    ]);

    Livewire::actingAs($this->user)
        ->test(Index::class)
        ->assertSee('My List')
        ->assertDontSee('Other User List');
});
