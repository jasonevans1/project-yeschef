# Task 004: Cross-Cutting Invariant Oracle Tests

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Regression tests for system-wide invariants that aren't owned by any single spec but are exactly the kind of implicit rule a from-scratch rewrite could silently violate. All describe already-correct current behavior — this task adds tests only, no production code changes are expected. If any test doesn't pass on first write, that's a real finding (the assumed invariant doesn't actually hold) — report it, do not "fix" it by changing runtime behavior to make the test pass; that decision belongs to whoever reviews the finding, not to this task.

## Context

**1. Delete-policy split: `Recipe` uses RESTRICT, `GroceryList`/`GroceryItem` use soft deletes.**
Verified during planning and re-verified during plan review:
- `database/migrations/2025_10_12_190501_create_meal_assignments_table.php:17` — `$table->foreignId('recipe_id')->constrained()->restrictOnDelete()`
- same file line 16 — `$table->foreignId('meal_plan_id')->constrained()->cascadeOnDelete()` (different policy on the same table, for a different relation)
- `app/Models/GroceryList.php:15` and `app/Models/GroceryItem.php:15` both `use HasFactory, SoftDeletes;`
- `Recipe` does **not** use `SoftDeletes`, so the RESTRICT constraint genuinely fires on delete
- `config/database.php:39` — `'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true)`, so SQLite enforces FKs during tests

> **Existing coverage caught in plan review — do not duplicate.** `tests/Feature/Recipes/DeleteRecipeTest.php:157` already has `'recipe preserved in existing meal plans due to ON DELETE RESTRICT on meal_assignments'`. It exercises the **controller** path: `RecipeController::destroy` catches the `QueryException` and redirects with a flashed error (lines 20-31), so it proves the UX, not the DB constraint. Your new test should cover what it doesn't: the **model-level** invariant (`$recipe->delete()` throws `Illuminate\Database\QueryException`) and the **cascade** half (deleting a `MealPlan` removes its `MealAssignment` rows). Reference the existing test by file:line in your test's comment rather than restating it.

Note: `meal_assignments` originally had `unique(['meal_plan_id','date','meal_type'])`, dropped by `2025_12_14_194206_remove_unique_constraint_from_meal_assignments.php` — multiple assignments per slot are fine in fixtures.

**2. N+1 prevention on index views.**
Verified during planning: one explicit test already exists for the grocery list Show component — `'batch-loads recipes in a single query regardless of item count (no N+1)'` at **`tests/Feature/GroceryLists/RecipeLinksTest.php:150`** (not in the generator test file, as originally written here). Read it first and reuse its exact `DB::listen()` + counter pattern rather than inventing a new one. Nothing blanket-checks `RecipesIndex` or `MealPlansIndex`.

Targets: `app/Livewire/Recipes/Index.php` (eager-loads `recipeIngredients.ingredient` and `user`, line 58) and `app/Livewire/MealPlans/Index.php` (eager-loads `user`, `withCount('mealAssignments')`, line 16-17).

> **Pagination trap caught in plan review.** `RecipesIndex` paginates **24** (line 110); `MealPlansIndex` paginates **10** (line 19). "Seed N and 2N" with N ≥ 12 (recipes) or N ≥ 5 (meal plans) yields an identical query count purely because both runs cap at one page — the test passes without proving anything. **Use N = 3 and 2N = 6**, both comfortably under both page sizes, so the second run genuinely renders twice the rows.

Each record must have the relations that would trigger the N+1 (recipes need ingredients and an owning user; meal plans need assignments), otherwise there's nothing for a lazy load to fetch.

**3. Every app-owned route actually requires auth.**
Verified during planning: `routes/web.php` wraps almost everything in `Route::middleware(['auth'])->group(...)` (lines 42-110), with `dashboard` carrying its own `['auth','verified']` directly on the route (lines 38-40) and `/` (`home`, line 30) intentionally public.

**Do not assert zero public routes exist** — that would fail the moment a legitimate public route (e.g. a future public share link) is added. Build an explicit allow-list and assert every *other* app-owned named route redirects a guest.

> **Four fragility traps caught in plan review. The task previously under-specified all four, and the likely outcome was a test that either silently asserted almost nothing or went red for reasons unrelated to auth.**
>
> **(a) `Route::livewire()` hides the component class.** `vendor/livewire/livewire/src/Mechanisms/HandleRouting/HandleRouting.php:20-22` registers `Route::get($uri, LivewirePageController::class)` and stashes the real component in `$route->action['livewire_component']`. So `$route->getActionName()` returns a **vendor** class for every page route. Filtering "app-owned" by `getActionName()` alone silently drops all ~20 page routes and leaves you asserting only the 9 controller routes. Use this discriminator:
> ```php
> $target = $route->action['livewire_component'] ?? $route->getActionName();
> $isAppOwned = str_starts_with($target, 'App\\');
> ```
> This also excludes, for free: `home` and the test-only routes (closures → `Closure`), Fortify routes (`Laravel\Fortify\...`), Livewire's internal `livewire.update` / upload routes, and `Route::redirect('settings', ...)` (`Illuminate\Routing\RedirectController`, and unnamed anyway).
>
> **(b) Two named public routes exist during the test run.** `routes/web.php:115-153` registers `test.recipe.valid` and `test.recipe.invalid` inside `if (app()->environment(['local','testing']))` — i.e. exactly when the suite runs. They return 200 to a guest. They're closures so the filter in (a) already drops them, but say so in the allow-list comment so nobody "simplifies" the filter later.
>
> **(c) `routes/auth.php` routes are `App\`-namespaced** and will pass the filter in (a). Allow-list them by name: `login`, `register`, `password.request`, `password.reset` (all `App\Livewire\Auth\*`, `guest` middleware) and `logout` (`App\Livewire\Actions\Logout`, POST, no auth middleware — it redirects a guest to `/`, not to login). `verification.notice` and `verification.verify` **are** auth-gated and should stay in the assertion set.
>
> **(d) Verbs.** Nine app routes are POST/PUT/DELETE (`meal-plans.store/update/destroy`, `recipes.destroy`, the three `meal-plans.assignments.*`, the three `grocery-lists.items.*`). `$this->get()` on those returns **405**, not a redirect. Dispatch with the route's own method: take the first entry of `$route->methods()` that isn't `HEAD` and use `$this->call($method, $uri)`.
>
> **Two things that are safe and need no workaround:** Laravel's middleware priority places `Authenticate` **before** `SubstituteBindings`, so substituting a throwaway `1` for `{recipe}` / `{mealPlan}` / `{token}` redirects to login before the model is ever resolved — no 404s. And `VerifyCsrfToken` short-circuits via `runningUnitTests()`, so guest POST/PUT/DELETE requests aren't blocked by a missing token.

Build the URI by replacing `{param}` / `{param?}` placeholders in `$route->uri()` with `1`, prefix with `/`, and assert `assertRedirect(route('login'))`. Keep the allow-list as a named constant or `$publicRouteNames` array with a comment per entry explaining *why* it's excluded.

## Requirements (Test Descriptions)
- [x] `it prevents deleting a recipe referenced by a meal assignment` — model-level (`$recipe->delete()` throws `QueryException`); comment cross-referencing the existing controller-level test at `DeleteRecipeTest.php:157`
- [x] `it cascades meal assignment deletion when a meal plan is deleted`
- [x] `it soft-deletes grocery lists and grocery items instead of removing the row`
- [x] `it avoids N+1 queries on the recipes index view regardless of record count` — N=3 vs 6
- [x] `it avoids N+1 queries on the meal plans index view regardless of record count` — N=3 vs 6
- [x] `it redirects unauthenticated users away from every app-owned web route except the known-public set`

## Acceptance Criteria
- All 6 tests pass against current behavior with no production code changes.
- The auth-route test uses the `livewire_component ?? getActionName()` discriminator, dispatches each route with its own HTTP verb, and its allow-list is explicit and documented in the test itself (named constant or array with a per-entry reason), not an implicit/silent exclusion.
- The auth-route test asserts against a non-trivial number of routes — add a sanity assertion (e.g. `expect($checked)->toBeGreaterThan(20)`) so a future filter regression can't turn it into a silent no-op.
- The N+1 tests use record counts below both components' page sizes (24 / 10) and reuse the `DB::listen()` pattern from `tests/Feature/GroceryLists/RecipeLinksTest.php:150`.
- The delete-policy tests extend rather than duplicate `DeleteRecipeTest.php:157`.
- `vendor/bin/pint --dirty` run.

## Implementation Notes
- All 6 tests added to `tests/Feature/CrossCutting/InvariantsTest.php`. All passed against current behavior on first write, with no production code changes — as expected for this oracle/regression task.
- Test 1/2 (delete-policy): used `Recipe::factory()`/`MealPlan::factory()`/`MealAssignment::factory()` directly at the model level, no controller/HTTP involved. Test 1's docblock cross-references `tests/Feature/Recipes/DeleteRecipeTest.php:157` per the task instructions rather than restating it.
- Test 3 (soft delete): confirmed `GroceryList::booted()`'s `deleting` hook (`app/Models/GroceryList.php:41`) cascades the soft delete to `GroceryItem` rows, and both use `assertSoftDeleted()` from `Pest\Laravel`.
- Tests 4/5 (N+1): reused the `DB::listen()` + counter closure pattern from `tests/Feature/GroceryLists/RecipeLinksTest.php:150`, but compare total query count for N=3 vs 2N=6 records (asserting equality) rather than a fixed absolute ceiling — this is a stronger and more direct proof of "no N+1" than checking a single run. Confirmed N=3/6 stays under both components' page sizes (24 for `RecipesIndex`, 10 for `MealPlansIndex`) so the second run genuinely renders double the rows.
- Test 6 (auth sweep): used the `$route->action['livewire_component'] ?? $route->getActionName()` discriminator plus `str_starts_with($target, 'App\\')` filter, dispatched each route with its own non-HEAD HTTP verb via `$this->call()`, and asserted `assertRedirect(route('login'))`. Allow-list (`$publicRouteNames`) is an explicit array keyed by route name with an inline reason per entry (`login`, `register`, `password.request`, `password.reset`, `logout`); a comment documents which routes are excluded "for free" by the filter (home, test-only closures, Fortify/Livewire vendor routes) so the filter doesn't get "simplified" later. `$checked` produced ~38 routes (77 assertions from `assertRedirect`'s two internal assertions + 1 sanity check), well above the required `toBeGreaterThan(20)` threshold.
- No findings to report — all assumed invariants held as documented in the task context.
- `vendor/bin/pint --dirty` run: PASS, no changes needed.
- Full suite: `php artisan test --parallel` → 151 passed + 901 "deprecated" (all passing; the "deprecated" label comes from a pre-existing, unrelated `PDO::MYSQL_ATTR_SSL_CA` deprecation notice in `config/database.php:61,81` under PHP 8.5, confirmed present even on baseline pre-existing test files before any change here), 0 failures.
- Note: the working tree already had uncommitted changes/untracked files from sibling tasks 001-003 in this plan (e.g. `tests/Feature/GroceryLists/AutocompleteFuzzyMatchBoundaryTest.php`, `tests/Feature/ItemTemplates/SeededCommonDefaultsTest.php`, `tests/Unit/Policies/MealPlanNotePolicyTest.php`, modified `tests/Feature/GroceryListGeneratorTest.php`/`MealPlans/ViewMealPlanTest.php`/`Sharing/ShareGroceryListTest.php`, and e2e specs). These were not touched by this task; only `tests/Feature/CrossCutting/InvariantsTest.php` and this plan file were added/edited here.
