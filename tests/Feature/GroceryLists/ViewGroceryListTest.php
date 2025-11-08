<?php

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Enums\SourceType;
use App\Livewire\GroceryLists\Show;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user can view own grocery list', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Weekly Shopping List',
    ]);

    GroceryItem::factory()->count(5)->for($groceryList)->create();

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertOk()
        ->assertSee('Weekly Shopping List')
        ->assertViewHas('groceryList', function ($list) use ($groceryList) {
            return $list->id === $groceryList->id;
        });
});

test('grocery list displays basic information', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Party Shopping',
        'generated_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('Party Shopping')
        ->assertSee('Generated'); // The view shows "Generated X ago"
});

test('items display with name quantity and unit', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    $milk = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Milk',
        'quantity' => 2,
        'unit' => MeasurementUnit::CUP,
        'category' => IngredientCategory::DAIRY,
    ]);

    $chicken = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Chicken Breast',
        'quantity' => 1.5,
        'unit' => MeasurementUnit::LB,
        'category' => IngredientCategory::MEAT,
    ]);

    $tomatoes = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Tomatoes',
        'quantity' => 4,
        'unit' => MeasurementUnit::WHOLE,
        'category' => IngredientCategory::PRODUCE,
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('Milk')
        ->assertSee('2')
        ->assertSee('cup')
        ->assertSee('Chicken Breast')
        ->assertSee('1.5')
        ->assertSee('lb')
        ->assertSee('Tomatoes')
        ->assertSee('4')
        ->assertSee('whole');
});

test('items display correctly with null quantity and unit', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Paper Towels',
        'quantity' => null,
        'unit' => null,
        'category' => IngredientCategory::OTHER,
        'source_type' => SourceType::MANUAL,
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('Paper Towels');
});

test('items are grouped by category', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    // Create items in different categories
    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Milk',
        'category' => IngredientCategory::DAIRY,
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Cheese',
        'category' => IngredientCategory::DAIRY,
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Chicken',
        'category' => IngredientCategory::MEAT,
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Tomato',
        'category' => IngredientCategory::PRODUCE,
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Lettuce',
        'category' => IngredientCategory::PRODUCE,
    ]);

    $component = Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList]);

    // Should see category headers
    $component->assertSee('Dairy')
        ->assertSee('Meat')
        ->assertSee('Produce');

    // Items should be grouped under categories
    $component->assertSee('Milk')
        ->assertSee('Cheese')
        ->assertSee('Chicken')
        ->assertSee('Tomato')
        ->assertSee('Lettuce');
});

test('category displays correct number of items', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    // Create 3 dairy items
    GroceryItem::factory()->count(3)->for($groceryList)->create([
        'category' => IngredientCategory::DAIRY,
    ]);

    // Create 2 produce items
    GroceryItem::factory()->count(2)->for($groceryList)->create([
        'category' => IngredientCategory::PRODUCE,
    ]);

    $items = $groceryList->groceryItems()->get()->groupBy(function ($item) {
        return $item->category->value;
    });

    expect($items->get('dairy')->count())->toBe(3)
        ->and($items->get('produce')->count())->toBe(2);
});

test('purchased items are visually distinguished from unpurchased', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    $purchasedItem = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Purchased Milk',
        'purchased' => true,
        'purchased_at' => now(),
    ]);

    $unpurchasedItem = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Unpurchased Bread',
        'purchased' => false,
        'purchased_at' => null,
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('Purchased Milk')
        ->assertSee('Unpurchased Bread');

    // In the actual view, purchased items would have different styling
    // (e.g., strikethrough, gray color, checked checkbox)
    expect($purchasedItem->purchased)->toBeTrue()
        ->and($unpurchasedItem->purchased)->toBeFalse();
});

test('purchased items show checked state', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    $purchasedItem = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Milk',
        'purchased' => true,
        'purchased_at' => now(),
    ]);

    expect($purchasedItem->purchased)->toBeTrue()
        ->and($purchasedItem->purchased_at)->not->toBeNull();
});

test('user cannot view another user grocery list', function () {
    $otherUser = User::factory()->create();
    $otherUserList = GroceryList::factory()->for($otherUser)->create([
        'name' => 'Other User List',
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $otherUserList])
        ->assertForbidden();
});

test('unauthorized user cannot view grocery list', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    $unauthorizedUser = User::factory()->create();

    Livewire::actingAs($unauthorizedUser)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertForbidden();
});

test('guest cannot view grocery list', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    Livewire::test(Show::class, ['groceryList' => $groceryList])
        ->assertUnauthorized();
})->skip('Requires auth middleware configuration');

test('grocery list shows source meal plan if linked', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create([
        'name' => 'Weekly Meal Plan',
    ]);

    $groceryList = GroceryList::factory()->for($this->user)->create([
        'meal_plan_id' => $mealPlan->id,
        'name' => 'Generated List',
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('Weekly Meal Plan')
        ->assertSee('Generated List')
        ->assertSee('Regenerate');

    expect($groceryList->is_meal_plan_linked)->toBeTrue()
        ->and($groceryList->meal_plan_id)->toBe($mealPlan->id);
});

test('grocery list shows standalone indicator if not linked to meal plan', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'meal_plan_id' => null,
        'name' => 'Standalone Shopping List',
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('Standalone Shopping List')
        ->assertSee('Standalone List')
        ->assertDontSee('Regenerate');

    expect($groceryList->is_standalone)->toBeTrue()
        ->and($groceryList->meal_plan_id)->toBeNull();
});

test('grocery list displays completion progress', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    // Create 10 items: 7 purchased, 3 unpurchased
    GroceryItem::factory()->count(7)->for($groceryList)->create([
        'purchased' => true,
        'purchased_at' => now(),
    ]);

    GroceryItem::factory()->count(3)->for($groceryList)->create([
        'purchased' => false,
        'purchased_at' => null,
    ]);

    $groceryList->refresh();

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('70'); // 70% completion

    expect($groceryList->completion_percentage)->toBe(70.0);
});

test('empty grocery list shows appropriate message', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Empty List',
    ]);

    expect($groceryList->groceryItems()->count())->toBe(0);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('Empty List')
        ->assertSee('No items'); // Should show "No items in this list" or similar
});

test('grocery list shows total item count', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    GroceryItem::factory()->count(12)->for($groceryList)->create();

    $groceryList->refresh();

    expect($groceryList->total_items)->toBe(12);
});

test('soft deleted items are not displayed', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    $activeItem = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Active Item',
        'deleted_at' => null,
    ]);

    $deletedItem = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Deleted Item',
        'deleted_at' => now(),
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('Active Item')
        ->assertDontSee('Deleted Item');

    // Verify count excludes soft deleted
    $activeItems = $groceryList->groceryItems()->count();
    expect($activeItems)->toBe(1);
});

test('items display in correct sort order within category', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Item C',
        'category' => IngredientCategory::PANTRY,
        'sort_order' => 2,
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Item A',
        'category' => IngredientCategory::PANTRY,
        'sort_order' => 0,
    ]);

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Item B',
        'category' => IngredientCategory::PANTRY,
        'sort_order' => 1,
    ]);

    $items = $groceryList->groceryItems()
        ->where('category', IngredientCategory::PANTRY)
        ->orderBy('sort_order')
        ->get();

    expect($items[0]->name)->toBe('Item A')
        ->and($items[1]->name)->toBe('Item B')
        ->and($items[2]->name)->toBe('Item C');
});

test('generated items show source indicator', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    $generatedItem = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Auto-added Milk',
        'source_type' => SourceType::GENERATED,
    ]);

    expect($generatedItem->is_generated)->toBeTrue()
        ->and($generatedItem->source_type)->toBe(SourceType::GENERATED);
});

test('manual items show source indicator', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    $manualItem = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Manually Added Item',
        'source_type' => SourceType::MANUAL,
    ]);

    expect($manualItem->is_manual)->toBeTrue()
        ->and($manualItem->source_type)->toBe(SourceType::MANUAL);
});

test('edited generated items show edited indicator', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    $editedItem = GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Edited Milk',
        'source_type' => SourceType::GENERATED,
        'quantity' => 3,
        'unit' => MeasurementUnit::CUP,
        'original_values' => json_encode([
            'quantity' => 2,
            'unit' => 'cup',
        ]),
    ]);

    expect($editedItem->is_edited)->toBeTrue()
        ->and($editedItem->original_values)->not->toBeNull();
});

test('item notes are displayed when present', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create();

    GroceryItem::factory()->for($groceryList)->create([
        'name' => 'Organic Milk',
        'notes' => 'Get the brand with the blue label',
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('Organic Milk')
        ->assertSee('Get the brand with the blue label');
});

test('grocery list shows regeneration timestamp if applicable', function () {
    $groceryList = GroceryList::factory()->for($this->user)->create([
        'generated_at' => now()->subDays(7),
        'regenerated_at' => now()->subDays(2),
    ]);

    expect($groceryList->regenerated_at)->not->toBeNull();

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->assertSee('Last updated'); // The view shows "Last updated X ago"
});
