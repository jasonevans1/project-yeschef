# Devil's Advocate Review: oracle-test-gap-fill

Reviewed against actual source, not spec text. Line numbers below were re-verified in this session.

**Headline:** the plan's line-number claims are all accurate. What isn't accurate is a handful of *semantic*
claims — "the markup is present" was verified, "the behavior is reachable" was not. Three of the twelve items
point at code that cannot make them go green as written.

---

## Critical (Must fix before building)

### C1 — Task 001: backdrop click is almost certainly not reachable in a real browser
`resources/views/livewire/meal-plans/show.blade.php`:

```
530  <div ... @click="$wire.closeRecipeDrawer()" class="fixed inset-0 bg-gray-500/75 dark:bg-zinc-900/75"></div>
543  <div class="fixed inset-0 overflow-hidden">          <-- no pointer-events-none
544      <div class="absolute inset-0 overflow-hidden">   <-- no pointer-events-none
545          <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
558              <div ... class="pointer-events-auto w-screen max-w-full sm:max-w-md lg:max-w-lg">
```

530 and 543 are siblings, both positioned, both `z-index: auto`. Paint order (and therefore hit-testing) falls
back to DOM order, so 543/544 sit **on top of** the backdrop across the entire viewport, and neither opts out of
pointer events. A real user click on the grey area lands on 544, whose ancestors (543, 524) have no `@click`.
The backdrop's handler is unreachable.

Playwright's actionability check will report `element intercepts pointer events` and time out. The tempting
"fix" — `click({ force: true })` — dispatches the event straight at the backdrop and produces a **green test for
behavior users cannot trigger**, which is the exact opposite of what a deletion-test oracle is for.

Task 001 verified line 538's markup exists; it did not verify the markup is reachable.

**Fix applied:** Task 001 now forbids `force: true`, requires a real click, and gives a bounded remedy — add
`pointer-events-none` to the div at line 543 (`pointer-events` is an inherited CSS property; 558 already
re-enables it with `pointer-events-auto`, and 545 already sets it, so the one class is consistent with the
surrounding intent). Documented as a bug the oracle caught, not silently patched. Escape/link/permission items
are unaffected, so this must not `TASK_FAILED` the whole task.

### C2 — Tasks 001 and 002: the shared e2e fixture is undefined, and "check if the file exists" doesn't cover it
Both tasks need the same non-trivial precondition: logged in as `test@example.com`, a meal plan that exists, a
recipe **assigned to a slot**, drawer opened. `e2e/meal-plans.spec.ts:91-203` shows what that costs today —
create plan, click the slot's add button, wait 500ms, click a `role=menuitem[name="Add Recipe"]` with
`{ force: true }`, search "Chicken", and then proceed **only** `if (recipeCount > 0)`. That last part means the
existing suite silently no-ops when the seeded DB has no matching recipe.

The tasks' race-handling ("if 001 lands first, add to it") covers the *file*, not the *fixture*. Two parallel
workers will independently write ~40 lines of setup, and there is no reason to expect them to converge. The
worker who lands second must then reconcile two setups in one file.

**Fix applied:** Task 002 now depends on Task 001. Task 001 owns `e2e/meal-plan-drawer.spec.ts` including a
single `openDrawer(page)` helper; Task 002 imports/reuses it and appends tests. The "check for existence" race
language is removed from both. `_plan.md`'s task table updated.

### C3 — Task 002: the page renders two independent calendar layouts, and the mobile one has no keyboard handlers
- Desktop cards, lines 106-112, inside `<div class="hidden md:block ...">` (line 72): `role="button"`,
  `tabindex="0"`, `@keydown.enter`, `@keydown.space.prevent`.
- Mobile cards, lines 284-290, inside `<div class="block md:hidden ..." data-mobile-calendar>` (lines 237-238):
  `role="button"`, `tabindex="0"`, `wire:click` — **and no `@keydown` handlers at all**.

Three consequences the task doesn't anticipate:
1. Any `[role="button"]` / "first recipe card" locator matches both layouts. Playwright strict mode throws, or
   the worker adds `.first()` and silently drives a `display:none` element.
2. The responsive-width test at a 375px viewport *must* open the drawer through the mobile card — the desktop
   table is hidden at that width.
3. FR-016 is only half-implemented. Enter/Space on a mobile card does nothing. That is a genuine oracle finding,
   and the task should predict it rather than have a worker trip over it at hour two.

**Fix applied:** Task 002 now names both layouts, their locators (`[wire\:key^="desktop-recipe-"]` /
`[data-mobile-calendar] [wire\:key^="mobile-recipe-"]`), scopes the keyboard test to the desktop layout, and
adds an explicit requirement to record the mobile keyboard gap as a finding.

### C4 — Task 003 item 5: points at code that does not implement FR-009
FR-009 (`specs/009-recipe-servings-multiplier/spec.md:79`) reads "round scaled quantities to a maximum of
3 decimal places **for display**".

- `app/Services/ServingSizeScaler.php:16-19` is `return $quantity * $multiplier;`. No rounding. And
  `tests/Unit/ServingSizeScalerTest.php:27-28` already asserts the raw float product — a new "at most 3 decimals"
  test on the same method contradicts a passing test in the same file.
- The `decimal(8,3)` column is irrelevant: scaled quantities are never persisted. The recipe page scales in the
  browser and writes nothing.
- The actual implementation is client-side: `resources/views/livewire/recipes/show.blade.php:210` calls
  `scaleQuantity()` → `resources/js/app.js:146-150` → `formatQuantity()` → line 54,
  `String(parseFloat(value.toFixed(3)).toString())`.

Written as specified, this item is either red (against `ServingSizeScaler`) or vacuous (asserting a migration's
column type). Separately: `app/Services/QuantityFormatter.php:114` has **no** equivalent clamp — it returns
`(string) $quantity` for any value that isn't a clean eighth/third, so the meal-plan drawer can render
`0.43333333333333`. The PHP and JS formatters disagree.

**Fix applied:** item 5 retargeted to a Playwright test appended to `e2e/recipe-servings-multiplier.spec.ts`
(which already has the login + first-recipe navigation `beforeEach`), asserting via regex that no rendered
ingredient quantity carries more than 3 decimals at a multiplier chosen to produce long decimals — deterministic
regardless of what the seeded recipe's quantities happen to be. Worker is explicitly told not to test
`ServingSizeScaler` or the migration, and to flag the PHP/JS asymmetry as a finding.

### C5 — Task 004 item 3: the route-iteration recipe yields a vacuous or a red test
Four concrete problems:

1. **`Route::livewire()` hides the component class.** `vendor/livewire/livewire/src/Mechanisms/HandleRouting/HandleRouting.php:20-22`
   registers `Route::get($uri, LivewirePageController::class)` and stashes the component in
   `$route->action['livewire_component']`. So `$route->getActionName()` returns a **vendor** class for every page
   route. A worker filtering "app-owned routes" by `App\` namespace silently drops all ~20 page routes and
   asserts only the 9 controller routes — a near-vacuous test that would still pass.
2. **Two named public routes exist during the test run.** `routes/web.php:115-153` registers
   `test.recipe.valid` and `test.recipe.invalid` when `app()->environment(['local','testing'])` — i.e. exactly
   when the suite runs. They return 200 to a guest. Not in the task's allow-list.
3. **`routes/auth.php` routes are `App\`-namespaced.** `login`, `register`, `password.request`,
   `password.reset` are `App\Livewire\Auth\*` and `logout` is `App\Livewire\Actions\Logout` (POST, no auth
   middleware — it redirects a guest to `/`, not to login). All need explicit allow-listing.
4. **Verbs.** Nine routes are POST/PUT/DELETE (`meal-plans.store/update/destroy`, `recipes.destroy`, the three
   assignment routes, the three grocery-item routes). `$this->get()` on those returns 405, not a redirect.

Two things the task got *right* and should keep: `Authenticate` outranks `SubstituteBindings` in Laravel's
middleware priority, so a fake `{recipe}` id redirects before model resolution; and CSRF is bypassed under
`VerifyCsrfToken::runningUnitTests()`, so guest POSTs are fine.

**Fix applied:** Task 004 item 3 rewritten with the exact discriminator
(`$route->action['livewire_component'] ?? $route->getActionName()` must start with `App\`), the complete
allow-list, and per-route verb dispatch.

---

## Important (Should fix before building)

### I1 — Task 004 item 1: the RESTRICT half already exists
`tests/Feature/Recipes/DeleteRecipeTest.php:157` — `'recipe preserved in existing meal plans due to ON DELETE
RESTRICT on meal_assignments'`. It exercises the **controller** path (`RecipeController::destroy` catches
`QueryException` and flashes an error, lines 20-31), not the model-level constraint. Scope the new test to the
DB invariant (`$recipe->delete()` throws) plus the cascade half, and cross-reference the existing test rather
than restating it. **Fix applied.**

### I2 — Task 004 item 2: pagination masks the N+1 check
`RecipesIndex` paginates 24 (`app/Livewire/Recipes/Index.php:110`); `MealPlansIndex` paginates 10
(`app/Livewire/MealPlans/Index.php:19`). "Seed N and 2N" with N ≥ 12 (recipes) or N ≥ 5 (meal plans) produces an
identical query count because both runs cap at one page — the test passes without proving anything. Also, the
referenced existing N+1 test is at `tests/Feature/GroceryLists/RecipeLinksTest.php:150`, not in the generator
test file. **Fix applied** (N=3 / 2N=6, both well under both page sizes; correct file reference).

### I3 — Task 003 item 4: `MealPlanNotePolicy::create()` is unconditional
`app/Policies/MealPlanNotePolicy.php:29-32` — `create(User $user): bool { return true; }`. It receives no note
instance, so there is no meal plan to check ownership against. The task's Requirements line correctly omits
`create`; its Context line says "covering view/create/update/delete for owner vs. non-owner". A worker following
Context writes a test that cannot pass. Note creation is actually gated one level up, in
`App\Livewire\MealPlans\Show::openNoteForm()` / `saveNote()` via `authorize('update', $this->mealPlan)`.
Also `delete()` delegates to the meal plan's **`delete`** ability, not `update`. **Fix applied.**

### I4 — Task 003 item 1: `RefreshDatabase` does not run seeders
No test in the suite runs `CommonItemTemplateSeeder`; all 10+ call sites hand-create `CommonItemTemplate` rows
(e.g. `tests/Feature/GroceryLists/AutocompleteItemTest.php:13-39`). A test that registers a user and searches
for "a seeded common item" finds an empty table. The test must call
`$this->seed(\Database\Seeders\CommonItemTemplateSeeder::class)` and assert on a name the seeder actually
contains (`tomato`, `banana`, `apple`, `onion` — `database/seeders/CommonItemTemplateSeeder.php:15-29`).
**Fix applied.**

### I5 — Task 003 item 2: "if it happens to pass through substring logic" is not a decidable instruction
It doesn't. `ItemAutoCompleteService` builds `LIKE '%tomatoe%'`, and a 7-character needle cannot match the
6-character stored name `tomato`. **No** misspelling that adds a character can ever match. The worker needs
concrete cases, not a conditional. **Fix applied** — `tomat` → hit (prefix), `omato` → hit (substring),
`tomatoe` → miss, `tomaot` → miss.

### I6 — Task 003 item 6: share access matches on `recipient_id`, not `recipient_email`
`GroceryListPolicy::hasShareAccess()` (lines 101-113) queries `ContentShare::where('recipient_id', $user->id)`.
A share created by email alone — the pending-invite case, which is exactly what `Show::shareWith()` creates when
the recipient hasn't registered (`app/Livewire/MealPlans/Show.php:315`, `recipient_id => $recipient?->id`) —
grants no access. The test must populate `recipient_id` or it 403s. Conversely `viewShared()` (lines 85-99)
returns true for **any** authenticated user holding a valid non-expired token, which is what makes the
"two independent access paths" assertion meaningful. **Fix applied.**

### I7 — Task 002: the cited dark-mode reference pattern doesn't do what the task describes
Task 002 says to follow `header-branding.spec.ts`'s `'logo is visible in light and dark mode'` and "toggle color
scheme, assert computed background color". That test (lines 39-49) never toggles anything — it asserts a class
regex. The real pattern in this repo is `e2e/torch-theme.spec.ts`: `test.use({ colorScheme: 'light' })` +
`toHaveCSS`. Also relevant: dark mode here is **class-based**
(`resources/css/app.css:9`, `@custom-variant dark (&:where(.dark, .dark *))`), applied by `@fluxAppearance` plus
a localStorage bootstrap (`resources/views/partials/head.blade.php:16-32`). `colorScheme` emulation works only
because Flux's default appearance is `system`; `page.addInitScript(() => localStorage.setItem('flux.appearance',
'dark'))` is the deterministic alternative. **Fix applied.**

### I8 — Task 002: "Tab to a card" is an unbounded number of Tab presses
The recipe card is inside the calendar table, after the entire sidebar nav, header, and per-slot add buttons.
"press Tab, then Enter" needs an unknown and layout-dependent number of Tabs. `card.focus()` followed by
`card.press('Enter')` still genuinely fires the `@keydown.enter` handler (the task's real requirement — don't
just assert `tabindex="0"` exists) without coupling the test to tab order. Assert focusability separately with
`toBeFocused`. **Fix applied.**

### I9 — Tasks 001/002: closing removes the drawer from the DOM entirely
The whole drawer sits inside `@if($showRecipeDrawer && $this->selectedAssignment)` (line 523). Closing sets the
property false, Livewire re-renders, and the block disappears — the `x-transition:leave` classes at 551-553
never play. Close assertions should be `await expect(page.getByRole('dialog')).toHaveCount(0)`, not
`not.toBeVisible()` (which passes too, but races the transition and hides what's really happening).
**Fix applied.**

### I10 — Task 002: drawer quantities render through `QuantityFormatter`, not as raw numbers
`Show::getScaledIngredientsProperty()` pipes every quantity through `QuantityFormatter::format()`
(`app/Livewire/MealPlans/Show.php:393`). `assertSee('2.5')` fails; the drawer renders `2½`. The existing tests at
`ViewMealPlanTest.php:433-435` already demonstrate this (`'2½'`, `'1'`, `'⅓'`). **Fix applied.**

---

## Minor (Nice to address)

- **Line numbers all check out.** Task 001's 526/538/574/671 and Task 002's 538/549/558/560/561/106-112 are each
  accurate to the current file. Only the *interpretation* of 538 is wrong (C1). Task 003's GroceryListGenerator
  line 264 is accurate. Task 004's "`routes/web.php` lines 42-102ish" is 42-110.
- `meal_assignments` originally had `unique(['meal_plan_id','date','meal_type'])`, dropped by
  `2025_12_14_194206_remove_unique_constraint_from_meal_assignments.php`. Task 004's cascade test can safely put
  two assignments in one slot.
- `tests/Browser/` exists but **every** test in both files is `markTestSkipped('Browser test placeholder - use
  Playwright E2E tests instead')`. Pest v4 browser testing is not operational in this repo — don't route new
  work there, despite CLAUDE.md advertising it.
- Task 003 lists `tests/Feature/GroceryListGeneratorTest.php` for item 3; there is also
  `tests/Unit/GroceryListGeneratorTest.php` and `tests/Feature/GroceryList/GroceryListGeneratorRecipeIdsTest.php`.
  Both Feature/Unit files exist, so the pointer isn't wrong, just ambiguous.
- `playwright.config.ts` has no `webServer` and defaults to `https://yeschef.ddev.site` — all e2e work assumes a
  running DDEV instance with a seeded `test@example.com` / `password` user. Worth stating once in the plan so a
  worker doesn't debug a connection refused for twenty minutes.
- `MealPlanNotePolicy` has no dedicated test file and `tests/Unit/Policies/` contains exactly one file
  (`GroceryListPolicyTest.php`), so the "follow the sibling pattern" instruction in Task 003 item 4 is sound.

---

## Questions for the Team

1. **C1 (backdrop click).** Is the one-class `pointer-events-none` fix in scope for this plan, or should the
   backdrop bug become its own ticket with the test written as a documented-gap (à la Task 003 item 2)? The task
   file currently permits the fix, bounded and documented. Say the word and it becomes report-only.
2. **C3 (mobile keyboard gap).** FR-016 is satisfied on desktop only — mobile cards advertise `role="button"` +
   `tabindex="0"` but drop the `@keydown` handlers, so screen-reader and keyboard users on narrow viewports get
   a control that announces as a button and does nothing. Two lines to fix. Fix now, or record as a finding?
   Currently recorded as a finding.
3. **C4 (formatter asymmetry).** `QuantityFormatter::format()` (PHP, meal-plan drawer) has no `toFixed(3)`
   equivalent and can emit `0.43333333333333`; `formatQuantity()` (JS, recipe page) clamps to 3 decimals.
   Separate bug ticket, or fold into a future formatter-consolidation plan?
4. **Task 004 framing.** The plan says a failing invariant test is "a finding to report, not a bug to fix." C1,
   C3 and C4 are all findings of exactly that shape but sit in Tasks 001/002/003, which are framed as
   "everything should go GREEN." Should that no-fix-report-instead rule be lifted to the plan level so all four
   workers apply it consistently?
