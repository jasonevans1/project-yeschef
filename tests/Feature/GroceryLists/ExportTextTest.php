<?php

use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can export own grocery list as plain text', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create([
        'name' => 'Weekly Shopping List',
    ]);

    GroceryItem::factory()->for($groceryList)->count(5)->create();

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();
});

test('text response has correct headers', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create([
        'name' => 'Shopping List',
    ]);

    GroceryItem::factory()->for($groceryList)->create();

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment');
});

test('text format includes category headers', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create([
        'name' => 'My Shopping List',
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Milk',
        'category' => \App\Enums\IngredientCategory::DAIRY,
        'quantity' => 1,
        'unit' => \App\Enums\MeasurementUnit::GALLON,
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Chicken Breast',
        'category' => \App\Enums\IngredientCategory::MEAT,
        'quantity' => 2,
        'unit' => \App\Enums\MeasurementUnit::LB,
    ]);

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();

    $content = $response->getContent();

    // Check for category headers in markdown style
    expect($content)
        ->toContain('Dairy')
        ->toContain('Meat');
});

test('text format includes item checkboxes in markdown style', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Tomatoes',
        'category' => \App\Enums\IngredientCategory::PRODUCE,
    ]);

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();

    $content = $response->getContent();

    // Check for markdown checkboxes
    expect($content)->toContain('- [ ]');
});

test('text export includes grocery list name', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create([
        'name' => 'Party Shopping List',
    ]);

    GroceryItem::factory()->for($groceryList)->create();

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();

    $content = $response->getContent();
    expect($content)->toContain('Party Shopping List');
});

test('text export includes item names with quantities and units', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Flour',
        'quantity' => 2.5,
        'unit' => \App\Enums\MeasurementUnit::CUP,
        'category' => \App\Enums\IngredientCategory::PANTRY,
    ]);

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();

    $content = $response->getContent();
    expect($content)
        ->toContain('Flour')
        ->toContain('2.5');
});

test('text export groups items by category', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    // Create items in different categories
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Milk',
        'category' => \App\Enums\IngredientCategory::DAIRY,
    ]);
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Eggs',
        'category' => \App\Enums\IngredientCategory::DAIRY,
    ]);
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Chicken',
        'category' => \App\Enums\IngredientCategory::MEAT,
    ]);
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Carrots',
        'category' => \App\Enums\IngredientCategory::PRODUCE,
    ]);

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();

    $content = $response->getContent();

    // Verify category grouping exists
    expect($content)
        ->toContain('Dairy')
        ->toContain('Meat')
        ->toContain('Produce');
});

test('user cannot export another user\'s grocery list', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $groceryList = GroceryList::factory()->for($otherUser)->create();

    GroceryItem::factory()->for($groceryList)->create();

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertForbidden();
});

test('guest cannot export grocery list as text', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create();

    $response = $this->get(route('grocery-lists.export.text', $groceryList));

    $response->assertRedirect(route('login'));
});

test('exported text filename includes grocery list name', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create([
        'name' => 'Weekly Shopping',
    ]);

    GroceryItem::factory()->for($groceryList)->create();

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();

    $contentDisposition = $response->headers->get('Content-Disposition');
    expect($contentDisposition)
        ->toContain('attachment')
        ->toContain('.txt');
});

test('text export works with empty grocery list', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create([
        'name' => 'Empty List',
    ]);

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    $content = $response->getContent();
    expect($content)->toContain('Empty List');
});

test('text export handles items without quantities', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Bananas',
        'quantity' => null,
        'unit' => null,
        'category' => \App\Enums\IngredientCategory::PRODUCE,
    ]);

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();

    $content = $response->getContent();
    expect($content)->toContain('Bananas');
});

test('text export shows purchased items differently', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Purchased Item',
        'purchased' => true,
        'category' => \App\Enums\IngredientCategory::PANTRY,
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Unpurchased Item',
        'purchased' => false,
        'category' => \App\Enums\IngredientCategory::PANTRY,
    ]);

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();

    $content = $response->getContent();

    // Purchased items should have checked checkbox [X]
    // Unpurchased items should have unchecked checkbox [ ]
    expect($content)
        ->toContain('- [X]')
        ->toContain('- [ ]');
});

test('text export includes date generated', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    GroceryItem::factory()->for($groceryList)->create();

    $response = $this->actingAs($user)
        ->get(route('grocery-lists.export.text', $groceryList));

    $response->assertSuccessful();

    $content = $response->getContent();

    // Should include some date information
    expect($content)->toMatch('/\d{4}/'); // Contains a year
});
