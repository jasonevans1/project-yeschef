# Task 001: Meal-Plan-Drawer Close Behavior, Navigation, and Permission Gating

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Cover `001-meal-plan-drawer` FR-009 (View Full Recipe link), FR-010 (close via backdrop / close button / Escape key), and FR-012 (permission check before opening). **The markup for all three is already present in `resources/views/livewire/meal-plans/show.blade.php`** — this task writes tests proving the behavior, it does not build features. Verified during planning and re-verified during plan review (line numbers accurate as of review):
- Backdrop click-to-close: line 538, `@click="$wire.closeRecipeDrawer()"` on the backdrop `<div>`. **Read the "Backdrop click may be intercepted" section below before writing this one — the markup is present but likely unreachable.**
- Escape-key close: line 526, `@keydown.escape.window="$wire.closeRecipeDrawer()"` on the drawer's outer `<div x-data="{ show: @entangle('showRecipeDrawer') }">`.
- Close-button click: line 574, `wire:click="closeRecipeDrawer"` on a `<flux:button>` in the drawer header (and a second "Close" button at line 678).
- View Full Recipe link: line 671, `href="{{ route('recipes.show', $this->selectedAssignment->recipe) }}"`.
- The component is `App\Livewire\MealPlans\Show`, actions `openRecipeDrawer($assignment)` / `closeRecipeDrawer()`, properties `showRecipeDrawer` (bool) / `selectedAssignmentId`.

**You own `e2e/meal-plan-drawer.spec.ts`.** Task 002 depends on this task and will append to the file you create, reusing the shared setup helper you write. Create it cleanly.

## Context

### Related files
- `app/Livewire/MealPlans/Show.php`, `resources/views/livewire/meal-plans/show.blade.php`
- `tests/Feature/MealPlans/ViewMealPlanTest.php` — existing drawer tests `'can open recipe drawer with correct state'` (line 294) and `'can close recipe drawer'` (line 319). Both call `closeRecipeDrawer()` directly via Livewire's testing API, which proves the PHP method works but not that the backdrop/Escape/button actually invoke it in a real browser. **Do not duplicate them.**
- `e2e/meal-plans.spec.ts` — closest existing e2e pattern for this feature area, and the source of the fixture recipe below.
- `e2e/recipe-servings-multiplier.spec.ts:6-19` — clean `beforeEach` login pattern to copy.

### E2E environment (read first)
`playwright.config.ts` has no `webServer` block and defaults `baseURL` to `https://yeschef.ddev.site`. Playwright runs against a **live DDEV instance** with a seeded `test@example.com` / `password` user — not against a test database. If DDEV isn't up, nothing here will run. There is no factory access from Playwright; all fixture data must be created through the UI.

### Required shared setup helper (you author this; Task 002 reuses it)
Both this task and Task 002 need the same precondition: logged in, on a meal plan show page, with at least one recipe **assigned to a slot**, drawer opened. Write it once, at the top of `e2e/meal-plan-drawer.spec.ts`, as a plain exported async function — roughly:

```ts
// login → /meal-plans/create → fill name + future start/end dates → submit → wait for /meal-plans/{id}
// → click the add button in a known slot → wait → click role=menuitem[name="Add Recipe"]
// → search a term → click the first [data-recipe-card] → wait for the slot to show the recipe
// → click the assigned card to open the drawer → wait for getByRole('dialog')
```

Follow `e2e/meal-plans.spec.ts:91-181` for the exact selectors (`[data-date][data-meal-type]`, the
`role=menuitem[name="Add Recipe"]` dropdown item, `[data-recipe-card]`). **One deviation from that file:**
it wraps the assignment step in `if (recipeCount > 0)` and silently skips when no recipe matches. Do **not**
copy that. Assert `expect(recipeCount).toBeGreaterThan(0)` so a fixture-less database fails loudly instead of
producing a green no-op.

The page renders **two** calendar layouts. At the default desktop viewport only the desktop table
(`hidden md:block`, line 72) is visible; its cards carry `wire:key="desktop-recipe-{id}"` (lines 106-112). The
mobile layout (`block md:hidden`, `[data-mobile-calendar]`, lines 237-238) is in the DOM but `display:none`.
Scope card locators to `[wire\\:key^="desktop-recipe-"]` — a bare `[role="button"]` locator matches both layouts
and will trip Playwright's strict mode or drive a hidden element.

### Backdrop click may be intercepted — verify before assuming
The backdrop at line 538 has the `@click` handler, but it is a **preceding sibling** of the panel container:

```
530  <div @click="$wire.closeRecipeDrawer()" class="fixed inset-0 bg-gray-500/75 ...">
543  <div class="fixed inset-0 overflow-hidden">          <-- no pointer-events-none
544      <div class="absolute inset-0 overflow-hidden">   <-- no pointer-events-none
545          <div class="pointer-events-none fixed inset-y-0 right-0 ...">
558              <div class="pointer-events-auto w-screen max-w-full sm:max-w-md lg:max-w-lg">
```

Both 530 and 543 are positioned with `z-index: auto`, so paint and hit-test order falls back to DOM order:
543/544 cover the whole viewport **on top of** the backdrop and neither opts out of pointer events. A genuine
click on the grey area very likely lands on 544, which has no handler.

Procedure — **report-only, do not fix** (plan-wide rule, see `_plan.md`):
1. Write the test with a normal `locator.click()` on the backdrop. **Never use `{ force: true }`** — forcing
   dispatches the event straight at the backdrop and yields a green test for behavior a user cannot trigger,
   which is the opposite of what this oracle suite is for.
2. If the click succeeds, done — record in Implementation Notes that reachability was empirically confirmed.
3. If Playwright reports `element intercepts pointer events` (or the drawer stays open), you have found a real
   bug — **do not fix it**. Rewrite the test to assert the *actual current* behavior instead: click the backdrop,
   then assert the dialog is still present (`await expect(page.getByRole('dialog')).toHaveCount(1)`) — the oracle
   should describe reality, not the spec's aspiration, exactly like Task 003 item 2's fuzzy-match handling.
   Record the finding in Implementation Notes verbatim: "Backdrop click does not close the drawer — a sibling
   `<div>` at `show.blade.php:543` has no `pointer-events-none` and intercepts the click across the full
   viewport. One-line fix (`pointer-events-none` on line 543) identified but not applied — this plan is
   test-only, see `_plan.md`."
4. Do not modify any production file in this task, here or anywhere else. If you find yourself wanting to, stop
   and write the finding instead of the fix.

### Asserting the drawer is closed
The entire drawer lives inside `@if($showRecipeDrawer && $this->selectedAssignment)` (line 523), so closing it
triggers a Livewire re-render that removes the block from the DOM — the `x-transition:leave` classes at 551-553
never play. Assert `await expect(page.getByRole('dialog')).toHaveCount(0)` rather than `not.toBeVisible()`.
The drawer panel is `role="dialog"` with `aria-labelledby="drawer-title"` (lines 555-557).

### On permission gating (FR-012)
`ViewMealPlanTest.php:95` already has `'prevents user from viewing another users meal plan'`, which asserts a
403 from the whole `Show` component. `Show::mount()` calls `$this->authorize('view', $mealPlan)` (line 71), and
`openRecipeDrawer()` re-authorizes `view` on line 144 — a non-owner can never reach a state where the drawer is
reachable. Verify both, then **do not write a redundant test**: mark the checkbox done and note in
Implementation Notes that FR-012 is satisfied transitively, with file:line pointers to both the existing test
and `Show.php:71`/`:144`. Only add a new test if you find a drawer-reachable code path that isn't gated.

## Requirements (Test Descriptions)
Add a new `e2e/meal-plan-drawer.spec.ts` (Playwright) for the DOM-event behaviors; extend `tests/Feature/MealPlans/ViewMealPlanTest.php` (Pest) for the rest.

- [x] (e2e) A shared, exported `openDrawer(page)` setup helper as described above, with a loud failure when no assignable recipe exists.
- [x] (e2e) `it closes the drawer when the backdrop is clicked` — real click, no `force: true`; follow the 4-step procedure above.
- [x] (e2e) `it closes the drawer when the Escape key is pressed`
- [x] (e2e) `it closes the drawer when the header close button is clicked` — the end-to-end counterpart to the existing Livewire-level test; completes the "all three close paths" spec.
- [x] (Pest, `ViewMealPlanTest.php`) `it renders a link to the full recipe page from the drawer` — open the drawer via `Livewire::test(Show::class, ['mealPlan' => $mealPlan])->call('openRecipeDrawer', $assignment)`, then assert the rendered HTML contains `href="{{ route('recipes.show', $recipe) }}"`. Use `assertSee($expected, false)` (escape=false) — see `tests/Feature/GroceryLists/RecipeLinksTest.php:147` for the exact idiom.
- [x] (Documented no-op, see Context) FR-012 permission gating — confirmed covered by the existing mount-level test, with file:line pointers. Only a new test if a real gap is found.

## Acceptance Criteria
- `e2e/meal-plan-drawer.spec.ts` exists, exports the shared `openDrawer(page)` helper, and passes.
- The recipe-link Pest test passes against the existing `href="{{ route('recipes.show', ...) }}"` markup — no view changes needed for it.
- FR-012 is explicitly documented as already covered, with a file:line pointer.
- **No production code changes, ever, in this task.** If the backdrop click is intercepted (see report-only procedure above), the test documents that reality and the fix is recorded as a finding, not applied. If any other requirement here can't go green against current code for a different reason, stop and report `TASK_FAILED` with what you found rather than building new behavior.
- `vendor/bin/pint --dirty` run.

## Implementation Notes

**e2e/meal-plan-drawer.spec.ts** created, exporting `openDrawer(page)` and three tests:
`closes the drawer when the backdrop is clicked`, `closes the drawer when the Escape key is
pressed`, `closes the drawer when the header close button is clicked`. All three pass against a
live DDEV instance.

- **DDEV/Playwright environment note**: `https://yeschef.ddev.site` presents a self-signed cert
  that isn't trusted in this sandbox (`net::ERR_CERT_AUTHORITY_INVALID` on every spec, including
  pre-existing ones like `e2e/meal-plans.spec.ts` — verified this is not specific to my change).
  DDEV's router accepts plain HTTP on the same host without a forced redirect, so tests were run
  with `BASE_URL=http://yeschef.ddev.site npx playwright test ...`. No config files were changed;
  this is an env var override only, needed at test-run time in this sandbox.

- **Backdrop click bug — confirmed, not fixed.** Ran the backdrop-click test with the click's
  error unsuppressed first (temporarily, then reverted) to empirically confirm the interception
  before committing to the final assertion. Playwright's own trace:
  `<div class="absolute inset-0 overflow-hidden">… from <div class="fixed inset-0
  overflow-hidden">… subtree intercepts pointer events`. Root cause: the sibling `<div>` at
  `resources/views/livewire/meal-plans/show.blade.php:543` (`class="fixed inset-0
  overflow-hidden"`) paints after the backdrop (line 530) in the same stacking context (both
  `position: fixed`, no `z-index`, so DOM order decides paint order) and has no
  `pointer-events-none`, so it covers the full viewport and intercepts every click meant for the
  backdrop underneath. **Backdrop click does not close the drawer** — a user cannot trigger this
  behavior today despite the `@click="$wire.closeRecipeDrawer()"` handler being present. One-line
  fix (`pointer-events-none` on line 543) identified but not applied — this plan is test-only, see
  `_plan.md`. The test documents this reality: it attempts a genuine (non-forced) click with a
  bounded 5s timeout, swallows the resulting timeout error, then asserts
  `getByRole('dialog')` still has count 1 (drawer stayed open).

- **FR-012 permission gating**: confirmed transitively covered, no new test added.
  `Show::mount()` calls `$this->authorize('view', $mealPlan)` at
  `app/Livewire/MealPlans/Show.php:71`, and `openRecipeDrawer()` re-authorizes `view` at
  `app/Livewire/MealPlans/Show.php:144`. A non-owner is rejected at `mount()` before any
  drawer-opening action is reachable — proven by the existing test
  `'prevents user from viewing another users meal plan'` at
  `tests/Feature/MealPlans/ViewMealPlanTest.php:95`, which asserts a 403 for the whole `Show`
  component. No drawer-reachable code path bypasses this gate, so no redundant test was added.

- **Recipe-link Pest test**: `it renders a link to the full recipe page from the drawer` added to
  `tests/Feature/MealPlans/ViewMealPlanTest.php` (after the existing `'can close recipe drawer'`
  test), following the `assertSee($expected, false)` idiom from
  `tests/Feature/GroceryLists/RecipeLinksTest.php:147`. Passes against the existing
  `href="{{ route('recipes.show', $this->selectedAssignment->recipe) }}"` markup at
  `show.blade.php:671` — no view changes needed.

- No production code was modified in this task. `vendor/bin/pint --dirty` run (no changes needed
  in touched files).
