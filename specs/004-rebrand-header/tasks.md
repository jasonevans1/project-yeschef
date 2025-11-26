# Tasks: Rebrand Application Header

**Input**: Design documents from `/specs/004-rebrand-header/`
**Prerequisites**: plan.md, spec.md, research.md

**Tests**: Test-First Development is REQUIRED per project constitution. Tests will be written BEFORE implementation.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- Web application with Laravel + Livewire
- Blade components in `resources/views/components/`
- Layouts in `resources/views/components/layouts/`
- Tests in `tests/Feature/` and `e2e/`
- Configuration in `config/`

---

## Phase 1: Setup (Test Infrastructure)

**Purpose**: Set up test files following Test-First Development principle (NON-NEGOTIABLE)

- [X] T001 [P] Create Pest feature test file in tests/Feature/BrandingTest.php with failing tests for header branding
- [X] T002 [P] Create Playwright E2E test file in e2e/header-branding.spec.ts with failing visual tests

**Checkpoint**: All tests should FAIL at this point (expected - following TDD)

---

## Phase 2: Foundational (Logo Design)

**Purpose**: Design and create the logo asset that will be used across all user stories

**⚠️ CRITICAL**: Logo must be designed before any branding updates can be made

- [X] T003 Design SVG logo for "Project Table Top" with tabletop gaming theme (dice, table, grid) using currentColor for dark mode support

**Checkpoint**: Logo design complete and ready to integrate

---

## Phase 3: User Story 3 - Custom Logo Display (Priority: P1) 🎯 MVP Component

**Goal**: Implement custom logo component that displays "Project Table Top" branding with SVG icon supporting light/dark themes

**Independent Test**: Navigate to any page and verify the logo SVG displays correctly in the header. Toggle dark mode to confirm logo remains visible with proper contrast.

**Why First**: Logo component is foundational for both US1 and US3. Completing this enables visual brand identity immediately.

### Implementation for User Story 3

- [X] T004 [US3] Update resources/views/components/app-logo-icon.blade.php with new SVG logo design from T003 using fill="currentColor"
- [X] T005 [US3] Verify logo icon component maintains {{ $attributes }} for Tailwind class pass-through

**Checkpoint**: Logo component complete - run E2E tests to verify logo displays in light and dark mode

---

## Phase 4: User Story 1 - Visual Brand Recognition (Priority: P1) 🎯 MVP

**Goal**: Replace "Laravel Starter Kit" text with "Project Table Top" in header and page titles for complete brand identity

**Independent Test**: Navigate to dashboard, recipes, meal plans, and grocery lists. Verify header shows "Project Table Top" text and browser tab title shows "Project Table Top".

### Implementation for User Story 1

- [X] T006 [P] [US1] Update resources/views/components/app-logo.blade.php line 5 to change "Laravel Starter Kit" to "Project Table Top"
- [X] T007 [P] [US1] Update config/app.php line ~17 to change 'name' value to env('APP_NAME', 'Project Table Top')
- [X] T008 [US1] Verify resources/views/partials/head.blade.php line 4 uses config('app.name') for page title (already implemented, just verify)

**Checkpoint**: Header and page titles show "Project Table Top" - run Pest tests to verify branding appears correctly

---

## Phase 5: User Story 2 - Simplified Header Navigation (Priority: P2)

**Goal**: Remove search, repository, and documentation links from header navigation for cleaner UI

**Independent Test**: View header on desktop and open mobile sidebar. Confirm search icon, repository link, and documentation link are not visible in DOM.

### Implementation for User Story 2

- [X] T009 [US2] Remove search, repository, and documentation navbar items from resources/views/components/layouts/app/header.blade.php lines 31-53 (desktop header)
- [X] T010 [US2] Remove repository and documentation navlist items from resources/views/components/layouts/app/header.blade.php lines 127-135 (mobile sidebar)

**Checkpoint**: All removed links are no longer visible - run E2E tests to verify links absent from DOM

---

## Phase 6: Polish & Validation

**Purpose**: Final testing, code quality, and validation

- [X] T011 Run php artisan test --filter=BrandingTest to verify all Pest tests pass
- [X] T012 Run npx playwright test e2e/header-branding.spec.ts to verify E2E tests pass
- [X] T013 Run vendor/bin/pint to format code per Laravel Pint standards
- [ ] T014 [P] Visual QA in DDEV environment - verify branding on dashboard, recipes, meal plans, grocery lists
- [ ] T015 [P] Visual QA for dark mode - toggle theme and verify logo contrast and visibility
- [ ] T016 [P] Visual QA for mobile responsive - test at 375px, 768px, 1024px, 1920px viewports
- [X] T017 Clear Laravel caches (php artisan config:clear && php artisan view:clear) and verify changes persist
- [X] T018 Run full test suite (php artisan test && npx playwright test) to ensure no regressions

**Checkpoint**: All tests passing, code formatted, visual QA complete

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - start immediately with test creation
- **Foundational (Phase 2)**: No dependencies - logo design can start immediately in parallel with tests
- **User Story 3 (Phase 3)**: Depends on logo design completion (T003) - BLOCKS US1 and US2 visual testing
- **User Story 1 (Phase 4)**: Depends on logo component (T004-T005) for complete branding
- **User Story 2 (Phase 5)**: Independent of US1 and US3 - can start after tests created
- **Polish (Phase 6)**: Depends on all user stories complete (US1, US2, US3)

### User Story Dependencies

- **User Story 3 (P1 - Logo)**: Can start after logo design (T003) - No dependencies on other stories
- **User Story 1 (P1 - Text Branding)**: Depends on logo component for visual completeness but text changes are independent
- **User Story 2 (P2 - Remove Links)**: Completely independent - can start after tests created

### Within Each User Story

- **US3 (Logo)**: T004 → T005 (sequential - verify after update)
- **US1 (Branding)**: T006, T007, T008 can all run in parallel [P]
- **US2 (Navigation)**: T009 → T010 (sequential - same file)

### Parallel Opportunities

**Phase 1 + 2 (Setup + Foundation)**:
```bash
# All three can run in parallel:
Task: "Create Pest feature test file" (T001)
Task: "Create Playwright E2E test file" (T002)
Task: "Design SVG logo for Project Table Top" (T003)
```

**Phase 4 (User Story 1)**:
```bash
# All three branding updates can run in parallel:
Task: "Update app-logo.blade.php to Project Table Top" (T006)
Task: "Update config/app.php name value" (T007)
Task: "Verify partials/head.blade.php uses config" (T008)
```

**Phase 6 (Polish - Visual QA)**:
```bash
# Multiple QA tasks can run in parallel:
Task: "Visual QA in DDEV environment" (T014)
Task: "Visual QA for dark mode" (T015)
Task: "Visual QA for mobile responsive" (T016)
```

---

## Parallel Example: Complete Feature

For maximum efficiency, execute in this parallel pattern:

**Round 1 - Parallel Setup (Start Immediately)**:
```bash
Task T001: "Create Pest tests"
Task T002: "Create Playwright tests"
Task T003: "Design logo SVG"
```

**Round 2 - Logo Implementation (After T003)**:
```bash
Task T004: "Update app-logo-icon.blade.php"
Task T005: "Verify attributes pass-through"
```

**Round 3 - Parallel Branding (After T004-T005)**:
```bash
Task T006: "Update app-logo text"
Task T007: "Update config name"
Task T008: "Verify head title"
```

**Round 4 - Navigation Cleanup (Sequential - Same File)**:
```bash
Task T009: "Remove desktop nav links"
Task T010: "Remove mobile nav links"
```

**Round 5 - Parallel QA (After All Implementation)**:
```bash
Task T011: "Run Pest tests"
Task T012: "Run Playwright tests"
Task T013: "Run Pint formatter"
Task T014: "Visual QA pages"
Task T015: "Visual QA dark mode"
Task T016: "Visual QA responsive"
```

---

## Implementation Strategy

### MVP First (User Stories 3 + 1 Only)

1. **Complete Phase 1**: Setup tests (T001-T002)
2. **Complete Phase 2**: Design logo (T003)
3. **Complete Phase 3**: Logo component (T004-T005) ← MVP Checkpoint
4. **Complete Phase 4**: Text branding (T006-T008) ← Full P1 Complete
5. **STOP and VALIDATE**: Run tests, visual QA
6. Deploy/demo with new branding

**Deliverable**: Application has complete "Project Table Top" branding with custom logo

### Incremental Delivery

1. **MVP**: US3 + US1 → Complete brand identity (logo + text)
   - Test independently: Header shows logo and "Project Table Top" text on all pages
   - Deploy/demo
2. **V2**: Add US2 → Cleaner navigation
   - Test independently: Links removed, navigation simplified
   - Deploy/demo
3. Each increment adds value without breaking previous functionality

### Parallel Team Strategy

With two developers:

1. **Developer A & B Together**:
   - T001-T002 (tests) + T003 (logo design) in parallel
   - T004-T005 (logo component)
2. **Split Work**:
   - **Developer A**: T006-T008 (User Story 1 - text branding)
   - **Developer B**: T009-T010 (User Story 2 - remove links)
3. **Together**: T011-T018 (testing and QA)

---

## Test-First Development Validation

**Critical Requirement**: Following the project constitution's Test-First principle

### Test Creation (Phase 1)
- ✅ T001: Pest feature tests created FIRST
- ✅ T002: Playwright E2E tests created FIRST

### Test Failure Verification
Before any implementation (T004-T010):
- Run `php artisan test --filter=BrandingTest` → Should FAIL
- Run `npx playwright test e2e/header-branding.spec.ts` → Should FAIL

### Implementation Validation
After implementation (T004-T010):
- Run tests again → Should PASS
- If tests still fail: Debug implementation
- If tests pass: Proceed to QA

### Success Criteria
- All tests written before implementation ✅
- Tests failed before implementation ✅
- Tests passed after implementation ✅
- Code formatted with Pint ✅
- Visual QA passed ✅

---

## Task Summary

**Total Tasks**: 18
- **Setup (Tests)**: 2 tasks
- **Foundational (Logo Design)**: 1 task
- **User Story 3 (Logo Component)**: 2 tasks
- **User Story 1 (Text Branding)**: 3 tasks
- **User Story 2 (Remove Links)**: 2 tasks
- **Polish & Validation**: 8 tasks

**Parallel Opportunities**: 8 tasks can run in parallel
- Phase 1+2: 3 tasks (T001, T002, T003)
- Phase 4: 3 tasks (T006, T007, T008)
- Phase 6: Multiple QA tasks (T014, T015, T016)

**Estimated Effort**: 30-45 minutes total (per quickstart.md)
- Tests: 10-15 minutes
- Logo design: 5-10 minutes
- Implementation: 10 minutes
- QA & validation: 10-15 minutes

**MVP Scope**: User Stories 3 + 1 (T001-T008) = Complete brand identity
**Full Feature**: All user stories (T001-T018) = Complete rebrand with navigation cleanup

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- Each user story is independently testable and deliverable
- Tests MUST fail before implementation (TDD validation)
- Commit after each phase completion
- Stop at any checkpoint to validate story independently
- Logo design (T003) is the critical path item - blocks visual implementation
- All file paths are absolute from repository root
