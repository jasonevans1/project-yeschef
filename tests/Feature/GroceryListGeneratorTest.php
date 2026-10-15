<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Enums\SourceType;
use App\Models\GroceryItem;
use App\Models\Ingredient;
use App\Models\MealPlan;
use App\Models\MealPlanNote;
use App\Models\Recipe;
use App\Models\User;
use App\Services\GroceryListGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->generator = app(GroceryListGenerator::class);
});

// Helper to build a meal plan with one recipe + one ingredient assignment
function makeMealPlanWithIngredient(
    User $user,
    string $ingredientName,
    IngredientCategory $category = IngredientCategory::PRODUCE,
    float $quantity = 1.0,
    MeasurementUnit $unit = MeasurementUnit::WHOLE,
): MealPlan {
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe = Recipe::factory()->create();
    $ingredient = Ingredient::factory()->create(['name' => $ingredientName, 'category' => $category]);
    $recipe->recipeIngredients()->create([
        'ingredient_id' => $ingredient->id,
        'quantity' => $quantity,
        'unit' => $unit,
        'sort_order' => 0,
    ]);
    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    return $mealPlan;
}

it('returns an empty collection from getIngredientPreview when meal plan has no assignments', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();

    $result = $this->generator->getIngredientPreview($mealPlan, $this->user->id);

    expect($result)->toBeEmpty();
});

it('returns all aggregated ingredients from getIngredientPreview for a meal plan with recipes', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $recipe = Recipe::factory()->create();

    $salt = Ingredient::factory()->create(['name' => 'salt', 'category' => IngredientCategory::COOKING_AND_BAKING]);
    $pepper = Ingredient::factory()->create(['name' => 'black pepper', 'category' => IngredientCategory::COOKING_AND_BAKING]);

    $recipe->recipeIngredients()->create(['ingredient_id' => $salt->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::TSP, 'sort_order' => 0]);
    $recipe->recipeIngredients()->create(['ingredient_id' => $pepper->id, 'quantity' => 0.5, 'unit' => MeasurementUnit::TSP, 'sort_order' => 1]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    $result = $this->generator->getIngredientPreview($mealPlan, $this->user->id);
    $names = $result->pluck('name')->all();

    expect($result)->toHaveCount(2)
        ->and($names)->toContain('Salt')
        ->toContain('Black pepper');
});

it('returns items with expected shape keys from getIngredientPreview', function () {
    $mealPlan = makeMealPlanWithIngredient($this->user, 'carrot', IngredientCategory::PRODUCE, 2.0, MeasurementUnit::WHOLE);

    $result = $this->generator->getIngredientPreview($mealPlan, $this->user->id);
    $item = $result->first();

    expect($result)->toHaveCount(1)
        ->and($item)->toHaveKey('name')
        ->toHaveKey('quantity')
        ->toHaveKey('unit')
        ->toHaveKey('category')
        ->toHaveKey('category_label')
        ->and($item['name'])->toBeString()
        ->and($item['category'])->toBeString()
        ->and($item['category_label'])->toBeString()
        ->and($item['unit'] === null || is_string($item['unit']))->toBeTrue();
});

it('sorts getIngredientPreview results by category enum order then alphabetically by name', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $recipe = Recipe::factory()->create();

    // MEAT = 'meat' (index 11), DAIRY = 'dairy' (index 5), PRODUCE = 'produce' (index 14)
    $chicken = Ingredient::factory()->create(['name' => 'chicken', 'category' => IngredientCategory::MEAT]);
    $milk = Ingredient::factory()->create(['name' => 'milk', 'category' => IngredientCategory::DAIRY]);
    $apple = Ingredient::factory()->create(['name' => 'apple', 'category' => IngredientCategory::PRODUCE]);
    $zucchini = Ingredient::factory()->create(['name' => 'zucchini', 'category' => IngredientCategory::PRODUCE]);

    foreach ([$chicken, $milk, $apple, $zucchini] as $sortOrder => $ingredient) {
        $recipe->recipeIngredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity' => 1.0,
            'unit' => MeasurementUnit::WHOLE,
            'sort_order' => $sortOrder,
        ]);
    }

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    $result = $this->generator->getIngredientPreview($mealPlan, $this->user->id);
    $names = $result->pluck('name')->all();

    // DAIRY (index 5) < MEAT (index 11) < PRODUCE (index 14); within PRODUCE: apple < zucchini
    expect($names[0])->toBe('Milk')
        ->and($names[1])->toBe('Chicken')
        ->and($names[2])->toBe('Apple')
        ->and($names[3])->toBe('Zucchini');
});

it('omits excluded ingredient names from generate output', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $recipe = Recipe::factory()->create();

    $salt = Ingredient::factory()->create(['name' => 'salt', 'category' => IngredientCategory::COOKING_AND_BAKING]);
    $pepper = Ingredient::factory()->create(['name' => 'black pepper', 'category' => IngredientCategory::COOKING_AND_BAKING]);

    $recipe->recipeIngredients()->create(['ingredient_id' => $salt->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::TSP, 'sort_order' => 0]);
    $recipe->recipeIngredients()->create(['ingredient_id' => $pepper->id, 'quantity' => 0.5, 'unit' => MeasurementUnit::TSP, 'sort_order' => 1]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    $groceryList = $this->generator->generate($mealPlan, [], ['salt']);

    $itemNames = $groceryList->groceryItems->pluck('name')->all();
    expect($itemNames)->not->toContain('Salt')
        ->and($itemNames)->toContain('Black pepper');
});

it('stores excluded_ingredients on the GroceryList after generate', function () {
    $mealPlan = makeMealPlanWithIngredient($this->user, 'salt', IngredientCategory::COOKING_AND_BAKING);

    $groceryList = $this->generator->generate($mealPlan, [], ['salt']);

    expect($groceryList->excluded_ingredients)->toBe(['salt']);
});

it('stores null for excluded_ingredients on the GroceryList when none excluded', function () {
    $mealPlan = makeMealPlanWithIngredient($this->user, 'salt', IngredientCategory::COOKING_AND_BAKING);

    $groceryList = $this->generator->generate($mealPlan, [], []);

    expect($groceryList->excluded_ingredients)->toBeNull();
});

it('omits excluded ingredient names from regenerate output', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $recipe = Recipe::factory()->create();

    $salt = Ingredient::factory()->create(['name' => 'salt', 'category' => IngredientCategory::COOKING_AND_BAKING]);
    $pepper = Ingredient::factory()->create(['name' => 'black pepper', 'category' => IngredientCategory::COOKING_AND_BAKING]);

    $recipe->recipeIngredients()->create(['ingredient_id' => $salt->id, 'quantity' => 1.0, 'unit' => MeasurementUnit::TSP, 'sort_order' => 0]);
    $recipe->recipeIngredients()->create(['ingredient_id' => $pepper->id, 'quantity' => 0.5, 'unit' => MeasurementUnit::TSP, 'sort_order' => 1]);

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    // First generate without exclusions
    $groceryList = $this->generator->generate($mealPlan);

    // Then regenerate with exclusions
    $regenerated = $this->generator->regenerate($groceryList, [], ['salt']);

    $itemNames = $regenerated->groceryItems->pluck('name')->all();
    expect($itemNames)->not->toContain('Salt')
        ->and($itemNames)->toContain('Black pepper');
});

it('stores excluded_ingredients on the GroceryList after regenerate', function () {
    $mealPlan = makeMealPlanWithIngredient($this->user, 'salt', IngredientCategory::COOKING_AND_BAKING);

    $groceryList = $this->generator->generate($mealPlan);
    $regenerated = $this->generator->regenerate($groceryList, [], ['salt']);

    expect($regenerated->excluded_ingredients)->toBe(['salt']);
});

it('does not remove manual items matching an excluded ingredient name during regenerate', function () {
    $mealPlan = makeMealPlanWithIngredient($this->user, 'salt', IngredientCategory::COOKING_AND_BAKING);

    $groceryList = $this->generator->generate($mealPlan);

    // Add a manual item with the same name as what will be excluded
    GroceryItem::create([
        'grocery_list_id' => $groceryList->id,
        'name' => 'Salt',
        'quantity' => 5.0,
        'unit' => MeasurementUnit::CUP,
        'category' => IngredientCategory::COOKING_AND_BAKING,
        'source_type' => SourceType::MANUAL,
        'sort_order' => 99,
    ]);

    $regenerated = $this->generator->regenerate($groceryList, [], ['salt']);

    // The manual item should remain
    $saltItems = $regenerated->groceryItems->where('name', 'Salt');
    expect($saltItems)->toHaveCount(1)
        ->and($saltItems->first()->source_type)->toBe(SourceType::MANUAL);
});

it('excludes by category AND by ingredient name independently in generate', function () {
    $mealPlan = MealPlan::factory()->for($this->user)->create();
    $recipe = Recipe::factory()->create();

    $chicken = Ingredient::factory()->create(['name' => 'chicken', 'category' => IngredientCategory::MEAT]);
    $milk = Ingredient::factory()->create(['name' => 'milk', 'category' => IngredientCategory::DAIRY]);
    $apple = Ingredient::factory()->create(['name' => 'apple', 'category' => IngredientCategory::PRODUCE]);

    foreach ([$chicken, $milk, $apple] as $sortOrder => $ingredient) {
        $recipe->recipeIngredients()->create([
            'ingredient_id' => $ingredient->id,
            'quantity' => 1.0,
            'unit' => MeasurementUnit::WHOLE,
            'sort_order' => $sortOrder,
        ]);
    }

    $mealPlan->mealAssignments()->create([
        'recipe_id' => $recipe->id,
        'date' => now()->toDateString(),
        'meal_type' => 'dinner',
        'serving_multiplier' => 1.0,
    ]);

    // Exclude DAIRY category AND 'chicken' by name
    $groceryList = $this->generator->generate($mealPlan, [IngredientCategory::DAIRY], ['chicken']);

    $itemNames = $groceryList->groceryItems->pluck('name')->all();
    expect($itemNames)->not->toContain('Milk')
        ->and($itemNames)->not->toContain('Chicken')
        ->and($itemNames)->toContain('Apple');
});

it('applies ingredient exclusion case-insensitively', function () {
    $mealPlan = makeMealPlanWithIngredient($this->user, 'black pepper', IngredientCategory::COOKING_AND_BAKING);

    // Pass exclusion in various cases
    $groceryList = $this->generator->generate($mealPlan, [], ['BLACK PEPPER']);

    $itemNames = $groceryList->groceryItems->pluck('name')->all();
    expect($itemNames)->not->toContain('Black pepper');
});

// FR-010: Notes excluded from grocery list generation
it('excludes meal plan notes from generated grocery list items', function () {
    $date = now()->toDateString();
    $mealPlan = makeMealPlanWithIngredient($this->user, 'carrot', IngredientCategory::PRODUCE, 2.0, MeasurementUnit::WHOLE);

    // Note lives in the same date/meal-type slot as the recipe assignment
    MealPlanNote::factory()->for($mealPlan)->create([
        'date' => $date,
        'meal_type' => 'dinner',
        'title' => 'Bring napkins',
        'details' => 'Remember the paprika garnish',
    ]);

    $groceryList = $this->generator->generate($mealPlan);

    $itemNames = $groceryList->groceryItems->pluck('name')->all();
    expect($itemNames)->toBe(['Carrot'])
        ->and($itemNames)->not->toContain('Bring napkins')
        ->and($itemNames)->not->toContain('Remember the paprika garnish');
});
