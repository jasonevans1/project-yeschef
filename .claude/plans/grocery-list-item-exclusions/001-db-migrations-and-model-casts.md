# Task 001: DB Migrations + Model Casts

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Add `pantry_items` (JSON, nullable) to the `users` table and `excluded_ingredients` (JSON, nullable) to the `grocery_lists` table. Update both Eloquent models with the new fillable fields and array casts.

## Context
- Related files:
  - `app/Models/User.php` — already has `grocery_category_exclusions` cast as `'array'`; follow that exact pattern
  - `app/Models/GroceryList.php` — already has `excluded_categories` cast as `'array'`; follow that exact pattern
  - `database/migrations/` — use `php artisan make:migration` for each table

## Requirements (Test Descriptions)
- [ ] `it stores and retrieves pantry_items as an array on User`
- [ ] `it defaults pantry_items to null when not set`
- [ ] `it stores and retrieves excluded_ingredients as an array on GroceryList`
- [ ] `it defaults excluded_ingredients to null when not set`
- [ ] `it casts pantry_items null to empty on User model`

## Acceptance Criteria
- Two migration files created (one per table)
- User model: `pantry_items` in `$fillable` + `'pantry_items' => 'array'` in the `casts()` method (User uses the method-based approach)
- GroceryList model: `excluded_ingredients` in `$fillable` + `'excluded_ingredients' => 'array'` in the `protected $casts` property (GroceryList uses the property-based approach, NOT the `casts()` method)
- **Note**: The `GroceryListFactory::withItems()` method calls `$groceryList->items()` but the model defines `groceryItems()`. If tests using that factory state fail, update the factory to use `groceryItems()`.
- All requirements have passing tests
- No changes to existing columns or casts

## Implementation Notes
(Left blank — filled in by implementer)
