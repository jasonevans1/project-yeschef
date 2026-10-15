# Task 003: Scattered Spec Gap/Partial Fixes (4 Specs)

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Six small, independent gap/partial items, one-per-spec, across `001-grocery-item-lookup`, `010-meal-plan-notes`, `009-recipe-servings-multiplier`, and `011-share-content`. Items 1, 3, 4, 6 are Pest tests; item 2 is a Pest test that deliberately documents a *missing* feature; item 5 is a Playwright test. None require production code changes. Two of them (2 and 5) need a judgment call — read their notes fully before writing anything.

## Context

**1. `001-grocery-item-lookup` FR-012 — "Common defaults seeded at registration."** Verified during planning: there is no per-registration hook (`app/Providers/AppServiceProvider.php` registers only `ResolvePendingShares` on `Registered::class`, nothing item-template-related). Instead, `database/seeders/CommonItemTemplateSeeder.php` populates a **global, shared** `CommonItemTemplate` table once, and `ItemAutoCompleteService::queryCommonTemplates()` reads from it directly — every user, including a brand-new one with zero `UserItemTemplate` rows, sees common defaults immediately. FR-012 is satisfied structurally, not via a registration event. Write the test against that reality.

> **Gotcha caught in plan review:** `RefreshDatabase` does **not** run seeders, and no existing test runs `CommonItemTemplateSeeder` — all 10+ call sites hand-create rows (see `tests/Feature/GroceryLists/AutocompleteItemTest.php:13-39`). Your test must call `$this->seed(\Database\Seeders\CommonItemTemplateSeeder::class)` explicitly, or the table is empty and the assertion has nothing to find. Assert on a name the seeder actually contains — `tomato`, `banana`, `apple`, `onion` (`database/seeders/CommonItemTemplateSeeder.php:15-29`). Using the real seeder (rather than hand-creating a row) is the point: it proves the shipped defaults reach a fresh user.

Test shape: seed the seeder, create a fresh `User` with no `UserItemTemplate` rows, call `(new ItemAutoCompleteService)->query($user->id, 'tomat')`, assert `tomato` is returned. Related files: `app/Services/ItemAutoCompleteService.php`, `database/seeders/CommonItemTemplateSeeder.php`, `tests/Feature/ItemTemplates/`.

**2. `001-grocery-item-lookup` FR-009 — "Fuzzy/partial text matching (e.g. partial matches, minor misspellings)."** Verified during planning: `ItemAutoCompleteService` implements prefix (`LIKE 'term%'`, lines 51/81) and substring (`LIKE '%term%'`, lines 65/93) matching only — no Levenshtein/edit-distance/soundex logic exists anywhere in the service. Partial-match is real and already tested (`'partial name matching works correctly'`, `AutocompleteItemTest.php:54`). Misspelling tolerance is **not implemented**.

**Do not implement it now** — building fuzzy edit-distance matching is a real feature addition, out of scope for a test-gap-fill plan, and exactly the scope creep this plan exists to avoid. Instead, write a test that documents the *actual* boundary.

> **Correction from plan review:** the original wording said to assert a misspelling like `tomatoe` "works if it happens to pass through substring logic." It cannot. `LIKE '%tomatoe%'` builds a 7-character needle against the 6-character stored name `tomato`, so *no* misspelling that adds a character can ever match. Use these four deterministic cases instead:
> - `query($user->id, 'tomat')` → returns `tomato` (prefix match works)
> - `query($user->id, 'omato')` → returns `tomato` (substring match works)
> - `query($user->id, 'tomatoe')` → empty (extra-character typo is **not** tolerated)
> - `query($user->id, 'tomaot')` → empty (transposition is **not** tolerated)

In Implementation Notes, flag plainly: "FR-009's misspelling-tolerance claim is not implemented; this test documents current substring/prefix-only behavior. True fuzzy matching is a feature gap for a future plan, not this one." This is a legitimate, expected outcome — not a failure.

**3. `010-meal-plan-notes` FR-010 — "Notes excluded from grocery list generation."** Verified during planning: `app/Services/GroceryListGenerator.php` queries `$mealPlan->mealAssignments()` exclusively (line 264) and never references `MealPlanNote` anywhere in `app/Services/`. Structurally already true — this is a pure regression guard. Write a test: create a meal plan with both a recipe assignment and a `MealPlanNote` in the same date/meal-type slot, generate the grocery list, assert only the recipe's ingredients appear and that nothing derived from the note's `title` or `details` appears as an item. Related files: `app/Services/GroceryListGenerator.php`, `tests/Feature/GroceryListGeneratorTest.php` (note there is also a `tests/Unit/GroceryListGeneratorTest.php` — put this in the Feature one, it needs models).

**4. `010-meal-plan-notes` FR-011 (partial) — "Note access restricted to plan owner."** Verified during planning: `app/Policies/MealPlanNotePolicy.php` delegates to the parent meal plan — `view()` → `$user->can('view', $note->mealPlan)`, `update()` → `can('update', ...)`, `delete()` → `can('delete', ...)`. No dedicated policy test file exists (only `tests/Unit/Policies/GroceryListPolicyTest.php` exists as a sibling pattern to follow). Write `tests/Unit/Policies/MealPlanNotePolicyTest.php`.

> **Two corrections from plan review, both of which would otherwise produce a red test:**
> - **`create()` is unconditional.** `MealPlanNotePolicy::create(User $user): bool { return true; }` (lines 29-32) — it receives no note instance, so there is no meal plan to check ownership against. Do **not** write an "only the owner can create" assertion; it cannot pass. Assert that `create()` returns true for any authenticated user, and note that note creation is actually gated one level up, in `App\Livewire\MealPlans\Show::openNoteForm()` (line 163) and `saveNote()` (line 186), both via `authorize('update', $this->mealPlan)`.
> - **`delete()` delegates to the meal plan's `delete` ability**, not `update`. Assert against the right ability.
>
> Cover `view` / `update` / `delete` for owner vs. an unrelated non-owner, plus the unconditional `create`/`viewAny`.

**5. `009-recipe-servings-multiplier` FR-009 (partial) — "Round scaled quantities to a maximum of 3 decimal places for display."**

> **Retargeted during plan review — the original file pointers were wrong.** `app/Services/ServingSizeScaler.php:16-19` is `return $quantity * $multiplier;` with **no rounding**, and `tests/Unit/ServingSizeScalerTest.php:27-28` already asserts the raw float product — a "max 3 decimals" test on that method would contradict a passing test in the same file. The `decimal(8,3)` column is also irrelevant: scaled quantities are never persisted; the recipe page scales in the browser and writes nothing. **Do not write a test against `ServingSizeScaler` or against a migration's column type.**

The requirement is a **display** requirement and is implemented **client-side**: `resources/views/livewire/recipes/show.blade.php:210` binds `x-text="scaleQuantity(...)"` → `resources/js/app.js:146-150` `scaleQuantity()` → `formatQuantity()` → line 54, `String(parseFloat(value.toFixed(3)).toString())`. There is no JS test runner in this repo (`package.json` devDependencies are Playwright + sharp only) and `tests/Browser/` is entirely `markTestSkipped` placeholders, so Playwright is the only real option.

Test shape: append to `e2e/recipe-servings-multiplier.spec.ts`, which already has the login + "click the first recipe" `beforeEach` (lines 6-19). Set the multiplier input (`page.getByLabel('Serving size multiplier')`) to a value that produces long decimals for essentially any starting quantity — e.g. `1.3333` — wait for Alpine, then collect every ingredient quantity element (`page.locator('[x-text^="scaleQuantity"]')` — Alpine leaves the attribute in the DOM) and assert each one's text has **at most 3 decimal digits**, e.g. matches `/^[^.]*(\.\d{1,3})?$/` after stripping unicode fraction characters. Asserting the *shape* rather than an exact value keeps it deterministic regardless of what the seeded recipe's quantities happen to be.

In Implementation Notes, flag the asymmetry: `app/Services/QuantityFormatter.php:114` returns `(string) $quantity` with **no** `toFixed(3)` equivalent, so the PHP path (the meal-plan drawer) can render values like `0.43333333333333`. That is a latent inconsistency between the JS and PHP formatters — record it as a finding for future scoping, do not fix it here.

**6. `011-share-content` FR-017 (partial) — "Anonymous token sharing continues to work alongside user-to-user sharing."** Verified during planning: `GroceryList` has both a `share_token` column (token-link access) and a `ContentShare` morphMany relation (user-to-user shares) — architecturally independent, coexisting fields on the same model. The "anonymous" framing is misleading given FR-033 elsewhere requires shared links to be authenticated: `grocery-lists/shared/{token}` sits inside `routes/web.php`'s `Route::middleware(['auth'])->group(...)` (line 79), and `Sharing/ShareGroceryListTest.php` already has `'unauthenticated user redirected to login'`. So "anonymous token sharing" really means *token-based* access as opposed to *ContentShare-based* access — both require login, they're two different ways an authenticated user reaches the same list.

> **Gotcha caught in plan review:** `GroceryListPolicy::hasShareAccess()` (lines 101-113) matches on **`recipient_id`**, not `recipient_email`. A `ContentShare` created by email alone — the pending-invite case, which is what `Show::shareWith()` produces when the recipient hasn't registered yet — grants no access and will 403. Your fixture must populate `recipient_id`. Conversely, `GroceryListPolicy::viewShared()` (lines 85-99) returns true for **any** authenticated user holding a valid, non-expired token — that asymmetry is precisely what makes "two independent access paths" a meaningful assertion.

Test shape: create a grocery list owned by user A with an active non-expired `share_token`; create a `ContentShare` for user B with `recipient_id` set; then assert user B reaches the list via `route('grocery-lists.show', $list)` **and** an unrelated authenticated user C reaches the same list via `route('grocery-lists.shared', $token)` — both succeed, neither path disables the other. Related files: `app/Models/GroceryList.php`, `app/Models/ContentShare.php`, `app/Policies/GroceryListPolicy.php`, `app/Livewire/GroceryLists/Shared.php`, `tests/Feature/Sharing/`.

## Requirements (Test Descriptions)
- [x] (Pest) `it surfaces seeded common item template defaults to a newly registered user with no personal templates` — must call `$this->seed(CommonItemTemplateSeeder::class)`
- [x] (Pest) `it matches partial item names but does not tolerate misspellings` — the four deterministic cases in note 2; documents current behavior, not a failure
- [x] (Pest) `it excludes meal plan notes from generated grocery list items`
- [x] (Pest, new `tests/Unit/Policies/MealPlanNotePolicyTest.php`) `it restricts meal plan note view/update/delete access to the plan owner` — plus an assertion that `create()` is unconditional, per note 4
- [x] (e2e, append to `e2e/recipe-servings-multiplier.spec.ts`) `it rounds displayed scaled quantities to at most 3 decimal places`
- [x] (Pest) `it allows token-link access and user-to-user share access to work simultaneously on the same grocery list`

## Acceptance Criteria
- All 6 tests pass.
- No production code changed — all six document already-correct (or already-absent) behavior.
- Item 2 does not add fuzzy/edit-distance matching logic — it documents the current boundary and flags the spec/implementation gap in Implementation Notes for future scoping.
- Item 5 does not test `ServingSizeScaler` or a migration column type, and its Implementation Notes record the `QuantityFormatter` (PHP) vs `formatQuantity` (JS) 3-decimal asymmetry.
- `vendor/bin/pint --dirty` run.

## Implementation Notes

All six items were pure regression/documentation tests against already-correct (or, for item 2,
already-absent) behavior — no production code was changed. Since there was nothing to make pass
via new implementation, each requirement's "RED" step was replaced with reading the relevant
production code to confirm the test's assertions matched real, current behavior, then confirming
the written test passed on the first run.

1. `tests/Feature/ItemTemplates/SeededCommonDefaultsTest.php` (new) — seeds
   `CommonItemTemplateSeeder` explicitly (RefreshDatabase does not run seeders), creates a fresh
   `User` with zero `UserItemTemplate` rows, and asserts `tomato` is returned by
   `ItemAutoCompleteService::query()`. Confirms FR-012 is satisfied structurally via the shared
   `CommonItemTemplate` table, not a registration hook.

2. `tests/Feature/GroceryLists/AutocompleteFuzzyMatchBoundaryTest.php` (new) —
   **FR-009's misspelling-tolerance claim is not implemented.** `ItemAutoCompleteService` only
   does LIKE-based prefix/substring matching (no Levenshtein/edit-distance/soundex). This test
   documents current behavior: prefix (`tomat`) and substring (`omato`) matches work; an
   extra-character typo (`tomatoe`) and a transposition (`tomaot`) return empty. This is a
   legitimate feature gap for a future plan, not something fixed here.

3. Appended `it excludes meal plan notes from generated grocery list items` to
   `tests/Feature/GroceryListGeneratorTest.php` — a meal plan with both a recipe assignment and a
   `MealPlanNote` in the same date/meal-type slot generates a grocery list containing only the
   recipe's ingredient ('Carrot'); the note's `title`/`details` never appear. Confirms
   `GroceryListGenerator` never references `MealPlanNote`.

4. `tests/Unit/Policies/MealPlanNotePolicyTest.php` (new) — covers `view`/`update`/`delete` for
   owner vs. an unrelated non-owner (all delegate to `MealPlanPolicy` on the parent meal plan),
   plus asserts `create()`/`viewAny()` are unconditionally `true` (no note instance is passed to
   `create()`, so no ownership check is possible — real gating for note creation happens one level
   up in `App\Livewire\MealPlans\Show::openNoteForm()`/`saveNote()` via
   `authorize('update', $this->mealPlan)`). Because the owner/non-owner delegation path queries
   `ContentShare` via Eloquent, this Unit-directory test needed a real app+DB, so it locally binds
   `uses(Tests\TestCase::class, RefreshDatabase::class)` rather than relying on Pest.php's
   Feature/Browser-only auto-binding (the sibling `GroceryListPolicyTest` avoids this because its
   `delete()` check never hits the DB).

5. Appended `it rounds displayed scaled quantities to at most 3 decimal places` to
   `e2e/recipe-servings-multiplier.spec.ts` (Playwright, chromium). Sets the multiplier to
   `1.3333` (produces long decimals for virtually any starting quantity), then asserts every
   `[x-text^="scaleQuantity"]` ingredient-quantity span's text — after stripping unicode fraction
   characters — has at most 3 decimal digits. This targets `formatQuantity()`'s
   `String(parseFloat(value.toFixed(3)).toString())` fallback in `resources/js/app.js`, not
   `ServingSizeScaler` (which intentionally returns the raw unrounded product; scaled quantities
   are never persisted) or any migration column type.
   **Finding for future scoping (not fixed here):** `app/Services/QuantityFormatter.php` (PHP,
   used by the meal-plan drawer) returns `(string) $quantity` with no `toFixed(3)`-equivalent
   rounding, so that server-rendered path can display raw floats like `0.43333333333333` while the
   JS path on the recipe show page is capped at 3 decimals. Latent JS/PHP formatter asymmetry.
   **DDEV gotcha:** the e2e suite's default `baseURL` (`https://yeschef.ddev.site`) uses a
   self-signed cert that Playwright's Chromium rejects (`net::ERR_CERT_AUTHORITY_INVALID`); ran
   with `BASE_URL=http://yeschef.ddev.site` to work around it (no Playwright config change made,
   consistent with how another e2e spec in this working tree already invokes it).

6. Appended `it allows token-link access and user-to-user share access to work simultaneously on
   the same grocery list` to `tests/Feature/Sharing/ShareGroceryListTest.php` — a `GroceryList`
   owned by user A gets both an active `share_token` and a `ContentShare` naming recipient B (with
   `recipient_id` populated, not just `recipient_email`, per the policy's `hasShareAccess()`
   lookup). Asserts B reaches it via `Show` (`grocery-lists.show`) and an unrelated user C reaches
   the same list via `Shared` (`grocery-lists.shared`, token-based) — both succeed independently.

Verification: `php artisan test --parallel` (full PHP suite) — 151 passed, 0 failed.
`npx playwright test e2e/recipe-servings-multiplier.spec.ts --project=chromium` — 12/12 passed.
`vendor/bin/pint` on all touched/new files — no changes needed.

Note: while working, unrelated concurrent changes appeared in this working tree
(`tests/Feature/MealPlans/ViewMealPlanTest.php`, `e2e/meal-plan-drawer.spec.ts`,
`tests/Feature/CrossCutting/`) from what appears to be another task running in parallel — these
were left untouched and are not part of this task's diff.
