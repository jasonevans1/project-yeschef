# Plan: Ingredient Category Resolution

## Created
2026-03-31

## Status
completed

## Objective
Ensure grocery list items generated from a meal plan always have the most accurate `IngredientCategory` possible, by consolidating category resolution logic into a shared service and wiring it into every point where ingredients are created or categorized.

## Scope

### In Scope
- Create a shared `IngredientCategorizationService` that resolves category via: UserItemTemplate → CommonItemTemplate → keyword matching → OTHER
- Update `ImportPreview.php` to use the service when creating new ingredients (currently always defaults to OTHER)
- Update `Create.php` and `Edit.php` to use the service instead of their local `guessIngredientCategory()` method
- Update `GroceryListGenerator::resolveIngredientCategory()` to delegate to the service, adding keyword matching as a final fallback it was previously missing

### Out of Scope
- Backfilling existing ingredients already stored as OTHER in the database
- Changes to the CommonItemTemplate or UserItemTemplate data
- UI changes for manually overriding item categories on a grocery list

## Success Criteria
- [ ] `IngredientCategorizationService` is the single source of truth for category resolution
- [ ] Imported recipe ingredients get accurate categories (not always OTHER)
- [ ] Manually created recipe ingredients get accurate categories using templates + keywords
- [ ] Generator-time category resolution includes keyword matching as a fallback
- [ ] All tests passing
- [ ] No duplication of `guessIngredientCategory()` logic across Livewire components

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Create IngredientCategorizationService | - | completed |
| 002 | Update ingredient creation in Create.php and Edit.php | 001 | completed |
| 003 | Update ImportPreview.php ingredient creation | 001 | completed |
| 004 | Update GroceryListGenerator to use the service | 001 | completed |

## Architecture Notes
- The service lives in `app/Services/IngredientCategorizationService.php`
- Public method: `resolve(string $name, int $userId = 0): IngredientCategory`
- Resolution order: UserItemTemplate (when userId > 0) → CommonItemTemplate → keyword matching → OTHER
- `userId = 0` is the documented sentinel meaning "no user / skip user template lookup". All authenticated callers should pass `auth()->id()`.
- Keyword matching consolidates the logic from `Create.php` and `Edit.php` `guessIngredientCategory()` — both are identical, so this also removes duplication
- Wine/beer keywords are corrected to map to `WINE_BEER_AND_SPIRITS` (existing code incorrectly mapped them to `BEVERAGES`)
- Tasks 002, 003, 004 are independent of each other and can run in parallel after 001

## Risks & Mitigations
- Keyword matching is limited: mitigated by CommonItemTemplate lookup which covers 149 known items
- `firstOrCreate` in Create/Edit/ImportPreview uses `OTHER` as the default only for *new* ingredients — existing ingredients already have their category set and are not affected
