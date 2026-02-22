# Data Model: Grocery List Category Filtering and Auto-Categorization

**Feature Branch**: `012-grocery-list-categories`
**Date**: 2026-02-22

---

## Schema Changes

### 1. `grocery_lists` table — Add `excluded_categories`

**New column**: `excluded_categories` (JSON, nullable, default null)

Stores the array of `IngredientCategory` string values that were excluded at the time this list was generated or last regenerated. Null means no categories were excluded (or the list was generated before this feature existed).

**Example stored value**:
```json
["pantry", "dairy"]
```

**Migration name**: `add_excluded_categories_to_grocery_lists_table`

---

### 2. `users` table — Add `grocery_category_exclusions`

**New column**: `grocery_category_exclusions` (JSON, nullable, default null)

Stores the user's saved default exclusion preferences. Applied as pre-selected defaults on the grocery list generation page. Null means no saved preferences.

**Example stored value**:
```json
["pantry"]
```

**Migration name**: `add_grocery_category_exclusions_to_users_table`

---

## Model Changes

### `GroceryList` model

Add `excluded_categories` to `$fillable` and add cast:

```php
// In $fillable:
'excluded_categories',

// In casts():
'excluded_categories' => 'array',
```

The `excluded_categories` array contains `IngredientCategory` string values (e.g., `['pantry', 'dairy']`). The model does not cast individual array elements to enums — the generator handles enum conversion at write time and the component/view handles label display.

---

### `User` model

Add `grocery_category_exclusions` to `$fillable` and add cast:

```php
// In $fillable:
'grocery_category_exclusions',

// In casts():
'grocery_category_exclusions' => 'array',
```

---

## Service Changes

### `GroceryListGenerator`

**New public method**:
```php
public function getCategoryItemCounts(MealPlan $mealPlan, int $userId): array
```
Returns `[string $categoryValue => int $itemCount]` for all categories present in the meal plan, after applying template-based category resolution. Used by the Generate component to display category counts in the UI.

**Updated method signatures** (backwards-compatible via default `[]`):
```php
public function generate(MealPlan $mealPlan, array $excludedCategories = []): GroceryList
public function regenerate(GroceryList $groceryList, array $excludedCategories = []): GroceryList
```

**New private method**:
```php
private function resolveIngredientCategory(
    string $name,
    IngredientCategory $currentCategory,
    int $userId
): IngredientCategory
```
Returns `$currentCategory` unless it is `IngredientCategory::OTHER`, in which case:
1. Query `UserItemTemplate` where `user_id = $userId` and `LOWER(name) = LOWER($name)`.
2. If found, return template's `category`.
3. Otherwise query `CommonItemTemplate` where `LOWER(name) = LOWER($name)`.
4. If found, return template's `category`.
5. Otherwise return `IngredientCategory::OTHER`.

**Updated `collectIngredientsFromMealPlan`** (private, signature update):
```php
private function collectIngredientsFromMealPlan(MealPlan $mealPlan, int $userId): Collection
```
Applies `resolveIngredientCategory()` to each ingredient before returning the collection.

**Exclusion filtering**: Both `generate()` and `regenerate()` skip creating a `GroceryItem` when the ingredient's resolved category value is in the `$excludedCategories` array. The `excluded_categories` column on the resulting `GroceryList` is set to the passed `$excludedCategories` array (or null if empty).

---

## Entity Relationships (unchanged)

```
User (1) ──────────────── (*) GroceryList
                                 │
MealPlan (1) ─────────────────── │
                                 │
                           (*) GroceryItem
                                 │
                    IngredientCategory (enum)
```

### UserItemTemplate
```
User (1) ──── (*) UserItemTemplate
  Fields: id, user_id, name, category (IngredientCategory), unit (MeasurementUnit?), default_quantity, usage_count, last_used_at
  Unique: (user_id, name)
```

### CommonItemTemplate
```
No user relationship (global)
  Fields: id, name, category (IngredientCategory), unit (MeasurementUnit?), default_quantity, search_keywords, usage_count
  Unique: name
```

---

## State Transitions

### Grocery List Generation with Exclusions

```
User visits Generate page
  → mount(): load user preferences → pre-select excluded categories
  → mount(): call getCategoryItemCounts() → show counts per category

User toggles categories + optionally saves preferences
  → savePreferences(): update users.grocery_category_exclusions

User clicks Generate/Regenerate
  → generate(mealPlan, excludedCategories):
      for each ingredient in mealPlan:
        resolveIngredientCategory(name, category, userId)   ← template lookup
        if category in excludedCategories → skip
        else → create GroceryItem
      set groceryList.excluded_categories = excludedCategories
```

### List View Exclusion Notice

```
User views GroceryList
  → groceryList.excluded_categories is not null and not empty
    → show dismissible banner: "Pantry items were excluded from this list. [Regenerate]"
  → User clicks Regenerate from banner
    → showRegenerateConfirmation() activates modal
    → modal pre-populates excluded categories from groceryList.excluded_categories
    → User adjusts and confirms → regenerate(groceryList, newExcludedCategories)
```
