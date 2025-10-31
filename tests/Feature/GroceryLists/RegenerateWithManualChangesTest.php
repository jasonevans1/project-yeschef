<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Enums\SourceType;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\Ingredient;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use App\Services\GroceryListGenerator;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->mealPlan = MealPlan::factory()->for($this->user)->create([
        'name' => 'Weekly Meal Plan',
        'start_date' => now(),
        'end_date' => now()->addDays(7),
    ]);

    // Create initial recipe with ingredients
    $this->recipe1 = Recipe::factory()->create(['name' => 'Recipe 1']);
    $this->ingredient1 = Ingredient::factory()->create([
        'name' => 'Milk',
        'category' => IngredientCategory::DAIRY,
    ]);
    $this->ingredient2 = Ingredient::factory()->create([
        'name' => 'Flour',
        'category' => IngredientCategory::PANTRY,
    ]);

    RecipeIngredient::create([
        'recipe_id' => $this->recipe1->id,
        'ingredient_id' => $this->ingredient1->id,
        'quantity' => 2.0,
        'unit' => MeasurementUnit::CUP,
    ]);

    RecipeIngredient::create([
        'recipe_id' => $this->recipe1->id,
        'ingredient_id' => $this->ingredient2->id,
        'quantity' => 3.0,
        'unit' => MeasurementUnit::CUP,
    ]);

    // Assign recipe to meal plan
    MealAssignment::create([
        'meal_plan_id' => $this->mealPlan->id,
        'recipe_id' => $this->recipe1->id,
        'date' => now(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    // Generate initial grocery list
    $generator = app(GroceryListGenerator::class);
    $this->groceryList = $generator->generate($this->mealPlan);
});

test('manual items preserved after regeneration', function () {
    actingAs($this->user);

    // Add a manual item
    $manualItem = GroceryItem::create([
        'grocery_list_id' => $this->groceryList->id,
        'name' => 'Paper Towels',
        'quantity' => 2.0,
        'unit' => MeasurementUnit::WHOLE,
        'category' => IngredientCategory::OTHER,
        'source_type' => SourceType::MANUAL,
        'sort_order' => 100,
    ]);

    // Regenerate the list
    $generator = app(GroceryListGenerator::class);
    $updatedList = $generator->regenerate($this->groceryList);

    // Verify manual item still exists
    $manualItemExists = $updatedList->groceryItems
        ->where('source_type', SourceType::MANUAL)
        ->where('name', 'Paper Towels')
        ->first();

    expect($manualItemExists)->not->toBeNull();
    expect($manualItemExists->name)->toBe('Paper Towels');
    expect((float) $manualItemExists->quantity)->toBe(2.0);
    expect($manualItemExists->unit)->toBe(MeasurementUnit::WHOLE);
});

test('edited generated items preserved with users values', function () {
    actingAs($this->user);

    // Find the generated milk item and edit it
    $milkItem = $this->groceryList->groceryItems()
        ->where('name', 'Milk')
        ->first();

    expect($milkItem)->not->toBeNull();

    // Edit the item (simulating user modification)
    $originalValues = [
        'name' => $milkItem->name,
        'quantity' => (string) $milkItem->quantity,
        'unit' => $milkItem->unit->value,
        'category' => $milkItem->category->value,
    ];

    $milkItem->update([
        'name' => 'Whole Milk',
        'quantity' => 3.0,
        'unit' => MeasurementUnit::PINT,
        'original_values' => $originalValues,
    ]);

    // Regenerate the list
    $generator = app(GroceryListGenerator::class);
    $updatedList = $generator->regenerate($this->groceryList);

    // Verify edited values are preserved
    $editedItem = $updatedList->groceryItems()
        ->where('name', 'Whole Milk')
        ->first();

    expect($editedItem)->not->toBeNull();
    expect($editedItem->name)->toBe('Whole Milk');
    expect((float) $editedItem->quantity)->toBe(3.0);
    expect($editedItem->unit)->toBe(MeasurementUnit::PINT);
    expect($editedItem->original_values)->not->toBeNull();
    expect($editedItem->original_values['name'])->toBe('Milk');
});

test('soft-deleted generated items not re-added', function () {
    actingAs($this->user);

    // Find the flour item and soft delete it (user removed it)
    $flourItem = $this->groceryList->groceryItems()
        ->where('name', 'Flour')
        ->first();

    expect($flourItem)->not->toBeNull();
    $flourItem->delete(); // Soft delete

    // Verify it's soft deleted
    expect($flourItem->fresh()->trashed())->toBeTrue();

    // Regenerate the list
    $generator = app(GroceryListGenerator::class);
    $updatedList = $generator->regenerate($this->groceryList);

    // Verify flour is not re-added
    $flourItemAfterRegen = $updatedList->groceryItems()
        ->where('name', 'Flour')
        ->first();

    expect($flourItemAfterRegen)->toBeNull();

    // Verify it still exists as soft-deleted
    $softDeletedFlour = $updatedList->groceryItems()
        ->withTrashed()
        ->where('name', 'Flour')
        ->first();

    expect($softDeletedFlour)->not->toBeNull();
    expect($softDeletedFlour->trashed())->toBeTrue();
});

test('unmodified generated items updated to reflect meal plan changes', function () {
    actingAs($this->user);

    // Add a new ingredient to the recipe
    $newIngredient = Ingredient::factory()->create([
        'name' => 'Sugar',
        'category' => IngredientCategory::PANTRY,
    ]);

    RecipeIngredient::create([
        'recipe_id' => $this->recipe1->id,
        'ingredient_id' => $newIngredient->id,
        'quantity' => 1.5,
        'unit' => MeasurementUnit::CUP,
    ]);

    // Change the quantity of milk in the recipe
    $milkRecipeIngredient = RecipeIngredient::where('recipe_id', $this->recipe1->id)
        ->where('ingredient_id', $this->ingredient1->id)
        ->first();
    $milkRecipeIngredient->update(['quantity' => 4.0]); // Changed from 2.0 to 4.0

    // Regenerate the list
    $generator = app(GroceryListGenerator::class);
    $updatedList = $generator->regenerate($this->groceryList);

    // Verify milk quantity was updated (since it wasn't edited by user)
    $milkItem = $updatedList->groceryItems()
        ->where('name', 'Milk')
        ->first();

    expect($milkItem)->not->toBeNull();
    expect((float) $milkItem->quantity)->toBe(4.0); // Updated quantity
    expect($milkItem->original_values)->toBeNull(); // Not edited by user
});

test('new ingredients from meal plan added as generated items', function () {
    actingAs($this->user);

    // Add a new ingredient to the recipe
    $newIngredient = Ingredient::factory()->create([
        'name' => 'Eggs',
        'category' => IngredientCategory::DAIRY,
    ]);

    RecipeIngredient::create([
        'recipe_id' => $this->recipe1->id,
        'ingredient_id' => $newIngredient->id,
        'quantity' => 6.0,
        'unit' => MeasurementUnit::WHOLE,
    ]);

    // Regenerate the list
    $generator = app(GroceryListGenerator::class);
    $updatedList = $generator->regenerate($this->groceryList);

    // Verify new ingredient was added
    $eggsItem = $updatedList->groceryItems()
        ->where('name', 'Eggs')
        ->first();

    expect($eggsItem)->not->toBeNull();
    expect($eggsItem->name)->toBe('Eggs');
    expect((float) $eggsItem->quantity)->toBe(6.0);
    expect($eggsItem->unit)->toBe(MeasurementUnit::WHOLE);
    expect($eggsItem->source_type)->toBe(SourceType::GENERATED);
    expect($eggsItem->original_values)->toBeNull();
});

test('regenerated_at timestamp updated after regeneration', function () {
    actingAs($this->user);

    $originalRegeneratedAt = $this->groceryList->regenerated_at;
    expect($originalRegeneratedAt)->toBeNull();

    // Wait a moment to ensure timestamp difference
    sleep(1);

    // Regenerate the list
    $generator = app(GroceryListGenerator::class);
    $updatedList = $generator->regenerate($this->groceryList);

    // Verify regenerated_at was set
    expect($updatedList->regenerated_at)->not->toBeNull();
    expect($updatedList->regenerated_at->greaterThan($this->groceryList->generated_at))->toBeTrue();
});

test('ingredients removed from meal plan are removed from unmodified generated items', function () {
    actingAs($this->user);

    // Remove flour from the recipe
    RecipeIngredient::where('recipe_id', $this->recipe1->id)
        ->where('ingredient_id', $this->ingredient2->id)
        ->delete();

    // Verify flour exists before regeneration
    $flourBeforeRegen = $this->groceryList->groceryItems()
        ->where('name', 'Flour')
        ->first();
    expect($flourBeforeRegen)->not->toBeNull();

    // Regenerate the list
    $generator = app(GroceryListGenerator::class);
    $updatedList = $generator->regenerate($this->groceryList);

    // Verify flour was removed from the list
    $flourAfterRegen = $updatedList->groceryItems()
        ->where('name', 'Flour')
        ->first();

    expect($flourAfterRegen)->toBeNull();
});

test('complex scenario with multiple types of changes', function () {
    actingAs($this->user);

    // 1. Add a manual item
    GroceryItem::create([
        'grocery_list_id' => $this->groceryList->id,
        'name' => 'Trash Bags',
        'quantity' => 1.0,
        'unit' => MeasurementUnit::WHOLE,
        'category' => IngredientCategory::OTHER,
        'source_type' => SourceType::MANUAL,
        'sort_order' => 100,
    ]);

    // 2. Edit a generated item (Milk)
    $milkItem = $this->groceryList->groceryItems()
        ->where('name', 'Milk')
        ->first();
    $milkItem->update([
        'quantity' => 5.0,
        'original_values' => [
            'name' => $milkItem->name,
            'quantity' => (string) $milkItem->quantity,
            'unit' => $milkItem->unit->value,
            'category' => $milkItem->category->value,
        ],
    ]);

    // 3. Soft delete a generated item (Flour)
    $flourItem = $this->groceryList->groceryItems()
        ->where('name', 'Flour')
        ->first();
    $flourItem->delete();

    // 4. Add new ingredient to recipe (Butter)
    $butterIngredient = Ingredient::factory()->create([
        'name' => 'Butter',
        'category' => IngredientCategory::DAIRY,
    ]);
    RecipeIngredient::create([
        'recipe_id' => $this->recipe1->id,
        'ingredient_id' => $butterIngredient->id,
        'quantity' => 0.5,
        'unit' => MeasurementUnit::CUP,
    ]);

    // 5. Remove an ingredient from recipe that hasn't been modified by user
    // (Flour is already in the recipe, but user deleted it, so it shouldn't come back)

    // Regenerate the list
    $generator = app(GroceryListGenerator::class);
    $updatedList = $generator->regenerate($this->groceryList);

    // Verify all expected behaviors:
    // - Manual item (Trash Bags) preserved
    $trashBags = $updatedList->groceryItems()
        ->where('name', 'Trash Bags')
        ->first();
    expect($trashBags)->not->toBeNull();
    expect($trashBags->source_type)->toBe(SourceType::MANUAL);

    // - Edited item (Milk) preserved with edited values
    $milk = $updatedList->groceryItems()
        ->where('name', 'Milk')
        ->first();
    expect($milk)->not->toBeNull();
    expect((float) $milk->quantity)->toBe(5.0);
    expect($milk->original_values)->not->toBeNull();

    // - Soft-deleted item (Flour) not re-added
    $flour = $updatedList->groceryItems()
        ->where('name', 'Flour')
        ->first();
    expect($flour)->toBeNull();

    // - New ingredient (Butter) added
    $butter = $updatedList->groceryItems()
        ->where('name', 'Butter')
        ->first();
    expect($butter)->not->toBeNull();
    expect($butter->source_type)->toBe(SourceType::GENERATED);
    expect((float) $butter->quantity)->toBe(0.5);
});

test('cannot regenerate standalone grocery list', function () {
    actingAs($this->user);

    // Create a standalone grocery list (no meal_plan_id)
    $standaloneList = GroceryList::factory()->for($this->user)->create([
        'name' => 'Standalone Shopping List',
        'meal_plan_id' => null,
        'generated_at' => now(),
    ]);

    // Attempt to regenerate
    $generator = app(GroceryListGenerator::class);

    expect(fn () => $generator->regenerate($standaloneList))
        ->toThrow(\InvalidArgumentException::class, 'Cannot regenerate a standalone grocery list');
});
