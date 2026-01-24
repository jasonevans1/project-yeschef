<?php

use App\Models\GroceryList;
use App\Models\User;
use App\Policies\GroceryListPolicy;

test('owner can delete their list', function () {
    // Create test objects without database
    $user = new User;
    $user->id = 1;
    $list = new GroceryList(['user_id' => 1]);
    $policy = new GroceryListPolicy;

    expect($policy->delete($user, $list))->toBeTrue();
});

test('non-owner cannot delete another user\'s list', function () {
    // Create test objects without database
    $owner = new User;
    $owner->id = 1;
    $attacker = new User;
    $attacker->id = 2;
    $list = new GroceryList(['user_id' => 1]);
    $policy = new GroceryListPolicy;

    expect($policy->delete($attacker, $list))->toBeFalse();
});
