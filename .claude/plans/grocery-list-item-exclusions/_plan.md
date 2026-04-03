# Plan: Grocery List Item-Level Exclusions (Pantry)

## Created
2026-04-02

## Status
completed

## Objective
Allow users to preview individual ingredients before generating a grocery list from a meal plan, and exclude specific items (e.g., salt, black pepper) they already have at home. Excluded items are saved persistently as a "pantry" list that auto-selects those items for exclusion on future generations.

## Scope

### In Scope
- Ingredient preview panel on the Generate page showing all items that would be generated, grouped by category
- Per-item checkboxes to exclude specific ingredients
- Pantry persistence: "Save checked as always have" saves excluded ingredient names to `users.pantry_items`
- Pre-checking pantry items when loading the Generate page
- Updating `GroceryListGenerator::generate()` and `regenerate()` to filter by excluded ingredient names
- Storing `excluded_ingredients` on `GroceryList` so regeneration (from both Generate and Show pages) respects prior exclusions
- Updating the Show page's `regenerate()` to pass the stored `excluded_ingredients`

### Out of Scope
- A dedicated Pantry management page (pantry is managed inline on the Generate page)
- Showing pantry items as greyed-out on the grocery list (items are completely omitted)
- Partial quantity tracking ("I have some but not all")
- Pantry items appear in recipe ingredient lists (no change to recipe views)

## Success Criteria
- [ ] Ingredient preview panel shows all items that would be generated, grouped by category
- [ ] Pantry items are pre-checked as excluded when the Generate page loads
- [ ] Generating/regenerating a list omits checked ingredients entirely
- [ ] "Save checked as always have" persists exclusions to user pantry
- [ ] Show page's inline regeneration also respects stored `excluded_ingredients`
- [ ] All tests passing

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | DB migrations + model casts | — | completed |
| 002 | GroceryListGenerator: preview method + exclusion filtering | 001 | completed |
| 003 | Generate component + view: preview UI + pantry management | 001, 002 | completed |
| 004 | Show component: pass excluded_ingredients on regenerate | 002 | completed |

## Architecture Notes

### Model Cast Patterns (important: they differ)
- `User` model uses the `casts()` **method** for cast definitions
- `GroceryList` model uses the `protected $casts` **property** for cast definitions
- Each model must follow its own existing pattern

### Data Storage Pattern
Mirrors the existing `grocery_category_exclusions` / `excluded_categories` pair:
- `users.pantry_items` (JSON) — user's persistent list of ingredient names to always exclude
- `grocery_lists.excluded_ingredients` (JSON) — which ingredients were excluded for this specific list

### Precedence Logic (in Generate component mount)
Existing list's `excluded_ingredients` overrides `pantry_items` — same as category exclusion precedence. This prevents silently changing exclusions on an already-built list.

### Ingredient Name Casing
Ingredient names are stored lowercase by `Ingredient::setNameAttribute`, but **read back as ucfirst** via `Ingredient::getNameAttribute` (returns `ucfirst($value)`). This means names flowing through `collectIngredientsFromMealPlan` are title-cased (e.g., "Salt"). Pantry/excluded_ingredients arrays store **lowercase** names. Checkboxes use `strtolower($item['name'])` as the value. All comparisons in the generator **must** use `strtolower()` defensively to bridge the ucfirst accessor.

### Filtering in Generator
Two independent filters applied before item creation:
1. Category exclusion (existing, checks `$excludedValues`)
2. Ingredient name exclusion (new, checks `$excludedIngredients` with `strtolower`)

Both are additive. In `generate()`, category filter runs at the outer loop (by category), ingredient filter runs at the **inner** loop (by ingredient name). In `regenerate()`, ingredient filter runs alongside existing skip checks in the single loop.

### Livewire Serialization
`getIngredientPreview()` returns data that becomes a Livewire public property (`$ingredientPreview`). Enum instances are NOT JSON-serializable in plain arrays. The method must return plain arrays with string values for `category` (enum value) and `unit` (enum value or null), plus a `category_label` field.

### Extract Shared Pipeline Method (within GroceryListGenerator)
`getCategoryItemCounts()` and `getIngredientPreview()` both run collect → scale → aggregate. Extract a private `getAggregatedIngredients(MealPlan, int): Collection` to eliminate duplication. No public API change.

## Dependency Graph
```
001 ──► 002 ──┬──► 003
              └──► 004
```
Tasks 003 and 004 can run in parallel after 002 completes.

## Risks & Mitigations
- **Large ingredient lists**: `$ingredientPreview` is a Livewire public property serialized on every request. For a week's meal plan (~30–60 ingredients), this is fine. Use `#[Computed]` caching if it becomes a concern later.
- **Naming mismatches**: Names must be consistently lowercased end-to-end. Verified: `setNameAttribute` lowercases on save; generator reads the attribute; checkboxes store lowercase; comparisons use `strtolower()`.
