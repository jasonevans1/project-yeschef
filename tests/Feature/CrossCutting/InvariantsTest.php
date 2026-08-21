<?php

declare(strict_types=1);

use App\Livewire\MealPlans\Index as MealPlansIndex;
use App\Livewire\Recipes\Index as RecipesIndex;
use App\Models\GroceryItem;
use App\Models\GroceryList;
use App\Models\MealAssignment;
use App\Models\MealPlan;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

use function Pest\Laravel\assertSoftDeleted;

it('prevents deleting a recipe referenced by a meal assignment', function () {
    // Model-level companion to the controller-level test at
    // tests/Feature/Recipes/DeleteRecipeTest.php:157 ('recipe preserved in existing
    // meal plans due to ON DELETE RESTRICT on meal_assignments'), which only proves
    // RecipeController::destroy catches the QueryException. This proves the DB
    // constraint itself fires when the model is deleted directly.
    $recipe = Recipe::factory()->create();
    $mealPlan = MealPlan::factory()->create();
    MealAssignment::factory()->create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe->id,
    ]);

    expect(fn () => $recipe->delete())->toThrow(QueryException::class);
});

it('cascades meal assignment deletion when a meal plan is deleted', function () {
    $mealPlan = MealPlan::factory()->create();
    $assignment = MealAssignment::factory()->create(['meal_plan_id' => $mealPlan->id]);

    $mealPlan->delete();

    expect(MealAssignment::find($assignment->id))->toBeNull();
});

it('soft-deletes grocery lists and grocery items instead of removing the row', function () {
    $groceryList = GroceryList::factory()->create();
    $item = GroceryItem::factory()->for($groceryList, 'groceryList')->create();

    $groceryList->delete();

    assertSoftDeleted($groceryList);
    assertSoftDeleted($item);
});

// N+1 tests below reuse the DB::listen() + counter pattern from
// tests/Feature/GroceryLists/RecipeLinksTest.php:150. Instead of asserting an
// absolute query ceiling, they compare the query count for N vs 2N records:
// eager-loaded views issue the same number of queries either way, while an N+1
// view issues more queries as records grow. N=3/2N=6 stays under both
// components' page sizes (24 for recipes, 10 for meal plans) so the second
// run genuinely renders twice the rows instead of being capped by pagination.
it('avoids N+1 queries on the recipes index view regardless of record count', function () {
    $user = User::factory()->create();

    $createRecipesWithIngredients = function (int $count) use ($user): void {
        Recipe::factory()->count($count)->create(['user_id' => $user->id])
            ->each(fn (Recipe $recipe) => RecipeIngredient::factory()->for($recipe)->create());
    };

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $createRecipesWithIngredients(3);
    $queryCount = 0;
    Livewire::actingAs($user)->test(RecipesIndex::class);
    $queryCountAtThree = $queryCount;

    $createRecipesWithIngredients(3); // 6 total
    $queryCount = 0;
    Livewire::actingAs($user)->test(RecipesIndex::class);
    $queryCountAtSix = $queryCount;

    expect($queryCountAtSix)->toBe($queryCountAtThree);
});

it('redirects unauthenticated users away from every app-owned web route except the known-public set', function () {
    // Routes that pass the App\-owned filter below but are legitimately guest-reachable
    // (or use a different redirect target than the login page), keyed by route name.
    $publicRouteNames = [
        // routes/auth.php, `guest` middleware group - only usable by a guest.
        'login' => 'guest-only auth route',
        'register' => 'guest-only auth route',
        'password.request' => 'guest-only auth route',
        'password.reset' => 'guest-only auth route',
        // routes/auth.php - POST, no auth middleware; redirects a guest to `/`, not to login.
        'logout' => 'no auth middleware, redirects guest to home rather than login',
    ];

    // Not in the list above because the App\-owned filter already drops them for free:
    // - `home` (Closure) and `test.recipe.valid` / `test.recipe.invalid` (Closures,
    //   only registered in local/testing env) - intentionally public.
    // - Fortify (`Laravel\Fortify\...`), Livewire internals (`livewire.update`, uploads),
    //   and `Route::redirect('settings', ...)` (`Illuminate\Routing\RedirectController`,
    //   and unnamed anyway) - vendor-owned, not app-owned.

    $checked = 0;

    foreach (Route::getRoutes() as $route) {
        $name = $route->getName();

        if ($name === null) {
            continue;
        }

        $target = $route->action['livewire_component'] ?? $route->getActionName();

        if (! str_starts_with($target, 'App\\')) {
            continue;
        }

        if (array_key_exists($name, $publicRouteNames)) {
            continue;
        }

        $uri = '/'.preg_replace('/\{[^}]+\}/', '1', $route->uri());
        $method = collect($route->methods())->first(fn (string $httpMethod) => $httpMethod !== 'HEAD');

        $this->call($method, $uri)->assertRedirect(route('login'));

        $checked++;
    }

    expect($checked)->toBeGreaterThan(20);
});

it('avoids N+1 queries on the meal plans index view regardless of record count', function () {
    $user = User::factory()->create();

    $createMealPlansWithAssignments = function (int $count) use ($user): void {
        MealPlan::factory()->count($count)->create(['user_id' => $user->id])
            ->each(fn (MealPlan $mealPlan) => MealAssignment::factory()->for($mealPlan)->create());
    };

    $queryCount = 0;
    DB::listen(function () use (&$queryCount) {
        $queryCount++;
    });

    $createMealPlansWithAssignments(3);
    $queryCount = 0;
    Livewire::actingAs($user)->test(MealPlansIndex::class);
    $queryCountAtThree = $queryCount;

    $createMealPlansWithAssignments(3); // 6 total
    $queryCount = 0;
    Livewire::actingAs($user)->test(MealPlansIndex::class);
    $queryCountAtSix = $queryCount;

    expect($queryCountAtSix)->toBe($queryCountAtThree);
});
