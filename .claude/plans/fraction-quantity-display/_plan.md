# Plan: Fraction Quantity Display

## Created
2026-04-07

## Status
completed

## Objective
Replace decimal quantity display (0.5, 0.25, 0.33) with human-readable fraction notation (½, ¼, ⅓) across recipe ingredients, serving-scaled quantities, and meal plan ingredient lists — while keeping the underlying `decimal:3` database storage unchanged.

## Scope

### In Scope
- PHP `QuantityFormatter` service: converts a float to a fraction string (e.g., `0.5` → `"½"`, `1.5` → `"1½"`)
- Update `RecipeIngredient::getDisplayQuantityAttribute()` to use the formatter
- Update Alpine.js `formatQuantity()` in `app.js` (used by the servings multiplier) to produce fraction strings
- Update meal plan ingredient display (`meal-plans/show.blade.php`)
- Unit tests for `QuantityFormatter`
- Fraction input parsing on create/edit: accept `1/2` typed by the user and store `0.5` (in scope — improves UX consistency)

### Out of Scope
- Changing the `quantity` database column type (stays `decimal:3`)
- Grocery item quantities (those are user-managed, not recipe-derived)
- Any changes to `ServingSizeScaler`, `IngredientAggregator`, or `UnitConverter` (they work with floats internally)

## Success Criteria
- [ ] `0.5` displays as `½`, `0.25` as `¼`, `0.333` as `⅓`, `1.5` as `1½`, `2.333` as `2⅓`, `1.0` as `1`
- [ ] Servings multiplier updates fractions live (JS `formatQuantity` produces fractions)
- [ ] Recipe create/edit inputs accept fraction strings (e.g., `1/2`) and store the correct decimal
- [ ] Meal plan ingredient quantities show fractions
- [ ] All new and modified tests pass

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | PHP QuantityFormatter service | - | completed |
| 002 | Update RecipeIngredient display_quantity | 001 | completed |
| 003 | Update JS formatQuantity for multiplier | - | completed |
| 004 | Accept fraction input on recipe create/edit | 001 | completed |
| 005 | Update meal plan ingredient quantity display | 001 | completed |
| 006 | E2E Playwright tests for fraction display | 002, 003, 004, 005 | completed |

## Architecture Notes
- Fraction rounding: round to nearest 1/8 (standard cooking fractions: ⅛, ¼, ⅜, ½, ⅝, ⅔, ¾, ⅞)
- Mixed numbers: whole part + fraction part (e.g., `1.5` → `1½`, not `3/2`)
- Unicode fraction characters for common fractions; ASCII fallback (`1/3`) for less-common ones
- The PHP formatter is the source of truth for fraction logic; replicate the same lookup table in JS
- `RecipeIngredient::display_quantity` is the server-rendered path (used when multiplier = 1x)
- `formatQuantity()` in Alpine.js is the client-side path (used when multiplier ≠ 1x)

## Risks & Mitigations
- Floating-point imprecision after scaling (e.g., `0.333 * 3 = 0.999`): mitigate by rounding to nearest 1/8 before lookup, with a small epsilon
- Unusual quantities (e.g., `0.1`, `0.7`) that don't map to clean fractions: display as decimal fallback
- 1/3 and 2/3 detection: these are NOT nearest-1/8 values. Both PHP and JS implementations must check for 1/3 and 2/3 via epsilon BEFORE rounding to nearest 1/8, or they will incorrectly map to 3/8 and 5/8
- Grocery list generate preview (`grocery-lists/generate.blade.php`) still shows raw decimal quantities — this is a known inconsistency since grocery quantities are out of scope
- Tasks 002 and 003 must be deployed together — if only one is updated, the server-rendered fallback (1x multiplier) and JS-rendered display (non-1x) will show inconsistent formats
- The quantity input in `ingredient-input.blade.php` uses `type="number"` which blocks fraction string entry — task 004 must change this to `type="text"`
