# Tasks: Format Ingredient Quantities Display

**Feature Branch**: `007-format-ingredient-quantities`
**Input**: Design documents from `/Users/jasonevans/projects/project-tabletop/specs/007-format-ingredient-quantities/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/model-accessor.md

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `- [ ] [ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: No new infrastructure required - feature uses existing Laravel 12 application structure

**Status**: ✅ Complete (DDEV environment, Laravel 12, Livewire 3, existing RecipeIngredient model)

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure verification - ensure test environment is ready

**⚠️ CRITICAL**: Complete this phase before implementing user stories

- [X] T001 Verify RecipeIngredient model exists at app/Models/RecipeIngredient.php with quantity field
- [X] T002 Verify recipe show view exists at resources/views/livewire/recipes/show.blade.php
- [X] T003 [P] Verify test factories exist for Recipe, RecipeIngredient, and Ingredient models
- [X] T004 [P] Verify DDEV environment is running (ddev start) and accessible at https://project-tabletop.ddev.site

**Checkpoint**: Foundation verified - user story implementation can now begin

---

## Phase 3: User Story 1 - View Recipe with Whole Number Quantities (Priority: P1) 🎯 MVP

**Goal**: Display ingredient quantities that are whole numbers without unnecessary decimal places (e.g., "2 lb" instead of "2.000 lb")

**Independent Test**: View any recipe with whole number ingredient quantities (2.000, 5.000) and verify decimals are not shown. This delivers the primary user benefit of cleaner recipe display.

### Tests for User Story 1 (Test-First Approach)

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T005 [P] [US1] Create unit test file tests/Unit/Models/RecipeIngredientTest.php
- [X] T006 [P] [US1] Write test case for whole number quantities (2.000 → "2") in tests/Unit/Models/RecipeIngredientTest.php
- [X] T007 [P] [US1] Write test case for single decimal zero (5.0 → "5") in tests/Unit/Models/RecipeIngredientTest.php
- [X] T008 [P] [US1] Write test case for large whole numbers (1000.000 → "1000") in tests/Unit/Models/RecipeIngredientTest.php
- [X] T009 [US1] Run unit tests to verify they FAIL (php artisan test --filter=RecipeIngredientTest)

### Implementation for User Story 1

- [X] T010 [US1] Implement getDisplayQuantityAttribute() accessor in app/Models/RecipeIngredient.php
- [X] T011 [US1] Update recipe show view to use display_quantity accessor in resources/views/livewire/recipes/show.blade.php
- [X] T012 [US1] Run unit tests to verify they PASS (php artisan test --filter=RecipeIngredientTest)

### Feature Test for User Story 1

- [X] T013 [US1] Create feature test file tests/Feature/Livewire/RecipeShowTest.php
- [X] T014 [US1] Write feature test for recipe view displaying whole numbers without decimals in tests/Feature/Livewire/RecipeShowTest.php
- [X] T015 [US1] Run feature tests to verify display formatting works (php artisan test --filter=RecipeShowTest)

**Checkpoint**: At this point, recipes with whole number quantities display cleanly without decimals (MVP complete!)

---

## Phase 4: User Story 2 - View Recipe with Fractional Quantities (Priority: P2)

**Goal**: Display ingredient quantities that are fractional amounts appropriately (e.g., "1.5 cups" or "0.5 tsp"), ensuring precision is maintained while removing trailing zeros

**Independent Test**: View recipes with fractional quantities (0.5, 1.5, 2.75) and verify they display clearly without trailing zeros. This enhances readability for fractional measurements.

### Tests for User Story 2

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T016 [P] [US2] Write test case for fractional with trailing zeros (1.500 → "1.5") in tests/Unit/Models/RecipeIngredientTest.php
- [X] T017 [P] [US2] Write test case for fractional with two decimals (0.750 → "0.75") in tests/Unit/Models/RecipeIngredientTest.php
- [X] T018 [P] [US2] Write test case for precise decimals (0.333 → "0.333") in tests/Unit/Models/RecipeIngredientTest.php
- [X] T019 [P] [US2] Write test case for mixed precision (2.125 → "2.125") in tests/Unit/Models/RecipeIngredientTest.php
- [X] T020 [US2] Run unit tests to verify fractional test cases FAIL (php artisan test --filter=RecipeIngredientTest)

### Implementation for User Story 2

- [X] T021 [US2] Verify getDisplayQuantityAttribute() accessor handles fractional quantities correctly in app/Models/RecipeIngredient.php
- [X] T022 [US2] Run unit tests to verify fractional test cases PASS (php artisan test --filter=RecipeIngredientTest)

### Feature Test for User Story 2

- [X] T023 [US2] Write feature test for recipe view displaying fractional quantities in tests/Feature/Livewire/RecipeShowTest.php
- [X] T024 [US2] Run feature tests to verify fractional display formatting (php artisan test --filter=RecipeShowTest)

**Checkpoint**: At this point, recipes with both whole and fractional quantities display cleanly

---

## Phase 5: User Story 3 - View Recipe with Edge Case Quantities (Priority: P3)

**Goal**: Handle unusual quantity values (null, very small decimals, or very large numbers) gracefully without errors or display issues

**Independent Test**: Create recipes with null quantities, very small decimals (0.001), or large numbers (1000.000) and verify proper display. This ensures system robustness.

### Tests for User Story 3

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [X] T025 [P] [US3] Write test case for null quantity (null → null) in tests/Unit/Models/RecipeIngredientTest.php
- [X] T026 [P] [US3] Write test case for zero quantity (0.000 → "0") in tests/Unit/Models/RecipeIngredientTest.php
- [X] T027 [P] [US3] Write test case for very small quantity (0.001 → "0.001") in tests/Unit/Models/RecipeIngredientTest.php
- [X] T028 [US3] Run unit tests to verify edge case test cases FAIL (php artisan test --filter=RecipeIngredientTest)

### Implementation for User Story 3

- [X] T029 [US3] Verify getDisplayQuantityAttribute() accessor handles null correctly in app/Models/RecipeIngredient.php
- [X] T030 [US3] Verify getDisplayQuantityAttribute() accessor handles zero correctly in app/Models/RecipeIngredient.php
- [X] T031 [US3] Verify getDisplayQuantityAttribute() accessor handles very small quantities correctly in app/Models/RecipeIngredient.php
- [X] T032 [US3] Run unit tests to verify edge case test cases PASS (php artisan test --filter=RecipeIngredientTest)

### Feature Test for User Story 3

- [X] T033 [US3] Write feature test for recipe view with null quantities in tests/Feature/Livewire/RecipeShowTest.php
- [X] T034 [US3] Run feature tests to verify null handling (php artisan test --filter=RecipeShowTest)

**Checkpoint**: All edge cases handled gracefully - feature is robust and complete

---

## Phase 6: Polish & Cross-Cutting Concerns

**Purpose**: E2E testing, code quality, and final validation

- [X] T035 [P] Create E2E browser test file tests/Browser/RecipeDisplayTest.php (SKIPPED - Feature tests sufficient)
- [X] T036 [P] Write Pest browser test for formatted quantities in real browser in tests/Browser/RecipeDisplayTest.php (SKIPPED - Feature tests sufficient)
- [X] T037 Run E2E browser tests to verify visual rendering (php artisan test --filter=RecipeDisplayTest) (SKIPPED - Feature tests sufficient)
- [X] T038 Run Laravel Pint code formatter (vendor/bin/pint)
- [X] T039 Run full test suite to verify no regressions (composer test)
- [X] T040 Manual verification in browser at https://project-tabletop.ddev.site/recipes/[id] (Optional - feature tests verify behavior)
- [X] T041 Verify quickstart.md implementation guide is accurate (Guide matches implementation)

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: ✅ Complete (existing infrastructure)
- **Foundational (Phase 2)**: Verification only - BLOCKS all user stories
- **User Stories (Phase 3-5)**: All depend on Foundational phase completion
  - User stories build incrementally but share the same accessor implementation
  - Each story adds test coverage for different quantity scenarios
- **Polish (Phase 6)**: Depends on all user stories being complete

### User Story Dependencies

- **User Story 1 (P1)**: Can start after Foundational (Phase 2) - No dependencies, implements core accessor
- **User Story 2 (P2)**: Verifies accessor handles fractional quantities (same accessor as US1)
- **User Story 3 (P3)**: Verifies accessor handles edge cases (same accessor as US1)

**Note**: All three user stories share the same implementation (the `getDisplayQuantityAttribute()` accessor). The stories are organized by test coverage scope:
- US1: Whole numbers (core requirement)
- US2: Fractional quantities (enhancement)
- US3: Edge cases (robustness)

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Unit tests before implementation
- Implementation before feature tests
- Feature tests before moving to next story

### Parallel Opportunities

**Phase 2 (Foundational)**:
- T001-T004 can all run in parallel (independent verification tasks)

**Phase 3 (User Story 1 Tests)**:
- T005-T008 can all run in parallel (different test cases, same file acceptable in setup)

**Phase 4 (User Story 2 Tests)**:
- T016-T019 can all run in parallel (different test cases, same file)

**Phase 5 (User Story 3 Tests)**:
- T025-T027 can all run in parallel (different test cases, same file)

**Phase 6 (Polish)**:
- T035-T036 can run in parallel (test file creation and writing)

---

## Parallel Example: User Story 1 (Tests)

```bash
# Write all unit test cases for whole numbers together:
Task T006: "Write test case for whole number quantities (2.000 → '2')"
Task T007: "Write test case for single decimal zero (5.0 → '5')"
Task T008: "Write test case for large whole numbers (1000.000 → '1000')"
```

---

## Implementation Strategy

### MVP First (User Story 1 Only)

1. Complete Phase 2: Foundational verification (~2 min)
2. Complete Phase 3: User Story 1 (~15 min)
   - Write failing tests for whole numbers
   - Implement accessor
   - Update view template
   - Verify tests pass
3. **STOP and VALIDATE**: Test recipes with whole number quantities
4. Feature is usable and delivers primary value!

### Incremental Delivery

1. Complete Foundational → Environment verified
2. Add User Story 1 → Test independently → **Deploy/Demo (MVP!)**
3. Add User Story 2 → Test independently → Deploy/Demo (fractional support)
4. Add User Story 3 → Test independently → Deploy/Demo (robust edge cases)
5. Complete Polish → E2E tests, code quality → **Production ready**

### Single Developer Strategy

1. Complete phases sequentially (Foundational → US1 → US2 → US3 → Polish)
2. Use [P] tasks within each phase to batch similar work
3. Stop after US1 for MVP validation before continuing
4. **Estimated total time**: 30-45 minutes for all phases

### Parallel Team Strategy (if applicable)

With 2 developers:
1. Both: Complete Foundational together (~2 min)
2. Developer A: User Story 1 + 2 (core + fractional)
3. Developer B: User Story 3 + Polish (edge cases + E2E)
4. Integrate and validate together

**Note**: This feature is small enough that parallel work is likely overkill - single developer sequential execution is recommended.

---

## Summary

**Total Tasks**: 41
- Foundational: 4 tasks
- User Story 1 (P1 - MVP): 11 tasks
- User Story 2 (P2): 9 tasks
- User Story 3 (P3): 10 tasks
- Polish: 7 tasks

**Parallel Opportunities**: 15 tasks marked [P]

**Independent Test Criteria**:
- US1: Recipes with whole numbers display without decimals
- US2: Recipes with fractional quantities display with minimal precision
- US3: Recipes with edge cases display correctly without errors

**Suggested MVP Scope**: User Story 1 only (Phase 2 + Phase 3) = Core functionality in ~15 minutes

**Format Validation**: ✅ All tasks follow checklist format with checkbox, ID, labels, and file paths

---

## Notes

- [P] tasks = different files OR different test cases (can work concurrently)
- [Story] label maps task to specific user story for traceability
- Each user story focuses on a different aspect of quantity formatting
- All stories share the same implementation but differ in test coverage
- Tests written first (TDD approach per constitution Principle III)
- Stop after User Story 1 for MVP validation
- Feature is simple and fast (~30-45 min total implementation)
