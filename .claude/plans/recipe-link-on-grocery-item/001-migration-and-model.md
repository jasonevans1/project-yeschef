# Task 001: Migration and GroceryItem Model Update

**Status**: pending
**Depends on**: none
**Retry count**: 0

## Description
Add a nullable `recipe_ids` JSON column to `grocery_items` and update the `GroceryItem` Eloquent model to include it in `$fillable` and `$casts`. This is the data-layer foundation all other tasks build on.

## Context
- Migration: `database/migrations/` — use `php artisan make:migration add_recipe_ids_to_grocery_items_table --no-interaction`
- Model: `app/Models/GroceryItem.php`
- The column is nullable so existing rows and manual items default to `null` (no recipe links shown)
- Cast as `'array'` so Eloquent JSON-encodes on write and decodes to a PHP array on read

## Requirements (Test Descriptions)
- [ ] `it adds a nullable recipe_ids JSON column to the grocery_items table`
- [ ] `it mass-assigns recipe_ids via GroceryItem::create() (verifies $fillable)`
- [ ] `it stores and retrieves recipe_ids as a PHP array (verifies array cast)`
- [ ] `it allows recipe_ids to be null`
- [ ] `it defaults recipe_ids to null when not provided`
- [ ] `it stores an empty array as [] (not null) when explicitly passed`

## Acceptance Criteria
- All requirements have passing tests
- Migration runs cleanly on a fresh SQLite database
- `GroceryItem` model casts `recipe_ids` as `'array'` (added to the `$casts` property) and includes `'recipe_ids'` in `$fillable`
- Migration places the column after `original_values` (keeps JSON columns together) — or another sensible position if that conflicts with SQLite column reordering

## Implementation Notes
(Left blank — filled in by programmer during implementation)
