# Livewire Component Contracts

**Feature Branch**: `012-grocery-list-categories`
**Date**: 2026-02-22

This application uses Livewire 3 (not REST APIs). Contracts are expressed as Livewire component interfaces.

---

## Component: `GroceryLists\Generate`

**Route**: `GET /grocery-lists/generate/{mealPlan}` (existing)
**File**: `app/Livewire/GroceryLists/Generate.php`

### New Public Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `$excludedCategories` | `array` | `[]` | Currently selected category values to exclude (e.g., `['pantry', 'dairy']`) |
| `$categoryItemCounts` | `array` | `[]` | Map of category value → item count from the meal plan preview |
| `$savedPreferences` | `array` | `[]` | User's saved exclusion preferences, loaded in mount |

### Updated `mount(MealPlan $mealPlan)`

1. Existing: authorize, set `$existingList`, `$recipeCount`, `$estimatedItemCount`.
2. **New**: Load `auth()->user()->grocery_category_exclusions ?? []` → `$this->savedPreferences`.
3. **New**: Set `$this->excludedCategories = $this->savedPreferences`.
4. **New**: If `$recipeCount > 0`: compute `$this->categoryItemCounts = app(GroceryListGenerator::class)->getCategoryItemCounts($mealPlan, auth()->id())`.
5. **New**: If `$existingList`: pre-populate `$excludedCategories` from `$existingList->excluded_categories ?? $savedPreferences`.

### New Actions

**`savePreferences(): void`**
- Validation: `excluded_categories.*` must each be a valid `IngredientCategory` value.
- Updates: `auth()->user()->update(['grocery_category_exclusions' => $this->excludedCategories])`.
- Dispatches: browser event `preferences-saved` (for flash/toast).

**`clearPreferences(): void`**
- Updates: `auth()->user()->update(['grocery_category_exclusions' => null])`.
- Sets: `$this->savedPreferences = []`.

### Updated `generate(): RedirectResponse`

1. Existing: authorize, resolve generator.
2. **New**: Convert `$this->excludedCategories` strings to `IngredientCategory` enum cases.
3. **New**: Pass enum array to `$generator->generate($mealPlan, $excludedCategories)` or `$generator->regenerate($existingList, $excludedCategories)`.
4. Existing: flash + redirect.

### View Bindings (`generate.blade.php`)

```
wire:model="excludedCategories"          ← array of selected category values
wire:click="savePreferences"             ← save current selection as defaults
wire:click="clearPreferences"            ← clear saved defaults
wire:click="generate"                    ← generate/regenerate the list (existing)
wire:click="cancel"                      ← cancel (existing)
```

---

## Component: `GroceryLists\Show`

**Route**: `GET /grocery-lists/{groceryList}` (existing)
**File**: `app/Livewire/GroceryLists/Show.php`

### New Public Properties

| Property | Type | Default | Description |
|---|---|---|---|
| `$regenerateExcludedCategories` | `array` | `[]` | Selected exclusion categories for the pending regeneration |

### Updated `mount(GroceryList $groceryList)`

No change to existing mount — `$groceryList` is already loaded.

### Updated `showRegenerateConfirmation()`

1. Existing: authorize, check meal plan linked, compute diff.
2. **New**: Set `$this->regenerateExcludedCategories = $this->groceryList->excluded_categories ?? []`.
3. Existing: set `$this->showRegenerateConfirm = true`.

### Updated `regenerate()`

1. Existing: authorize, check meal plan linked.
2. **New**: Convert `$this->regenerateExcludedCategories` to `IngredientCategory` enum cases.
3. **New**: Call `$generator->regenerate($this->groceryList, $excludedCategories)` (with enum array).
4. Existing: flash + reset state.

### View Bindings (`show.blade.php`)

```
wire:model="regenerateExcludedCategories"    ← exclusion selection in regenerate modal
wire:click="showRegenerateConfirmation"      ← open regenerate modal (replaces wire:confirm)
wire:click="cancelRegenerate"               ← cancel modal (existing method)
wire:click="regenerate"                      ← confirm regeneration (existing)
```

**Excluded categories notice** (new, conditional on `$groceryList->excluded_categories`):

```blade
@if (!empty($groceryList->excluded_categories))
    {{-- Dismissible callout listing excluded categories, with link to regenerate --}}
@endif
```

---

## Service Contract: `GroceryListGenerator`

### Method Signatures (updated)

```php
/**
 * Generate a new grocery list from a meal plan.
 *
 * @param  MealPlan  $mealPlan
 * @param  IngredientCategory[]  $excludedCategories  Category cases to skip. Empty = include all.
 * @return GroceryList
 */
public function generate(MealPlan $mealPlan, array $excludedCategories = []): GroceryList;

/**
 * Regenerate an existing meal-plan-linked grocery list.
 *
 * @param  GroceryList  $groceryList  Must have a meal_plan_id.
 * @param  IngredientCategory[]  $excludedCategories  Category cases to skip. Empty = include all.
 * @return GroceryList
 * @throws \InvalidArgumentException  If $groceryList has no meal_plan_id.
 */
public function regenerate(GroceryList $groceryList, array $excludedCategories = []): GroceryList;

/**
 * Preview the number of aggregated items per category for a meal plan.
 * Applies template-based category resolution (UserItemTemplate → CommonItemTemplate).
 *
 * @param  MealPlan  $mealPlan
 * @param  int  $userId  The user for whom templates are looked up.
 * @return array<string, int>  Keys are IngredientCategory string values. Values are item counts.
 */
public function getCategoryItemCounts(MealPlan $mealPlan, int $userId): array;
```

### Category Resolution Priority

```
Ingredient.category == OTHER?
  YES → lookup UserItemTemplate by name (case-insensitive, user_id scoped)
          Found? → use template's category
          NOT found → lookup CommonItemTemplate by name (case-insensitive, global)
                        Found? → use template's category
                        NOT found → use OTHER
  NO  → use Ingredient.category (no template lookup)
```

### Exclusion Filtering

```
For each aggregated ingredient item:
  if resolvedCategory is in $excludedCategories → skip (no GroceryItem created)
  else → create GroceryItem as normal

After item creation:
  groceryList->excluded_categories = array_map(fn(e) => e->value, $excludedCategories)
  or null if $excludedCategories is empty
```
