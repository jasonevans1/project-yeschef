# Tasks: Grocery List Category Filtering and Auto-Categorization

**Input**: Design documents from `/specs/012-grocery-list-categories/`
**Prerequisites**: plan.md ✅, spec.md ✅, research.md ✅, data-model.md ✅, contracts/ ✅

**Tests**: Included per Constitution Principle III (Test-First Development — NON-NEGOTIABLE). Write failing tests before each implementation block.

**Organization**: Tasks grouped by user story for independent implementation and testing.

## Format: `[ID] [P?] [Story?] Description`

- **[P]**: Can run in parallel (different files, no conflicting dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3)

---

## Phase 1: Foundational (Blocking Prerequisites)

**Purpose**: Database schema additions and model updates that block all three user stories. Must complete before any story work begins.

**⚠️ CRITICAL**: No user story work can begin until this phase is complete.

- [x] T001 Create migration `add_excluded_categories_to_grocery_lists_table` with nullable JSON column `excluded_categories` in `database/migrations/`
- [x] T002 [P] Create migration `add_grocery_category_exclusions_to_users_table` with nullable JSON column `grocery_category_exclusions` in `database/migrations/`
- [x] T003 [P] Add `excluded_categories` to `$fillable` and `'excluded_categories' => 'array'` to `casts()` in `app/Models/GroceryList.php`
- [x] T004 [P] Add `grocery_category_exclusions` to `$fillable` and `'grocery_category_exclusions' => 'array'` to `casts()` in `app/Models/User.php`
- [x] T005 Run `php artisan migrate` to apply both new migrations (depends on T001 + T002)

**Checkpoint**: Both JSON columns exist in the database; models cast them to arrays. Foundation ready.

---

## Phase 2: User Story 1 — Exclude Categories When Generating (Priority: P1) 🎯 MVP

**Goal**: When generating or regenerating a grocery list from a meal plan, users can optionally select ingredient categories to exclude. Excluded items are omitted from the list. The list view shows which categories were excluded with a link to regenerate.

**Independent Test**: Generate a grocery list from a meal plan that has Pantry-category ingredients. Select "Pantry" in the exclusion step and confirm. Verify: (a) no Pantry items appear in the resulting list, (b) the list view shows a callout naming "Pantry" as excluded, (c) re-opening the regeneration modal pre-selects "Pantry."

### Tests for User Story 1 ⚠️ Write these FIRST — ensure they FAIL before implementation

- [ ] T006 [US1] Write failing unit tests for `generate()` with `$excludedCategories` (items in excluded categories omitted, `excluded_categories` stored on list, empty array stores null) and for basic `getCategoryItemCounts()` (returns map of category value → item count) in `tests/Unit/GroceryListGeneratorTest.php`
- [ ] T007 [P] [US1] Write failing unit tests for `regenerate()` with `$excludedCategories` (excluded items omitted, manual items always preserved, `excluded_categories` updated on list) in `tests/Unit/GroceryListGeneratorTest.php`
- [ ] T008 [P] [US1] Write failing feature tests for: Generate component mounts with pre-populated exclusions from existing list; category checkboxes render with counts; `generate()` action passes correct exclusion enums to generator; Show component displays exclusion callout when `excluded_categories` is set; Show regenerate modal pre-populates exclusions from list in `tests/Feature/GroceryLists/CategoryExclusionTest.php`

### Implementation for User Story 1

- [ ] T009 [US1] Add `getCategoryItemCounts(MealPlan $mealPlan, int $userId): array` public method to `app/Services/GroceryListGenerator.php` — calls `collectIngredientsFromMealPlan()` and `aggregateIngredients()`, groups by `category->value`, returns `['produce' => 4, 'pantry' => 6, ...]` (basic version without template lookup; enhanced in US2)
- [ ] T010 [US1] Update `generate(MealPlan $mealPlan, array $excludedCategories = []): GroceryList` in `app/Services/GroceryListGenerator.php` — pass `$mealPlan->user_id` to `collectIngredientsFromMealPlan()`; skip `GroceryItem` creation when resolved category is in `$excludedCategories`; update `groceryList->excluded_categories` with string values (or null if empty)
- [ ] T011 [US1] Update `regenerate(GroceryList $groceryList, array $excludedCategories = []): GroceryList` in `app/Services/GroceryListGenerator.php` — same exclusion filtering as `generate()`; pass `$groceryList->user_id` to `collectIngredientsFromMealPlan()`; update `groceryList->excluded_categories`
- [ ] T012 [US1] Add public properties `$excludedCategories = []` and `$categoryItemCounts = []` to `app/Livewire/GroceryLists/Generate.php`
- [ ] T013 [P] [US1] Add public property `$regenerateExcludedCategories = []` to `app/Livewire/GroceryLists/Show.php` (parallel with T012, different file)
- [ ] T014 [US1] Update `Generate::mount()` in `app/Livewire/GroceryLists/Generate.php` — after existing logic: if `$recipeCount > 0` call `app(GroceryListGenerator::class)->getCategoryItemCounts($mealPlan, auth()->id())` → `$this->categoryItemCounts`; pre-populate `$this->excludedCategories` from `$existingList->excluded_categories ?? []` (depends on T012)
- [ ] T015 [US1] Update `Generate::generate()` in `app/Livewire/GroceryLists/Generate.php` — convert `$this->excludedCategories` string values to `IngredientCategory` enum cases; pass enum array to `$generator->generate()` or `$generator->regenerate()` (depends on T014)
- [ ] T016 [P] [US1] Update `Show::showRegenerateConfirmation()` to set `$this->regenerateExcludedCategories = $this->groceryList->excluded_categories ?? []` before setting `$showRegenerateConfirm = true`; update `Show::regenerate()` to convert `$this->regenerateExcludedCategories` to `IngredientCategory` enums and pass to generator in `app/Livewire/GroceryLists/Show.php` (parallel with T014–T015, different file; depends on T013)
- [ ] T017 [US1] Add category exclusion checkboxes panel to `resources/views/livewire/grocery-lists/generate.blade.php` — shown only when `$recipeCount > 0` and `$categoryItemCounts` is non-empty; uses `flux:checkbox` with `wire:model="excludedCategories"` for each category that has a count > 0; displays category label and count (e.g., "Pantry (6)")
- [ ] T018 [P] [US1] Update `resources/views/livewire/grocery-lists/show.blade.php` — (a) replace the `wire:confirm` attribute on the regenerate button with `wire:click="showRegenerateConfirmation"`; (b) add a Flux modal wired to `$showRegenerateConfirm` containing exclusion checkboxes (`wire:model="regenerateExcludedCategories"`) for all categories plus Cancel/Regenerate buttons; (c) add a dismissible `flux:callout` notice above the item list that appears when `$groceryList->excluded_categories` is non-empty, naming the excluded categories and linking to `showRegenerateConfirmation` (parallel with T017, different file)

**Checkpoint**: User Story 1 fully functional. Generate page shows category checkboxes with counts, exclusion works end-to-end, list view shows exclusion notice, regenerate modal shows pre-selected exclusions.

---

## Phase 3: User Story 2 — Improve Ingredient Categorization Using Personal Templates (Priority: P2)

**Goal**: When generating a grocery list, recipe ingredients currently categorized as "Other" are automatically improved by checking the user's personal item templates first, then the shared common item templates. The Generate page exclusion counts become more accurate as a result.

**Independent Test**: (a) Add a manual grocery item "Cumin" categorized as "Pantry" to any list (creates a UserItemTemplate). (b) Create a recipe with a "Cumin" ingredient that has category "Other." (c) Generate a grocery list from a meal plan containing that recipe. (d) Verify the generated "Cumin" item appears under "Pantry," not "Other." (e) Verify `getCategoryItemCounts()` now counts "Cumin" under "Pantry" on the Generate page.

### Tests for User Story 2 ⚠️ Write these FIRST — ensure they FAIL before implementation

- [ ] T019 [US2] Write failing unit tests for `resolveIngredientCategory()` in `tests/Unit/GroceryListGeneratorTest.php`: returns original category when not OTHER; returns UserItemTemplate category when ingredient is OTHER and user template matches; returns CommonItemTemplate category when no user template exists; returns OTHER when no template matches; matching is case-insensitive
- [ ] T020 [P] [US2] Write failing feature tests for end-to-end template-based categorization in `tests/Feature/GroceryLists/CategoryLookupTest.php`: ingredient with OTHER category + matching UserItemTemplate → grocery item gets template category; ingredient with OTHER + no user template + matching CommonItemTemplate → gets common template category; ingredient with existing non-OTHER category → category unchanged regardless of templates

### Implementation for User Story 2

- [ ] T021 [US2] Add private `resolveIngredientCategory(string $name, IngredientCategory $currentCategory, int $userId): IngredientCategory` method to `app/Services/GroceryListGenerator.php` — returns `$currentCategory` immediately if not `OTHER`; queries `UserItemTemplate` by `user_id` + case-insensitive name match; falls back to `CommonItemTemplate` by case-insensitive name; returns `OTHER` if no match
- [ ] T022 [US2] Update private `collectIngredientsFromMealPlan(MealPlan $mealPlan, int $userId): Collection` in `app/Services/GroceryListGenerator.php` — add `int $userId` parameter; after scaling each ingredient, apply `resolveIngredientCategory($item['name'], $item['category'], $userId)` and update the `category` key (depends on T021)
- [ ] T023 [US2] Update `getCategoryItemCounts()` in `app/Services/GroceryListGenerator.php` — pass `$userId` to `collectIngredientsFromMealPlan()` so category counts reflect template-resolved categories (depends on T022)

**Checkpoint**: User Story 2 fully functional. Ingredients previously stuck in "Other" are now correctly categorized via user and common templates. Exclusion counts on the Generate page reflect accurate template-resolved categories.

---

## Phase 4: User Story 3 — Save Category Exclusion Preferences (Priority: P3)

**Goal**: Users can save their selected category exclusions as default preferences directly from the grocery list generation page. On future generations, the saved exclusions are pre-selected. Preferences can be cleared from the same page.

**Independent Test**: (a) On the Generate page, select "Pantry" and click "Save as default." (b) Navigate away and return to the Generate page for any meal plan. (c) Verify "Pantry" is pre-selected without any user action. (d) Click "Clear saved defaults" and verify the next generation shows no pre-selection.

### Tests for User Story 3 ⚠️ Write these FIRST — ensure they FAIL before implementation

- [ ] T024 [US3] Write failing feature tests for preference lifecycle in `tests/Feature/GroceryLists/CategoryExclusionTest.php`: `savePreferences()` stores selection on `users.grocery_category_exclusions`; `clearPreferences()` sets the column to null; Generate component mounts with saved preferences pre-selected in `$excludedCategories`; existing list's `excluded_categories` takes precedence over saved preferences when regenerating

### Implementation for User Story 3

- [ ] T025 [US3] Add `public array $savedPreferences = []` property and update `Generate::mount()` in `app/Livewire/GroceryLists/Generate.php` — load `$this->savedPreferences = auth()->user()->grocery_category_exclusions ?? []`; initialize `$this->excludedCategories = $this->existingList?->excluded_categories ?? $this->savedPreferences` (replaces T014 pre-population logic; update existing mount accordingly)
- [ ] T026 [US3] Add `savePreferences(): void` action to `app/Livewire/GroceryLists/Generate.php` — validate each value in `$this->excludedCategories` is a valid `IngredientCategory` enum value; call `auth()->user()->update(['grocery_category_exclusions' => $this->excludedCategories ?: null])`; update `$this->savedPreferences`
- [ ] T027 [US3] Add `clearPreferences(): void` action to `app/Livewire/GroceryLists/Generate.php` — call `auth()->user()->update(['grocery_category_exclusions' => null])`; reset `$this->savedPreferences = []`
- [ ] T028 [US3] Add "Save as default" button (`wire:click="savePreferences"`) and conditionally visible "Clear saved defaults" button (`wire:click="clearPreferences"`, shown only when `$savedPreferences` is non-empty) to the exclusion panel in `resources/views/livewire/grocery-lists/generate.blade.php`

**Checkpoint**: All three user stories fully functional. Preferences persist across sessions. All stories independently testable.

---

## Phase 5: Polish & Cross-Cutting Concerns

**Purpose**: Regression safety, code style, and final validation.

- [ ] T029 [P] Add test to `tests/Feature/GroceryLists/RegenerateWithManualChangesTest.php` confirming that manually added items are never removed during regeneration even when their category is in the excluded categories list
- [ ] T030 Run `vendor/bin/pint --dirty` and fix all code style violations across all modified files
- [ ] T031 Run `php artisan test` (full suite) and confirm all existing and new tests pass with zero failures

---

## Dependencies & Execution Order

### Phase Dependencies

- **Foundational (Phase 1)**: No dependencies — start immediately
- **US1 (Phase 2)**: Depends on Phase 1 completion — BLOCKS until T005 (migrations run) and T003/T004 (models updated)
- **US2 (Phase 3)**: Depends on Phase 1 + US1 service changes (T009–T011 must be complete; `collectIngredientsFromMealPlan` signature updated in US2 builds on US1 changes)
- **US3 (Phase 4)**: Depends on Phase 1 + US1 Generate component (T012, T014, T015 must be complete; T025 updates T014's mount logic)
- **Polish (Phase 5)**: Depends on all desired user stories being complete

### User Story Dependencies

- **US1 (P1)**: Can start after Phase 1 — no story-to-story dependencies
- **US2 (P2)**: Can start after Phase 1 — service changes build on US1's GroceryListGenerator modifications (same file, sequential within generator)
- **US3 (P3)**: Can start after Phase 1 — Generate component changes extend US1's component work (same file, sequential within component)

### Within Each User Story

- Tests MUST be written and confirmed FAILING before implementation tasks begin
- Generator methods (T008–T011, T021–T023) are sequential (same file)
- Generate component tasks (T012, T014, T015, T025–T027) are sequential (same file)
- Show component tasks (T013, T016) are sequential (same file)
- View files can proceed in parallel with component files (different files)

### Parallel Opportunities

- **Phase 1**: T001, T002, T003, T004 all parallel (different files)
- **US1 tests**: T006/T007 sequential (same file); T008 parallel with T006/T007 (different file)
- **US1 impl**: T012 ∥ T013 (different files); T014/T015 ∥ T016 (Generate.php vs Show.php); T017 ∥ T018 (different view files)
- **US2 tests**: T019/T020 parallel (different files)
- **Polish**: T029 ∥ T030 (different files)

---

## Parallel Example: User Story 1

```bash
# Phase 1 — all four can start simultaneously:
Task T001: "Create excluded_categories migration in database/migrations/"
Task T002: "Create grocery_category_exclusions migration in database/migrations/"
Task T003: "Update GroceryList model in app/Models/GroceryList.php"
Task T004: "Update User model in app/Models/User.php"
# Then T005: php artisan migrate

# US1 tests — start T008 immediately alongside T006/T007:
Task T006+T007: "Write failing unit tests in GroceryListGeneratorTest.php"
Task T008: "Write failing feature tests in CategoryExclusionTest.php"

# US1 impl — after tests confirmed failing; run Generate and Show changes in parallel:
Task T009+T010+T011: "Update GroceryListGenerator (getCategoryItemCounts, generate, regenerate)"
Task T012+T014+T015: "Update Generate component"    ← parallel with →
Task T013+T016:       "Update Show component"

Task T017: "Update generate.blade.php"    ← parallel with →
Task T018: "Update show.blade.php"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Foundational (T001–T005)
2. Complete Phase 2: User Story 1 (T006–T018)
3. **STOP and VALIDATE**: Generate a list with exclusions. Verify the callout appears. Verify regeneration modal pre-selects exclusions.
4. Deploy/demo if ready

### Incremental Delivery

1. Complete Phase 1 → Foundation ready
2. Add US1 (T006–T018) → Category exclusion works → Demo (MVP!)
3. Add US2 (T019–T023) → Ingredient categorization improved → Demo
4. Add US3 (T024–T028) → Preferences persist → Demo
5. Polish (T029–T031) → Production ready

### Parallel Team Strategy

Once Phase 1 is complete:
- **Developer A**: US1 (T006–T018) — core exclusion flow
- **Developer B**: US2 (T019–T023) — template categorization (can work on generator in isolation; integrate after US1 generator tasks complete)
- **Developer C**: US3 (T024–T028) — preferences (can work on Generate component once T012/T014 are merged)

---

## Notes

- [P] tasks = different files with no conflicting dependencies
- [Story] label maps each task to its user story for traceability
- Constitution Principle III requires all tests to FAIL before implementation begins — do not skip this
- Run `vendor/bin/pint --dirty` after completing each phase, not just in Polish
- Commit after each logical group (e.g., after all foundational tasks, after each user story)
- T025 in US3 modifies the same `mount()` method updated in T014 (US1) — ensure T014 is fully committed before starting T025
- US2 and US3 modify `GroceryListGenerator.php` and `Generate.php` respectively, both also modified in US1 — work sequentially on these files across stories
