# Testing Configuration

## Test Framework
- **PHP**: Pest 4 + pest-plugin-laravel
- **E2E**: Playwright (Chromium, Firefox, WebKit)

## TDD Methodology

Each task follows strict Red → Green → Refactor:

1. Write failing test for one requirement
2. Write minimum code to pass
3. Refactor while tests stay green
4. Repeat for next requirement
5. Commit when task complete

## Commands

```bash
# Run all tests
php artisan test

# Run all tests (alias)
composer test

# Run specific test file
php artisan test tests/Feature/GroceryList/GroceryListTest.php

# Run with filter
php artisan test --filter=test_method_name
```

### E2E Tests (Playwright)

**Requires**: DDEV running (`ddev start`) — base URL is `https://yeschef.ddev.site`

```bash
# Run all E2E tests (all browsers)
npx playwright test

# Run in a specific browser
npx playwright test --project=chromium
npx playwright test --project=firefox
npx playwright test --project=webkit

# Run a specific spec file
npx playwright test e2e/grocery-lists.spec.ts

# Run a specific test by name filter
npx playwright test --grep "manual item appears in correct category"

# Run in UI mode (interactive, with browser visible)
npx playwright test --ui

# Run headed (browser visible, no UI mode)
npx playwright test --headed

# Show HTML report from last run
npx playwright show-report

# Update snapshots
npx playwright test --update-snapshots
```

**E2E test files live in** `e2e/` and follow the naming pattern `*.spec.ts`.

**When to run E2E tests**: Run the relevant spec file(s) after any change to Livewire views, Blade templates, or user-facing flows. The full suite is slow — filter to affected files rather than running all specs every time.

## Parallel Execution
Pest 4 supports parallel execution:
```bash
php artisan test --parallel
```
Use parallel by default for full suite runs; use sequential for debugging flaky failures.

## Test File Locations
- Unit tests: `tests/Unit/`
- Feature/Integration tests: `tests/Feature/` (organized by domain, e.g. `GroceryList/`, `MealPlans/`, `Recipes/`)
- E2E tests: `e2e/`
- Browser tests (Pest 4): `tests/Browser/`

## Database
- SQLite in-memory for PHP tests (configured in `phpunit.xml`)
- Use `RefreshDatabase` trait in tests that need a clean DB state

## Coverage Requirements
- Minimum: 80%
- All happy paths, failure paths, and edge cases must be covered
- Every code change requires a corresponding test

## Test Naming Convention
- Files: `*Test.php` (e.g., `GroceryListTest.php`)
- Methods: `it('does something')` or `test('something happens')`
- Use Pest datasets for validation rule tests to avoid duplication

## Key Testing Patterns
- Create models via factories: `User::factory()->create()`
- Check factory states before manually setting attributes
- Use `Livewire::test(Component::class)` for Livewire components
- Use `Volt::test('path.to.component')` for Volt components
- Use `assertForbidden()`, `assertNotFound()` over `assertStatus(403)`
- Import mocks: `use function Pest\Laravel\mock;`
