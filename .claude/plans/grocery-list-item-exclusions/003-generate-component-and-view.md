# Task 003: Generate Component + View

**Status**: completed
**Depends on**: [001, 002]
**Retry count**: 0

## Description
Update the `Generate` Livewire component to load an ingredient preview, pre-populate exclusions from the user's pantry (or existing list), and save pantry preferences. Update the Blade view to render the ingredient exclusion panel with checkboxes and pantry management buttons.

## Context
- Related files:
  - `app/Livewire/GroceryLists/Generate.php` — add properties, update `mount()`, update `generate()`, add `savePantry()` / `clearPantry()`
  - `resources/views/livewire/grocery-lists/generate.blade.php` — add ingredient exclusion panel below the existing category exclusion panel

**Existing precedence pattern** (mirror for ingredient exclusions):
```php
// Category exclusion already does this in mount():
$this->excludedCategories = $this->existingList?->excluded_categories ?? $this->savedPreferences;
```

**New ingredient exclusion must use same pattern:**
```php
$this->excludedIngredients = $this->existingList?->excluded_ingredients ?? $this->savedPantry;
```

**Ingredient preview shape** (each item in the array):
```php
[
    'name'           => string,   // ucfirst display; value used in checkbox = strtolower($item['name'])
    'quantity'       => mixed,    // numeric or null
    'unit'           => ?string,  // MeasurementUnit->value or null
    'category'       => string,   // IngredientCategory->value (for groupBy)
    'category_label' => string,   // IngredientCategory->label() for display
]
```

**View grouping**: In Blade, use `collect($ingredientPreview)->groupBy('category')` to group by category. `getIngredientPreview()` already returns items sorted by category enum order then name, so `groupBy` preserves that order.

**Checkbox value**: Must be `strtolower($item['name'])` (lowercase) to match what the generator compares against.

**Pantry badge**: Show a small "pantry" indicator next to items where `in_array(strtolower($item['name']), $savedPantry)` is true, so users can see which items are saved to their pantry.

## Requirements (Test Descriptions)
- [ ] `it loads ingredientPreview with items when meal plan has recipes`
- [ ] `it loads empty ingredientPreview when meal plan has no recipes`
- [ ] `it pre-populates excludedIngredients from existing list excluded_ingredients`
- [ ] `it falls back to user pantry_items when no existing list`
- [ ] `it uses empty array when no existing list and no pantry`
- [ ] `it prefers existing list excluded_ingredients over pantry_items`
- [ ] `it generates list excluding checked ingredients`
- [ ] `it regenerates list excluding checked ingredients`
- [ ] `it savePantry persists excludedIngredients to user pantry_items`
- [ ] `it savePantry sets pantry_items to null when no ingredients are excluded`
- [ ] `it clearPantry sets user pantry_items to null`
- [ ] `it clearPantry resets savedPantry to empty array`
- [ ] `it shows ingredient exclusion panel when recipes exist`
- [ ] `it does not show ingredient exclusion panel when no recipes`
- [ ] `it shows the real item count from ingredientPreview instead of the estimated heuristic`

## Acceptance Criteria
- All requirements have passing tests
- Category exclusion UI (existing) is unchanged
- Ingredient exclusion panel renders below category panel, above action buttons
- Items are grouped by category with category headings
- Checked items are added to `$excludedIngredients` array (Livewire handles checkbox binding)
- "Save checked as always have" and "Clear pantry" buttons work
- The preview panel shows ALL items regardless of category exclusion state
- The estimated item count heuristic (`$recipeCount * 8`) is replaced with `count($ingredientPreview)` (real count) once the preview is loaded; keep the heuristic display only when `$ingredientPreview` is empty (no recipes assigned) — category exclusion and ingredient exclusion are independent filters both applied at generation time, not at preview time

## Implementation Notes
(Left blank — filled in by implementer)
