# Implementation Plan: Delete Grocery List

**Branch**: `005-delete-grocery-list` | **Date**: 2025-11-30 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/005-delete-grocery-list/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Add delete functionality with confirmation dialog to the grocery list page. Users can permanently delete a grocery list after confirming their intent, preventing accidental data loss. The feature includes:

- Delete button on grocery list show page (app/Livewire/GroceryLists/Show.php)
- Confirmation modal dialog using Flux UI components
- Soft deletion with database cascade for related grocery items
- Authorization check to ensure only list owners can delete
- Redirect to grocery lists index after successful deletion
- Error handling for already-deleted lists

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12)
**Primary Dependencies**: Livewire 3, Livewire Flux (UI components), Laravel Fortify (authentication)
**Storage**: MariaDB (production via DDEV), SQLite (development/testing)
**Testing**: Pest (PHP unit/feature tests), Playwright (E2E browser tests)
**Target Platform**: Web application (Laravel server-side rendering with Livewire reactivity)
**Project Type**: Web application (Livewire-first architecture)
**Performance Goals**: <200ms response time for delete operation, instant UI feedback on delete button click
**Constraints**: Must use Livewire Flux components for confirmation dialog, must follow Livewire-first architecture, must have Pest tests before implementation
**Scale/Scope**: Single feature affecting 1 Livewire component (Show.php), 1 model (GroceryList), cascade to related GroceryItems

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Principle I: Livewire-First Architecture ✅

- **Compliance**: Feature will be implemented as methods within existing Livewire component (`app/Livewire/GroceryLists/Show.php`)
- **Rationale**: Delete functionality is part of the grocery list show page, following the pattern of existing features (regenerate, share) in the same component
- **No Violations**: No traditional controllers needed, all logic in Livewire component

### Principle II: Component-Driven Development ✅

- **Compliance**: Confirmation dialog will use Flux `<flux:modal>` component
- **Rationale**: Flux provides modal/dialog components that match existing UI patterns (see regenerate confirmation at line 38 of Show.php)
- **No Violations**: No custom modal components needed, using existing Flux UI library

### Principle III: Test-First Development ✅

- **Compliance**: Pest tests will be written before implementation
- **Test Plan**:
  - Feature test: `tests/Feature/GroceryList/DeleteGroceryListTest.php`
    - Test delete button appears for authorized users
    - Test delete requires confirmation
    - Test successful deletion removes list and items
    - Test unauthorized users cannot delete
    - Test already-deleted lists return 404
  - E2E test: `e2e/grocery-lists/delete-grocery-list.spec.ts`
    - Test complete user flow: click delete → confirm → redirect
    - Test cancel deletion preserves data
- **No Violations**: Tests written first, implementation second

### Principle IV: Full-Stack Integration Testing ✅

- **Compliance**: E2E tests will validate complete Livewire component lifecycle
- **Coverage**:
  - Modal dialog rendering and interaction
  - Wire:click bindings for delete/cancel actions
  - Database state changes (soft delete cascade)
  - Redirect after deletion
  - Authorization middleware
- **No Violations**: Both unit and integration tests planned

### Principle V: Developer Experience & Observability ✅

- **Compliance**: Feature developed using standard workflow
- **Workflow**:
  - Use DDEV environment for development
  - Run `composer dev` for concurrent services
  - Use Laravel Pint for code formatting
  - Monitor logs via `php artisan pail` during testing
- **No Violations**: Standard development practices followed

**GATE STATUS: ✅ PASSED** - All constitutional principles satisfied, no violations to justify

---

### Post-Design Re-Evaluation (After Phase 1)

**Date**: 2025-11-30

All design decisions documented in research.md, data-model.md, and contracts/ have been reviewed against constitutional principles:

#### Principle I: Livewire-First Architecture ✅
- **Confirmed**: Design uses Livewire component methods (`confirmDelete`, `cancelDelete`, `delete`)
- **Confirmed**: No controllers introduced, all logic in `Show.php` component
- **Pattern Compliance**: Follows existing pattern of `showRegenerateConfirm` and `showShareDialog` properties

#### Principle II: Component-Driven Development ✅
- **Confirmed**: Flux modal component used (`<flux:modal wire:model="showDeleteConfirm">`)
- **Confirmed**: Flux button components for delete/cancel actions
- **Confirmed**: No custom components created, reusing existing Flux UI library

#### Principle III: Test-First Development ✅
- **Test Coverage Planned**:
  - 3 feature tests in DeleteGroceryListTest.php (owner delete, unauthorized, cascade)
  - 2 policy tests in GroceryListPolicyTest.php (owner can/cannot)
  - 2 E2E tests in delete-grocery-list.spec.ts (complete flow, cancel flow)
- **Quickstart**: Test-first workflow documented in quickstart.md Phase 1
- **Compliance**: All tests written before implementation (per workflow)

#### Principle IV: Full-Stack Integration Testing ✅
- **E2E Coverage**: Playwright tests cover full user journey (UI → modal → delete → redirect)
- **Integration Points**: Tests validate Livewire reactivity, policy authorization, database cascade
- **Database Testing**: SQLite for fast test execution (per constitution)

#### Principle V: Developer Experience & Observability ✅
- **DDEV**: All development uses DDEV environment (documented in quickstart)
- **Code Quality**: Laravel Pint required before commit (Phase 9 of quickstart)
- **Monitoring**: Development workflow includes `php artisan pail` for log monitoring

**FINAL GATE STATUS: ✅ PASSED** - Design phase complete, all principles satisfied, ready for implementation

## Project Structure

### Documentation (this feature)

```text
specs/005-delete-grocery-list/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
app/
├── Livewire/
│   └── GroceryLists/
│       └── Show.php                    # Add delete methods here
├── Models/
│   ├── GroceryList.php                # Existing model, may add soft delete trait
│   └── GroceryItem.php                # Cascade delete relationship
└── Policies/
    └── GroceryListPolicy.php          # Add delete authorization method

resources/views/livewire/grocery-lists/
└── show.blade.php                     # Add delete button and confirmation modal

database/migrations/
└── YYYY_MM_DD_add_soft_deletes_to_grocery_lists.php  # Add deleted_at column

tests/
├── Feature/GroceryList/
│   └── DeleteGroceryListTest.php      # Pest feature tests
└── Unit/Policies/
    └── GroceryListPolicyTest.php      # Policy authorization tests

e2e/grocery-lists/
└── delete-grocery-list.spec.ts        # Playwright E2E tests

routes/
└── web.php                            # No changes needed (uses Livewire component routes)
```

**Structure Decision**: Laravel 12 web application structure with Livewire-first architecture. Delete functionality integrated into existing `Show.php` component following the pattern of existing features (regenerate confirmation, share dialog). Uses soft deletes for data safety and audit trail.

## Complexity Tracking

> **No violations - this table is empty**

All constitutional principles are satisfied without exceptions. The feature follows established patterns in the codebase (confirmation dialogs, Livewire component methods, Flux UI components).
