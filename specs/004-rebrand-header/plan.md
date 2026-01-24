# Implementation Plan: Rebrand Application Header

**Branch**: `004-rebrand-header` | **Date**: 2025-11-25 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/004-rebrand-header/spec.md`

## Summary

Replace "Laravel Starter Kit" branding with "Project Table Top" throughout the application header, page titles, and logo components. Remove search, repository, and documentation links from the header navigation. Create a new logo design that represents the "Project Table Top" brand with support for both light and dark themes across all screen sizes.

## Technical Context

**Language/Version**: PHP 8.3, Laravel 12
**Primary Dependencies**: Livewire 3, Livewire Flux (UI components), Tailwind CSS 4.x
**Storage**: N/A (view-only changes)
**Testing**: Pest (PHP feature tests), Playwright (E2E browser tests)
**Target Platform**: Web application (desktop and mobile responsive)
**Project Type**: Web application with Livewire full-page components
**Performance Goals**: No performance impact (static template changes only)
**Constraints**: Must maintain responsive design, dark mode support, and Flux component compatibility
**Scale/Scope**: 6 affected Blade template files, 1 new SVG logo asset

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### Principle I: Livewire-First Architecture
✅ **PASS** - This feature only modifies Blade view templates and components. No controllers or backend logic required. Uses existing Livewire Flux components (navbar, header, sidebar).

### Principle II: Component-Driven Development
✅ **PASS** - Changes are made to existing Blade components:
- `resources/views/components/app-logo.blade.php` (brand text)
- `resources/views/components/app-logo-icon.blade.php` (logo SVG)
- `resources/views/components/layouts/app/header.blade.php` (header navigation)
- `resources/views/partials/head.blade.php` (page titles)

All use Flux components (flux:header, flux:navbar, flux:sidebar) as required by the constitution.

### Principle III: Test-First Development (NON-NEGOTIABLE)
✅ **PASS** - Will write tests first:
- **Pest Feature Tests**: Verify correct text rendering in header component, page title updates
- **Playwright E2E Tests**: Visual verification of header branding, logo display, removed links, responsive behavior, dark mode support

Tests will be written BEFORE implementation changes.

### Principle IV: Full-Stack Integration Testing
✅ **PASS** - E2E tests will validate:
- Header rendering on all authenticated pages (dashboard, recipes, meal plans, grocery lists)
- Header rendering on unauthenticated pages (if applicable)
- Mobile sidebar branding consistency
- Dark mode logo visibility
- Responsive logo sizing across breakpoints

### Principle V: Developer Experience & Observability
✅ **PASS** - Changes are view-only and will be immediately visible in DDEV environment:
- `composer dev` runs Vite for HMR (hot module reload)
- No queue jobs or background processing involved
- Laravel Pint will be run before committing
- Changes can be visually verified at https://project-tabletop.ddev.site

**Constitution Check Result**: ✅ ALL GATES PASSED - Proceed to Phase 0

## Project Structure

### Documentation (this feature)

```text
specs/004-rebrand-header/
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output - logo design research
├── data-model.md        # N/A - no data entities for this feature
├── quickstart.md        # Phase 1 output - developer guide for branding changes
└── contracts/           # N/A - no API contracts for this feature
```

### Source Code (repository root)

```text
resources/
├── views/
│   ├── components/
│   │   ├── app-logo.blade.php              # UPDATE: Change "Laravel Starter Kit" to "Project Table Top"
│   │   ├── app-logo-icon.blade.php         # UPDATE: Replace SVG with new logo
│   │   └── layouts/
│   │       └── app/
│   │           ├── header.blade.php        # UPDATE: Remove search, repo, docs links
│   │           └── sidebar.blade.php       # (referenced by header, may need mobile logo update)
│   └── partials/
│       └── head.blade.php                  # UPDATE: Ensure title uses config('app.name')
│
├── images/                                  # NEW: Logo asset directory
│   └── logo-project-tabletop.svg           # NEW: Custom logo SVG
│
tests/
├── Feature/
│   └── BrandingTest.php                    # NEW: Pest tests for header branding
│
e2e/
└── header-branding.spec.ts                 # NEW: Playwright E2E tests for visual verification
```

**Structure Decision**: This is a web application using Laravel + Livewire. All changes are in the `resources/views/` directory for Blade templates and `tests/` for test files. No backend logic or database changes required.

## Complexity Tracking

> **No violations** - This feature fully complies with all constitutional principles.
