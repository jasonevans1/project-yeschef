# Task 005: Update Meal Plan Ingredient Quantity Display

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Update meal plan ingredient quantity display to show fractions instead of raw decimals. The formatting happens in the Livewire component's computed property, not in the Blade view.

## Context
- Related files:
  - `app/Livewire/MealPlans/Show.php` — `getScaledIngredientsProperty()` (lines 370-392). This is where the actual formatting happens. Line 384 currently does `rtrim(rtrim(number_format($scaledQuantity, 3, '.', ''), '0'), '.')` — this decimal-trimming logic must be replaced with `QuantityFormatter::format($scaledQuantity)`.
  - `resources/views/livewire/meal-plans/show.blade.php` — line 634: `{{ $ingredient['quantity'] }} {{ $ingredient['unit'] }}`. This view renders the already-formatted value from the component. No Blade changes are needed if the component formats correctly.
  - `app/Services/QuantityFormatter.php` — use `QuantityFormatter::format()` in the component
- The fix is in `getScaledIngredientsProperty()` — replace the manual formatting with `QuantityFormatter::format()`. The `$ingredient['quantity']` key will then contain the fraction string.
- Note: `$recipeIngredient->quantity` can be null. The existing code does `$recipeIngredient->quantity * $multiplier` which would produce 0 for null. Check if null quantity should be handled before multiplication.

## Requirements (Test Descriptions)
- [ ] `it displays fraction quantity for meal plan recipe ingredients`
- [ ] `it displays whole number quantity without fraction for integer values`
- [ ] `it displays nothing for null quantity on meal plan ingredient`

## Acceptance Criteria
- All requirements have passing tests
- `getScaledIngredientsProperty()` in `Show.php` uses `QuantityFormatter::format()` instead of manual decimal formatting
- Raw decimal values no longer appear in the meal plan ingredient list
- Tests live in `tests/Feature/MealPlans/` (check existing test files in that directory and add to an appropriate existing file, or create `MealPlanIngredientDisplayTest.php`)
