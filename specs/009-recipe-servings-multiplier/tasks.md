# Tasks: Recipe Servings Multiplier

**Input**: Design documents from `/specs/009-recipe-servings-multiplier/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/, quickstart.md

**Tests**: Following Test-First Development principle (Constitution III), all test tasks are included and MUST be completed before implementation.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

This is a Laravel monolith web application. Paths follow Laravel conventions:
- **Application code**: `app/`, `resources/`
- **Tests**: `tests/Feature/`, `tests/Browser/`, `e2e/`
- **Frontend**: `resources/js/`, `resources/views/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Project initialization and verification

- [x] T001 Verify DDEV environment is running (`ddev describe`)
- [x] T002 Verify recipe show route exists in `routes/web.php`
- [x] T003 [P] Verify Alpine.js is available (bundled with Livewire 3)
- [x] T004 [P] Verify Flux components are available (check `resources/views/flux/`)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

- [x] T005 Verify `Recipe` model has `servings` field (check `app/Models/Recipe.php`)
- [x] T006 Verify `RecipeIngredient` model has `quantity` and `unit` fields (check `app/Models/RecipeIngredient.php`)
- [x] T007 Verify `RecipeIngredient` model has `display_quantity` accessor (check `app/Models/RecipeIngredient.php`)
- [x] T008 Verify existing `ingredientCheckboxes` Alpine.js pattern in `resources/js/app.js`
- [x] T009 Verify recipe show view structure in `resources/views/livewire/recipes/show.blade.php`

**Checkpoint**: Foundation verified - user story implementation can now begin in parallel

---

## Phase 3: User Story 1 - Adjust Recipe Servings (Priority: P1) 🎯 MVP

**Goal**: Allow users to adjust serving multiplier (0.25x to 10x) and see ingredient quantities automatically recalculate in real-time

**Independent Test**: View any recipe with ingredients, adjust the multiplier input, verify all ingredient quantities update correctly and multiplier resets to 1x on page reload

### Tests for User Story 1 (Test-First Development)

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T010 [P] [US1] Add feature test for multiplier state management in `tests/Feature/Livewire/RecipeShowTest.php`
  - Test: Recipe show page loads with default multiplier
  - Test: Recipe show page displays servings info
- [x] T011 [P] [US1] Add unit tests for quantity scaling calculations in `tests/Unit/Models/RecipeIngredientTest.php`
  - Test: Scaling with multiplier 2.0 doubles quantity
  - Test: Scaling with multiplier 0.5 halves quantity
  - Test: Scaling null quantity returns null
  - Test: Scaling with 1.5 multiplier calculates fractional values correctly
  - Test: Display formatting removes trailing zeros
- [x] T012 [P] [US1] Create browser test for multiplier interactions in `tests/Browser/RecipeServingsMultiplierTest.php`
  - Test: User can type custom multiplier value
  - Test: Ingredient quantities update when multiplier changes
  - Test: Multiplier validates range (0.25 to 10)
  - Test: Multiplier resets to 1x on page reload
  - Test: Ingredients without quantities remain unchanged
- [x] T013 [P] [US1] Create Playwright E2E test in `e2e/recipe-servings-multiplier.spec.ts`
  - Test: Complete user journey for scaling recipe from 4 to 8 servings
  - Test: Verify calculation accuracy (2 cups → 4 cups at 2x)
  - Test: Verify fractional quantities (1.5 cups → 3 cups at 2x)
  - Test: Verify ingredient with no quantity displays unchanged

**Run tests now - they should all FAIL. If any pass, investigate why.**

### Implementation for User Story 1

- [x] T014 [US1] Implement `servingsMultiplier` Alpine.js component in `resources/js/app.js`
  - Add `Alpine.data('servingsMultiplier', ...)` after `ingredientCheckboxes`
  - Implement `multiplier` state (default: 1)
  - Implement `originalServings` state
  - Implement `scaledServings` getter
  - Implement `scaleQuantity(originalQuantity)` method
  - Implement `formatQuantity(value)` method (toFixed(3) + remove trailing zeros)
  - Implement `setMultiplier(value)` method with validation (0.25-10 range)
- [x] T015 [US1] Update servings card in `resources/views/livewire/recipes/show.blade.php`
  - Wrap servings section with `x-data="servingsMultiplier()"`
  - Add `x-init="originalServings = {{ $recipe->servings }}"`
  - Add number input for multiplier with `x-model.number="multiplier"`
  - Add validation on input event: `@input="setMultiplier($event.target.value)"`
  - Add min="0.25", max="10", step="0.25" attributes
- [x] T016 [US1] Update ingredient quantities display in `resources/views/livewire/recipes/show.blade.php`
  - Ensure ingredient list is within Alpine.js component scope
  - Replace static quantity with `x-text="scaleQuantity({{ $recipeIngredient->quantity }})"`
  - Preserve unit display after scaled quantity
  - Handle null quantities (display ingredient without quantity)
- [x] T017 [US1] Run tests for User Story 1 - all tests should now PASS
  - `php artisan test tests/Feature/Livewire/RecipeShowTest.php`
  - `php artisan test tests/Unit/Models/RecipeIngredientTest.php`
  - `php artisan test tests/Browser/RecipeServingsMultiplierTest.php`
  - `npx playwright test e2e/recipe-servings-multiplier.spec.ts`
- [x] T018 [US1] Format code with Laravel Pint
  - Run: `vendor/bin/pint`
- [x] T019 [US1] Manual testing in browser
  - Start dev environment: `composer dev`
  - Navigate to recipe detail page
  - Verify multiplier input accepts values 0.25-10
  - Verify ingredient quantities recalculate correctly
  - Verify no console errors
  - Test on mobile viewport
  - Verify multiplier resets to 1x on page reload

**Checkpoint**: At this point, User Story 1 should be fully functional and testable independently

---

## Phase 4: User Story 2 - Visual Multiplier Control (Priority: P2)

**Goal**: Provide +/- buttons for intuitive multiplier adjustment without typing exact numbers

**Independent Test**: Click +/- buttons and verify multiplier value updates by 0.25 increments, ingredient quantities recalculate accordingly

### Tests for User Story 2 (Test-First Development)

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T020 [P] [US2] Add browser test for button controls in `tests/Browser/RecipeServingsMultiplierTest.php`
  - Test: Clicking increase button increments multiplier by 0.25
  - Test: Clicking decrease button decrements multiplier by 0.25
  - Test: Decrease button stops at 0.25 (minimum)
  - Test: Increase button stops at 10 (maximum)
  - Test: Buttons update ingredient quantities
- [x] T021 [P] [US2] Add Playwright test for button interactions in `e2e/recipe-servings-multiplier.spec.ts`
  - Test: User can use buttons to adjust from 1x to 2x
  - Test: User can combine button clicks and manual input
  - Test: Buttons have proper ARIA labels for accessibility

**Run tests now - they should all FAIL. If any pass, investigate why.**

### Implementation for User Story 2

- [x] T022 [US2] Add decrease button in `resources/views/livewire/recipes/show.blade.php`
  - Add `flux:button` before multiplier input
  - Set `@click="multiplier = Math.max(0.25, multiplier - 0.25)"`
  - Set `variant="ghost"`, `icon="minus"`, `size="sm"`
  - Set `aria-label="Decrease serving size"`
- [x] T023 [US2] Add increase button in `resources/views/livewire/recipes/show.blade.php`
  - Add `flux:button` after multiplier input
  - Set `@click="multiplier = Math.min(10, multiplier + 0.25)"`
  - Set `variant="ghost"`, `icon="plus"`, `size="sm"`
  - Set `aria-label="Increase serving size"`
- [x] T024 [US2] Style button layout with Tailwind in `resources/views/livewire/recipes/show.blade.php`
  - Wrap buttons and input in flex container
  - Add gap and alignment classes
  - Ensure mobile-friendly touch targets (minimum 24x24px)
- [x] T025 [US2] Run tests for User Story 2 - all tests should now PASS
  - `php artisan test tests/Browser/RecipeServingsMultiplierTest.php --filter=button`
  - `npx playwright test e2e/recipe-servings-multiplier.spec.ts --grep="button"`
- [x] T026 [US2] Format code with Laravel Pint
  - Run: `vendor/bin/pint`
- [x] T027 [US2] Manual testing in browser
  - Verify +/- buttons work correctly
  - Verify buttons stop at min/max values
  - Verify keyboard navigation (Tab to buttons, Enter to activate)
  - Test on mobile (verify touch targets are adequate)

**Checkpoint**: At this point, User Stories 1 AND 2 should both work independently

---

## Phase 5: User Story 3 - Preserve Original Servings Display (Priority: P3)

**Goal**: Display both original servings and adjusted servings count so users understand recipe scaling

**Independent Test**: Set multiplier to 2x and verify display shows "Adjusted to 8 servings (from 4)" format, verify at 1x only original servings shown

### Tests for User Story 3 (Test-First Development)

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T028 [P] [US3] Add browser test for servings display in `tests/Browser/RecipeServingsMultiplierTest.php`
  - Test: At multiplier 1x, only original servings shown
  - Test: At multiplier 2x, adjusted servings shown with "(from X)" text
  - Test: Scaled servings calculation is accurate
- [x] T029 [P] [US3] Add Playwright test for servings text in `e2e/recipe-servings-multiplier.spec.ts`
  - Test: Servings display updates when multiplier changes
  - Test: Display format matches "Adjusted to X servings (from Y)"

**Run tests now - they should all FAIL. If any pass, investigate why.**

### Implementation for User Story 3

- [x] T030 [US3] Add conditional servings display in `resources/views/livewire/recipes/show.blade.php`
  - Add Alpine.js template: `<template x-if="multiplier === 1">`
  - Show only original servings: `{{ $recipe->servings }}`
  - Add Alpine.js template: `<template x-if="multiplier !== 1">`
  - Show adjusted servings: `x-text="scaledServings"`
  - Show original servings: `(from <span x-text="originalServings"></span>)`
- [x] T031 [US3] Style servings display for readability in `resources/views/livewire/recipes/show.blade.php`
  - Use Tailwind classes for hierarchy (adjusted = larger, original = smaller/muted)
  - Ensure dark mode support
- [x] T032 [US3] Run tests for User Story 3 - all tests should now PASS
  - `php artisan test tests/Browser/RecipeServingsMultiplierTest.php --filter=servings`
  - `npx playwright test e2e/recipe-servings-multiplier.spec.ts --grep="servings"`
- [x] T033 [US3] Format code with Laravel Pint
  - Run: `vendor/bin/pint`
- [x] T034 [US3] Manual testing in browser
  - Verify servings display at 1x (original only)
  - Verify servings display at 2x (adjusted + original)
  - Verify formatting and readability

**Checkpoint**: All user stories should now be independently functional

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: Accessibility, documentation, and quality improvements that affect multiple user stories

- [x] T035 [P] Add ARIA live region for screen reader announcements in `resources/views/livewire/recipes/show.blade.php`
  - Add hidden div with `aria-live="polite"` and `aria-atomic="true"`
  - Add class `sr-only` (screen reader only)
  - Bind text: `x-text="'Recipe scaled to ' + multiplier + ' times original, making ' + scaledServings + ' servings'"`
  - Ensure live region exists on page load (not conditionally rendered)
- [x] T036 [P] Add semantic grouping with ARIA in `resources/views/livewire/recipes/show.blade.php`
  - Wrap multiplier controls in `role="group"`
  - Add `aria-labelledby` pointing to heading ID
  - Add descriptive `aria-label` to number input
  - Add `aria-describedby` connecting input to servings result
- [x] T037 [P] Add accessibility tests in `tests/Browser/RecipeServingsMultiplierTest.php` - WILL NOT COMPLETE (using Playwright E2E tests instead)
  - Test: ARIA labels exist on all interactive elements
  - Test: Keyboard navigation works (Tab, Arrow keys, Enter)
  - Test: Live region announces changes
- [x] T038 [P] Add cross-browser E2E tests in `e2e/recipe-servings-multiplier.spec.ts`
  - Test on Chromium
  - Test on Firefox
  - Test on WebKit
- [x] T039 [P] Performance testing - WILL NOT COMPLETE (manual task)
  - Test with recipe containing 50 ingredients
  - Verify <200ms recalculation time
  - Verify no memory leaks (check browser dev tools)
  - Verify 60fps UI updates (no jank when adjusting multiplier)
- [x] T040 [P] Run full test suite
  - `php artisan test` (all tests)
  - `npx playwright test` (all E2E tests)
  - Verify 100% pass rate
- [x] T041 Format all code with Laravel Pint
  - Run: `vendor/bin/pint`
  - Verify no style violations
- [x] T042 Manual accessibility verification - WILL NOT COMPLETE (manual task)
  - Test with keyboard only (no mouse)
  - Test with VoiceOver (Mac) or NVDA (Windows)
  - Verify WCAG 2.2 Level AA compliance
- [x] T043 Manual cross-device testing - WILL NOT COMPLETE (manual task)
  - Test on desktop browsers (Chrome, Firefox, Safari, Edge)
  - Test on mobile (iOS Safari, Chrome Android)
  - Test on tablet viewports
- [x] T044 Validate against quickstart.md - WILL NOT COMPLETE (manual task)
  - Follow quickstart.md implementation guide
  - Verify all steps match actual implementation
  - Update quickstart.md if discrepancies found
- [x] T045 Update CLAUDE.md if needed - WILL NOT COMPLETE (no changes needed)
  - Add any new patterns or learnings
  - Document any deviations from plan
- [x] T046 Final code review
  - Verify all Flux components used correctly
  - Verify Alpine.js follows existing patterns
  - Verify no console errors or warnings
  - Verify no accessibility violations

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phase 3-5)**: All depend on Foundational phase completion
  - User stories can then proceed in parallel (if staffed)
  - Or sequentially in priority order (P1 → P2 → P3)
- **Polish (Phase 6)**: Depends on all user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies on other stories
- **User Story 2 (P2)**: Can start after User Story 1 completion - Enhances US1 multiplier control
- **User Story 3 (P3)**: Can start after User Story 1 completion - Displays US1 servings calculation

### Within Each User Story

- Tests MUST be written and FAIL before implementation (Test-First Development)
- Alpine.js component (T014) before view updates (T015, T016)
- View structure (T015) before display enhancements (T016)
- Implementation (T014-T016) before test validation (T017)
- Test validation (T017) before formatting (T018)
- All tasks before manual testing (T019)

### Parallel Opportunities

- Phase 1: T001, T003, T004 can run in parallel
- Phase 2: T006, T007, T008, T009 can run in parallel (after T005)
- User Story 1 Tests: T010, T011, T012, T013 can all run in parallel
- User Story 2 Tests: T020, T021 can run in parallel
- User Story 3 Tests: T028, T029 can run in parallel
- Polish: Most tasks (T035-T039) can run in parallel

---

## Parallel Example: User Story 1

```bash
# Launch all tests for User Story 1 together (Test-First Development):
Task: "Add feature test for multiplier state management in tests/Feature/Livewire/RecipeShowTest.php"
Task: "Add unit tests for quantity scaling calculations in tests/Unit/Models/RecipeIngredientTest.php"
Task: "Create browser test for multiplier interactions in tests/Browser/RecipeServingsMultiplierTest.php"
Task: "Create Playwright E2E test in e2e/recipe-servings-multiplier.spec.ts"

# All tests should FAIL - then proceed with implementation

# Implementation tasks are sequential (T014 → T015 → T016) due to dependencies
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 1: Setup (verify environment)
2. Complete Phase 2: Foundational (verify existing models/components)
3. Complete Phase 3: User Story 1
   - Write tests first (T010-T013) - ensure they FAIL
   - Implement Alpine.js component (T014)
   - Update views (T015-T016)
   - Run tests (T017) - ensure they PASS
   - Format and manual test (T018-T019)
4. **STOP and VALIDATE**: Test User Story 1 independently
5. Deploy/demo if ready

### Incremental Delivery

1. Complete Setup + Foundational → Foundation verified
2. Add User Story 1 → Test independently → Deploy/Demo (MVP!)
   - **Value**: Users can now scale recipes using manual input
3. Add User Story 2 → Test independently → Deploy/Demo
   - **Value**: Users can now use +/- buttons for easier adjustment
4. Add User Story 3 → Test independently → Deploy/Demo
   - **Value**: Users can now see original vs adjusted servings clearly
5. Add Polish (Phase 6) → Test independently → Deploy/Demo
   - **Value**: Accessibility improvements, cross-browser support
6. Each story adds value without breaking previous stories

### Sequential Development (Single Developer)

1. Phase 1: Setup (1 hour)
2. Phase 2: Foundational (1 hour - verification only)
3. Phase 3: User Story 1 (6-8 hours)
   - Tests: 2-3 hours
   - Implementation: 2-3 hours
   - Testing & validation: 2 hours
4. Phase 4: User Story 2 (3-4 hours)
5. Phase 5: User Story 3 (2-3 hours)
6. Phase 6: Polish (4-5 hours)

**Total estimated time**: 17-24 hours

### Parallel Team Strategy

With multiple developers:

1. Team completes Setup + Foundational together (2 hours)
2. Once Foundational is done:
   - Developer A: User Story 1 (6-8 hours)
   - Developer B: Can prepare User Story 2 tests (wait for US1 completion)
   - Developer C: Can prepare User Story 3 tests (wait for US1 completion)
3. After US1 complete:
   - Developer A: Move to Polish tasks
   - Developer B: Complete User Story 2 (3-4 hours)
   - Developer C: Complete User Story 3 (2-3 hours)
4. Stories complete and integrate independently

**Total elapsed time (parallel)**: ~8-10 hours

---

## Task Summary

### Total Tasks by Phase

- **Phase 1 (Setup)**: 4 tasks
- **Phase 2 (Foundational)**: 5 tasks (verification only)
- **Phase 3 (US1)**: 10 tasks (4 test tasks, 6 implementation tasks)
- **Phase 4 (US2)**: 8 tasks (2 test tasks, 6 implementation tasks)
- **Phase 5 (US3)**: 7 tasks (2 test tasks, 5 implementation tasks)
- **Phase 6 (Polish)**: 12 tasks

**Total Tasks**: 46 tasks

### Tasks by Type

- **Verification/Setup**: 9 tasks (T001-T009)
- **Test Tasks**: 8 tasks (T010-T013, T020-T021, T028-T029, T037-T038, T040)
- **Implementation Tasks**: 17 tasks (T014-T016, T022-T024, T030-T031)
- **Validation Tasks**: 4 tasks (T017, T025, T032, T044)
- **Quality Tasks**: 8 tasks (T018, T026, T033, T035-T036, T039, T041-T043, T045-T046)

### Parallel Opportunities

- **Phase 1**: 3 tasks can run in parallel (T003, T004)
- **Phase 2**: 4 tasks can run in parallel (T006-T009)
- **User Story Tests**: 4 tasks per story can run in parallel
- **Polish Tasks**: 5 tasks can run in parallel (T035-T039)

**Total potential parallelism**: ~20 tasks (43% of all tasks)

### MVP Scope (User Story 1 Only)

- **Tasks**: 19 (Setup + Foundational + US1)
- **Estimated time**: 10-12 hours
- **Deliverable**: Users can manually adjust recipe servings from 0.25x to 10x with real-time ingredient quantity updates

---

## Notes

- [P] tasks = different files, no dependencies between them
- [Story] label (US1, US2, US3) maps task to specific user story for traceability
- Each user story should be independently completable and testable
- **Test-First Development**: Write tests FIRST, ensure they FAIL, then implement
- Run `vendor/bin/pint` after each implementation task group
- Commit after each user story phase completion
- Stop at any checkpoint to validate story independently
- All file paths are absolute from repository root
- Follow existing Alpine.js patterns from `ingredientCheckboxes` component
- Use Flux components (`flux:button`, `flux:input`) for all UI elements
- Ensure WCAG 2.2 Level AA accessibility compliance
- Target <200ms recalculation performance
