# Task 002: Update IngredientAggregator to Track Recipe IDs

**Status**: pending
**Depends on**: none
**Retry count**: 0

## Description
Extend `IngredientAggregator::aggregate()` to accept an optional `recipe_id` key on each input ingredient array and propagate/merge those IDs through the aggregation process. When multiple items with the same name are combined, all contributing recipe IDs are merged into a single deduplicated array.

## Context
- Service: `app/Services/IngredientAggregator.php`
- Input items are plain PHP arrays with keys: `name`, `quantity`, `unit`, `category`
- The new optional input key is `recipe_id` (a single integer — the ID of the recipe this ingredient came from)
- **Output items always have a `recipe_ids` key** (array of integers, possibly empty `[]` when no input items had `recipe_id`). The singular `recipe_id` key is an INPUT convenience; it must be normalized to the plural `recipe_ids` array on every output path.
- Callers that don't pass `recipe_id` continue to work unchanged — output will contain `recipe_ids => []` but no consumers rely on the absence of this key.

### Code paths that MUST be updated (all output paths)
The `aggregate()` pipeline has several return paths, and EVERY ONE must emit `recipe_ids` consistently:

1. **`aggregateGroup()` single-item group** (currently `if ($group->count() === 1) { return $group; }`): The returned item still carries the raw input `recipe_id` key and no `recipe_ids` key. Must normalize: convert `recipe_id => int|null` into `recipe_ids => [int] | []` and unset `recipe_id`.
2. **`aggregateGroup()` single compatible-unit subgroup** (`if ($compatibleItems->count() === 1) { $result->push($compatibleItems->first()); }`): Same normalization required.
3. **`aggregateCompatibleItems()`**: The hardcoded return array (currently `['name', 'quantity', 'unit', 'category']`) must ALSO include `'recipe_ids' => $merged`, where `$merged` is the deduplicated array of all `recipe_id` values from `$items` (filter nulls, unique, re-index).

Consider extracting a small private helper `normalizeRecipeIds(array|Collection $items): array` to avoid duplicating the filter/unique/values logic across the three call sites.

## Requirements (Test Descriptions)
- [ ] `it passes through recipe_id as recipe_ids array for a single-item group`
- [ ] `it passes through recipe_id as recipe_ids array for a single compatible-unit subgroup within a multi-item group`
- [ ] `it merges recipe_ids from multiple compatible items into one array`
- [ ] `it merges recipe_ids across multiple unit-compatible subgroups of the same name (e.g., milk in cups + milk in grams becomes two output rows, each with its own merged recipe_ids)`
- [ ] `it deduplicates recipe_ids when the same recipe appears multiple times`
- [ ] `it produces an empty recipe_ids array when no recipe_id keys are present in the input`
- [ ] `it handles items without a recipe_id key alongside items that have one`
- [ ] `it strips the singular recipe_id input key from the output (only recipe_ids should remain)`

## Acceptance Criteria
- All requirements have passing tests
- Existing `IngredientAggregator` tests continue to pass unchanged (they should — they don't assert against unknown keys)
- **Every** output item includes a `recipe_ids` key (empty array `[]` when no IDs), regardless of which internal code path produced it
- No output item retains the singular `recipe_id` input key

## Implementation Notes
(Left blank — filled in by programmer during implementation)
