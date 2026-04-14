# Task 003: Update GroceryListGenerator to Store Recipe IDs

**Status**: pending
**Depends on**: 001, 002
**Retry count**: 0

## Description
Attach the source `recipe_id` to every ingredient entry collected in `collectIngredientsFromMealPlan()`, then write the aggregated `recipe_ids` array to the `GroceryItem` when creating records in both `generate()` and `regenerate()`.

## Context
- Service: `app/Services/GroceryListGenerator.php`
- `collectIngredientsFromMealPlan()` iterates `$mealAssignments`, and for each `$recipeIngredient` pushes an ingredient array. Add `'recipe_id' => $recipe->id` to that array.
- After `aggregateIngredients()`, each result item will carry `recipe_ids` (from Task 002 — always present, possibly empty).
- In `generate()`, pass `'recipe_ids' => $ingredient['recipe_ids'] ?? null` when calling `GroceryItem::create()`. Empty arrays from the aggregator should be fine to store as empty JSON arrays (the view gates on non-empty), but `?? null` is a safe fallback if the key is ever missing.
- In `regenerate()`, same — pass `recipe_ids` in the `GroceryItem::create()` call for newly added generated items. **Do not** touch `recipe_ids` on existing manual items, soft-deleted items, or edited-generated items — regenerate leaves those alone, which is the correct behavior.

### Preview / diff paths — no changes needed but be aware
- `getIngredientPreview()` (line 29) explicitly rebuilds output arrays with only 5 keys and will silently drop `recipe_ids`. That is acceptable — the preview UI does not need recipe linkage.
- `getCategoryItemCounts()` (line 229) only counts per category and does not care about `recipe_ids`.
- `Show::calculateRegenerateDiff()` (in the Livewire component) compares items by lowercased `name` only — it will continue to work unchanged after this task.
- `ServingSizeScaler::scaleIngredients()` preserves unknown array keys, so `recipe_id` survives the scaling pass in `collectIngredientsFromMealPlan()`.

## Requirements (Test Descriptions)
- [ ] `it stores recipe_ids on generated grocery items when generating from a meal plan`
- [ ] `it stores multiple recipe_ids when the same ingredient appears in multiple recipes (aggregation merges them)`
- [ ] `it stores recipe_ids on newly added items during regeneration`
- [ ] `it leaves recipe_ids untouched on existing manual items during regeneration`
- [ ] `it leaves recipe_ids untouched on edited generated items during regeneration`
- [ ] `it does not break getIngredientPreview() — preview output still contains only the 5 expected keys (name, quantity, unit, category, category_label)`

## Acceptance Criteria
- All requirements have passing tests
- Existing `GroceryListGenerator` tests (unit + feature) continue to pass unchanged
- Generated `GroceryItem` records in the database have a non-empty `recipe_ids` array when created from a meal plan that has recipes with ingredients
- `getIngredientPreview()` output signature remains exactly as documented in its PHPDoc

## Implementation Notes
(Left blank — filled in by programmer during implementation)
