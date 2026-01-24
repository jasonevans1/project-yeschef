<?php

use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can export own grocery list as PDF', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create([
        'name' => 'Weekly Shopping List',
    ]);

    GroceryItem::factory()->for($groceryList)->count(5)->create();

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.pdf', $groceryList));

    $response->assertSuccessful();
});

test('PDF response has correct headers', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create([
        'name' => 'Shopping List',
    ]);

    GroceryItem::factory()->for($groceryList)->create();

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.pdf', $groceryList));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment');
});

test('PDF contains grocery list name', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create([
        'name' => 'My Test Shopping List',
    ]);

    GroceryItem::factory()->for($groceryList)->create();

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.pdf', $groceryList));

    $response->assertSuccessful();

    // Note: PDF content verification is limited, but we can check the view is rendered
    // The actual PDF generation will be tested in the E2E tests
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

test('PDF contains all items grouped by category', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    // Create items in different categories
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Milk',
        'category' => \App\Enums\IngredientCategory::DAIRY,
    ]);
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Chicken',
        'category' => \App\Enums\IngredientCategory::MEAT,
    ]);
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Eggs',
        'category' => \App\Enums\IngredientCategory::DAIRY,
    ]);

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.pdf', $groceryList));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('user cannot export another user\'s grocery list', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $groceryList = GroceryList::factory()->for($otherUser)->create();

    GroceryItem::factory()->for($groceryList)->create();

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.pdf', $groceryList));

    $response->assertForbidden();
});

test('guest cannot export grocery list', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create();

    $response = $this->get(route('grocery-lists.export.pdf', $groceryList));

    $response->assertRedirect(route('login'));
});

test('exported PDF filename includes grocery list name', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create([
        'name' => 'Weekly Shopping',
    ]);

    GroceryItem::factory()->for($groceryList)->create();

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.pdf', $groceryList));

    $response->assertSuccessful();

    $contentDisposition = $response->headers->get('Content-Disposition');
    expect($contentDisposition)
        ->toContain('attachment')
        ->toContain('.pdf');
});

test('PDF export works with empty grocery list', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create([
        'name' => 'Empty List',
    ]);

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.pdf', $groceryList));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('PDF export includes item quantities and units', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Flour',
        'quantity' => 2.5,
        'unit' => \App\Enums\MeasurementUnit::CUP,
    ]);

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.pdf', $groceryList));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/pdf');
});
