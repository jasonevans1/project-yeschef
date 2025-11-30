# Tasks: Delete Grocery List

**Feature**: Delete Grocery List with Confirmation
**Branch**: `005-delete-grocery-list`
**Date**: 2025-11-30
**Status**: Ready for Implementation

## Overview

This task list implements the delete grocery list feature using a test-first, user-story-driven approach. Tasks are organized by user story to enable independent implementation and testing.

**Total Tasks**: 17
**User Stories**: 2 (US1: P1, US2: P2)
**MVP Scope**: User Story 1 only (complete delete with confirmation)

## Implementation Strategy

### MVP-First Approach

**Minimum Viable Product (MVP)**: User Story 1 - Delete Grocery List with Confirmation
- Delivers core value: Users can delete grocery lists with confirmation
- Independently testable and deployable
- Estimated time: 2-3 hours

**Incremental Delivery**:
1. Phase 2: Foundational (database + models) → Enables all stories
2. Phase 3: US1 (P1) - Delete with confirmation → **MVP COMPLETE**
3. Phase 4: US2 (P2) - Cancel deletion (UI enhancement)

### User Story Dependencies

```
Foundational (Phase 2)
    ↓
US1 (P1) - Delete with Confirmation ← MVP
    ↓ (optional)
US2 (P2) - Cancel Deletion
```

**Dependency Notes**:
- US2 depends on US1 (uses same modal, just tests cancel path)
- Both stories can be implemented in sequence or US2 can be deferred
- Each story is independently testable

---

## Phase 1: Setup

**Goal**: Verify development environment is ready

- [ ] T001 Verify DDEV environment is running (`ddev start`)
- [ ] T002 Verify on feature branch `005-delete-grocery-list`
- [ ] T003 Verify composer dependencies installed (`composer install`)
- [ ] T004 Verify database migrated (`php artisan migrate`)

**Completion Criteria**: All commands execute successfully, no errors

---

## Phase 2: Foundational Tasks

**Goal**: Set up database schema and model soft deletes (blocking prerequisites for all user stories)

**Why Foundational**: Both US1 and US2 require soft delete functionality in database and models. These tasks must complete before any user story implementation.

### Database Migrations

- [ ] T005 [P] Create migration for grocery_lists soft deletes in database/migrations/YYYY_MM_DD_add_soft_deletes_to_grocery_lists_table.php
- [ ] T006 [P] Create migration for grocery_items soft deletes in database/migrations/YYYY_MM_DD_add_soft_deletes_to_grocery_items_table.php
- [ ] T007 Run both migrations (`php artisan migrate`)

### Model Updates

- [ ] T008 [P] Add SoftDeletes trait to GroceryList model in app/Models/GroceryList.php
- [ ] T009 [P] Add SoftDeletes trait to GroceryItem model in app/Models/GroceryItem.php

**Parallel Opportunities**: T005 + T006 can run in parallel (different files), T008 + T009 can run in parallel (different files)

**Completion Criteria**:
- Migrations run successfully
- `deleted_at` column exists on both tables
- Models have SoftDeletes trait
- Database schema supports soft deletion

**Independent Test**: Run `php artisan tinker` and execute:
```php
$list = GroceryList::first();
$list->delete();
$list->trashed(); // Should return true
```

---

## Phase 3: User Story 1 - Delete Grocery List with Confirmation (P1)

**Story Goal**: Users can delete a grocery list with confirmation dialog, preventing accidental data loss

**Why P1**: Core functionality, delivers immediate value, prevents accidental deletions

**Independent Test Criteria**:
1. Navigate to a grocery list show page
2. Click delete button → confirmation modal appears
3. Click confirm → list is soft deleted, redirect to index
4. Verify list no longer appears in index
5. Access deleted list URL → receive 404 error

### Tests (Test-First Development)

- [ ] T010 [P] [US1] Create Pest feature test file in tests/Feature/GroceryList/DeleteGroceryListTest.php
- [ ] T011 [P] [US1] Create Pest policy test file in tests/Unit/Policies/GroceryListPolicyTest.php
- [ ] T012 [US1] Write all Pest tests (should fail - methods don't exist yet)
- [ ] T013 [US1] Verify tests fail (`php artisan test --filter=DeleteGroceryList`)

### Authorization

- [ ] T014 [US1] Add delete() method to GroceryListPolicy in app/Policies/GroceryListPolicy.php
- [ ] T015 [US1] Run policy tests to verify authorization (`php artisan test --filter=GroceryListPolicy`)

### Livewire Component

- [ ] T016 [US1] Add $showDeleteConfirm property to Show component in app/Livewire/GroceryLists/Show.php
- [ ] T017 [US1] Add confirmDelete() method to Show component in app/Livewire/GroceryLists/Show.php
- [ ] T018 [US1] Add delete() method to Show component in app/Livewire/GroceryLists/Show.php

### UI Components

- [ ] T019 [US1] Add delete button to show.blade.php in resources/views/livewire/grocery-lists/show.blade.php
- [ ] T020 [US1] Add confirmation modal to show.blade.php in resources/views/livewire/grocery-lists/show.blade.php

### Integration & Testing

- [ ] T021 [US1] Run Pest tests to verify implementation (`php artisan test --filter=DeleteGroceryList`)
- [ ] T022 [US1] Manual testing: Delete button appears and works
- [ ] T023 [US1] Manual testing: Confirmation modal displays correctly
- [ ] T024 [US1] Manual testing: Confirm deletes list and redirects
- [ ] T025 [US1] Manual testing: Deleted list returns 404

**Parallel Opportunities**:
- T010 + T011 can run in parallel (different test files)
- T019 + T020 can be done together (same file)

**Completion Criteria**:
- ✅ All Pest tests pass
- ✅ Delete button appears on show page
- ✅ Confirmation modal displays when delete clicked
- ✅ List is soft deleted on confirmation
- ✅ User redirected to grocery-lists.index
- ✅ Deleted list returns 404
- ✅ Only owners can delete (authorization works)

**MVP Milestone**: 🎉 Feature is complete and deployable after this phase!

---

## Phase 4: User Story 2 - Cancel Deletion to Avoid Mistakes (P2)

**Story Goal**: Users can cancel deletion from confirmation dialog without any data changes

**Why P2**: Enhances user confidence, provides escape path for accidental clicks

**Depends On**: US1 (uses same modal component)

**Independent Test Criteria**:
1. Navigate to a grocery list show page
2. Click delete button → confirmation modal appears
3. Click cancel → modal closes
4. Verify list still exists and is accessible
5. Verify no data changes occurred

### Tests

- [ ] T026 [P] [US2] Add cancel tests to DeleteGroceryListTest.php in tests/Feature/GroceryList/DeleteGroceryListTest.php
- [ ] T027 [US2] Verify cancel tests fail (`php artisan test --filter=cancel`)

### Livewire Component

- [ ] T028 [US2] Add cancelDelete() method to Show component in app/Livewire/GroceryLists/Show.php

### UI Components

- [ ] T029 [US2] Add cancel button to confirmation modal in resources/views/livewire/grocery-lists/show.blade.php

### Integration & Testing

- [ ] T030 [US2] Run Pest tests to verify cancel functionality (`php artisan test --filter=cancel`)
- [ ] T031 [US2] Manual testing: Cancel button closes modal
- [ ] T032 [US2] Manual testing: Cancel preserves all data

**Parallel Opportunities**: T026 can be written while T028-T029 are being implemented

**Completion Criteria**:
- ✅ Cancel tests pass
- ✅ Cancel button appears in modal
- ✅ Clicking cancel closes modal
- ✅ No data changes when cancel clicked
- ✅ User remains on show page

---

## Phase 5: E2E Testing (Cross-Story)

**Goal**: Validate complete user journeys with Playwright

**Covers**: Both US1 and US2

- [ ] T033 [P] Create Playwright test file in e2e/grocery-lists/delete-grocery-list.spec.ts
- [ ] T034 Write E2E test for complete delete flow (US1)
- [ ] T035 Write E2E test for cancel flow (US2)
- [ ] T036 Run Playwright tests (`npx playwright test e2e/grocery-lists/delete-grocery-list.spec.ts`)
- [ ] T037 Fix any E2E test failures

**Parallel Opportunities**: T034 + T035 can be written in parallel (different test cases)

**Completion Criteria**:
- ✅ E2E tests pass in all browsers
- ✅ Complete delete flow validated end-to-end
- ✅ Cancel flow validated end-to-end

---

## Phase 6: Polish & Quality

**Goal**: Ensure code quality and consistency

- [ ] T038 Run Laravel Pint code formatter (`vendor/bin/pint`)
- [ ] T039 Run all Pest tests (`php artisan test`)
- [ ] T040 Run all Playwright tests (`npx playwright test`)
- [ ] T041 Verify no console errors in browser
- [ ] T042 Review code for constitutional compliance

**Completion Criteria**:
- ✅ All tests pass
- ✅ Code formatted per project standards
- ✅ No console errors
- ✅ Constitutional principles satisfied

---

## Parallel Execution Examples

### Phase 2: Foundational (Maximum Parallelization)

**Parallel Group 1** (Migrations):
```bash
# Terminal 1
Create migration: grocery_lists soft deletes (T005)

# Terminal 2
Create migration: grocery_items soft deletes (T006)
```

**Sequential** (Must wait for migrations):
```bash
Run migrations (T007)
```

**Parallel Group 2** (Models):
```bash
# Terminal 1
Update GroceryList model (T008)

# Terminal 2
Update GroceryItem model (T009)
```

### Phase 3: US1 (Test Files in Parallel)

**Parallel Group** (Test files):
```bash
# Terminal 1
Create feature test file (T010)

# Terminal 2
Create policy test file (T011)
```

**Sequential** (Write and run tests):
```bash
Write tests (T012)
Run tests - should fail (T013)
Implement authorization (T014-T015)
Implement component (T016-T018)
Implement UI (T019-T020)
Verify tests pass (T021)
```

### Phase 5: E2E (Test Cases in Parallel)

**Parallel Group** (Different test cases):
```bash
# Terminal 1
Write delete flow E2E test (T034)

# Terminal 2
Write cancel flow E2E test (T035)
```

---

## Task Summary by User Story

### User Story 1 (P1) - Delete with Confirmation
**Tasks**: T010-T025 (16 tasks)
**Test Tasks**: 4 (T010-T013)
**Implementation Tasks**: 12 (T014-T025)
**Estimated Time**: 2-3 hours

### User Story 2 (P2) - Cancel Deletion
**Tasks**: T026-T032 (7 tasks)
**Test Tasks**: 2 (T026-T027)
**Implementation Tasks**: 5 (T028-T032)
**Estimated Time**: 30-45 minutes

### Cross-Story Tasks
**Foundational**: T005-T009 (5 tasks) - Required for all stories
**E2E Testing**: T033-T037 (5 tasks) - Validates both stories
**Polish**: T038-T042 (5 tasks) - Quality gates

---

## Verification Checklist

Before considering feature complete:

### User Story 1 (P1) - MVP
- [ ] Delete button appears on grocery list show page
- [ ] Clicking delete opens confirmation modal
- [ ] Modal clearly states deletion is permanent
- [ ] Confirming deletion soft deletes the list
- [ ] Related grocery items are cascade deleted
- [ ] User redirected to grocery-lists.index after deletion
- [ ] Success flash message appears
- [ ] Deleted list returns 404 when accessed
- [ ] Only list owner can delete (authorization)
- [ ] All US1 Pest tests pass
- [ ] Manual testing confirms expected behavior

### User Story 2 (P2) - Enhancement
- [ ] Cancel button appears in confirmation modal
- [ ] Clicking cancel closes modal
- [ ] No data changes occur when cancelled
- [ ] User remains on show page
- [ ] All US2 Pest tests pass
- [ ] Manual testing confirms expected behavior

### Overall Quality
- [ ] All Playwright E2E tests pass
- [ ] Code formatted with Laravel Pint
- [ ] No console errors in browser
- [ ] Constitutional principles satisfied
- [ ] Documentation updated (if needed)

---

## Next Steps After Completion

1. **Commit Changes**: `git add .` and create commit with feature description
2. **Push Branch**: `git push origin 005-delete-grocery-list`
3. **Create Pull Request**: Use GitHub CLI or web interface
4. **Code Review**: Address reviewer feedback
5. **Merge to Main**: After approval

---

## References

- **Specification**: [spec.md](./spec.md)
- **Implementation Plan**: [plan.md](./plan.md)
- **Research Decisions**: [research.md](./research.md)
- **Data Model**: [data-model.md](./data-model.md)
- **Component Contract**: [contracts/livewire-component.md](./contracts/livewire-component.md)
- **Quickstart Guide**: [quickstart.md](./quickstart.md)

---

**Ready to implement?** Start with Phase 2 (Foundational Tasks), then proceed to Phase 3 (US1 - MVP)!
