# Task 005: Full Test Suite Verification and Fix Remaining Failures

**Status**: completed
**Depends on**: [002, 003, 004]
**Retry count**: 0

## Description
Run the full test suite after all upgrade changes have been applied. Diagnose and fix any remaining failures caused by the framework upgrades. This is the final verification gate before the upgrade is considered complete.

## Context
- Test commands: `php artisan test`, `php artisan test --filter=<name>`
- E2E tests: `npx playwright test`
- Test directories: `tests/Feature/`, `tests/Unit/`, `tests/Browser/`, `e2e/`
- Key test areas to verify:
  - Auth flows (login, register, password reset, 2FA)
  - Recipe CRUD
  - Meal plan management
  - Grocery list generation and sharing
  - Settings (profile, password, appearance, item templates)
  - Recipe import flow
  - PDF/text export

### Known Areas That May Need Fixing

1. **Livewire test assertions**: Any use of deprecated Livewire 3 test helpers that changed in v4. Volt::test() stays as-is (Volt supports Livewire 4).

2. **Stream method signature**: Livewire 4 changed `$this->stream()` parameter order:
   ```php
   // Old: $this->stream(to: '#container', content: 'Hello', replace: true);
   // New: $this->stream('Hello', replace: true, el: '#container');
   ```
   Check if any component uses `stream()`.

3. **JavaScript hook changes** (if any custom hooks in `resources/js/app.js`):
   - `commit` and `request` hooks → `interceptMessage()` and `interceptRequest()`

4. **URL endpoint change**: Livewire 4 uses `/livewire-{hash}/update` instead of `/livewire/update`. If any tests make direct HTTP requests to this endpoint, they'll need updating. Standard Livewire test helpers handle this automatically.

5. **smart_wire_keys default**: In Livewire 4, `smart_wire_keys` defaults to `true`. If any component-in-loop rendering was relying on the old key behavior, this may cause issues.

## Requirements (Test Descriptions)
- [x] `it passes all feature tests in tests/Feature/Auth/`
- [x] `it passes all feature tests in tests/Feature/Recipe/`
- [x] `it passes all feature tests in tests/Feature/RecipeImporter/`
- [x] `it passes all feature tests in tests/Feature/MealPlans/`
- [x] `it passes all feature tests in tests/Feature/GroceryList/`
- [x] `it passes all feature tests in tests/Feature/Settings/`
- [x] `it passes all feature tests in tests/Feature/Sharing/`
- [x] `it passes all unit tests in tests/Unit/`
- [x] `it passes browser tests in tests/Browser/`

## Acceptance Criteria
- All requirements have passing tests
- `composer test` exits with code 0
- No test failures due to upgrade-related breaking changes
- If new failures found, they are diagnosed and fixed within this task

## Implementation Notes
Run tests in order from most specific to broadest:
1. `php artisan test tests/Feature/Auth/` — auth is most likely to break
2. `php artisan test tests/Feature/Recipe/` — recipe is largest feature area
3. `php artisan test` — full suite

For E2E tests, ensure the dev server is running before executing Playwright tests.
