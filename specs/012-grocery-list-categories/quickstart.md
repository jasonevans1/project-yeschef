# Quickstart: Grocery List Category Filtering and Auto-Categorization

**Feature Branch**: `012-grocery-list-categories`

## Local Development Setup

```bash
# Ensure DDEV is running
ddev start

# Run all migrations (includes new excluded_categories columns)
php artisan migrate

# Start all dev services
composer dev
```

## Running Tests for This Feature

```bash
# Unit tests (GroceryListGenerator service)
php artisan test tests/Unit/GroceryListGeneratorTest.php

# Feature tests (new: category exclusion flow)
php artisan test tests/Feature/GroceryLists/CategoryExclusionTest.php

# Feature tests (new: template-based categorization)
php artisan test tests/Feature/GroceryLists/CategoryLookupTest.php

# Feature tests (existing: ensure no regressions)
php artisan test tests/Feature/GroceryLists/GenerateGroceryListTest.php
php artisan test tests/Feature/GroceryLists/RegenerateWithManualChangesTest.php

# Full test suite
composer test
```

## Key Files Changed

| File | Change Type |
|---|---|
| `database/migrations/xxxx_add_excluded_categories_to_grocery_lists_table.php` | New migration |
| `database/migrations/xxxx_add_grocery_category_exclusions_to_users_table.php` | New migration |
| `app/Models/GroceryList.php` | Add `excluded_categories` fillable + cast |
| `app/Models/User.php` | Add `grocery_category_exclusions` fillable + cast |
| `app/Services/GroceryListGenerator.php` | Template lookup + exclusion filtering |
| `app/Livewire/GroceryLists/Generate.php` | Exclusion UI + save preferences |
| `app/Livewire/GroceryLists/Show.php` | Activate regenerate modal + exclusion notice |
| `resources/views/livewire/grocery-lists/generate.blade.php` | Category exclusion checkboxes |
| `resources/views/livewire/grocery-lists/show.blade.php` | Exclusion notice + regenerate modal |

## User Flow (Manual Verification)

1. **Assign recipes to a meal plan** that include Pantry-category ingredients (e.g., flour, olive oil).
2. **Navigate to Generate Grocery List** for that meal plan.
3. **Observe** the category exclusion panel showing categories with item counts.
4. **Check "Pantry"** in the exclusion list.
5. **Optionally click "Save as default"** to persist the preference.
6. **Click "Generate List"** — the resulting list should contain zero Pantry items.
7. **View the generated list** — a banner should indicate "Pantry items were excluded from this list."
8. **Regenerate from the banner** — the Pantry exclusion should be pre-checked in the modal.

## Template-Based Category Verification

1. **Add a manual grocery item** "Cumin" to any grocery list, categorized as "Pantry" — this creates a `UserItemTemplate` record.
2. **Create a recipe** with "Cumin" as an ingredient, where the ingredient has no category (Other).
3. **Generate a grocery list** from a meal plan containing that recipe.
4. **Verify** the generated "Cumin" item appears under "Pantry" (not "Other").
