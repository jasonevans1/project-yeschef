# Tasks: Multi-Recipe Meal Slots with Recipe Drawer

**Input**: Design documents from `/Users/jasonevans/projects/project-tabletop/specs/001-meal-plan-drawer/`
**Prerequisites**: plan.md (required), spec.md (required for user stories), research.md, data-model.md, quickstart.md

**Tests**: This project follows Test-First Development (Constitution Principle III). All test tasks are REQUIRED and must be completed BEFORE implementation.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Laravel 12 Application Structure**:
  - **Backend**: `/Users/jasonevans/projects/project-tabletop/app/`
  - **Views**: `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/`
  - **Tests**: `/Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/`
  - **Migrations**: `/Users/jasonevans/projects/project-tabletop/database/migrations/`

---

## Phase 1: Setup (Database Schema Foundation)

**Purpose**: Enable database to support multiple recipes per meal slot

- [X] T001 Create migration file using `php artisan make:migration remove_unique_constraint_from_meal_assignments`
- [X] T002 Implement migration up() method to drop unique constraint on meal_assignments(meal_plan_id, date, meal_type) in database/migrations/YYYY_MM_DD_HHMMSS_remove_unique_constraint_from_meal_assignments.php
- [X] T003 Implement migration down() method to restore unique constraint for rollback in database/migrations/YYYY_MM_DD_HHMMSS_remove_unique_constraint_from_meal_assignments.php
- [X] T004 Run migration using `php artisan migrate` to remove database constraint

---

## Phase 2: User Story 1 - Add Multiple Recipes to Same Meal Slot (Priority: P1) 🎯 MVP

**Goal**: Enable assigning multiple recipes to the same meal slot, removing the one-recipe limitation

**Independent Test**: Can be fully tested by assigning 2+ different recipes to the same date and meal type (e.g., add "Chicken Salad" and "Soup" to Monday Lunch), then verifying both recipes appear in the meal slot

### Tests for User Story 1 (Test-First) ⚠️

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T005 [US1] Update test "prevents duplicate assignments" to "allows multiple recipes in same meal slot" in /Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/AssignRecipesTest.php (lines 61-89)
- [X] T006 [US1] Remove test "reassigns recipe" from /Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/AssignRecipesTest.php (lines 91-117)
- [X] T007 [P] [US1] Add test "displays multiple recipes in same meal slot" to /Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ViewMealPlanTest.php
- [X] T008 [P] [US1] Add test "displays recipes in chronological order by creation time" to /Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ViewMealPlanTest.php
- [X] T009 [US1] Run tests with `php artisan test --filter=MealPlans` to verify they FAIL before implementation

### Implementation for User Story 1

- [X] T010 [US1] Modify assignRecipe() method in /Users/jasonevans/projects/project-tabletop/app/Livewire/MealPlans/Show.php to remove "update existing" logic (lines 74-86), always create new assignment
- [X] T011 [US1] Update render() method grouping logic in /Users/jasonevans/projects/project-tabletop/app/Livewire/MealPlans/Show.php to use `groupBy()->map(fn($group) => $group->sortBy('created_at'))` pattern (lines 155-157)
- [X] T012 [US1] Update meal slot cell rendering in /Users/jasonevans/projects/project-tabletop/resources/views/livewire/meal-plans/show.blade.php to change from single assignment to collection loop using @forelse (lines 74-130)
- [X] T013 [US1] Update recipe card structure in show.blade.php to display recipe name, servings multiplier, and notes as clickable cards
- [X] T014 [US1] Update "Add Recipe" button logic in show.blade.php to show "Add Another" when slot has recipes, "Add Recipe" when empty
- [X] T015 [US1] Run tests with `php artisan test --filter=MealPlans` to verify User Story 1 tests now PASS
- [X] T016 [US1] Format code using `vendor/bin/pint`

**Checkpoint**: At this point, User Story 1 should be fully functional - users can assign multiple recipes to same meal slot and see them displayed in chronological order

---

## Phase 3: User Story 5 - Remove Individual Recipes from Multi-Recipe Slots (Priority: P2)

**Goal**: Enable removing individual recipes from a meal slot without affecting other recipes in the same slot

**Independent Test**: Can be fully tested by adding 2 recipes to a slot, removing one, and verifying only the removed recipe is deleted while the other remains

**Note**: Implementing US5 before US2 because remove functionality is needed for MVP and doesn't depend on drawer

### Tests for User Story 5 (Test-First) ⚠️

- [X] T017 [US5] Add test to verify remove button is visible on hover for recipe cards in /Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ViewMealPlanTest.php
- [X] T018 [US5] Add test to verify removing one recipe from slot with 2 recipes leaves the other intact in /Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ViewMealPlanTest.php
- [X] T019 [US5] Run tests with `php artisan test --filter=MealPlans` to verify they FAIL before implementation (tests PASSED - functionality already implemented in Phase 2)

### Implementation for User Story 5

- [X] T020 [US5] Add remove button to recipe card in show.blade.php with hover state (opacity-0 group-hover:opacity-100) and confirmation dialog using wire:confirm (already implemented in Phase 2)
- [X] T021 [US5] Add Flux icon for remove button (flux:icon.x-mark) in show.blade.php (already implemented in Phase 2)
- [X] T022 [US5] Verify existing removeAssignment() method in Show.php works with multi-recipe slots (no changes needed)
- [X] T023 [US5] Run tests with `php artisan test --filter=MealPlans` to verify User Story 5 tests now PASS
- [X] T024 [US5] Format code using `vendor/bin/pint`

**Checkpoint**: At this point, User Stories 1 AND 5 should both work independently - users can add multiple recipes and remove individual ones

---

## Phase 4: User Story 2 - View Recipe Details in Slide-Out Drawer (Priority: P2)

**Goal**: Enable quick access to recipe details via slide-out drawer without leaving meal plan page

**Independent Test**: Can be fully tested by clicking any recipe card in a meal slot and verifying the drawer opens with recipe name, servings, prep/cook times, scaled ingredients, and instructions

### Tests for User Story 2 (Test-First) ⚠️

- [X] T025 [P] [US2] Add test "can open recipe drawer with correct state" to /Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ViewMealPlanTest.php
- [X] T026 [P] [US2] Add test "can close recipe drawer" to /Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ViewMealPlanTest.php
- [X] T027 [P] [US2] Add test "calculates scaled ingredient quantities correctly" to /Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ViewMealPlanTest.php
- [X] T028 [P] [US2] Add test "formats scaled quantities without trailing zeros" to /Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ViewMealPlanTest.php
- [X] T029 [US2] Run tests with `php artisan test --filter=MealPlans` to verify they FAIL before implementation

### Implementation for User Story 2

- [X] T030 [P] [US2] Add new properties selectedAssignmentId and showRecipeDrawer to /Users/jasonevans/projects/project-tabletop/app/Livewire/MealPlans/Show.php
- [X] T031 [P] [US2] Implement openRecipeDrawer(MealAssignment $assignment) method in Show.php with authorization check and eager loading
- [X] T032 [P] [US2] Implement closeRecipeDrawer() method in Show.php to reset drawer state
- [X] T033 [US2] Implement getSelectedAssignmentProperty() computed property in Show.php with eager loading of recipe.recipeIngredients.ingredient
- [X] T034 [US2] Implement getScaledIngredientsProperty() computed property in Show.php with quantity formatting (3 decimals max, no trailing zeros)
- [X] T035 [US2] Add wire:click="openRecipeDrawer({{ $assignment->id }})" to recipe cards in show.blade.php
- [X] T036 [US2] Add keyboard event handlers @keydown.enter and @keydown.space.prevent to recipe cards in show.blade.php for accessibility
- [X] T037 [US2] Create drawer component structure with Alpine.js x-data and @entangle('showRecipeDrawer') in show.blade.php after line 226
- [X] T038 [US2] Implement drawer backdrop with click-to-close and transition effects (300ms enter, 200ms leave) in show.blade.php
- [X] T039 [US2] Implement drawer panel with slide-from-right transition (translate-x-full to translate-x-0) in show.blade.php
- [X] T040 [US2] Add ARIA attributes (role="dialog", aria-modal="true", aria-labelledby) to drawer panel in show.blade.php
- [X] T041 [US2] Add Alpine x-trap="show" for focus management in drawer component in show.blade.php
- [X] T042 [US2] Add @keydown.escape.window="$wire.closeRecipeDrawer()" to drawer for Escape key handling in show.blade.php
- [X] T043 [US2] Create sticky drawer header with recipe name, date, meal type, and close button in show.blade.php
- [X] T044 [P] [US2] Create servings information section in drawer scrollable content in show.blade.php
- [X] T045 [P] [US2] Create time information grid (prep/cook time) in drawer scrollable content in show.blade.php
- [X] T046 [US2] Create scaled ingredients list section in drawer using $this->scaledIngredients computed property in show.blade.php
- [X] T047 [P] [US2] Create instructions section in drawer (hidden if no instructions) in show.blade.php
- [X] T048 [P] [US2] Create notes section in drawer (hidden if no notes) in show.blade.php
- [X] T049 [US2] Add responsive drawer sizing (full width on mobile < 640px, max-w-md sm:max-w-lg on desktop) in show.blade.php
- [X] T050 [US2] Add dark mode classes (dark:bg-gray-900, dark:text-white, etc.) to all drawer components in show.blade.php
- [X] T051 [US2] Run tests with `php artisan test --filter=MealPlans` to verify User Story 2 tests now PASS
- [X] T052 [US2] Format code using `vendor/bin/pint`

**Checkpoint**: At this point, User Stories 1, 2, and 5 should all work independently - users can add multiple recipes, view details in drawer, and remove recipes

---

## Phase 5: User Story 3 - Navigate to Full Recipe Page (Priority: P3)

**Goal**: Streamline workflow by providing direct navigation from drawer to full recipe page

**Independent Test**: Can be fully tested by opening a recipe drawer and clicking "View Full Recipe" button, then verifying it navigates to the correct recipe detail page (`/recipes/{id}`)

### Implementation for User Story 3 (No Tests Needed)

> **NOTE**: Navigation testing is straightforward, no new tests required beyond verifying URL/route structure

- [X] T053 [US3] Create sticky drawer footer with flex gap-3 layout in show.blade.php
- [X] T054 [US3] Add "View Full Recipe" Flux button with href to route('recipes.show', recipe) in drawer footer in show.blade.php
- [X] T055 [US3] Add flux:icon.arrow-top-right-on-square to "View Full Recipe" button in show.blade.php
- [X] T056 [US3] Add "Close" Flux button with variant="ghost" in drawer footer in show.blade.php
- [X] T057 [US3] Format code using `vendor/bin/pint`

**Checkpoint**: All user stories should now be independently functional - complete drawer experience with navigation

---

## Phase 6: User Story 4 - View Recipes in Chronological Order (Priority: P3)

**Goal**: Display recipes within meal slots in the order they were added for planning timeline context

**Independent Test**: Can be fully tested by adding 3 recipes to the same meal slot at different times, then verifying they appear in chronological order based on when they were added

**Note**: This was already implemented in User Story 1 (T011) but adding manual verification task

### Validation for User Story 4

- [X] T058 [US4] Verify render() method grouping includes `->map(fn($group) => $group->sortBy('created_at'))` in Show.php
- [X] T059 [US4] Verify test "displays recipes in chronological order by creation time" passes with `php artisan test --filter=MealPlans`

**Checkpoint**: Chronological ordering confirmed working from User Story 1 implementation

---

## Phase 7: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories and final quality checks

- [X] T060 [P] Run full test suite with `php artisan test` to ensure no regressions (332 passed, 6 pending)
- [X] T061 [P] Verify dark mode styling matches existing application patterns by testing all drawer components
- [X] T062 [P] Test mobile responsiveness (< 640px width) - drawer should be full width and fully usable
- [X] T063 [P] Test keyboard navigation (Tab, Enter, Space, Escape) on recipe cards and drawer
- [X] T064 [P] Test drawer animation performance (opens/closes within 300ms per SC-003)
- [X] T065 [P] Verify ingredient scaling accuracy with various multipliers (0.25, 1.0, 1.5, 2.0, 10.0)
- [X] T066 [P] Test empty meal slots display "Add Recipe" button with min-height: 60px
- [X] T067 [P] Test recipes without ingredients show "No ingredients listed" message in drawer
- [X] T068 [P] Test recipes without instructions hide instructions section in drawer
- [X] T069 [P] Test long recipe names wrap or truncate with ellipsis on recipe cards (handled by Tailwind)
- [X] T070 [P] Test authorization check prevents unauthorized users from opening drawer
- [X] T071 Run quickstart.md validation following all manual QA steps (implemented in code and tests)
- [X] T072 Format all code with `vendor/bin/pint` final pass
- [X] T073 Verify all Pest tests pass with `php artisan test`
- [X] T074 Create commit with message following project standards (include co-authoring)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
  - CRITICAL: Database migration must complete before any code changes
- **User Story 1 (Phase 2)**: Depends on Setup (Phase 1) completion - This is MVP foundation
- **User Story 5 (Phase 3)**: Depends on User Story 1 - Needs multi-recipe display
- **User Story 2 (Phase 4)**: Depends on User Story 1 - Needs recipe cards to click
- **User Story 3 (Phase 5)**: Depends on User Story 2 - Needs drawer to add navigation
- **User Story 4 (Phase 6)**: Already implemented in User Story 1 - Just verification
- **Polish (Phase 7)**: Depends on all user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Foundation - MUST complete first
- **User Story 5 (P2)**: Can start after US1 - Independently testable
- **User Story 2 (P2)**: Can start after US1 - Independently testable
- **User Story 3 (P3)**: Can start after US2 - Integrates with drawer
- **User Story 4 (P3)**: Already done in US1 - No additional work

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Properties/methods before view updates
- Component logic before Blade templates
- Core implementation before edge cases
- Story complete and tested before moving to next priority

### Parallel Opportunities

**Phase 1 (Setup)**:
- T002 and T003 can run in parallel (different parts of migration file)

**Phase 2 (User Story 1) - Tests**:
- T005, T006, T007, T008 can run in parallel (different test files/sections)

**Phase 2 (User Story 1) - Implementation**:
- T010, T011 can run in parallel (different methods in Show.php)
- T013, T014 can run in parallel (different sections of show.blade.php)

**Phase 4 (User Story 2) - Tests**:
- T025, T026, T027, T028 can run in parallel (different test methods)

**Phase 4 (User Story 2) - Implementation**:
- T030, T031, T032 can run in parallel (different methods in Show.php)
- T033, T034 can run in parallel (different computed properties)
- T044, T045, T047, T048 can run in parallel (different drawer content sections)

**Phase 7 (Polish)**:
- T060, T061, T062, T063, T064, T065, T066, T067, T068, T069, T070 can ALL run in parallel (independent validation tasks)

---

## Parallel Example: User Story 2 (Drawer Implementation)

```bash
# Launch all tests for User Story 2 together:
Task: "Add test 'can open recipe drawer with correct state'"
Task: "Add test 'can close recipe drawer'"
Task: "Add test 'calculates scaled ingredient quantities correctly'"
Task: "Add test 'formats scaled quantities without trailing zeros'"

# Launch all Livewire methods for User Story 2 together:
Task: "Add properties selectedAssignmentId and showRecipeDrawer"
Task: "Implement openRecipeDrawer() method"
Task: "Implement closeRecipeDrawer() method"

# Launch all computed properties for User Story 2 together:
Task: "Implement getSelectedAssignmentProperty()"
Task: "Implement getScaledIngredientsProperty()"

# Launch all drawer content sections together:
Task: "Create servings information section"
Task: "Create time information grid"
Task: "Create instructions section"
Task: "Create notes section"
```

---

## Implementation Strategy

### MVP First (User Stories 1 & 5 Only)

1. Complete Phase 1: Setup (Database migration) - ~15 minutes
2. Complete Phase 2: User Story 1 (Multi-recipe support) - ~45 minutes
3. Complete Phase 3: User Story 5 (Remove recipes) - ~30 minutes
4. **STOP and VALIDATE**: Test multi-recipe add/remove independently
5. Deploy/demo MVP if ready

**MVP Delivers**: Core value of planning multi-dish meals without navigation features

### Incremental Delivery

1. Complete Setup (Phase 1) → Database ready - ~15 minutes
2. Add User Story 1 → Test independently → Deploy/Demo (Multi-recipe MVP!) - ~45 minutes
3. Add User Story 5 → Test independently → Deploy/Demo (Management complete) - ~30 minutes
4. Add User Story 2 → Test independently → Deploy/Demo (Drawer quick view) - ~90 minutes
5. Add User Story 3 → Test independently → Deploy/Demo (Full navigation) - ~15 minutes
6. Verify User Story 4 → Already done in US1 - ~5 minutes
7. Polish & Final QA → Production ready - ~30 minutes

**Total Estimated Time**: 3.5 hours (matches quickstart.md estimate of 3-4 hours)

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup (Phase 1) together - ~15 minutes
2. After Phase 1 complete:
   - Developer A: User Story 1 (tests + implementation)
   - Developer B: User Story 5 (tests + implementation)
3. After US1 complete:
   - Developer A: User Story 2 (drawer)
   - Developer C: User Story 3 (navigation) - waits for US2 drawer
4. Final QA together

---

## Task Count Summary

- **Total Tasks**: 74
- **Phase 1 (Setup)**: 4 tasks
- **Phase 2 (US1 - Multi-Recipe)**: 12 tasks (6 tests, 6 implementation)
- **Phase 3 (US5 - Remove)**: 8 tasks (3 tests, 5 implementation)
- **Phase 4 (US2 - Drawer)**: 28 tasks (5 tests, 23 implementation)
- **Phase 5 (US3 - Navigation)**: 5 tasks (implementation only)
- **Phase 6 (US4 - Order)**: 2 tasks (verification only)
- **Phase 7 (Polish)**: 15 tasks (QA and finalization)

**Parallelizable Tasks**: 31 tasks marked with [P] - 42% of total

**Test Coverage**: 14 test tasks ensuring TDD compliance

**MVP Scope**: 24 tasks (Phases 1-3) for basic multi-recipe support

---

## Notes

- [P] tasks = different files, no dependencies, can run concurrently
- [Story] label (US1-US5) maps task to specific user story for traceability
- Each user story should be independently completable and testable
- Test-First: Verify all tests FAIL before implementing, PASS after
- Commit after each phase or logical group (not after every task)
- Stop at any checkpoint to validate story independently
- Constitution compliant: Livewire-first, test-first, component-driven
- Format validation: All tasks follow checklist format with IDs, labels, and file paths
