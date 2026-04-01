# Task 002: Create Database Migration

**Status**: pending
**Depends on**: 001
**Retry count**: 0

## Description
Create a single Laravel migration that (1) alters the `category` ENUM column on all four tables (`grocery_items`, `ingredients`, `common_item_templates`, `user_item_templates`) to accept all 19 new values, (2) remaps existing `pantry` rows to `soups-and-canned-goods` in all four tables, and (3) updates the `excluded_categories` JSON column on `grocery_lists` and the `grocery_category_exclusions` JSON column on `users` to replace any `"pantry"` entries with `"soups-and-canned-goods"`.

## Context
- Generate with: `php artisan make:migration update_grocery_category_enum_and_migrate_data --no-interaction`
- **Four tables** have `category` ENUM columns that must be altered: `grocery_items`, `ingredients`, `common_item_templates`, `user_item_templates`
- All four currently use the old 10-value ENUM including `pantry`
- Wrap each `ALTER TABLE ... MODIFY COLUMN` in `if (DB::getDriverName() !== 'sqlite')` so tests on SQLite don't fail
- Both JSON columns (`grocery_lists.excluded_categories`, `users.grocery_category_exclusions`) are nullable -- handle `null` gracefully (skip if null)
- JSON migration must be done in PHP using `chunkById(100, ...)` (not `->each()` which loads all rows, and not raw SQL JSON functions) for cross-driver compatibility and memory safety
- Use `DB::table()` queries inside the migration -- do NOT use Eloquent models (models may change after the migration runs)
- The `down()` method should reverse: change ENUM back to old values on all four tables, remap `soups-and-canned-goods` back to `pantry` in data and JSON columns, and map any other new category values (e.g., `breakfast`, `snacks`, etc.) to `other` before reverting the ENUM constraint

## MariaDB ENUM Statements
The same ENUM value set must be applied to all four tables. Note the differences in column constraints:

```sql
-- grocery_items: NOT NULL DEFAULT 'other'
ALTER TABLE grocery_items
MODIFY COLUMN category ENUM(
  'bakery','breakfast','beverages',
  'condiments-and-dressings','cooking-and-baking',
  'dairy','deli','frozen',
  'grains-and-pasta','health-and-personal-care','household-and-cleaning',
  'meat','other','pet-supplies',
  'produce','seafood','snacks',
  'soups-and-canned-goods','wine-beer-and-spirits'
) NOT NULL DEFAULT 'other';

-- ingredients: DEFAULT 'other' (check original migration for NOT NULL)
ALTER TABLE ingredients
MODIFY COLUMN category ENUM(
  'bakery','breakfast','beverages',
  'condiments-and-dressings','cooking-and-baking',
  'dairy','deli','frozen',
  'grains-and-pasta','health-and-personal-care','household-and-cleaning',
  'meat','other','pet-supplies',
  'produce','seafood','snacks',
  'soups-and-canned-goods','wine-beer-and-spirits'
) NOT NULL DEFAULT 'other';

-- common_item_templates: NOT NULL, no default
ALTER TABLE common_item_templates
MODIFY COLUMN category ENUM(
  'bakery','breakfast','beverages',
  'condiments-and-dressings','cooking-and-baking',
  'dairy','deli','frozen',
  'grains-and-pasta','health-and-personal-care','household-and-cleaning',
  'meat','other','pet-supplies',
  'produce','seafood','snacks',
  'soups-and-canned-goods','wine-beer-and-spirits'
) NOT NULL;

-- user_item_templates: NOT NULL, no default
ALTER TABLE user_item_templates
MODIFY COLUMN category ENUM(
  'bakery','breakfast','beverages',
  'condiments-and-dressings','cooking-and-baking',
  'dairy','deli','frozen',
  'grains-and-pasta','health-and-personal-care','household-and-cleaning',
  'meat','other','pet-supplies',
  'produce','seafood','snacks',
  'soups-and-canned-goods','wine-beer-and-spirits'
) NOT NULL;
```

## Data Migration Logic (pseudo-code)
```php
// 1. Remap pantry -> soups-and-canned-goods in ALL four category columns
foreach (['grocery_items', 'ingredients', 'common_item_templates', 'user_item_templates'] as $table) {
    DB::table($table)->where('category', 'pantry')
        ->update(['category' => 'soups-and-canned-goods']);
}

// 2. JSON columns: grocery_lists.excluded_categories
DB::table('grocery_lists')->whereNotNull('excluded_categories')
    ->chunkById(100, function ($rows) {
        foreach ($rows as $row) {
            $cats = json_decode($row->excluded_categories, true);
            if (is_array($cats) && in_array('pantry', $cats)) {
                $cats = array_values(array_map(
                    fn($c) => $c === 'pantry' ? 'soups-and-canned-goods' : $c,
                    $cats
                ));
                DB::table('grocery_lists')->where('id', $row->id)
                    ->update(['excluded_categories' => json_encode($cats)]);
            }
        }
    });

// 3. JSON columns: users.grocery_category_exclusions (same pattern)
DB::table('users')->whereNotNull('grocery_category_exclusions')
    ->chunkById(100, function ($rows) {
        foreach ($rows as $row) {
            $cats = json_decode($row->grocery_category_exclusions, true);
            if (is_array($cats) && in_array('pantry', $cats)) {
                $cats = array_values(array_map(
                    fn($c) => $c === 'pantry' ? 'soups-and-canned-goods' : $c,
                    $cats
                ));
                DB::table('users')->where('id', $row->id)
                    ->update(['grocery_category_exclusions' => json_encode($cats)]);
            }
        }
    });
```

## Requirements (Test Descriptions)
- [ ] `it remaps pantry grocery items to soups and canned goods`
- [ ] `it remaps pantry ingredients to soups and canned goods`
- [ ] `it remaps pantry common item templates to soups and canned goods`
- [ ] `it remaps pantry user item templates to soups and canned goods`
- [ ] `it leaves all other grocery item categories unchanged`
- [ ] `it remaps pantry in grocery list excluded categories json`
- [ ] `it remaps pantry in user grocery category exclusions json`
- [ ] `it handles null excluded categories gracefully`
- [ ] `it handles null user grocery category exclusions gracefully`
- [ ] `it handles malformed json in excluded categories gracefully`
- [ ] `it accepts all 19 new category values on grocery items`

## Acceptance Criteria
- All requirements have passing tests
- `php artisan migrate` runs without error on SQLite (test environment)
- `vendor/bin/pint --dirty` passes
- `down()` method reverses the data changes for all four tables
- `down()` maps new-only category values (e.g., `breakfast`, `snacks`) to `other` before reverting the ENUM
