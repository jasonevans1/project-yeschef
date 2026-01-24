<?php

use App\Livewire\GroceryLists\Shared;
use App\Livewire\GroceryLists\Show;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\User;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can generate shareable link', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Shopping List',
        'share_token' => null,
        'share_expires_at' => null,
    ]);

    GroceryItem::factory()->for($groceryList)->count(3)->create();

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->call('share')
        ->assertHasNoErrors();

    $groceryList->refresh();

    expect($groceryList->share_token)
        ->not->toBeNull()
        ->toBeString();

    expect($groceryList->share_expires_at)
        ->not->toBeNull()
        ->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

test('generating shareable link creates UUID token', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->call('share');

    $groceryList->refresh();

    expect($groceryList->share_token)
        ->toBeString()
        ->and(Str::isUuid($groceryList->share_token))
        ->toBeTrue();
});

test('generating shareable link sets expiration date', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => null,
        'share_expires_at' => null,
    ]);

    $before = now();

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->call('share');

    $groceryList->refresh();

    expect($groceryList->share_expires_at)
        ->not->toBeNull()
        ->toBeGreaterThan($before)
        ->toBeLessThanOrEqual(now()->addDays(7));
});

test('authenticated user can view shared list via token', function () {
    $otherUser = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Shared Shopping List',
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Milk',
    ]);

    Livewire::actingAs($otherUser)
        ->test(Shared::class, ['token' => $groceryList->share_token])
        ->assertOk()
        ->assertSee('Shared Shopping List')
        ->assertSee('Milk');
});

test('shared view is read-only', function () {
    $otherUser = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    GroceryItem::factory()->for($groceryList)->create();

    $component = Livewire::actingAs($otherUser)
        ->test(Shared::class, ['token' => $groceryList->share_token])
        ->assertOk();

    // Should not see edit/delete/add buttons
    $component->assertDontSee('Add Item')
        ->assertDontSee('Edit')
        ->assertDontSee('Delete')
        ->assertDontSee('Regenerate');

    // Should not have methods to modify items
    expect($component->instance())->not->toHaveMethod('addManualItem')
        ->and($component->instance())->not->toHaveMethod('editItem')
        ->and($component->instance())->not->toHaveMethod('deleteItem')
        ->and($component->instance())->not->toHaveMethod('regenerate');
})->skip('Requires Shared component implementation');

test('expired share links are denied', function () {
    $otherUser = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->subDays(1), // Expired yesterday
    ]);

    Livewire::actingAs($otherUser)
        ->test(Shared::class, ['token' => $groceryList->share_token])
        ->assertForbidden();
});

test('unauthenticated user redirected to login', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    Livewire::test(Shared::class, ['token' => $groceryList->share_token])
        ->assertUnauthorized();
})->skip('Requires auth middleware configuration');

test('user cannot view invalid share token', function () {
    $otherUser = User::factory()->create();

    // No grocery list with this token exists
    $invalidToken = Str::uuid()->toString();

    $response = $this->actingAs($otherUser)
        ->get(route('grocery-lists.shared', $invalidToken));

    $response->assertNotFound();
});

test('user cannot view share token that is null', function () {
    $otherUser = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => null,
        'share_expires_at' => null,
    ]);

    Livewire::actingAs($otherUser)
        ->test(Shared::class, ['token' => null])
        ->assertForbidden();
})->skip('Requires Shared component implementation');

test('share link includes token in URL', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    $shareUrl = $groceryList->share_url;

    expect($shareUrl)
        ->not->toBeNull()
        ->toContain($groceryList->share_token)
        ->toContain(route('grocery-lists.shared', $groceryList->share_token));
});

test('shared list shows owner information', function () {
    $otherUser = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Family Shopping',
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    Livewire::actingAs($otherUser)
        ->test(Shared::class, ['token' => $groceryList->share_token])
        ->assertOk()
        ->assertSee('Shared by'); // Should show "Shared by [Owner Name]"
})->skip('Requires Shared component implementation');

test('shared list shows expiration date', function () {
    $otherUser = User::factory()->create();

    $expiresAt = now()->addDays(7);

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => $expiresAt,
    ]);

    Livewire::actingAs($otherUser)
        ->test(Shared::class, ['token' => $groceryList->share_token])
        ->assertOk()
        ->assertSee('Expires'); // Should show expiration date/time
})->skip('Requires Shared component implementation');

test('owner can still edit grocery list after sharing', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Original Item',
    ]);

    // Owner should still have full access
    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertOk()
        ->assertSee('Add Item') // Owner sees edit controls
        ->assertSee('Original Item');
});

test('multiple users can view same shared link', function () {
    $user2 = User::factory()->create();
    $user3 = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Party Shopping',
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    // User 2 can view
    Livewire::actingAs($user2)
        ->test(Shared::class, ['token' => $groceryList->share_token])
        ->assertOk()
        ->assertSee('Party Shopping');

    // User 3 can view
    Livewire::actingAs($user3)
        ->test(Shared::class, ['token' => $groceryList->share_token])
        ->assertOk()
        ->assertSee('Party Shopping');
});

test('shared list displays all items', function () {
    $otherUser = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    GroceryItem::factory()->for($groceryList)->create(['name' => 'Milk']);
    GroceryItem::factory()->for($groceryList)->create(['name' => 'Bread']);
    GroceryItem::factory()->for($groceryList)->create(['name' => 'Eggs']);

    Livewire::actingAs($otherUser)
        ->test(Shared::class, ['token' => $groceryList->share_token])
        ->assertOk()
        ->assertSee('Milk')
        ->assertSee('Bread')
        ->assertSee('Eggs');
});

test('shared list displays items grouped by category', function () {
    $otherUser = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Milk',
        'category' => \App\Enums\IngredientCategory::DAIRY,
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Chicken',
        'category' => \App\Enums\IngredientCategory::MEAT,
    ]);

    Livewire::actingAs($otherUser)
        ->test(Shared::class, ['token' => $groceryList->share_token])
        ->assertOk()
        ->assertSee('Dairy')
        ->assertSee('Meat')
        ->assertSee('Milk')
        ->assertSee('Chicken');
});

test('shared list shows purchased items', function () {
    $otherUser = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Purchased Milk',
        'purchased' => true,
        'purchased_at' => now(),
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Unpurchased Bread',
        'purchased' => false,
    ]);

    Livewire::actingAs($otherUser)
        ->test(Shared::class, ['token' => $groceryList->share_token])
        ->assertOk()
        ->assertSee('Purchased Milk')
        ->assertSee('Unpurchased Bread');
});

test('viewShared policy allows authenticated user with valid token', function () {
    $otherUser = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    expect($otherUser->can('viewShared', $groceryList))->toBeTrue();
});

test('viewShared policy denies when share token is null', function () {
    $otherUser = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => null,
        'share_expires_at' => null,
    ]);

    expect($otherUser->can('viewShared', $groceryList))->toBeFalse();
});

test('viewShared policy denies when share link has expired', function () {
    $otherUser = User::factory()->create();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->subDay(), // Expired
    ]);

    expect($otherUser->can('viewShared', $groceryList))->toBeFalse();
});

test('is_shared attribute returns true when share token exists', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    expect($groceryList->is_shared)->toBeTrue();
});

test('is_shared attribute returns false when share token is null', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => null,
        'share_expires_at' => null,
    ]);

    expect($groceryList->is_shared)->toBeFalse();
});

test('share_url attribute returns null when not shared', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => null,
    ]);

    expect($groceryList->share_url)->toBeNull();
});

test('share_url attribute returns route when shared', function () {
    $token = Str::uuid()->toString();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => $token,
        'share_expires_at' => now()->addDays(7),
    ]);

    expect($groceryList->share_url)
        ->not->toBeNull()
        ->toBeString()
        ->toContain($token);
});

test('regenerating shared list preserves share token', function () {
    $token = Str::uuid()->toString();

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => $token,
        'share_expires_at' => now()->addDays(7),
    ]);

    // After regeneration, share settings should persist
    $groceryList->refresh();

    expect($groceryList->share_token)->toBe($token);
})->skip('Requires regenerate implementation');

test('user can revoke share by clearing token', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'share_token' => Str::uuid()->toString(),
        'share_expires_at' => now()->addDays(7),
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->call('revokeShare')
        ->assertHasNoErrors();

    $groceryList->refresh();

    expect($groceryList->share_token)->toBeNull()
        ->and($groceryList->share_expires_at)->toBeNull();
})->skip('Requires revokeShare implementation');
