# Devil's Advocate Review: fraction-quantity-display

## Critical (Must fix before building)

### C1. Task 004 quantity input is `type="number"` -- fractions will be rejected by browser (Task 004)
The ingredient quantity input in `resources/views/components/ingredient-input.blade.php` (line 20) uses `type="number"` with `step="0.01"` and `min="0.01"`. HTML number inputs reject non-numeric characters like `/`, spaces, and unicode fraction chars at the browser level. The user literally cannot type `1/2` into this field. Task 004 must also update the input to `type="text"` (or `inputmode="decimal"`) and adjust the validation accordingly. The `min` and `step` HTML attributes will also need to be removed since they are number-specific.

**Fix**: Update task 004 to include modifying the `ingredient-input.blade.php` component to change `type="number"` to `type="text"` with `inputmode="decimal"`, and remove `step` and `min` attributes. Add this file to the Related Files list.

### C2. Task 004 targets wrong line numbers and wrong method structure in Create.php/Edit.php (Task 004)
Task 004 says "lines 118-141" for `validateIngredients()` and ingredient save logic. Actually:
- `validateIngredients()` in Create.php is at lines 132-151
- The save logic that writes `$ingredientData['quantity']` is at lines 118-124
- Neither Create.php nor Edit.php has any fraction-parsing step before the quantity reaches `$ingredientData['quantity'] ?? 1` on line 120/158

The quantity arrives as a string from the Livewire wire:model binding. Currently, because the input is `type="number"`, the browser sends a numeric string. When we change to `type="text"`, the string `"1/2"` will arrive in `$this->ingredients[$index]['quantity']`. The parsing must happen before validation (which checks `$ingredient['quantity'] <= 0`) because `"1/2" <= 0` evaluates oddly in PHP string comparison.

**Fix**: Task 004 must specify that fraction parsing happens in a new method (e.g., `parseIngredientQuantities()`) called at the start of `save()`/`update()` before `$this->validate()` and `$this->validateIngredients()`, converting each `$this->ingredients[$index]['quantity']` from string to float. Both Create.php and Edit.php must be updated.

### C3. Existing tests in RecipeIngredientTest.php will break (Task 002)
`tests/Unit/Models/RecipeIngredientTest.php` has 12+ tests that assert specific decimal formatting behavior (e.g., `expect($ingredient->display_quantity)->toBe('1.5')` on line 28, `->toBe('0.75')` on line 33, `->toBe('0.333')` on line 38). After task 002 changes `getDisplayQuantityAttribute()` to use `QuantityFormatter`, these will return fraction strings instead (`1 1/2`, `3/4`, `1/3`). Task 002 must explicitly call out updating these existing tests.

**Fix**: Add requirement to task 002 to update all existing tests in `tests/Unit/Models/RecipeIngredientTest.php` to expect fraction output. Also update the existing `tests/Feature/Livewire/RecipeShowTest.php` assertions (line 48-49 check for decimal fallback values like `'2.000'`).

### C4. `scaleQuantity` JS fallback expression will mismatch (Task 002 + 003)
In `show.blade.php` line 204: `x-text="scaleQuantity({{ $recipeIngredient->quantity }}) || '{{ $recipeIngredient->display_quantity }}'"`. After task 002, `display_quantity` will return fraction strings like `1/2`. After task 003, `scaleQuantity()` will also return fraction strings. But there is a timing risk: if task 002 is completed before task 003, the fallback shows fractions but the JS shows decimals (or vice versa). More critically, the `||` JavaScript operator: if `scaleQuantity()` returns the string `"0"` (for quantity 0), JS treats that as falsy and falls through to the PHP fallback. This is an existing bug but worth noting.

**Fix**: Document in both tasks 002 and 003 that they must both be deployed together, and add a note about the `"0"` falsy edge case.

## Important (Should fix before building)

### I1. Task 005 targets the wrong display location -- `getScaledIngredientsProperty()` not the Blade view (Task 005)
Task 005 says to "format in the Livewire component when building the ingredient array" and references `show.blade.php` line 634. But the actual formatting happens in `getScaledIngredientsProperty()` (lines 370-392 of `app/Livewire/MealPlans/Show.php`), which already does its own decimal formatting with `rtrim(rtrim(number_format(...)))` on line 384. The fix should replace that formatting logic with `QuantityFormatter::format()`, not add a `displayQuantity` key.

**Fix**: Update task 005 context to reference `getScaledIngredientsProperty()` in `Show.php` line 384 as the actual location to change. The Blade view just renders `$ingredient['quantity']` and needs no changes if the component formats correctly.

### I2. Architecture note lists `3/8` and `5/8` in fraction set but `1/3` and `2/3` are not nearest-1/8 values (Task 001, _plan.md)
The plan says "round fractional part to the nearest 1/8" but then lists 1/3 and 2/3 as special cases. This is correct in task 001 but the JS task (003) does not mention the epsilon/special-case handling for 1/3 and 2/3. A naive round-to-nearest-1/8 in JS would map 0.333 to 0.375 (3/8) and 0.667 to 0.625 (5/8), which is wrong.

**Fix**: Add explicit requirement to task 003 that 1/3 and 2/3 must be detected before rounding to nearest 1/8, using an epsilon check (e.g., `Math.abs(frac - 0.333) < 0.02`).

### I3. Task 004 does not mention Edit.php explicitly in requirements (Task 004)
The context says "similar pattern" for Edit.php but the requirements only test creation. Edit.php has identical quantity handling at line 158 (`$ingredientData['quantity'] ?? 1`). A worker might only modify Create.php.

**Fix**: Add explicit test requirements for editing a recipe with fraction input, and list Edit.php as a file that must be modified.

### I4. `validateIngredients()` comparison `$ingredient['quantity'] <= 0` will break with string fraction input (Task 004)
After changing the input to `type="text"`, the quantity value arrives as a string like `"1/2"`. The check `$ingredient['quantity'] <= 0` in `validateIngredients()` (Create.php line 139, Edit.php line 179) does a string-to-number coercion where `"1/2"` becomes `1` (PHP takes everything before the `/`). This silently passes but is wrong for inputs like `"0/2"`. The parsing must happen before this validation.

**Fix**: Already addressed by C2 above -- ensure parsing is called before validateIngredients().

### I5. Grocery list generate view shows raw decimal quantities (Out of scope but worth noting)
`resources/views/livewire/grocery-lists/generate.blade.php` line 156 shows `{{ $item['quantity'] }}` which comes from `GroceryListGenerator` and will still show decimals. The plan explicitly marks grocery quantities as out of scope, but the grocery list *generate preview* shows recipe-derived quantities before they become user-managed. This may look inconsistent.

**Fix**: Note in _plan.md risks section as a known inconsistency.

### I6. Task 002 test file path inconsistency (Task 002)
Task 002 says tests live in `tests/Feature/Recipe/RecipeIngredientDisplayQuantityTest.php` but the existing display_quantity tests are in `tests/Unit/Models/RecipeIngredientTest.php`. Creating a second test file for the same accessor in a different location is confusing.

**Fix**: Update task 002 to add/modify tests in the existing `tests/Unit/Models/RecipeIngredientTest.php` file instead.

## Minor (Nice to address)

### M1. `formatQuantity` extraction in task 003 may break Alpine `this` context
Task 003 says to extract `formatQuantity` into a module-level function. Currently it is called as `this.formatQuantity(scaled)` from `scaleQuantity()`. If extracted to module level, the call sites in both Alpine components need updating from `this.formatQuantity(scaled)` to `formatQuantity(scaled)`. The task should note this.

### M2. No accessibility considerations for fraction unicode characters
Screen readers may read unicode fractions inconsistently (e.g., `1/2` vs `one half` for the character). This is a minor UX concern for screen reader users.

### M3. Zero quantity edge case
The plan does not specify what `0.0` should display as. Currently `getDisplayQuantityAttribute()` returns `"0"`. Should `QuantityFormatter::format(0.0)` return `"0"`, `""`, or `null`? The existing test expects `"0"`.

## Questions for the Team

1. **Should the edit form pre-populate quantities as fractions?** Currently `Edit.php` mount() loads `(float) $ri->quantity` which gives `0.5`. Should this display as `1/2` in the input field, or stay as `0.5`? Displaying as fractions in an editable input would require the input component to format on load.

2. **What about the recipe import flow?** `app/Livewire/Recipes/ImportPreview.php` and related views also display quantities. Should imported recipe quantities also show fractions?

3. **Should the grocery list generate preview (`generate.blade.php`) also use fraction display?** The plan marks grocery quantities as out of scope, but the preview shows recipe-derived quantities.
