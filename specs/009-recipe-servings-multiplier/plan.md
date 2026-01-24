# Implementation Plan: Recipe Servings Multiplier

**Branch**: `009-recipe-servings-multiplier` | **Date**: 2025-12-14 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/009-recipe-servings-multiplier/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Add a dynamic servings multiplier control (0.25x to 10x range) to the recipe detail page that allows users to scale ingredient quantities in real-time. This is implemented as a Livewire component enhancement that performs client-side calculations using Alpine.js for reactive updates, maintaining the existing recipe data structure without database modifications.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12)
**Primary Dependencies**: Livewire 3, Livewire Flux (UI components), Alpine.js (included with Livewire), Tailwind CSS 4.x
**Storage**: N/A (client-side state only, no database changes)
**Testing**: Pest 4 (PHP unit/feature tests, browser tests), Playwright (E2E tests)
**Target Platform**: Web application (modern browsers: Chrome, Firefox, Safari, Edge), mobile-responsive
**Project Type**: Web application (Laravel monolith with Livewire frontend)
**Performance Goals**: <200ms ingredient quantity recalculation when multiplier changes, 60 fps UI updates
**Constraints**: Client-side calculation only (no server roundtrips for scaling), maintain existing Recipe/RecipeIngredient data structure, session-based state (no persistence)
**Scale/Scope**: Single recipe detail page enhancement, affects Recipe and RecipeIngredient display components, ~20-30 ingredients per recipe maximum

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### I. Livewire-First Architecture ✅ PASS

- **Requirement**: All features MUST be built using Livewire components as the primary architectural pattern
- **Compliance**: This feature enhances an existing Livewire recipe detail component. The multiplier functionality will be integrated into the Livewire component using Alpine.js for reactive client-side state (as per Livewire best practices for non-persistent UI state)
- **Justification**: No violations. This is a Livewire component enhancement, not a traditional controller approach

### II. Component-Driven Development ✅ PASS

- **Requirement**: All UI elements MUST use Flux components before creating custom implementations
- **Compliance**: The multiplier control will use Flux components (`flux:input` for numeric input, `flux:button` for preset buttons) following existing component patterns
- **Justification**: No violations. Using existing Flux components for all UI elements

### III. Test-First Development ✅ PASS

- **Requirement**: All features MUST be developed following test-first principles using Pest for PHP and Playwright for E2E
- **Compliance**:
  - Pest feature tests will verify multiplier calculation logic
  - Pest browser tests (Pest 4) will validate real-time UI updates and user interactions
  - Playwright E2E tests will verify the complete user journey for scaling recipes
- **Justification**: No violations. Full test coverage planned with TDD approach

### IV. Full-Stack Integration Testing ✅ PASS

- **Requirement**: Critical user journeys MUST be validated with end-to-end integration tests
- **Compliance**: E2E tests will cover the complete recipe scaling workflow including component rendering, Alpine.js reactivity, multiplier validation, and ingredient quantity calculations
- **Justification**: No violations. Integration tests planned for critical user journeys

### V. Developer Experience & Observability ✅ PASS

- **Requirement**: Development environment MUST provide consistent tooling, fast feedback loops, and comprehensive observability
- **Compliance**:
  - DDEV environment used for development
  - Laravel Pint will format code before commit
  - `composer dev` runs all required services
  - Browser console will log any calculation errors for debugging
- **Justification**: No violations. Following established development workflow

### Technology Stack Compliance ✅ PASS

- **Backend**: Laravel 12, PHP 8.3 ✅
- **Frontend**: Livewire 3, Alpine.js (included), Flux components ✅
- **Styling**: Tailwind CSS 4.x ✅
- **Testing**: Pest 4 + Playwright ✅
- **Development**: DDEV ✅

### **GATE STATUS: ✅ PASS - No constitutional violations. Proceed to Phase 0.**

## Project Structure

### Documentation (this feature)

```text
specs/[###-feature]/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (/speckit.plan command)
├── data-model.md        # Phase 1 output (/speckit.plan command)
├── quickstart.md        # Phase 1 output (/speckit.plan command)
├── contracts/           # Phase 1 output (/speckit.plan command)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```text
# Laravel Web Application Structure
app/
├── Http/
│   └── Controllers/
│       └── RecipeController.php      # Existing controller (may need routes)
├── Livewire/
│   └── Recipes/
│       └── Show.php                  # Existing component to enhance
└── Models/
    ├── Recipe.php                    # Existing model (servings field)
    └── RecipeIngredient.php          # Existing model (quantity, unit fields)

resources/
├── views/
│   └── livewire/
│       └── recipes/
│           └── show.blade.php        # MODIFY: Add multiplier UI and Alpine.js logic
└── js/
    └── app.js                        # Alpine.js included via Livewire

tests/
├── Feature/
│   ├── Livewire/
│   │   └── RecipeShowTest.php        # MODIFY: Add multiplier tests
│   └── Recipes/
│       └── RecipeAuthorizationTest.php # Existing (no changes)
├── Browser/
│   └── RecipeServingsMultiplierTest.php # NEW: Pest 4 browser tests
└── Unit/
    └── Models/
        └── RecipeIngredientTest.php  # MODIFY: Add calculation tests

e2e/
└── recipe-servings-multiplier.spec.ts # NEW: Playwright E2E tests

routes/
└── web.php                           # VERIFY: Recipe show route exists
```

**Structure Decision**: This is a web application (Laravel monolith) enhancement. The feature modifies the existing `Livewire\Recipes\Show` component and its corresponding Blade view. No new models or migrations are required since this is a client-side feature. The implementation uses Alpine.js (bundled with Livewire) for reactive state management without server roundtrips.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

No constitutional violations detected. All principles satisfied:
- Livewire-first architecture maintained
- Using Flux components for UI
- Test-first development approach planned
- Full integration testing coverage
- Standard development workflow followed

## Post-Design Constitution Re-Check

*GATE: Re-evaluated after Phase 1 design completion*

### Design Artifacts Review

**Completed Artifacts**:
- ✅ `research.md`: Technical decisions documented with rationale
- ✅ `data-model.md`: Read-only data model analysis (no schema changes)
- ✅ `contracts/README.md`: No API contracts needed (client-side only)
- ✅ `quickstart.md`: Developer implementation guide

### Constitutional Compliance Verification

**I. Livewire-First Architecture** ✅ CONFIRMED
- Implementation uses Alpine.js for client-side reactivity (recommended Livewire pattern)
- No traditional controllers or non-Livewire approaches introduced
- Follows existing `ingredientCheckboxes` pattern in codebase

**II. Component-Driven Development** ✅ CONFIRMED
- Uses `flux:button` and `flux:input` components (no custom components)
- Follows existing Blade component patterns
- Reuses Flux UI design system consistently

**III. Test-First Development** ✅ CONFIRMED
- Quickstart guide includes test examples (Pest feature + browser tests)
- Playwright E2E tests planned for user journeys
- Test files identified in project structure

**IV. Full-Stack Integration Testing** ✅ CONFIRMED
- Browser tests (Pest 4) planned for Alpine.js reactivity
- E2E tests planned for complete user workflow
- Integration with existing component authorization

**V. Developer Experience & Observability** ✅ CONFIRMED
- DDEV workflow maintained
- Laravel Pint formatting required
- Browser console logging for debugging client-side calculations

### Technology Stack Compliance ✅ CONFIRMED
- Alpine.js is bundled with Livewire 3 (no new dependencies)
- Tailwind CSS 4.x used for styling
- Pest 4 + Playwright for testing
- No technology stack changes

### **FINAL GATE STATUS: ✅ PASS - Design maintains full constitutional compliance**

All design decisions align with project constitution. No violations introduced during planning phase.

---

## Phase 2 Planning (Out of Scope for /speckit.plan)

**Note**: Task generation (`tasks.md`) is handled by the `/speckit.tasks` command, not `/speckit.plan`.

After this planning phase is complete, run:
```bash
/speckit.tasks
```

This will generate the implementation tasks based on the design artifacts created in this plan.

---

## Planning Summary

### Artifacts Generated

| Artifact | Status | Location |
|----------|--------|----------|
| Implementation Plan | ✅ Complete | `specs/009-recipe-servings-multiplier/plan.md` |
| Technical Research | ✅ Complete | `specs/009-recipe-servings-multiplier/research.md` |
| Data Model | ✅ Complete | `specs/009-recipe-servings-multiplier/data-model.md` |
| API Contracts | ✅ Complete | `specs/009-recipe-servings-multiplier/contracts/README.md` |
| Developer Quickstart | ✅ Complete | `specs/009-recipe-servings-multiplier/quickstart.md` |
| Agent Context | ✅ Updated | `CLAUDE.md` (Active Technologies section) |

### Key Decisions

1. **Alpine.js Pattern**: `Alpine.data()` component with JavaScript getters for reactivity
2. **Decimal Precision**: Native `toFixed(3)` matching existing `RecipeIngredient` model accessor
3. **UI Control**: Hybrid buttons + number input for accessibility and mobile UX
4. **Accessibility**: ARIA live regions with polite announcements
5. **No Database Changes**: Client-side only, no persistence, no migrations

### Implementation Approach

- **Type**: Client-side enhancement (no backend changes)
- **Files Modified**: 3 (app.js, show.blade.php, RecipeShowTest.php)
- **New Test Files**: 2 (browser test, E2E test)
- **Dependencies**: None (Alpine.js bundled with Livewire)
- **Estimated Complexity**: Low (presentation layer only)

### Next Steps

1. Run `/speckit.tasks` to generate implementation tasks
2. Follow Test-Driven Development workflow:
   - Write failing tests first
   - Implement features to pass tests
   - Run `vendor/bin/pint` for code formatting
3. Use `quickstart.md` as implementation reference
4. Verify accessibility with keyboard navigation and screen readers

### Branch Information

- **Feature Branch**: `009-recipe-servings-multiplier`
- **Base Branch**: `main` (for PR)
- **Spec Directory**: `/Users/jasonevans/projects/project-tabletop/specs/009-recipe-servings-multiplier`

---

**Planning Phase Complete** ✅

Generated by `/speckit.plan` command on 2025-12-14
