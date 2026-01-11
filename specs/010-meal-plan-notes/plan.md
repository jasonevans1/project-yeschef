# Implementation Plan: Meal Plan Notes

**Branch**: `010-meal-plan-notes` | **Date**: 2026-01-11 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/010-meal-plan-notes/spec.md`

## Summary

Add free-form notes to meal plan slots alongside recipes. Users can add, view, edit, and delete notes with a title and optional details. Notes are displayed in the meal plan calendar view with distinct visual styling from recipes. Notes are explicitly excluded from grocery list generation since they have no associated ingredients.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 12
**Primary Dependencies**: Livewire 3, Livewire Flux 2.10, Tailwind CSS 4.x
**Storage**: MariaDB (production via DDEV), SQLite (development/testing)
**Testing**: Pest 4 (PHP feature/unit tests), Playwright (E2E tests)
**Target Platform**: Web application (responsive, desktop and mobile)
**Project Type**: Web application (Laravel Livewire full-stack)
**Performance Goals**: Standard web app expectations - page loads under 2 seconds, interactions feel instant
**Constraints**: Notes excluded from grocery list generation, authorization inherits from MealPlan
**Scale/Scope**: Single user meal planning - typical meal plans are 1-28 days with 1-4 meal types per day

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. Livewire-First Architecture | PASS | Feature extends existing `MealPlans\Show` Livewire component |
| II. Component-Driven Development | PASS | Uses existing Flux UI components (modal, button, input, textarea) |
| III. Test-First Development | PASS | Pest feature tests + Playwright E2E tests planned |
| IV. Full-Stack Integration Testing | PASS | E2E tests for add/edit/delete note flows |
| V. Developer Experience & Observability | PASS | Uses DDEV, standard Laravel patterns |

**All gates PASS. No violations to justify.**

## Project Structure

### Documentation (this feature)

```text
specs/010-meal-plan-notes/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/           # Phase 1 output
└── tasks.md             # Phase 2 output (/speckit.tasks command)
```

### Source Code (repository root)

```text
app/
├── Livewire/
│   └── MealPlans/
│       └── Show.php              # Extended with note CRUD methods
├── Models/
│   └── MealPlanNote.php          # NEW: Note entity
├── Policies/
│   └── MealPlanNotePolicy.php    # NEW: Authorization (inherits from MealPlan)
database/
├── migrations/
│   └── xxxx_create_meal_plan_notes_table.php  # NEW
├── factories/
│   └── MealPlanNoteFactory.php   # NEW
resources/views/
└── livewire/meal-plans/
    └── show.blade.php            # Extended with note display/forms
tests/
├── Feature/
│   └── MealPlans/
│       └── MealPlanNotesTest.php # NEW: Note CRUD tests
e2e/
└── meal-plans-notes.spec.ts      # NEW: E2E tests
```

**Structure Decision**: Extends existing meal plan structure. New `MealPlanNote` model follows same pattern as `MealAssignment`. Note functionality integrated into existing `MealPlans\Show` component to maintain single-page experience.

## Complexity Tracking

> No Constitution violations to justify. Implementation uses established patterns.
