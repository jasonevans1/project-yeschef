# Task 001: Create IngredientCategorizationService

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Create a shared `IngredientCategorizationService` that provides a single, authoritative method for resolving the `IngredientCategory` for a given ingredient name. This consolidates the duplicated `guessIngredientCategory()` methods from `Create.php` and `Edit.php`, adds `CommonItemTemplate` lookup that was previously missing from those components, and combines everything into a resolution chain usable in both ingredient creation and grocery list generation contexts.

## Context
- **Create**: `app/Services/IngredientCategorizationService.php`
- **Existing keyword logic to consolidate**: `app/Livewire/Recipes/Create.php:146-172` and `app/Livewire/Recipes/Edit.php` (identical method)
- **Templates to query**: `App\Models\UserItemTemplate`, `App\Models\CommonItemTemplate`
- **Target enum**: `App\Enums\IngredientCategory`
- Patterns to follow: `app/Services/GroceryListGenerator.php` — constructor injection, typed return hints

## Resolution Order
Method signature: `public function resolve(string $name, int $userId = 0): IngredientCategory`
- Pass `auth()->id()` for authenticated contexts; pass `0` (the documented sentinel for "no user") when no user is available.

1. If `$userId > 0`: check `UserItemTemplate` by lowercase name match → return category if found
2. Check `CommonItemTemplate` by lowercase name match → return category if found
3. Keyword matching (consolidate from `Create.php:150-161`, with corrections noted below)
4. Return `IngredientCategory::OTHER`

### Keyword Corrections
The existing `guessIngredientCategory()` has two issues to fix when consolidating:
- `wine` and `beer` are currently mapped to `BEVERAGES` but should map to `WINE_BEER_AND_SPIRITS`
- Add a `WINE_BEER_AND_SPIRITS` keyword group: `['wine', 'beer', 'spirits', 'liquor', 'whiskey', 'vodka', 'rum']`
- Move `wine` and `beer` out of the `BEVERAGES` group

### Keyword Coverage Gap (Acknowledged)
The keyword map covers 10 of 19 `IngredientCategory` values. The following 9 have no keyword coverage and rely entirely on template lookups:
`BREAKFAST`, `CONDIMENTS_AND_DRESSINGS`, `DELI`, `HEALTH_AND_PERSONAL_CARE`, `HOUSEHOLD_AND_CLEANING`, `PET_SUPPLIES`, `SNACKS`, and `OTHER`.
This is acceptable given `CommonItemTemplate` covers 149 items. Adding keywords for obvious gaps (e.g., `condiment`, `snack`, `cereal`) is encouraged but not required.

### `search_keywords` Column (Out of Scope)
`CommonItemTemplate` has a `search_keywords` column that is not used by this service's name-based lookup. Fuzzy keyword searching is deferred to a future iteration.

## Requirements (Test Descriptions)
- [ ] `it returns the category from the user item template when a match exists`
- [ ] `it skips user item template lookup when user id is zero (the no-user sentinel)`
- [ ] `it falls back to common item template when no user template match`
- [ ] `it falls back to keyword matching when no template match`
- [ ] `it matches produce keywords correctly`
- [ ] `it matches dairy keywords correctly`
- [ ] `it matches meat keywords correctly`
- [ ] `it matches seafood keywords correctly`
- [ ] `it matches cooking and baking keywords correctly`
- [ ] `it matches grains and pasta keywords correctly`
- [ ] `it matches bakery keywords correctly`
- [ ] `it maps wine and beer to wine-beer-and-spirits not beverages`
- [ ] `it maps juice and soda to beverages`
- [ ] `it returns OTHER when no template or keyword match`
- [ ] `it is case insensitive for name matching`

## Acceptance Criteria
- All requirements have passing tests
- No keyword logic remains in `Create.php` or `Edit.php` (those are cleaned up in tasks 002/003)
- Service follows the constructor injection pattern used by other services in `app/Services/`
- Code formatted with `vendor/bin/pint --dirty`
