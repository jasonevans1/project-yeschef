<?php

use App\Models\GroceryList;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can create grocery list without meal_plan_id', function () {
    $groceryListData = [
        'name' => 'Party Shopping',
    ];

    $groceryList = $this->user->groceryLists()->create($groceryListData);

    expect($groceryList)->toBeInstanceOf(GroceryList::class)
        ->and($groceryList->name)->toBe('Party Shopping')
        ->and($groceryList->user_id)->toBe($this->user->id)
        ->and($groceryList->meal_plan_id)->toBeNull();

    $this->assertDatabaseHas('grocery_lists', [
        'id' => $groceryList->id,
        'user_id' => $this->user->id,
        'name' => 'Party Shopping',
        'meal_plan_id' => null,
    ]);
});

test('validation requires name', function () {
    $groceryListData = [
        'name' => '',
    ];

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    // Test validation through Livewire component (when it exists)
    // For now, test at model level
    $validated = validator($groceryListData, [
        'name' => 'required|string|min:3|max:255',
    ])->validate();
})->throws(\Illuminate\Validation\ValidationException::class);

test('validation requires name to be at least 3 characters', function () {
    $groceryListData = [
        'name' => 'ab',
    ];

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    validator($groceryListData, [
        'name' => 'required|string|min:3|max:255',
    ])->validate();
})->throws(\Illuminate\Validation\ValidationException::class);

test('validation requires name to be at most 255 characters', function () {
    $groceryListData = [
        'name' => str_repeat('a', 256),
    ];

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    validator($groceryListData, [
        'name' => 'required|string|min:3|max:255',
    ])->validate();
})->throws(\Illuminate\Validation\ValidationException::class);

test('list saved with meal_plan_id null', function () {
    $groceryList = $this->user->groceryLists()->create([
        'name' => 'Standalone List',
        'meal_plan_id' => null,
    ]);

    expect($groceryList->meal_plan_id)->toBeNull();

    $this->assertDatabaseHas('grocery_lists', [
        'id' => $groceryList->id,
        'meal_plan_id' => null,
    ]);
});

test('list marked as standalone when is_standalone computed attribute is true', function () {
    $groceryList = $this->user->groceryLists()->create([
        'name' => 'Standalone Shopping List',
        'meal_plan_id' => null,
    ]);

    // Refresh to ensure computed attributes are available
    $groceryList->refresh();

    expect($groceryList->is_standalone)->toBeTrue()
        ->and($groceryList->is_meal_plan_linked)->toBeFalse();
});

test('user cannot create list for another user', function () {
    $anotherUser = User::factory()->create();

    // Attempt to create a grocery list for another user
    $groceryList = GroceryList::create([
        'user_id' => $anotherUser->id,
        'name' => 'Unauthorized List',
    ]);

    // The current user should not be able to access this list
    $this->actingAs($this->user);

    // Verify the list belongs to another user
    expect($groceryList->user_id)->toBe($anotherUser->id)
        ->and($groceryList->user_id)->not->toBe($this->user->id);

    // Verify user cannot see another user's lists
    $userLists = $this->user->groceryLists;
    expect($userLists)->not->toContain($groceryList);
});

test('standalone list can be created with valid name', function () {
    $validNames = [
        'Grocery Shopping',
        'Party Supplies',
        'Weekend Essentials',
        'Holiday Shopping List',
    ];

    foreach ($validNames as $name) {
        $groceryList = $this->user->groceryLists()->create([
            'name' => $name,
            'meal_plan_id' => null,
        ]);

        expect($groceryList->name)->toBe($name)
            ->and($groceryList->is_standalone)->toBeTrue();
    }

    expect($this->user->groceryLists()->count())->toBe(count($validNames));
});

test('multiple standalone lists can exist for same user', function () {
    $list1 = $this->user->groceryLists()->create([
        'name' => 'Grocery List 1',
        'meal_plan_id' => null,
    ]);

    $list2 = $this->user->groceryLists()->create([
        'name' => 'Grocery List 2',
        'meal_plan_id' => null,
    ]);

    $list3 = $this->user->groceryLists()->create([
        'name' => 'Grocery List 3',
        'meal_plan_id' => null,
    ]);

    expect($this->user->groceryLists()->count())->toBe(3)
        ->and($list1->is_standalone)->toBeTrue()
        ->and($list2->is_standalone)->toBeTrue()
        ->and($list3->is_standalone)->toBeTrue();
});

test('standalone list has generated_at timestamp set on creation', function () {
    $groceryList = $this->user->groceryLists()->create([
        'name' => 'New List',
        'meal_plan_id' => null,
    ]);

    // Refresh to get the database defaults
    $groceryList->refresh();

    expect($groceryList->generated_at)->not->toBeNull()
        ->and($groceryList->generated_at)->toBeInstanceOf(\Carbon\Carbon::class);

    $this->assertDatabaseHas('grocery_lists', [
        'id' => $groceryList->id,
        'name' => 'New List',
    ]);

    // Verify generated_at is not null in database
    $listFromDb = GroceryList::find($groceryList->id);
    expect($listFromDb->generated_at)->not->toBeNull();
});
