# Task 002: Update RecipeIngredient display_quantity

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Update `RecipeIngredient::getDisplayQuantityAttribute()` in `app/Models/RecipeIngredient.php` to use `QuantityFormatter` instead of the current decimal-trimming logic. This fixes the server-rendered ingredient display on the recipe show page (used when multiplier is at 1x).

## Context
- Related files:
  - `app/Models/RecipeIngredient.php` — the accessor to update (lines 40–51)
  - `app/Services/QuantityFormatter.php` — created in task 001
  - `resources/views/livewire/recipes/show.blade.php` — uses `$recipeIngredient->display_quantity` (line 204) as fallback when no multiplier is active. The expression is `x-text="scaleQuantity({{ $recipeIngredient->quantity }}) || '{{ $recipeIngredient->display_quantity }}'"` — note that this fallback value is now a fraction string, so it must be properly escaped for JS string context.
- The accessor is already wired into the view; only the formatting logic changes
- **IMPORTANT**: Existing tests in `tests/Unit/Models/RecipeIngredientTest.php` assert decimal formatting (e.g., `->toBe('1.5')`, `->toBe('0.75')`, `->toBe('0.333')`). These MUST be updated to expect fraction output (e.g., `'1 1/2'` or `'1½'`, `'3/4'` or `'¾'`, `'1/3'` or `'⅓'`). Match the exact output format from QuantityFormatter.
- **IMPORTANT**: Existing tests in `tests/Feature/Livewire/RecipeShowTest.php` assert decimal fallback values (lines 48-49: `assertDontSee("|| '2.000'")`). These assertions may need updating since the fallback now contains fraction strings.
- **Deploy note**: This task and task 003 (JS formatQuantity) should be deployed together. If only one side is updated, the server-rendered fallback and JS-rendered display will show inconsistent formats.

## Requirements (Test Descriptions)
- [ ] `it displays one half fraction for quantity zero point five`
- [ ] `it displays one and one half fraction for quantity one point five`
- [ ] `it displays whole number for integer quantity`
- [ ] `it returns null display quantity when quantity is null`
- [ ] `it displays three quarters fraction for quantity zero point seven five`
- [ ] `it displays one third fraction for quantity zero point three three three`
- [ ] Update existing tests in `tests/Unit/Models/RecipeIngredientTest.php` to expect fraction output
- [ ] Update existing tests in `tests/Feature/Livewire/RecipeShowTest.php` if assertions conflict with new output

## Acceptance Criteria
- All requirements have passing tests
- `getDisplayQuantityAttribute()` delegates to `QuantityFormatter`
- Existing tests in `tests/Unit/Models/RecipeIngredientTest.php` are updated (not duplicated in a separate file)
- Tests for new fraction-specific behavior live in `tests/Unit/Models/RecipeIngredientTest.php` (same file)
