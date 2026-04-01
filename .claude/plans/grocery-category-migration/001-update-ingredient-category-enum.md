# Task 001: Update IngredientCategory Enum

**Status**: complete
**Depends on**: none
**Retry count**: 0

## Description
Replace the 10-case `IngredientCategory` enum with the full 19-case set. Update the `label()` method to produce human-readable strings for multi-word hyphenated values (e.g. `soups-and-canned-goods` → `Soups And Canned Goods`). Remove the `PANTRY` case; all other existing cases are retained.

## Context
- File to modify: `app/Enums/IngredientCategory.php`
- Current values are single lowercase words (e.g. `'produce'`, `'dairy'`)
- New multi-word values use lowercase hyphens (e.g. `'soups-and-canned-goods'`)
- `label()` currently does `ucfirst($this->value)` — must handle hyphens
- The enum is used throughout via `IngredientCategory::cases()` so adding cases is safe; removing `PANTRY` will cause type errors anywhere `PANTRY` is referenced — search the codebase and remove/replace those references

## New Cases
```
BAKERY              = 'bakery'
BREAKFAST           = 'breakfast'
BEVERAGES           = 'beverages'
CONDIMENTS_AND_DRESSINGS = 'condiments-and-dressings'
COOKING_AND_BAKING  = 'cooking-and-baking'
DAIRY               = 'dairy'
DELI                = 'deli'
FROZEN              = 'frozen'
GRAINS_AND_PASTA    = 'grains-and-pasta'
HEALTH_AND_PERSONAL_CARE = 'health-and-personal-care'
HOUSEHOLD_AND_CLEANING   = 'household-and-cleaning'
MEAT                = 'meat'
OTHER               = 'other'
PET_SUPPLIES        = 'pet-supplies'
PRODUCE             = 'produce'
SEAFOOD             = 'seafood'
SNACKS              = 'snacks'
SOUPS_AND_CANNED_GOODS   = 'soups-and-canned-goods'
WINE_BEER_AND_SPIRITS    = 'wine-beer-and-spirits'
```

## Requirements (Test Descriptions)
- [x] `it has exactly 19 cases`
- [x] `it does not have a PANTRY case`
- [x] `it has a SOUPS_AND_CANNED_GOODS case with value soups-and-canned-goods`
- [x] `it has a WINE_BEER_AND_SPIRITS case with value wine-beer-and-spirits`
- [x] `it returns a human readable label for single word values`
- [x] `it returns a human readable label for hyphenated multi-word values`

## Files Requiring PANTRY Reference Updates

When removing the `PANTRY` case, the following files contain direct `IngredientCategory::PANTRY` references that must be updated. Replace with `IngredientCategory::SOUPS_AND_CANNED_GOODS` (or redistribute to more appropriate new categories where it makes sense, e.g., `COOKING_AND_BAKING` for flour/sugar/oil keywords):

### Production Code
- `app/Livewire/Recipes/Edit.php:205` -- `IngredientCategory::PANTRY->value` in `guessIngredientCategory()` keyword map. Replace key with `COOKING_AND_BAKING` (or split keywords across new categories: flour/sugar/oil to `COOKING_AND_BAKING`, rice/pasta/beans to `GRAINS_AND_PASTA`, sauce to `SOUPS_AND_CANNED_GOODS`, spices to `COOKING_AND_BAKING`).
- `app/Livewire/Recipes/Create.php:164` -- same pattern, same fix.
- `database/seeders/DevelopmentSeeder.php:224` -- replace `IngredientCategory::PANTRY->value` with `IngredientCategory::SOUPS_AND_CANNED_GOODS->value` (or redistribute items).
- `database/seeders/CommonItemTemplateSeeder.php` -- ~48 rows with `'category' => 'pantry'`. Replace with appropriate new category strings (e.g., `'grains-and-pasta'` for pasta/rice/flour, `'condiments-and-dressings'` for ketchup/mustard/mayo, `'snacks'` for chips/crackers/popcorn, `'soups-and-canned-goods'` for canned items/broth, `'cooking-and-baking'` for oils/spices/baking items, `'breakfast'` for cereal/oatmeal).
- `database/seeders/RecipeSeeder.php` -- ~38 rows with `'category' => 'pantry'`. Same redistribution approach.

### Factories (will cause random test failures if not updated)
- `database/factories/GroceryItemFactory.php:24` -- hardcoded `randomElement` array includes `'pantry'`. Replace the entire array with `IngredientCategory::cases()` to stay in sync (like `UserItemTemplateFactory` already does), or update the string list to the new 19 values.
- `database/factories/IngredientFactory.php:21` -- same issue, same fix.

### Test Files (replace `IngredientCategory::PANTRY` with `IngredientCategory::SOUPS_AND_CANNED_GOODS`)
- `tests/Unit/IngredientAggregatorTest.php` (~8 references)
- `tests/Unit/GroceryListGeneratorTest.php` (~20+ references)
- `tests/Feature/GroceryLists/CategoryLookupTest.php` (~4 references)
- `tests/Feature/GroceryLists/CategoryExclusionTest.php` (~6 references)
- `tests/Feature/GroceryLists/StandaloneListOperationsTest.php` (~2 references)
- `tests/Feature/GroceryLists/ExportTextTest.php` (~3 references)
- `tests/Feature/ItemTemplates/CreateUserTemplateTest.php` (~1 reference)
- `tests/Feature/ItemTemplates/ManageTemplatesTest.php` (~2 references)
- `tests/Feature/GroceryLists/ViewGroceryListTest.php` (~5 references)
- `tests/Feature/GroceryLists/GenerateGroceryListTest.php` (~1 reference)
- `tests/Feature/GroceryLists/DeleteItemTest.php` (~2 references)
- `tests/Feature/GroceryLists/ScaledQuantitiesTest.php` (~4 references)
- `tests/Feature/GroceryLists/RegenerateWithManualChangesTest.php` (~2 references)
- `tests/Feature/GroceryLists/EditItemTest.php` (~4 references)

### Blade Templates (replace `ucfirst($category->value)` with `$category->label()`)
These use `ucfirst()` directly which will produce broken labels like "Soups-and-canned-goods" for multi-word categories:
- `resources/views/components/grocery-category.blade.php:11` -- `$categoryName = ucfirst($category->value)` should become `$categoryName = $category->label()`
- `resources/views/components/grocery-category.blade.php:88` -- `{{ ucfirst($cat->value) }}` should become `{{ $cat->label() }}`
- `resources/views/livewire/grocery-lists/show.blade.php:262` -- `{{ ucfirst($category->value) }}` should become `{{ $category->label() }}`

## Acceptance Criteria
- All requirements have passing tests
- `vendor/bin/pint --dirty` passes
- No references to `IngredientCategory::PANTRY` remain in the codebase (production code, tests, seeders, factories)
- No `ucfirst($category->value)` or `ucfirst($cat->value)` patterns remain in Blade templates (use `->label()` instead)
- All factories use the new category values (no hardcoded `'pantry'`)
