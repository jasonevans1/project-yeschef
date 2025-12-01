# Implementation Plan: Import Recipe from URL

**Branch**: `006-import-recipe` | **Date**: 2025-11-30 | **Spec**: [spec.md](spec.md)
**Input**: Feature specification from `/specs/006-import-recipe/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

Implement a recipe import feature that allows authenticated users to import recipes from external URLs containing schema.org Recipe microdata. The system will fetch, parse, validate, preview, and import recipe data with user confirmation. Primary approach includes HTTP client for fetching, HTML parsing for microdata extraction, validation layer, Livewire preview component, and secure content sanitization.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 12
**Primary Dependencies**: Livewire 3, Livewire Volt, Livewire Flux (UI), Laravel Fortify (auth), Guzzle HTTP client (NEEDS CLARIFICATION - verify if already present or need alternative), HTML parser for microdata extraction (NEEDS CLARIFICATION - symfony/dom-crawler, masterminds/html5, or other)
**Storage**: MariaDB (production via DDEV), SQLite (dev/test) - Recipe model with source_url field
**Testing**: Pest (PHP unit/feature tests), Playwright (E2E tests)
**Target Platform**: Web application (DDEV: nginx-fpm, PHP 8.3, MariaDB 10.11)
**Project Type**: Web application (Livewire-first full-page components)
**Performance Goals**: URL fetch + parse + preview < 30 seconds (per SC-001), error feedback < 5 seconds (per SC-003)
**Constraints**: 30-second timeout for external HTTP requests (standard web limits), XSS/injection prevention via content sanitization (SC-006), authenticated users only
**Scale/Scope**: Single import workflow (2 pages: input URL + preview/confirm), 95% accuracy for valid schema.org markup (SC-002)

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### I. Livewire-First Architecture ✅

**Status**: PASS

- Import URL input page: Full-page Livewire component
- Recipe preview/confirmation page: Full-page Livewire component
- Routes will use Livewire component classes directly as handlers
- No traditional controllers for UI rendering

### II. Component-Driven Development ✅

**Status**: PASS

- UI will use Flux components (input, button, card, etc.) from the Livewire Flux library
- Livewire components will be organized in `app/Livewire/` with matching views in `resources/views/livewire/`
- Custom components only if Flux does not provide required functionality

**Action**: Verify Flux component availability for recipe preview layout during Phase 1

### III. Test-First Development ✅

**Status**: PASS (commitment)

- Pest tests will be written in `tests/Feature/` before implementation
- Test coverage required for:
  - URL validation and fetching
  - Schema.org microdata parsing
  - Recipe preview display
  - Import confirmation and database persistence
  - Error handling (invalid URLs, no microdata, network failures)
- E2E Playwright tests for complete import flow

**Action**: Phase 1 must include test scenarios in implementation plan

### IV. Full-Stack Integration Testing ✅

**Status**: PASS (commitment)

- E2E tests in `e2e/` will validate:
  - Complete import workflow (URL input → preview → confirmation)
  - Livewire component reactivity and state management
  - Form validation and error display
  - Database state changes (recipe creation)
  - Authentication requirement enforcement

### V. Developer Experience & Observability ✅

**Status**: PASS

- DDEV environment already configured
- Development workflow using `composer dev` for all services
- Laravel Pint will format code before commits
- Error messages will be clear and actionable per FR-014, FR-015

### Technology Stack Standards ✅

**Status**: PASS

- Backend: Laravel 12, PHP 8.3 ✅
- Frontend: Livewire 3, Volt, Flux ✅
- Authentication: Laravel Fortify (users must be authenticated) ✅
- Styling: Tailwind CSS 4.x ✅
- Testing: Pest + Playwright ✅
- Database: SQLite (test), MariaDB (DDEV) ✅
- Build: Vite ✅

**No technology stack changes required.**

### Development Workflow Requirements ✅

**Status**: PASS (commitment)

Workflow for this feature:
1. Create Livewire components via `php artisan make:livewire`
2. Write failing Pest tests first
3. Add routes pointing to component classes
4. Implement using Flux components
5. Run `composer test` - all must pass
6. Run `vendor/bin/pint` for formatting
7. Add E2E tests for critical flow
8. Commit

### Gates Summary

**All constitutional gates PASS** - No violations to justify in Complexity Tracking table.

Feature aligns with all five core principles and follows established technology stack and development workflows.

---

### Post-Design Re-Evaluation (Phase 1 Complete)

**Date**: 2025-11-30
**Design artifacts reviewed**: research.md, data-model.md, contracts/livewire-components.md, quickstart.md

#### Re-evaluation Results

✅ **I. Livewire-First Architecture** - CONFIRMED PASS
- Design includes 2 full-page Livewire components (Import, ImportPreview)
- Routes use component classes directly: `Route::get('/recipes/import', Import::class)`
- No traditional controllers in design
- Service layer separated from UI (RecipeImportService, RecipeFetcher, MicrodataParser, RecipeSanitizer)

✅ **II. Component-Driven Development** - CONFIRMED PASS
- Contracts specify Flux components: `<flux:input>`, `<flux:button>`, `<flux:card>`
- Component organization follows conventions: `app/Livewire/Recipe/`, `resources/views/livewire/recipe/`
- No custom components required beyond Livewire pages

✅ **III. Test-First Development** - CONFIRMED PASS
- Quickstart.md mandates test-first workflow for all components
- Test files specified: RecipeFetcherTest, MicrodataParserTest, RecipeSanitizerTest, RecipeImportServiceTest, ImportRecipeTest
- Testing strategy documented: Unit → Feature → E2E progression

✅ **IV. Full-Stack Integration Testing** - CONFIRMED PASS
- E2E test file specified: `e2e/recipe-import.spec.ts`
- Test scenarios cover complete user journey
- Database state changes validated in feature tests

✅ **V. Developer Experience & Observability** - CONFIRMED PASS
- Quickstart.md includes comprehensive debugging tips
- Error handling matrix defined in contracts
- Clear, actionable error messages specified (FR-014, FR-015)

✅ **Technology Stack Standards** - CONFIRMED PASS
- Research.md confirms: Laravel HTTP facade (no new dependencies), native PHP for JSON-LD parsing
- Optional dependency: brick/structured-data (only if Microdata/RDFa needed - deferred to Phase 2+)
- All existing stack components used correctly

✅ **Development Workflow Requirements** - CONFIRMED PASS
- Quickstart.md follows exact workflow: make:livewire → write tests → implement → run tests → pint → commit
- Quality gates defined: all tests pass, pint passes, no console errors

**Final Verdict**: All constitutional principles satisfied post-design. No violations. Ready to proceed to implementation.

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
├── Livewire/
│   └── Recipe/
│       ├── Import.php              # URL input page component
│       └── ImportPreview.php       # Preview/confirmation page component
├── Models/
│   └── Recipe.php                  # Existing or new Recipe model (verify in Phase 0)
└── Services/
    └── RecipeImporter/
        ├── RecipeImportService.php # Orchestrates fetch, parse, validate
        ├── RecipeFetcher.php       # HTTP client wrapper for URL fetching
        ├── MicrodataParser.php     # Extracts schema.org Recipe data
        └── RecipeSanitizer.php     # XSS/injection prevention

resources/views/livewire/recipe/
├── import.blade.php                # URL input form view
└── import-preview.blade.php        # Preview/confirmation view

routes/
└── web.php                         # Add routes for import pages

database/migrations/
└── YYYY_MM_DD_HHMMSS_add_source_url_to_recipes_table.php  # If needed

tests/
├── Feature/
│   └── Recipe/
│       ├── ImportRecipeTest.php    # Feature tests for import workflow
│       └── RecipeImportServiceTest.php  # Service layer tests
└── Unit/
    └── RecipeImporter/
        ├── MicrodataParserTest.php # Unit tests for parser
        └── RecipeSanitizerTest.php # Unit tests for sanitization

e2e/
└── recipe-import.spec.ts           # E2E test for complete flow
```

**Structure Decision**: Laravel web application structure following existing project conventions. Livewire components in `app/Livewire/Recipe/` namespace for feature grouping. Service layer in `app/Services/RecipeImporter/` for separation of concerns (fetch, parse, sanitize). Tests mirror source structure in `tests/Feature/` and `tests/Unit/`. E2E tests in `e2e/` directory.

## Complexity Tracking

> **Fill ONLY if Constitution Check has violations that must be justified**

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| [e.g., 4th project] | [current need] | [why 3 projects insufficient] |
| [e.g., Repository pattern] | [specific problem] | [why direct DB access insufficient] |
