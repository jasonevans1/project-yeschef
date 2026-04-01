# Task 003: Full Suite Verification and Regression Check

**Status**: pending
**Depends on**: 002
**Retry count**: 0

## Description
Run the full test suite to verify no regressions from the PANTRY removal and category expansion. Tasks 001 and 002 will have already written their own tests (enum unit tests and migration feature tests). This task's purpose is to catch any remaining breakage across the ~50+ test files that referenced `IngredientCategory::PANTRY` and ensure everything integrates correctly.

## Context
- Tasks 001 and 002 should have already updated all `IngredientCategory::PANTRY` references in test files, factories, and seeders
- This task runs the full suite and fixes any remaining issues
- Run: `php artisan test` (full suite)
- If any tests fail due to lingering `PANTRY` references, fix them by replacing with `IngredientCategory::SOUPS_AND_CANNED_GOODS` (or another appropriate new category)
- Verify that factories no longer produce `'pantry'` values (check `GroceryItemFactory` and `IngredientFactory`)

## Requirements (Test Descriptions)
- [ ] `the full test suite passes with zero failures`
- [ ] `no references to IngredientCategory::PANTRY remain in the codebase` (verify with grep)
- [ ] `no hardcoded pantry string remains in factories or seeders` (verify with grep)

## Acceptance Criteria
- `php artisan test` (full suite) passes with no regressions
- `grep -r 'IngredientCategory::PANTRY' app/ tests/ database/` returns no results
- `grep -r "'pantry'" database/factories/ database/seeders/` returns no results
- `vendor/bin/pint --dirty` passes
