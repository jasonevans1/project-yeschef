# Tasks: Meal Plan Notes

**Input**: Design documents from `/specs/010-meal-plan-notes/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/meal-plan-notes.yaml, quickstart.md

**Tests**: This feature includes Pest feature tests and Playwright E2E tests as specified in plan.md.

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`

- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (e.g., US1, US2, US3)
- Include exact file paths in descriptions

## Path Conventions

- **Backend**: `app/`, `database/`, `tests/Feature/`
- **Frontend**: `resources/views/livewire/`
- **E2E Tests**: `e2e/`

---

## Phase 1: Setup (Shared Infrastructure)

**Purpose**: Create the database schema, model, factory, and policy required for all user stories

- [x] T001 Create migration for meal_plan_notes table in database/migrations/xxxx_create_meal_plan_notes_table.php
- [x] T002 Run database migration to create meal_plan_notes table
- [x] T003 [P] Create MealPlanNote model in app/Models/MealPlanNote.php with fillable, casts, and relationships
- [x] T004 [P] Create MealPlanNoteFactory in database/factories/MealPlanNoteFactory.php
- [x] T005 [P] Create MealPlanNotePolicy in app/Policies/MealPlanNotePolicy.php delegating to MealPlanPolicy
- [x] T006 Add mealPlanNotes() hasMany relationship to app/Models/MealPlan.php

**Checkpoint**: Database and model infrastructure ready - user story implementation can now begin

---

## Phase 2: User Story 1 & 2 - Add Note and View Note (Priority: P1) MVP

**Goal**: Users can add free-form notes to meal plan slots and view them in the calendar grid

**Independent Test**: Add a note with title and details to a meal slot, verify it appears in the calendar view with distinct visual styling

**Why Combined**: US1 (Add) and US2 (View) are tightly coupled - adding a note requires viewing it to confirm success. Testing either in isolation is impractical.

### Tests for User Story 1 & 2

> **NOTE: Write these tests FIRST, ensure they FAIL before implementation**

- [x] T007 [P] [US1] Create Pest feature test file in tests/Feature/MealPlans/MealPlanNotesTest.php
- [x] T008 [US1] Add test: user can add note to meal plan with title and details
- [x] T009 [P] [US1] Add test: title validation required and max 255 characters
- [x] T010 [P] [US1] Add test: details validation nullable and max 2000 characters
- [x] T011 [P] [US2] Add test: notes are displayed in meal plan grouped by date and meal type
- [x] T012 [P] [US1] Add test: user cannot add note to another user's meal plan (authorization)

### Implementation for User Story 1 & 2

- [x] T013 [US1] Add note-related properties to app/Livewire/MealPlans/Show.php (showNoteForm, noteTitle, noteDetails, editingNoteId)
- [x] T014 [US1] Add openNoteForm() method to app/Livewire/MealPlans/Show.php
- [x] T015 [US1] Add closeNoteForm() method to app/Livewire/MealPlans/Show.php
- [x] T016 [US1] Add saveNote() method to app/Livewire/MealPlans/Show.php with validation and create logic
- [x] T017 [US2] Modify render() method to load and group notes by date_mealType in app/Livewire/MealPlans/Show.php
- [x] T018 [US1] Add "Add Note" option to meal slot dropdown in resources/views/livewire/meal-plans/show.blade.php
- [x] T019 [US1] Add note form modal (title input, details textarea) in resources/views/livewire/meal-plans/show.blade.php
- [x] T020 [US2] Add note display in calendar cells with distinct visual styling (amber background, document icon) in resources/views/livewire/meal-plans/show.blade.php
- [x] T021 [US1] Run Pint formatter and verify all US1/US2 tests pass

**Checkpoint**: Users can add notes to meal plan slots and see them in the calendar. MVP deliverable.

---

## Phase 3: User Story 3 - Edit Existing Note (Priority: P2)

**Goal**: Users can edit the title and details of existing notes

**Independent Test**: Edit an existing note's title and details, verify changes are saved and displayed

### Tests for User Story 3

- [x] T022 [P] [US3] Add test: user can edit existing note title and details
- [x] T023 [P] [US3] Add test: edit validation prevents empty title

### Implementation for User Story 3

- [x] T024 [US3] Add note drawer properties to app/Livewire/MealPlans/Show.php (showNoteDrawer, selectedNoteId)
- [x] T025 [US3] Add selectedNote computed property to app/Livewire/MealPlans/Show.php
- [x] T026 [US3] Add openNoteDrawer() and closeNoteDrawer() methods to app/Livewire/MealPlans/Show.php
- [x] T027 [US3] Add editNote() method to app/Livewire/MealPlans/Show.php
- [x] T028 [US3] Update saveNote() method to handle update logic (when editingNoteId is set) in app/Livewire/MealPlans/Show.php
- [x] T029 [US3] Add note detail drawer component in resources/views/livewire/meal-plans/show.blade.php
- [x] T030 [US3] Add click handler on notes in calendar to open drawer in resources/views/livewire/meal-plans/show.blade.php
- [x] T031 [US3] Add edit button in note drawer that opens note form in resources/views/livewire/meal-plans/show.blade.php
- [x] T032 [US3] Run Pint formatter and verify all US3 tests pass

**Checkpoint**: Users can view note details in a drawer and edit notes. US1, US2, US3 all work independently.

---

## Phase 4: User Story 4 - Delete Note (Priority: P2)

**Goal**: Users can delete notes from the meal plan

**Independent Test**: Delete a note and verify it no longer appears in the meal plan

### Tests for User Story 4

- [x] T033 [P] [US4] Add test: user can delete note from meal plan
- [x] T034 [P] [US4] Add test: deleted note no longer appears in calendar

### Implementation for User Story 4

- [x] T035 [US4] Add deleteNote() method to app/Livewire/MealPlans/Show.php with confirmation
- [x] T036 [US4] Add delete button on note hover in calendar cell in resources/views/livewire/meal-plans/show.blade.php
- [x] T037 [US4] Add delete button in note drawer footer in resources/views/livewire/meal-plans/show.blade.php
- [x] T038 [US4] Run Pint formatter and verify all US4 tests pass

**Checkpoint**: Full CRUD operations for notes. US1-4 all work independently.

---

## Phase 5: User Story 5 - Notes Excluded from Grocery List (Priority: P1)

**Goal**: Notes do not contribute ingredients to grocery list generation

**Independent Test**: Create meal plan with recipes and notes, generate grocery list, verify only recipe ingredients appear

### Tests for User Story 5

- [x] T039 [P] [US5] Add test: grocery list generation excludes notes entirely
- [x] T040 [P] [US5] Add test: meal plan with only notes generates empty or no-recipes-message grocery list

### Verification for User Story 5

- [x] T041 [US5] Verify GroceryListGenerator only queries mealAssignments relationship (no code changes expected)
- [x] T042 [US5] Run existing grocery list tests to confirm no regression

**Checkpoint**: Grocery list exclusion verified. All P1 stories complete.

---

## Phase 6: E2E Tests & Polish

**Purpose**: End-to-end user flow testing and final cleanup

### E2E Tests (Playwright)

- [x] T043 [P] Create Playwright test file in e2e/meal-plans-notes.spec.ts
- [x] T044 [P] Add E2E test: add note to empty meal slot flow
- [x] T045 [P] Add E2E test: add note to slot with existing recipe flow
- [x] T046 [P] Add E2E test: view note details in drawer flow
- [x] T047 [P] Add E2E test: edit note flow
- [x] T048 [P] Add E2E test: delete note flow

### Final Verification

- [x] T049 Run full test suite (composer test)
- [x] T050 Run Playwright E2E tests for meal plan notes
- [x] T051 Run quickstart.md verification checklist
- [x] T052 Run Pint formatter on all modified files

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **User Story 1 & 2 (Phase 2)**: Depends on Phase 1 completion - MVP
- **User Story 3 (Phase 3)**: Depends on Phase 2 (needs notes to edit)
- **User Story 4 (Phase 4)**: Depends on Phase 2 (needs notes to delete)
- **User Story 5 (Phase 5)**: Depends on Phase 1 only (verification of existing behavior)
- **E2E & Polish (Phase 6)**: Depends on all user stories being complete

### User Story Dependencies

- **User Story 1 & 2 (P1)**: Can start after Phase 1 - No dependencies on other stories
- **User Story 3 (P2)**: Depends on US1/2 (needs notes to exist to edit them)
- **User Story 4 (P2)**: Depends on US1/2 (needs notes to exist to delete them)
- **User Story 5 (P1)**: Independent verification - can run in parallel with US1/2 after Phase 1

### Within Each User Story

- Tests MUST be written and FAIL before implementation
- Livewire component methods before Blade view updates
- Story complete before moving to next priority
- Run Pint after each story phase

### Parallel Opportunities

- **Phase 1**: T003, T004, T005 can run in parallel (different files)
- **Phase 2 Tests**: T007-T012 can mostly run in parallel
- **Phase 5**: US5 can run in parallel with US3/US4 after Phase 2
- **Phase 6 E2E**: All E2E tests (T043-T048) can run in parallel

---

## Parallel Example: Phase 1 Setup

```bash
# Launch all model/factory/policy tasks together:
Task: "Create MealPlanNote model in app/Models/MealPlanNote.php"
Task: "Create MealPlanNoteFactory in database/factories/MealPlanNoteFactory.php"
Task: "Create MealPlanNotePolicy in app/Policies/MealPlanNotePolicy.php"
```

## Parallel Example: Phase 2 Tests

```bash
# Launch all tests for User Story 1 & 2 together:
Task: "Create Pest feature test file in tests/Feature/MealPlans/MealPlanNotesTest.php"
Task: "Add test: title validation required and max 255 characters"
Task: "Add test: details validation nullable and max 2000 characters"
Task: "Add test: notes are displayed in meal plan grouped by date and meal type"
Task: "Add test: user cannot add note to another user's meal plan"
```

---

## Implementation Strategy

### MVP First (User Stories 1 & 2 Only)

1. Complete Phase 1: Setup (migration, model, factory, policy)
2. Complete Phase 2: User Story 1 & 2 (add note, view notes)
3. **STOP and VALIDATE**: Test adding and viewing notes independently
4. Deploy/demo if ready - users can now add and see notes in meal plans

### Incremental Delivery

1. Complete Setup → Infrastructure ready
2. Add User Story 1 & 2 → Test independently → Deploy/Demo (MVP!)
3. Add User Story 3 (Edit) → Test independently → Deploy/Demo
4. Add User Story 4 (Delete) → Test independently → Deploy/Demo
5. Verify User Story 5 (Grocery exclusion) → Test independently → Deploy/Demo
6. E2E tests and polish → Final validation

### Parallel Team Strategy

With multiple developers:

1. Developer A: Setup (Phase 1) → US1/2 (Phase 2)
2. Developer B: Can start US5 verification after Phase 1 completes
3. Developer A: After US1/2 → US3 → US4
4. Developer B: E2E tests (Phase 6) after all stories complete

---

## Notes

- [P] tasks = different files, no dependencies
- [Story] label maps task to specific user story for traceability
- US1/US2 combined because add+view are inseparable for testing
- US5 is verification-only (no new code expected per research.md)
- Verify tests fail before implementing
- Commit after each task or logical group
- Stop at any checkpoint to validate story independently
