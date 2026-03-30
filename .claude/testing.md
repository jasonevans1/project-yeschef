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

# Run E2E tests
npx playwright test

# Run E2E in specific browser
npx playwright test --project=chromium

# Run E2E in UI mode
npx playwright test --ui
```

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
