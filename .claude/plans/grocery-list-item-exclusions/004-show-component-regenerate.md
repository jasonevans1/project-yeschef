# Task 004: Show Component — Regenerate with Excluded Ingredients

**Status**: completed
**Depends on**: [002]
**Retry count**: 0

## Description
Update the `Show` Livewire component's regeneration flow to pass the grocery list's stored `excluded_ingredients` when calling `GroceryListGenerator::regenerate()`, so pantry-excluded items are also skipped during in-page regeneration.

## Context
- Related files:
  - `app/Livewire/GroceryLists/Show.php` — three methods need updating

**Existing `regenerateExcludedCategories` pattern** (lines ~54, 268, 282, 394–404):
- Property: `public array $regenerateExcludedCategories = [];`
- Populated in `showRegenerateConfirmation()`: `$this->regenerateExcludedCategories = $this->groceryList->excluded_categories ?? [];`
- Reset in `cancelRegenerate()`: `$this->regenerateExcludedCategories = [];`
- Passed in `regenerate()`: `$generator->regenerate($this->groceryList, array_values($excludedEnums))`

**Add the identical parallel for ingredient exclusions:**
- New property: `public array $regenerateExcludedIngredients = [];`
- In `showRegenerateConfirmation()`: `$this->regenerateExcludedIngredients = $this->groceryList->excluded_ingredients ?? [];`
- In `cancelRegenerate()`: `$this->regenerateExcludedIngredients = [];`
- In `regenerate()`: pass as third argument to `$generator->regenerate()`
- In `regenerate()` post-success cleanup (lines 402-404): also reset `$this->regenerateExcludedIngredients = [];` alongside the existing `$this->regenerateExcludedCategories = [];`

**Note on design**: Show uses the list's *stored* `excluded_ingredients`, not the user's current pantry. This ensures the regenerated list matches the same exclusions that were in effect when it was last generated. Users who want to change pantry exclusions should use the Generate page.

## Requirements (Test Descriptions)
- [ ] `it pre-populates regenerateExcludedIngredients from groceryList excluded_ingredients on showRegenerateConfirmation`
- [ ] `it passes regenerateExcludedIngredients to generator on regenerate`
- [ ] `it excluded ingredient is absent from regenerated grocery items`
- [ ] `it resets regenerateExcludedIngredients on cancelRegenerate`
- [ ] `it uses empty array when groceryList has no excluded_ingredients`

## Acceptance Criteria
- All requirements have passing tests
- `$generator->regenerate()` called with three arguments: `$groceryList`, `$excludedEnums`, `$this->regenerateExcludedIngredients`
- No changes to existing category exclusion logic
- No changes to Show component's other features

## Implementation Notes
(Left blank — filled in by implementer)
