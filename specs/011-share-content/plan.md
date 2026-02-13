# Implementation Plan: Content Sharing

**Branch**: `011-share-content` | **Date**: 2026-02-10 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/011-share-content/spec.md`

## Summary

Add user-to-user content sharing for recipes, meal plans, and grocery lists. Users share by email address with read-only or read-write permissions. A "share all" mode auto-includes future items. Unregistered recipients receive an invitation email. Shared content appears immediately in the recipient's dashboard with owner labels. A sharing management screen in Settings allows owners to view, modify, and revoke shares.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 12
**Primary Dependencies**: Livewire 3, Livewire Flux (UI components), Laravel Fortify (auth)
**Storage**: SQLite (dev/test), MariaDB (production via DDEV)
**Testing**: Pest (PHP feature tests), Playwright (E2E)
**Target Platform**: Web application (DDEV local, server deployment)
**Project Type**: Web (Laravel monolith with Livewire frontend)
**Performance Goals**: Share actions complete in under 1 second; list queries with shared content add minimal overhead
**Constraints**: Polymorphic queries must work identically on SQLite and MariaDB; share-all queries must not cause N+1 problems
**Scale/Scope**: Small user base (household/family sharing); typically 1-5 share recipients per user

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Livewire-First Architecture | PASS | All sharing UI built as Livewire components; share management as Settings page; share dialogs as Livewire modals |
| II. Component-Driven Development | PASS | Uses Flux components (modal, input, select, button, badge); follows existing `app/Livewire/Settings/` pattern for management screen |
| III. Test-First Development | PASS | Pest feature tests for ContentShare model, policies, and Livewire components; E2E tests for critical sharing flows |
| IV. Full-Stack Integration Testing | PASS | Livewire component tests validate share creation, permission enforcement, revocation; policy tests for all three content types |
| V. Developer Experience & Observability | PASS | Standard Laravel patterns; no new dev tooling required |
| Technology Stack Standards | PASS | No new dependencies; uses Laravel Mail (built-in), Eloquent polymorphic relationships, existing Flux components |

**Pre-design gate**: PASS - No violations.

## Project Structure

### Documentation (this feature)

```text
specs/011-share-content/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
│   └── sharing-actions.md
└── tasks.md             # Phase 2 output (created by /speckit.tasks)
```

### Source Code (repository root)

```text
app/
├── Enums/
│   ├── SharePermission.php          # NEW: ReadOnly, ReadWrite
│   └── ShareableType.php            # NEW: Recipe, MealPlan, GroceryList
├── Models/
│   └── ContentShare.php             # NEW: Polymorphic sharing model
├── Livewire/
│   └── Settings/
│       └── Sharing.php              # NEW: Share management settings page
├── Mail/
│   └── ShareInvitation.php          # NEW: Invitation mailable for non-users
├── Policies/
│   ├── RecipePolicy.php             # MODIFIED: Add shared access checks
│   ├── MealPlanPolicy.php           # MODIFIED: Add shared access checks
│   └── GroceryListPolicy.php        # MODIFIED: Add shared access checks

database/
├── migrations/
│   └── 2026_02_10_*_create_content_shares_table.php  # NEW
├── factories/
│   └── ContentShareFactory.php      # NEW

resources/views/
├── livewire/
│   └── settings/
│       └── sharing.blade.php        # NEW: Management UI
├── components/
│   └── share-modal.blade.php        # NEW: Reusable share dialog
├── mail/
│   └── share-invitation.blade.php   # NEW: Invitation email template

routes/
└── web.php                          # MODIFIED: Add sharing settings route

tests/Feature/
└── Sharing/
    ├── ContentShareModelTest.php    # NEW
    ├── ShareRecipeTest.php          # NEW
    ├── ShareMealPlanTest.php        # NEW
    ├── ShareGroceryListTest.php     # NEW
    ├── ShareAllTest.php             # NEW
    ├── SharePermissionsTest.php     # NEW
    ├── ShareManagementTest.php      # NEW
    ├── ShareInvitationTest.php      # NEW
    └── SharedContentDisplayTest.php # NEW
```

**Structure Decision**: Follows existing Laravel monolith structure. New files placed alongside existing siblings (enums in `app/Enums/`, model in `app/Models/`, etc.). Settings page follows the established `app/Livewire/Settings/` pattern with navlist entry. Share dialog is a reusable Blade component invoked from Recipe/MealPlan/GroceryList show pages.

## Complexity Tracking

No constitutional violations. No complexity justifications needed.
