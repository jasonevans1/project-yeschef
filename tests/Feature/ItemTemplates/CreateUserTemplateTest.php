<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Enums\SourceType;
use App\Jobs\UpdateUserItemTemplate;
use App\Models\GroceryList;
use App\Models\User;
use App\Models\UserItemTemplate;
use Illuminate\Support\Facades\Queue;

// T033: Test user template creation on item save
test('user template is created when manual item is saved', function () {
    Queue::fake();

    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    $groceryItem = $groceryList->groceryItems()->create([
        'name' => 'almond milk',
        'quantity' => 1,
        'unit' => MeasurementUnit::GALLON,
        'category' => IngredientCategory::BEVERAGES,
        'source_type' => SourceType::MANUAL,
        'sort_order' => 1,
    ]);

    // Verify job was dispatched
    Queue::assertPushed(UpdateUserItemTemplate::class, function ($job) use ($user) {
        return $job->userId === $user->id
            && $job->itemName === 'almond milk'
            && $job->category === IngredientCategory::BEVERAGES->value
            && $job->unit === MeasurementUnit::GALLON->value
            && $job->defaultQuantity === 1.0;
    });
});

// T034: Test usage_count increment on repeat saves
test('usage count increments when same item is saved multiple times', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    // First time adding "banana"
    $job1 = new UpdateUserItemTemplate(
        userId: $user->id,
        itemName: 'banana',
        category: IngredientCategory::PRODUCE->value,
        unit: MeasurementUnit::WHOLE->value,
    );
    $job1->handle();

    $template = UserItemTemplate::where('user_id', $user->id)
        ->where('name', 'banana')
        ->first();

    expect($template)->not->toBeNull()
        ->and($template->usage_count)->toBe(1);

    // Second time adding "banana"
    $job2 = new UpdateUserItemTemplate(
        userId: $user->id,
        itemName: 'banana',
        category: IngredientCategory::PRODUCE->value,
        unit: MeasurementUnit::WHOLE->value,
    );
    $job2->handle();

    $template->refresh();

    expect($template->usage_count)->toBe(2);

    // Third time adding "banana"
    $job3 = new UpdateUserItemTemplate(
        userId: $user->id,
        itemName: 'banana',
        category: IngredientCategory::PRODUCE->value,
        unit: MeasurementUnit::WHOLE->value,
    );
    $job3->handle();

    $template->refresh();

    expect($template->usage_count)->toBe(3);
});

// T037: Test observer only tracks manual items (not generated)
test('observer does not track generated items', function () {
    Queue::fake();

    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    // Create a GENERATED item (from recipe import)
    $groceryItem = $groceryList->groceryItems()->create([
        'name' => 'olive oil',
        'quantity' => 2,
        'unit' => MeasurementUnit::TBSP,
        'category' => IngredientCategory::PANTRY,
        'source_type' => SourceType::GENERATED,
        'sort_order' => 1,
    ]);

    // Verify job was NOT dispatched for generated items
    Queue::assertNotPushed(UpdateUserItemTemplate::class);
});

// T038: Test job updates last_used_at timestamp
test('last used at timestamp is updated when template is used again', function () {
    $user = User::factory()->create();

    // Create initial template
    $initialTime = now()->subDays(5);
    $template = UserItemTemplate::create([
        'user_id' => $user->id,
        'name' => 'sourdough bread',
        'category' => IngredientCategory::BAKERY,
        'unit' => MeasurementUnit::WHOLE,
        'default_quantity' => 1,
        'usage_count' => 3,
        'last_used_at' => $initialTime,
    ]);

    expect($template->last_used_at->toDateTimeString())
        ->toBe($initialTime->toDateTimeString());

    // Use the item again
    $job = new UpdateUserItemTemplate(
        userId: $user->id,
        itemName: 'sourdough bread',
        category: IngredientCategory::BAKERY->value,
        unit: MeasurementUnit::WHOLE->value,
    );
    $job->handle();

    $template->refresh();

    // last_used_at should be updated to current time
    expect($template->last_used_at->diffInSeconds(now()))->toBeLessThan(2)
        ->and($template->usage_count)->toBe(4);
});

test('user template stores correct default quantity from item', function () {
    $user = User::factory()->create();

    $job = new UpdateUserItemTemplate(
        userId: $user->id,
        itemName: 'chicken breast',
        category: IngredientCategory::MEAT->value,
        unit: MeasurementUnit::LB->value,
        defaultQuantity: 2.5,
    );
    $job->handle();

    $template = UserItemTemplate::where('user_id', $user->id)
        ->where('name', 'chicken breast')
        ->first();

    expect($template)->not->toBeNull()
        ->and($template->name)->toBe('chicken breast')
        ->and($template->category)->toBe(IngredientCategory::MEAT)
        ->and($template->unit)->toBe(MeasurementUnit::LB)
        ->and($template->default_quantity)->toBe(2.5)
        ->and($template->usage_count)->toBe(1);
});

test('user template category is updated when user changes preference', function () {
    $user = User::factory()->create();

    // First time: categorize as DAIRY
    $job1 = new UpdateUserItemTemplate(
        userId: $user->id,
        itemName: 'almond milk',
        category: IngredientCategory::DAIRY->value,
        unit: MeasurementUnit::GALLON->value,
    );
    $job1->handle();

    $template = UserItemTemplate::where('user_id', $user->id)
        ->where('name', 'almond milk')
        ->first();

    expect($template->category)->toBe(IngredientCategory::DAIRY);

    // Second time: user re-categorizes as BEVERAGES
    $job2 = new UpdateUserItemTemplate(
        userId: $user->id,
        itemName: 'almond milk',
        category: IngredientCategory::BEVERAGES->value,
        unit: MeasurementUnit::GALLON->value,
    );
    $job2->handle();

    $template->refresh();

    // Category should be updated to most recent preference
    expect($template->category)->toBe(IngredientCategory::BEVERAGES)
        ->and($template->usage_count)->toBe(2);
});
