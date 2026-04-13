<?php

declare(strict_types=1);

use App\Enums\IngredientCategory;
use App\Enums\SourceType;
use App\Livewire\GroceryLists\Show;
use App\Models\ContentShare;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->mealPlan = MealPlan::factory()->for($this->user)->create();
    $this->groceryList = GroceryList::factory()->for($this->user)->create([
        'meal_plan_id' => $this->mealPlan->id,
    ]);
    $this->recipe = Recipe::factory()->for($this->user)->create(['name' => 'Pasta Carbonara']);
});

it('displays recipe name as a link on a generated item from a meal plan grocery list', function () {
    GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Eggs',
        'source_type' => SourceType::GENERATED,
        'category' => IngredientCategory::DAIRY,
        'recipe_ids' => [$this->recipe->id],
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->assertSee('Pasta Carbonara')
        ->assertSee(route('recipes.show', $this->recipe));
});

it('displays multiple recipe links when an item was aggregated from multiple recipes', function () {
    $recipe2 = Recipe::factory()->for($this->user)->create(['name' => 'Egg Fried Rice']);

    GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Eggs',
        'source_type' => SourceType::GENERATED,
        'category' => IngredientCategory::DAIRY,
        'recipe_ids' => [$this->recipe->id, $recipe2->id],
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->assertSee('Pasta Carbonara')
        ->assertSee('Egg Fried Rice')
        ->assertSee(route('recipes.show', $this->recipe))
        ->assertSee(route('recipes.show', $recipe2));
});

it('does not display recipe links on manual items', function () {
    GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Eggs',
        'source_type' => SourceType::MANUAL,
        'category' => IngredientCategory::DAIRY,
        'recipe_ids' => [$this->recipe->id],
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->assertDontSee(route('recipes.show', $this->recipe));
});

it('does not display recipe links on a standalone grocery list', function () {
    $standaloneList = GroceryList::factory()->for($this->user)->create([
        'meal_plan_id' => null,
    ]);

    GroceryItem::factory()->for($standaloneList)->create([
        'name' => 'Eggs',
        'source_type' => SourceType::GENERATED,
        'category' => IngredientCategory::DAIRY,
        'recipe_ids' => [$this->recipe->id],
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $standaloneList])
        ->assertDontSee(route('recipes.show', $this->recipe));
});

it('does not display a recipe link when the recipe has been hard-deleted', function () {
    $deletedRecipe = Recipe::factory()->for($this->user)->create(['name' => 'Deleted Recipe']);
    $deletedRecipeId = $deletedRecipe->id;
    $deletedRecipeUrl = route('recipes.show', $deletedRecipe);
    $deletedRecipe->delete();

    GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Eggs',
        'source_type' => SourceType::GENERATED,
        'category' => IngredientCategory::DAIRY,
        'recipe_ids' => [$deletedRecipeId],
    ]);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->assertDontSee('Deleted Recipe')
        ->assertDontSee($deletedRecipeUrl);
});

it('does not display a recipe link when the current user lacks permission to view that recipe (shared grocery list recipient)', function () {
    $owner = $this->user;
    $recipient = User::factory()->create();

    // Share the grocery list with the recipient
    ContentShare::factory()->create([
        'owner_id' => $owner->id,
        'recipient_id' => $recipient->id,
        'recipient_email' => $recipient->email,
        'shareable_type' => GroceryList::class,
        'shareable_id' => $this->groceryList->id,
        'share_all' => false,
    ]);

    // Recipe is owned by owner, not shared with recipient
    GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Eggs',
        'source_type' => SourceType::GENERATED,
        'category' => IngredientCategory::DAIRY,
        'recipe_ids' => [$this->recipe->id],
    ]);

    Livewire::actingAs($recipient)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->assertDontSee('Pasta Carbonara')
        ->assertDontSee(route('recipes.show', $this->recipe));
});

it('renders the recipe link with the correct route(\'recipes.show\') href', function () {
    GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Pasta',
        'source_type' => SourceType::GENERATED,
        'category' => IngredientCategory::OTHER,
        'recipe_ids' => [$this->recipe->id],
    ]);

    $expectedUrl = route('recipes.show', $this->recipe);

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->assertSee("href=\"{$expectedUrl}\"", false);
});

it('batch-loads recipes in a single query regardless of item count (no N+1)', function () {
    $recipe2 = Recipe::factory()->for($this->user)->create(['name' => 'Recipe Two']);
    $recipe3 = Recipe::factory()->for($this->user)->create(['name' => 'Recipe Three']);

    GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Item A',
        'source_type' => SourceType::GENERATED,
        'category' => IngredientCategory::DAIRY,
        'recipe_ids' => [$this->recipe->id],
    ]);

    GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Item B',
        'source_type' => SourceType::GENERATED,
        'category' => IngredientCategory::PRODUCE,
        'recipe_ids' => [$recipe2->id],
    ]);

    GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Item C',
        'source_type' => SourceType::GENERATED,
        'category' => IngredientCategory::MEAT,
        'recipe_ids' => [$recipe3->id],
    ]);

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $this->groceryList]);

    // At most one query should be used to load all recipes (the whereIn query)
    // We capture all queries and verify recipe queries are batched
    // Since we have 3 items with different recipe_ids, a naive N+1 would issue 3+ recipe queries.
    // A batch-load issues exactly 1 recipe query.
    $recipeQueryCount = 0;
    $queryCount2 = 0;
    DB::listen(function ($query) use (&$recipeQueryCount) {
        if (str_contains($query->sql, 'recipes') && str_contains(strtolower($query->sql), 'where')) {
            $recipeQueryCount++;
        }
    });

    // Re-run after setting up listener
    Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $this->groceryList]);

    expect($recipeQueryCount)->toBeLessThanOrEqual(1);
});

it('does not render an empty recipe links container when every referenced recipe is filtered out', function () {
    $otherOwner = User::factory()->create();
    $privateRecipe = Recipe::factory()->for($otherOwner)->create(['name' => 'Private Recipe']);

    GroceryItem::factory()->for($this->groceryList)->create([
        'name' => 'Eggs',
        'source_type' => SourceType::GENERATED,
        'category' => IngredientCategory::DAIRY,
        'recipe_ids' => [$privateRecipe->id],
    ]);

    $html = Livewire::actingAs($this->user)
        ->test(Show::class, ['groceryList' => $this->groceryList])
        ->html();

    // Should not contain empty recipe link container
    // The guard @if(count($visibleRecipes) > 0) should prevent rendering empty containers
    expect($html)->not->toContain('Private Recipe');
    // Should not render the "from recipe" section at all when all recipes are filtered
    expect($html)->not->toContain('recipe-links-container');
});
