# Research: Grocery List Category Filtering and Auto-Categorization

**Feature Branch**: `012-grocery-list-categories`
**Date**: 2026-02-22

---

## Decision 1: Category Exclusion Preference Storage

**Decision**: Add a `grocery_category_exclusions` nullable JSON column to the `users` table.

**Rationale**: The users table has no existing settings or preferences mechanism — only core auth columns plus two-factor fields. Adding a JSON column is the lightest-touch approach that matches the Laravel/Eloquent pattern already used elsewhere in the app (other models use JSON casts). A separate `user_grocery_settings` table would be over-engineered for a single setting.

**Alternatives considered**:
- Separate `user_preferences` table: No existing precedent; over-engineering for one setting.
- Separate `user_grocery_settings` table: Same concern. Adds migration + model + relationship for one column.
- Store in session only: Fails FR-010 (must persist as a saved preference).

---

## Decision 2: Excluded Categories per Grocery List

**Decision**: Add an `excluded_categories` nullable JSON column to the `grocery_lists` table.

**Rationale**: The spec requires the list view to show which categories were excluded at generation time (FR-012). Storing them on the grocery list record is the natural, self-contained approach — the list carries its own generation history. The column is nullable so standalone lists and lists generated without exclusions store `null`.

**Alternatives considered**:
- Derive from missing items: Impossible — there is no record of items that were not added.
- Store in a generation log table: Excessive infrastructure for a single JSON array.

---

## Decision 3: Template-Based Category Lookup Strategy

**Decision**: Add a private `resolveIngredientCategory(string $name, IngredientCategory $category, int $userId): IngredientCategory` method to `GroceryListGenerator`. This method returns the original category unless it is `OTHER`, in which case it checks `UserItemTemplate` first (by case-insensitive name), then `CommonItemTemplate`, and returns the template's category if found.

**Rationale**: The lookup is narrow in scope (name match, category retrieval) and fits naturally within the generator. Both `generate()` and `regenerate()` already have access to `user_id` via model relationships (`$mealPlan->user_id`, `$groceryList->user_id`). The two-tier lookup (user templates → common templates) mirrors the existing `ItemAutoCompleteService` strategy, keeping behavior consistent.

**Alternatives considered**:
- Create a separate `IngredientCategorizationService`: Over-engineering; the lookup logic is simple enough to live in the generator.
- Look up via `ItemAutoCompleteService`: That service is query-heavy and returns suggestion arrays, not just categories — it would need to be adapted unnecessarily.

---

## Decision 4: Category Count Preview in Generate Component

**Decision**: Add a public `getCategoryItemCounts(MealPlan $mealPlan, int $userId): array` method to `GroceryListGenerator`. The Generate Livewire component calls this in `mount()` to populate the exclusion checkboxes with per-category item counts.

**Rationale**: This reuses the existing ingredient collection, template lookup, and aggregation pipeline. Making it a dedicated public method keeps the component lean (no business logic in the component) and makes the counts independently testable.

**Method signature**:
```php
public function getCategoryItemCounts(MealPlan $mealPlan, int $userId): array
// Returns: [IngredientCategory->value => int]
// Example: ['produce' => 4, 'pantry' => 6, 'dairy' => 2]
```

**Alternatives considered**:
- Compute counts inside the component: Violates single-responsibility; duplicates generator logic.
- Count via raw DB query on ingredients: Bypasses the aggregation and template lookup steps, producing inaccurate counts.

---

## Decision 5: Regeneration with Exclusions in Show Component

**Decision**: Wire the Show component's existing (but view-unused) `showRegenerateConfirmation()` method and `showRegenerateConfirm` property into a Flux modal. The modal includes category exclusion checkboxes pre-populated from the list's `excluded_categories`. The `regenerate()` method in Show accepts the selected exclusion categories and passes them to `GroceryListGenerator::regenerate()`.

**Rationale**: The `showRegenerateConfirmation()` and `regenerateDiff` infrastructure already exists in `Show.php` but the view currently bypasses it with a `wire:confirm` native browser dialog. Wiring a Flux modal here removes dead code, enables checkboxes, and keeps the exclusion UX consistent with the Generate component.

**Existing unused properties in Show that will be activated**:
- `public bool $showRegenerateConfirm`
- `public array $regenerateDiff`
- `showRegenerateConfirmation()` method
- `cancelRegenerate()` method

**Alternatives considered**:
- Keep `wire:confirm` native dialog and add exclusions: Native dialogs don't support checkboxes.
- New route/page for regeneration with exclusions: Breaks the "all on one page" model of the existing Show component.

---

## Decision 6: Modifications to GroceryListGenerator Public API

**Decision**: Update `generate()` and `regenerate()` method signatures to accept excluded categories and apply template-based category resolution:

```php
public function generate(MealPlan $mealPlan, array $excludedCategories = []): GroceryList
public function regenerate(GroceryList $groceryList, array $excludedCategories = []): GroceryList
public function getCategoryItemCounts(MealPlan $mealPlan, int $userId): array
```

The `$excludedCategories` parameter is an array of `IngredientCategory` enum cases. Items whose final resolved category (after template lookup) is in `$excludedCategories` are skipped during item creation.

**Rationale**: The existing method signatures accept only model objects. Adding the optional `$excludedCategories` parameter is backwards-compatible (defaults to `[]`) and preserves all existing tests without modification.

---

## Existing Test Coverage Gaps (Relevant to This Feature)

- `GroceryListGenerator::generate()` with category exclusions: **not tested** — new tests required.
- `GroceryListGenerator::regenerate()` with category exclusions: **not tested** — new tests required.
- Template-based category lookup (UserItemTemplate / CommonItemTemplate fallback): **not tested** — new tests required.
- Generate Livewire component (`Generate.php`): **not directly tested** — feature tests exist for the service but not the Livewire component. New component feature tests required.
- Excluded categories notice on Show component: **not tested** — new feature tests required.
- Category exclusion preference save/clear on users table: **not tested** — new feature tests required.
