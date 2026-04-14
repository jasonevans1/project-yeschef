# Plan: Recipe Link on Grocery List Item

## Created
2026-04-09

## Status
completed

## Objective
Display the source recipe name(s) as clickable link(s) on each generated grocery list item when the list was produced from a meal plan. This helps users trace which recipe requires a given ingredient.

## Scope

### In Scope
- Store recipe ID(s) on each generated `GroceryItem` as a JSON array (`recipe_ids`)
- Track and merge recipe IDs through the `IngredientAggregator` (an ingredient aggregated from multiple recipes carries all contributing recipe IDs)
- Thread recipe IDs from `GroceryListGenerator` (`generate` and `regenerate`) into `GroceryItem` creation
- Display recipe name(s) as link(s) in `grocery-category.blade.php` — only for generated items on a meal-plan-linked grocery list
- Efficiently eager-load recipe names in the `Show` Livewire component

### Out of Scope
- Showing recipe links on the shared/public grocery list view
- Modifying the ingredient preview before generation
- Changing any editing, deletion, or regeneration logic (beyond what is needed to carry `recipe_ids`)

## Success Criteria
- [ ] `grocery_items` table has a nullable `recipe_ids` JSON column
- [ ] `GroceryItem` model casts `recipe_ids` as array, includes it in `$fillable`
- [ ] `IngredientAggregator::aggregate()` preserves and merges recipe IDs from input items
- [ ] `GroceryListGenerator::generate()` and `regenerate()` store recipe IDs on created `GroceryItem` records
- [ ] Generated items linked to a meal-plan grocery list display recipe name link(s) below the item name
- [ ] Items from multiple recipes show all contributing recipe links
- [ ] Manual items and standalone list items show no recipe links
- [ ] All tests passing

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Migration + GroceryItem model | - | completed |
| 002 | Update IngredientAggregator to track recipe IDs | - | completed |
| 003 | Update GroceryListGenerator to store recipe IDs | 001, 002 | completed |
| 004 | Update Show component and grocery-category view | 001, 003 | completed |

## Architecture Notes
- `recipe_ids` is stored as a JSON array of integer IDs on `grocery_items` — simple and avoids a pivot table
- Aggregated items may carry IDs from multiple recipes; the array captures all contributors
- Recipes are batch-loaded in the `Show` component's `render()` method (not the computed property) via `Recipe::whereIn('id', ...)` to avoid N+1 queries, then filtered through `$user->can('view', $recipe)` to respect the `RecipePolicy`
- The `grocery-category` Blade component receives a `$recipesById` keyed collection as a new prop (defaults to `collect()` so other callers — if added later — don't break)
- Only generated items with non-empty `recipe_ids` on a meal-plan-linked list show the recipe link
- The `IngredientAggregator` uses singular `recipe_id` as the input key and always emits plural `recipe_ids` (array) on output — every internal code path must normalize this

## Risks & Mitigations
- **Stale recipe IDs after recipe deletion**: Recipe links silently omit deleted recipes (look up by ID returns null) — filtered gracefully in the view
- **IngredientAggregator API change**: The `recipe_id` key is optional on input items so existing callers (preview/diff) continue to work. Existing aggregator tests assert specific keys only — unknown `recipe_ids` key will not break them
- **Shared grocery list recipients lacking recipe view permission**: When a list is shared with a user via `ContentShare`, the recipient may not have permission to view the source recipe. We filter `$recipesById` through `auth()->user()->can('view', $recipe)` so unviewable recipes are silently omitted from links, avoiding 403s
- **Multiple aggregator output paths**: `IngredientAggregator` has three return paths (single-item group, single-unit subgroup, multi-item aggregated). ALL THREE must emit `recipe_ids` consistently — this is called out explicitly in Task 002 to prevent a partial fix
