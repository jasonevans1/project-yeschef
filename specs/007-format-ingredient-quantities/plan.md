# Implementation Plan: Format Ingredient Quantities Display

**Branch**: `007-format-ingredient-quantities` | **Date**: 2025-12-06 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/007-format-ingredient-quantities/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Format ingredient quantities in recipe displays to remove unnecessary trailing zeros (e.g., "2.000 lb" → "2 lb") while preserving precision for fractional amounts. This is a view-only display enhancement that improves recipe readability without modifying data storage or input validation.

**Technical Approach**: Add a display accessor method to the RecipeIngredient model that formats the quantity field, removing trailing zeros while preserving necessary decimal precision. Update the recipe show view to use this formatted accessor instead of the raw quantity value.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12)
**Primary Dependencies**: Laravel Framework 12, Livewire 3, Livewire Flux (UI components)
**Storage**: RecipeIngredient model with decimal(8,3) quantity field, MeasurementUnit enum
**Testing**: Pest 4 (PHP unit/feature tests), Playwright (E2E browser tests)
**Target Platform**: Web application (DDEV local environment, nginx-fpm, MariaDB/SQLite)
**Project Type**: Web application (Laravel monolith with Livewire components)
**Performance Goals**: Negligible impact (simple number formatting in accessor)
**Constraints**: Must not modify database schema, must preserve null handling, must maintain backward compatibility
**Scale/Scope**: Single model accessor, one view file update, comprehensive test coverage

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Principle I: Livewire-First Architecture
- **Status**: ✅ **PASS** - No new components required
- **Rationale**: This feature modifies an existing model accessor and Blade view template. Recipe display already uses Livewire components (`app/Livewire/Recipes/Show.php` if exists, or inline in `resources/views/livewire/recipes/show.blade.php`). No controller or route changes needed.

### Principle II: Component-Driven Development
- **Status**: ✅ **PASS** - No new UI components required
- **Rationale**: Feature modifies how ingredient quantity is displayed within existing recipe view. Uses existing Blade templating within the Livewire component structure. No new Flux components needed.

### Principle III: Test-First Development
- **Status**: ✅ **PASS** - Test-first workflow will be followed
- **Commitment**:
  1. Write Pest unit tests for RecipeIngredient display accessor (test whole numbers, fractional, null, edge cases)
  2. Write Pest feature test for recipe view displaying formatted quantities
  3. Add Playwright E2E test verifying formatted quantities in browser
  4. Ensure all tests FAIL before implementation
  5. Implement accessor and view update
  6. Verify all tests PASS

### Principle IV: Full-Stack Integration Testing
- **Status**: ✅ **PASS** - E2E test will validate complete rendering
- **Coverage**: Playwright test will load recipe page, verify ingredient quantities display without trailing zeros for whole numbers, verify fractional quantities preserve precision

### Principle V: Developer Experience & Observability
- **Status**: ✅ **PASS** - Standard workflow applies
- **Process**: Standard DDEV environment, run `composer dev` for development, `vendor/bin/pint` before commit, `composer test` to validate changes

### Gate Evaluation (Initial)
**ALL GATES PASSED** ✅ - No constitutional violations. Proceeding to Phase 0 research.

### Gate Re-Evaluation (Post-Design)

**Re-checked after Phase 1 design artifacts complete** (2025-12-06)

#### Design Artifacts Review:
- ✅ `research.md` - All technical decisions documented
- ✅ `data-model.md` - Model accessor documented, no schema changes
- ✅ `contracts/model-accessor.md` - Accessor contract fully specified
- ✅ `quickstart.md` - Implementation guide follows TDD workflow

#### Constitutional Compliance:
- **Principle I (Livewire-First)**: ✅ CONFIRMED - No controller changes, existing Livewire view modified
- **Principle II (Component-Driven)**: ✅ CONFIRMED - No new UI components, uses existing Blade template
- **Principle III (Test-First)**: ✅ CONFIRMED - Quickstart enforces test-first workflow (unit → feature → E2E)
- **Principle IV (Integration Testing)**: ✅ CONFIRMED - Feature tests + E2E tests documented
- **Principle V (Developer Experience)**: ✅ CONFIRMED - Standard DDEV workflow, Pint formatting, fast implementation

**FINAL GATE STATUS**: ✅ **ALL GATES PASSED** - Design complete, ready for implementation.

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
app/
├── Models/
│   └── RecipeIngredient.php         # Add display_quantity accessor
├── Livewire/
│   └── Recipes/
│       └── Show.php                  # (if exists) May need view path update
└── Enums/
    └── MeasurementUnit.php           # Reference only (unchanged)

resources/views/livewire/recipes/
└── show.blade.php                    # Update quantity display to use accessor

tests/
├── Unit/
│   └── Models/
│       └── RecipeIngredientTest.php  # Test display_quantity accessor
├── Feature/
│   └── Livewire/
│       └── RecipeShowTest.php        # Test recipe view rendering
└── Browser/                          # (Pest 4 browser tests)
    └── RecipeDisplayTest.php         # E2E test for formatted quantities

e2e/
└── recipe-display.spec.ts            # Playwright E2E test (if preferred over Pest browser)
```

**Structure Decision**: Laravel monolith structure with Livewire components. This feature touches:
1. **Model Layer**: Add `display_quantity` accessor to `RecipeIngredient` model
2. **View Layer**: Update recipe show Blade template to use the accessor
3. **Test Layer**: Comprehensive tests at unit (model), feature (view rendering), and E2E (browser) levels

No new files created - only modifications to existing model and view, plus new test files.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

**No constitutional violations** - This section is not applicable for this feature.

---

## Planning Phase Summary

### Phase 0: Research ✅ Complete

**Artifacts Generated**:
- `research.md` - Technical decisions and best practices research

**Key Decisions**:
1. **Implementation Approach**: Eloquent accessor method (Laravel best practice)
2. **Formatting Logic**: `number_format()` + `rtrim()` to remove trailing zeros
3. **Null Handling**: Accessor returns null to preserve existing view behavior
4. **Testing Strategy**: Three-tier (Unit → Feature → E2E)
5. **Edge Cases**: Identified and documented (zero, very small, very large, null)

**Alternatives Evaluated**:
- Blade helper function (rejected - scatters logic)
- Custom Blade directive (rejected - overcomplicated)
- JavaScript formatting (rejected - server-side rendering)
- Fraction conversion like GroceryItem (out of scope)

### Phase 1: Design & Contracts ✅ Complete

**Artifacts Generated**:
- `data-model.md` - Entity documentation and accessor specification
- `contracts/model-accessor.md` - Detailed accessor contract with test cases
- `quickstart.md` - Developer implementation guide

**Key Deliverables**:
1. **Data Model**: RecipeIngredient accessor documented, no schema changes
2. **Contract Specification**: 10 test cases (TC-001 to TC-010) defined
3. **Performance Analysis**: Negligible impact (<0.15ms for typical recipe)
4. **Backward Compatibility**: Full compatibility confirmed
5. **Implementation Guide**: Test-first workflow with step-by-step instructions

**Agent Context Updated**:
- ✅ Added PHP 8.3 (Laravel 12) to active technologies
- ✅ Added Laravel Framework 12, Livewire 3, Livewire Flux to active technologies
- ✅ Added RecipeIngredient model context to CLAUDE.md

### Constitutional Compliance ✅ Verified

**Initial Gate Check**: All principles passed
**Post-Design Gate Check**: All principles confirmed

- ✅ Livewire-First Architecture (no controller changes)
- ✅ Component-Driven Development (no new UI components)
- ✅ Test-First Development (TDD workflow enforced)
- ✅ Full-Stack Integration Testing (E2E tests included)
- ✅ Developer Experience (standard DDEV workflow)

**No violations** - Feature aligns perfectly with project constitution.

### Ready for Next Phase

**Status**: ✅ Planning complete, ready for `/speckit.tasks`

**What's Next**:
1. Run `/speckit.tasks` to generate implementation tasks from this plan
2. Follow quickstart guide for test-first implementation
3. Estimated implementation time: 30-45 minutes

**Artifacts Location**: `/Users/jasonevans/projects/project-tabletop/specs/007-format-ingredient-quantities/`
- `plan.md` (this file)
- `research.md`
- `data-model.md`
- `contracts/model-accessor.md`
- `quickstart.md`

---

**Planning Completed**: 2025-12-06
**Branch**: `007-format-ingredient-quantities`
**Next Command**: `/speckit.tasks` to generate actionable implementation tasks
