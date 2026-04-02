# Task 004: Update GroceryListGenerator to use IngredientCategorizationService

**Status**: completed
**Depends on**: [001]
**Retry count**: 0

## Description
Update `GroceryListGenerator::resolveIngredientCategory()` to delegate to `IngredientCategorizationService`. The current implementation checks `UserItemTemplate` and `CommonItemTemplate` but stops there — it's missing keyword matching as a final fallback. The service provides the complete chain (UserItemTemplate → CommonItemTemplate → keyword matching → OTHER), so the generator gets better coverage for ingredients that slip through with `OTHER` in the database.

## Context
- `app/Services/GroceryListGenerator.php` — `resolveIngredientCategory()` at line 239
- Current behavior: if category is not OTHER, return as-is. If OTHER, check UserItemTemplate, then CommonItemTemplate, then return OTHER
- New behavior: delegate the entire OTHER-resolution chain to `IngredientCategorizationService::resolve(string $name, int $userId)`
- The early-return for non-OTHER categories stays in `GroceryListGenerator` (no change to that path)
- Inject `IngredientCategorizationService` into `GroceryListGenerator` constructor alongside existing services
- **The constructor change will break 30 existing unit tests** in `tests/Unit/GroceryListGeneratorTest.php` that instantiate the generator with 3 positional args. Update all 30 instantiations to pass the new service as the 4th argument.

## Requirements (Test Descriptions)
Focus on what is unique to the generator — service-level resolution is already tested in Task 001 and end-to-end resolution is already covered by `tests/Feature/GroceryLists/CategoryLookupTest.php`.

- [ ] `it passes the correct user id to the categorization service when resolving ingredient categories`
- [ ] `it does not call the categorization service for non-other ingredient categories (early return preserved)`
- [ ] `it returns a resolved category for an ingredient that is OTHER in the database`

## Acceptance Criteria
- `GroceryListGenerator` injects and uses `IngredientCategorizationService`
- The template-lookup logic inside `resolveIngredientCategory()` is removed (delegated to service)
- All 30 existing `GroceryListGeneratorTest` instantiations updated with the new service parameter
- All existing `GroceryListGenerator` tests still pass
- Code formatted with `vendor/bin/pint --dirty`
