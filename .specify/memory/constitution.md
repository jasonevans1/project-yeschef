<!--
SYNC IMPACT REPORT
===================
Version Change: INITIAL → 1.0.0
Change Type: Initial Constitution Ratification
Date: 2025-10-08

Principles Established:
- I. Livewire-First Architecture (Component-driven approach over traditional MVC)
- II. Component-Driven Development (Reusable Flux components, consistent patterns)
- III. Test-First Development (Pest + Playwright, non-negotiable)
- IV. Full-Stack Integration Testing (E2E flows for critical paths)
- V. Developer Experience & Observability (DDEV, logging, monitoring)

New Sections Added:
- Technology Stack Standards
- Development Workflow Requirements
- Governance (amendment procedures, compliance review)

Templates Requiring Updates:
✅ plan-template.md - Constitution Check section validated (generic placeholder compatible)
✅ spec-template.md - User story priorities align with testability principle
✅ tasks-template.md - Task organization supports component-driven, test-first approach
⚠ PENDING: Future commands/*.md files should reference this constitution generically

Follow-up TODOs:
- None - all placeholders filled with concrete values

Notes:
- This is the initial ratification for Project Tabletop
- Principles derived from CLAUDE.md project documentation
- Version 1.0.0 establishes baseline governance for Laravel 12 + Livewire 3 application
-->

# Project Tabletop Constitution

## Core Principles

### I. Livewire-First Architecture

**All features MUST be built using Livewire components as the primary architectural pattern.**

- Prefer Livewire components over traditional controllers for all user-facing features
- Full-page Livewire components with layouts rather than traditional Blade views
- Component classes MUST be used directly as route handlers (e.g., `Route::get('login', Login::class)`)
- Authentication UI MUST use custom Livewire components, not framework-provided views
- Traditional controllers are ONLY permitted for API endpoints or backend services that do not render UI

**Rationale**: Livewire provides a reactive, SPA-like experience without heavy JavaScript while maintaining the simplicity of server-side rendering. This architecture choice is foundational to the project and must be consistently applied to avoid mixing paradigms that increase complexity.

### II. Component-Driven Development

**All UI elements MUST use Flux components before creating custom implementations.**

- Use existing Flux components from the UI library as the first choice
- Only create custom Blade components when Flux does not provide the required functionality
- All Livewire components MUST follow the established organizational structure:
  - `app/Livewire/Auth/` for authentication components
  - `app/Livewire/Settings/` for user settings components
  - `app/Livewire/Actions/` for reusable actions
  - Corresponding Blade views in `resources/views/livewire/` matching class structure
- Component views MUST be placed in directories matching their Livewire class namespace

**Rationale**: Consistent component usage ensures UI consistency, reduces custom code maintenance, and leverages the design system. The organizational structure makes components discoverable and maintainable.

### III. Test-First Development (NON-NEGOTIABLE)

**All features MUST be developed following test-first principles using Pest for PHP and Playwright for E2E.**

Test-Driven Development (TDD) workflow:
1. Write Pest tests in `tests/Feature/` for new functionality
2. Ensure tests FAIL before implementation
3. Implement the feature to make tests pass
4. Refactor while keeping tests green
5. Run `composer test` before committing any changes

E2E testing requirements:
- Critical user flows MUST have Playwright tests in `e2e/`
- Tests MUST run against Chromium, Firefox, and WebKit (as configured)
- E2E tests validate the complete Livewire component lifecycle

**No feature implementation may begin without failing tests in place.**

**Rationale**: Test-first development catches bugs early, ensures testability by design, provides living documentation, and maintains confidence during refactoring. This is non-negotiable because retrofitting tests is significantly more difficult and error-prone.

### IV. Full-Stack Integration Testing

**Critical user journeys MUST be validated with end-to-end integration tests.**

Integration test focus areas:
- Authentication flows (login, registration, password reset, email verification, 2FA)
- Full-page Livewire component rendering and interactivity
- Form submissions and validation cycles
- Multi-step user workflows
- Database state changes and persistence
- Session management and authorization checks

Testing database configuration:
- Use in-memory SQLite for fast test execution (configured in `phpunit.xml`)
- Tests MUST be isolated and not depend on shared state
- Database migrations MUST run successfully in test environment

**Rationale**: Livewire's reactive nature and full-page component pattern require integration testing to validate the complete request-response cycle. Unit tests alone cannot catch issues in component lifecycle, wire:model bindings, or authorization middleware interactions.

### V. Developer Experience & Observability

**Development environment MUST provide consistent tooling, fast feedback loops, and comprehensive observability.**

Development environment requirements:
- DDEV MUST be used for local development to ensure environment consistency
- `composer dev` MUST run all services concurrently (Laravel server, queue worker, log monitoring, Vite)
- Development server accessible at https://project-tabletop.ddev.site

Code quality requirements:
- Laravel Pint MUST be run before committing (`vendor/bin/pint`)
- No code style violations permitted in commits
- Linting errors MUST be fixed immediately

Observability requirements:
- `php artisan pail` MUST be running during development for log monitoring
- Queue jobs MUST be monitored via `php artisan queue:listen`
- Error handling MUST provide clear, actionable error messages
- Database queries MUST be logged in development for debugging

**Rationale**: Fast feedback loops and consistent environments reduce context switching and debugging time. Observability tools catch issues immediately rather than in production. DDEV ensures "works on my machine" problems are eliminated.

## Technology Stack Standards

**The following technology choices are foundational and MUST NOT be changed without constitutional amendment.**

Core stack:
- **Backend**: Laravel 12, PHP 8.2+
- **Frontend Framework**: Livewire 3, Livewire Volt, Livewire Flux
- **Authentication**: Laravel Fortify (backend) + custom Livewire components (UI)
- **Styling**: Tailwind CSS 4.x (via Vite plugin, no separate config file)
- **Build Tool**: Vite (asset bundling and HMR)
- **Testing**: Pest (PHP unit/feature tests), Playwright (E2E tests)
- **Database**: SQLite (dev/test), MariaDB (DDEV production-like environment)
- **Development Environment**: DDEV (PHP 8.3, MariaDB 10.11, nginx-fpm)

**Rationale**: These technologies were chosen as a cohesive stack. Changing one component (e.g., switching from Livewire to Inertia.js) would require re-architecting large portions of the application. Any proposed changes require full impact analysis and constitutional amendment.

## Development Workflow Requirements

**All developers MUST follow this workflow for consistency and quality.**

Feature development workflow:
1. Create Livewire component: `php artisan make:livewire ComponentName`
2. Write failing Pest tests in `tests/Feature/`
3. Add route in `routes/web.php` pointing to component class
4. Implement component logic and view using Flux components
5. Run tests: `composer test` - all tests MUST pass
6. Run code formatter: `vendor/bin/pint`
7. Add E2E tests in `e2e/` for critical user flows
8. Commit changes

Database changes workflow:
1. Create migration: `php artisan make:migration description`
2. For models: `php artisan make:model ModelName -m` (includes migration)
3. Run migration: `php artisan migrate`
4. Verify in both SQLite (test) and MariaDB (DDEV) environments

Quality gates before commit:
- All Pest tests pass (`composer test`)
- E2E tests pass for affected flows (`npx playwright test`)
- Laravel Pint passes (`vendor/bin/pint`)
- No console errors in browser (for frontend changes)
- DDEV environment starts successfully (`ddev start`)

**Rationale**: Standardized workflows reduce cognitive load, prevent common mistakes, and ensure every commit meets quality standards. Following this workflow ensures features are tested, formatted, and verified before integration.

## Governance

**This constitution supersedes all other development practices and must be followed without exception.**

Amendment procedure:
1. Proposed changes MUST be documented with rationale and impact analysis
2. Amendment type determines version bump:
   - **MAJOR**: Backward-incompatible changes (e.g., removing Livewire-first principle)
   - **MINOR**: New principles added or material expansions (e.g., adding security requirements)
   - **PATCH**: Clarifications, wording improvements, typo fixes
3. All dependent templates (plan, spec, tasks) MUST be updated to reflect amendments
4. Sync Impact Report MUST be prepended to constitution as HTML comment
5. Constitution file MUST be committed with version update

Compliance verification:
- All pull requests MUST verify compliance with constitution principles
- Feature specifications MUST reference constitution principles that apply
- Implementation plans MUST include "Constitution Check" section validating adherence
- Code reviews MUST reject work that violates constitutional principles

Complexity justification:
- Any violation of constitutional principles MUST be explicitly justified
- Justification MUST appear in implementation plan's "Complexity Tracking" table
- Simpler alternatives MUST be documented and rejected with clear reasoning
- Persistent violations indicate need for constitutional amendment, not exceptions

Runtime guidance:
- This constitution defines governance and principles
- `CLAUDE.md` provides runtime development guidance for AI assistants
- In case of conflict, constitution principles take precedence
- `CLAUDE.md` MUST be updated if it contradicts constitutional principles

**Version**: 1.0.0 | **Ratified**: 2025-10-08 | **Last Amended**: 2025-10-08
