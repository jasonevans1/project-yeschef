# Task 002: GroceryListGenerator Updates

**Status**: completed
**Depends on**: [001]
**Retry count**: 0

## Description
Add a `getIngredientPreview()` public method to `GroceryListGenerator` that returns the full aggregated ingredient list (used to populate the UI preview). Update `generate()` and `regenerate()` to accept and apply an `$excludedIngredients` array of lowercase ingredient names, and persist those exclusions on the `GroceryList`.

## Context
- Related files:
  - `app/Services/GroceryListGenerator.php` — primary file; all changes are here
  - `app/Models/GroceryList.php` — `excluded_ingredients` field added in task 001

**Key existing methods to understand:**
- `collectIngredientsFromMealPlan(MealPlan, int): Collection` — private; collects, scales, resolves categories
- `aggregateIngredients(Collection): Collection` — public; deduplicates by name+unit
- `getCategoryItemCounts(MealPlan, int): array` — public; runs collect→aggregate pipeline; returns `['category-value' => count]`
- `generate(MealPlan, array $excludedCategories): GroceryList` — creates list + GroceryItems
- `regenerate(GroceryList, array $excludedCategories): GroceryList` — updates existing list

**Refactor opportunity**: Extract a private `getAggregatedIngredients(MealPlan $mealPlan, int $userId): Collection` that both `getCategoryItemCounts` and `getIngredientPreview` call. Eliminates duplicating the collect→aggregate pipeline.

## Requirements (Test Descriptions)
- [ ] `it returns an empty collection from getIngredientPreview when meal plan has no assignments`
- [ ] `it returns all aggregated ingredients from getIngredientPreview for a meal plan with recipes`
- [ ] `it returns items with expected shape keys from getIngredientPreview` (each item must have: `name` (string), `quantity` (numeric|null), `unit` (?string), `category` (string — enum value), `category_label` (string — human label))
- [ ] `it sorts getIngredientPreview results by category enum order then alphabetically by name`
- [ ] `it omits excluded ingredient names from generate output`
- [ ] `it stores excluded_ingredients on the GroceryList after generate`
- [ ] `it stores null for excluded_ingredients on the GroceryList when none excluded`
- [ ] `it omits excluded ingredient names from regenerate output`
- [ ] `it stores excluded_ingredients on the GroceryList after regenerate`
- [ ] `it does not remove manual items matching an excluded ingredient name during regenerate`
- [ ] `it excludes by category AND by ingredient name independently in generate`
- [ ] `it applies ingredient exclusion case-insensitively`

## Acceptance Criteria
- All requirements have passing tests
- `generate()` signature: `generate(MealPlan $mealPlan, array $excludedCategories = [], array $excludedIngredients = []): GroceryList`
- `regenerate()` signature: `regenerate(GroceryList $groceryList, array $excludedCategories = [], array $excludedIngredients = []): GroceryList`
- `getIngredientPreview()` signature: `getIngredientPreview(MealPlan $mealPlan, int $userId): Collection`
- Existing call sites (Generate component, Show component) still compile without change because new params default to `[]`
- No test regressions

## Implementation Notes

### Critical: Name casing in `generate()`
The `Ingredient` model's `getNameAttribute()` returns `ucfirst($value)`, so ingredient names flowing through `collectIngredientsFromMealPlan` are title-cased (e.g., "Salt" not "salt"). In `generate()`, the ingredient exclusion check **must** use `strtolower($ingredient['name'])` before comparing against `$excludedIngredients`. The existing `regenerate()` already does this at its line 116.

### Where to apply ingredient exclusion in `generate()`
Category exclusion happens at the **outer** loop (`foreach ($organizedIngredients as $category => $ingredients)`). Ingredient name exclusion must be applied inside the **inner** loop (`foreach ($ingredients as $ingredient)`) with a `continue` to skip matching names:
```php
foreach ($ingredients as $ingredient) {
    if (in_array(strtolower($ingredient['name']), $excludedIngredients, true)) {
        continue;
    }
    // ... create GroceryItem
}
```

### Storing `excluded_ingredients` on GroceryList
In `generate()`: add `'excluded_ingredients' => $excludedIngredients ?: null` to the `GroceryList::create()` array (same pattern as `excluded_categories`).
In `regenerate()`: add `'excluded_ingredients' => $excludedIngredients ?: null` to the existing `$groceryList->update()` call alongside `excluded_categories`.

### `getIngredientPreview()` return shape
This data becomes a Livewire public property and must be JSON-serializable. The method must transform aggregated output to plain arrays:
- `category` must be the enum's string **value** (not the enum instance)
- `unit` must be the enum's string **value** or null (not the enum instance)
- Add `category_label` from `IngredientCategory->label()`

Do NOT return enum instances — Livewire cannot serialize them in public array properties.

