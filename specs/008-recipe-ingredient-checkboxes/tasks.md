# Tasks: Recipe Ingredient Checkboxes

**Input**: Design documents from `/specs/008-recipe-ingredient-checkboxes/`
**Branch**: `008-recipe-ingredient-checkboxes`
**Prerequisites**: plan.md

**Tech Stack**: Laravel 12, Livewire 3, Alpine.js (bundled), Flux UI, Tailwind CSS 4.x, Pest v4, Playwright

**Tests**: This feature includes comprehensive test coverage with both Pest browser tests and Playwright E2E tests.

**Organization**: This is a single user story feature. Tasks are organized by implementation phase for clarity.

## Format: `- [ ] [ID] [P?] [Story?] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1)
- Include exact file paths in descriptions

## Phase 1: Core Implementation 🎯 MVP

**Goal**: Add interactive checkboxes to recipe ingredients with client-side state management

**Independent Test**: Navigate to any recipe page, check ingredient checkboxes, verify strikethrough + opacity applied, refresh page and verify checkboxes reset

### Implementation for Ingredient Checkboxes

- [X] T001 [P] [US1] Add Alpine.js component registration in resources/js/app.js
- [X] T002 [US1] Update ingredient section in resources/views/livewire/recipes/show.blade.php (lines 105-133)
- [X] T003 [US1] Replace bullet points with Flux checkboxes and Alpine.js wrapper
- [X] T004 [US1] Add dynamic styling with :class binding for strikethrough and opacity
- [X] T005 [US1] Add ARIA labels for accessibility
- [X] T006 [US1] Update spacing and add dark mode support
- [X] T007 [US1] Manual testing in browser - verify checkbox interaction, visual feedback, and state persistence

**Checkpoint**: At this point, ingredient checkboxes should be fully functional in the UI

---

## Phase 2: Pest Browser Tests

**Goal**: Comprehensive browser testing of checkbox functionality using Pest v4

**Independent Test**: Run `php artisan test --group=browser` - all tests should pass

### Browser Tests for Ingredient Checkboxes

- [X] T008 [P] [US1] Create RecipeIngredientsCheckboxTest.php in tests/Browser/
- [X] T009 [P] [US1] Add test: recipe ingredients display with checkboxes
- [X] T010 [P] [US1] Add test: checking ingredient applies visual feedback (strikethrough + opacity)
- [X] T011 [P] [US1] Add test: checkbox state persists during in-app navigation
- [X] T012 [P] [US1] Add test: checkbox state resets on page refresh
- [X] T013 [P] [US1] Add test: multiple ingredients can be checked independently
- [X] T014 [US1] Run Pest browser tests with `php artisan test --group=browser`

**Checkpoint**: All Pest browser tests should pass

---

## Phase 3: Playwright E2E Tests

**Goal**: End-to-end testing of complete user journeys with Playwright

**Independent Test**: Run `npx playwright test e2e/recipe-ingredient-checkboxes.spec.ts` - all tests should pass

### E2E Tests for Ingredient Checkboxes

- [X] T015 [P] [US1] Create recipe-ingredient-checkboxes.spec.ts in e2e/
- [X] T016 [P] [US1] Add test: ingredient checkboxes are visible and functional
- [X] T017 [P] [US1] Add test: checking ingredient applies strikethrough and opacity
- [X] T018 [P] [US1] Add test: multiple ingredients can be checked independently
- [X] T019 [P] [US1] Add test: checkbox state resets after page refresh
- [X] T020 [P] [US1] Add test: checkbox state resets when navigating to different recipe and back
- [X] T021 [P] [US1] Add test: checkboxes work on mobile viewport
- [X] T022 [P] [US1] Add test: checkboxes are keyboard accessible (Tab + Space)
- [X] T023 [US1] Run Playwright E2E tests with `npx playwright test e2e/recipe-ingredient-checkboxes.spec.ts`

**Checkpoint**: All Playwright E2E tests should pass

---

## Phase 4: Code Quality & Final Validation

**Purpose**: Ensure code quality and verify no regressions

### Quality Assurance

- [X] T024 [P] [US1] Run Laravel Pint code formatter with `vendor/bin/pint --dirty`
- [X] T025 [US1] Run full Pest test suite with `php artisan test`
- [X] T026 [US1] Run full Playwright test suite with `npx playwright test`
- [X] T027 [US1] Manual testing: verify checkboxes work on real recipe with 10+ ingredients
- [X] T028 [US1] Manual testing: verify dark mode styling looks correct
- [X] T029 [US1] Manual testing: verify mobile responsiveness and touch interaction
- [X] T030 [US1] Manual testing: verify keyboard navigation (Tab to focus, Space to toggle)

**Checkpoint**: Feature is production-ready

---

## Dependencies & Execution Order

### Phase Dependencies

- **Core Implementation (Phase 1)**: No dependencies - can start immediately
- **Pest Browser Tests (Phase 2)**: Depends on Phase 1 completion (needs functional UI to test)
- **Playwright E2E Tests (Phase 3)**: Depends on Phase 1 completion (needs functional UI to test)
- **Code Quality (Phase 4)**: Depends on Phases 1, 2, and 3 completion

### Task Dependencies

**Phase 1: Core Implementation**
- T001 and T002 can run in parallel (different files)
- T003-T006 must run sequentially (same file, building on each other)
- T007 depends on T001-T006 completion

**Phase 2: Pest Browser Tests**
- T008-T013 can all run in parallel (independent test cases)
- T014 depends on T008-T013 completion (runs all tests)

**Phase 3: Playwright E2E Tests**
- T015-T022 can all run in parallel (independent test cases)
- T023 depends on T015-T022 completion (runs all tests)

**Phase 4: Code Quality**
- T024-T026 can run in parallel (different tools)
- T027-T030 should run sequentially (manual testing scenarios)

### Parallel Opportunities

```bash
# Phase 1 - Core Implementation (parallel start)
Task T001: "Add Alpine.js component in resources/js/app.js"
Task T002: "Update ingredient section in resources/views/livewire/recipes/show.blade.php"

# Phase 2 - Pest Browser Tests (all parallel)
Task T008: "Create test file"
Task T009: "Test: checkboxes display"
Task T010: "Test: visual feedback"
Task T011: "Test: state persistence"
Task T012: "Test: state reset on refresh"
Task T013: "Test: multiple checkboxes"

# Phase 3 - Playwright E2E Tests (all parallel)
Task T015: "Create E2E test file"
Task T016: "Test: checkboxes visible and functional"
Task T017: "Test: strikethrough and opacity"
Task T018: "Test: multiple ingredients"
Task T019: "Test: refresh state reset"
Task T020: "Test: navigation state reset"
Task T021: "Test: mobile viewport"
Task T022: "Test: keyboard accessibility"

# Phase 4 - Code Quality (parallel tooling)
Task T024: "Run Pint formatter"
Task T025: "Run Pest test suite"
Task T026: "Run Playwright test suite"
```

---

## Implementation Strategy

### MVP First (Single User Story)

1. **Complete Phase 1**: Core Implementation
   - Add Alpine.js component
   - Update Blade template
   - Manual testing in browser
   - **STOP and VALIDATE**: Test checkbox functionality manually

2. **Complete Phase 2**: Pest Browser Tests
   - Create browser test file
   - Add all test cases
   - Run tests and verify they pass
   - **STOP and VALIDATE**: All browser tests passing

3. **Complete Phase 3**: Playwright E2E Tests
   - Create E2E test file
   - Add all test scenarios
   - Run tests and verify they pass
   - **STOP and VALIDATE**: All E2E tests passing

4. **Complete Phase 4**: Code Quality
   - Format code with Pint
   - Run full test suites
   - Manual QA testing
   - **FINAL VALIDATION**: Feature ready for production

### Incremental Delivery

- **After Phase 1**: Feature is usable but not fully tested → Good for early feedback
- **After Phase 2**: Feature has browser test coverage → Good for QA review
- **After Phase 3**: Feature has complete E2E coverage → Production-ready candidate
- **After Phase 4**: Feature is production-ready → Deploy

### Single Developer Strategy

Work through phases sequentially:
1. Core Implementation → Test manually
2. Pest Browser Tests → Automated browser coverage
3. Playwright E2E Tests → Complete E2E coverage
4. Code Quality → Polish and verify

### Parallel Team Strategy (if multiple developers)

**Developer A**: Core Implementation (Phase 1)
- Once T002 is complete, Developer B can start

**Developer B**: Test Files Creation (Phases 2 & 3)
- Can start writing test file structure while Dev A finishes implementation
- T008 and T015 can be created in parallel

**After Phase 1 complete**:
- Developer A: Pest Browser Tests (Phase 2)
- Developer B: Playwright E2E Tests (Phase 3)
- Both phases can proceed in parallel

---

## Files Modified

### Existing Files
- `resources/js/app.js` - Add Alpine.js component registration (T001)
- `resources/views/livewire/recipes/show.blade.php` - Update ingredient section (T002-T006)

### New Files Created
- `tests/Browser/RecipeIngredientsCheckboxTest.php` - Pest browser tests (T008-T013)
- `e2e/recipe-ingredient-checkboxes.spec.ts` - Playwright E2E tests (T015-T022)

### No Changes Required
- `app/Livewire/Recipes/Show.php` - No backend changes needed
- `app/Models/RecipeIngredient.php` - No model changes needed
- `app/Models/Recipe.php` - No model changes needed
- Database migrations - No schema changes needed
- Routes - No route changes needed

---

## Notes

- **[P] tasks**: Different files, no dependencies on other tasks
- **[US1] label**: All tasks belong to User Story 1 (Recipe Ingredient Checkboxes)
- **State Management**: Client-side only using Alpine.js - no database persistence
- **Visual Feedback**: Strikethrough text + reduced opacity when checked
- **Accessibility**: ARIA labels, keyboard navigation, semantic HTML
- **Browser Compatibility**: Tested in Chromium, Firefox, WebKit (via Playwright)
- **Mobile Support**: Responsive design with touch-friendly checkboxes
- **Dark Mode**: Full dark mode support with appropriate Tailwind variants

---

## Success Criteria

✅ Checkboxes appear next to each ingredient on recipe view page
✅ Clicking checkbox applies strikethrough and reduced opacity
✅ Multiple ingredients can be checked independently
✅ Checkbox state persists during in-app navigation (wire:navigate)
✅ Checkbox state resets on page refresh
✅ Checkbox state resets when navigating to different recipe
✅ All Pest browser tests pass
✅ All Playwright E2E tests pass
✅ Code formatted with Laravel Pint
✅ No regressions in existing test suite
✅ Accessible via keyboard (Tab + Space)
✅ Works on mobile viewports
✅ Dark mode styling correct
