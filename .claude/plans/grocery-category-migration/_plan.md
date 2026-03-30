# Plan: Grocery Category Migration

## Created
2026-03-29

## Status
completed

## Objective
Expand grocery item categories from 10 to 19 values, mapping the removed `pantry` category to `soups-and-canned-goods`, retaining `beverages`, and migrating all existing data (DB enum column, JSON exclusion arrays on grocery_lists and users).

## Scope

### In Scope
- Update `IngredientCategory` PHP enum with all 19 new cases and improved `label()` method
- Database migration to alter the `category` ENUM column on **four tables**: `grocery_items`, `ingredients`, `common_item_templates`, `user_item_templates`
- Data migration: `pantry` → `soups-and-canned-goods` in all four `category` columns
- Data migration: update `grocery_lists.excluded_categories` JSON arrays (replace `pantry` with `soups-and-canned-goods`)
- Data migration: update `users.grocery_category_exclusions` JSON arrays (same replacements)
- Update all `IngredientCategory::PANTRY` references in production code, tests, factories, and seeders
- Update Blade templates using `ucfirst($category->value)` to use `$category->label()` instead
- Update factories to use new category values (remove hardcoded `'pantry'`)
- Tests covering enum, migration logic, and JSON migration
- Full suite regression check

### Out of Scope
- UI/UX changes to how categories are displayed or ordered
- Re-categorising existing items beyond the `pantry` → `soups-and-canned-goods` mapping
- Any changes to the recipe ingredient side of the app

## Mapping Reference
| Old value  | New value              |
|------------|------------------------|
| produce    | produce (unchanged)    |
| dairy      | dairy (unchanged)      |
| meat       | meat (unchanged)       |
| seafood    | seafood (unchanged)    |
| pantry     | soups-and-canned-goods |
| frozen     | frozen (unchanged)     |
| bakery     | bakery (unchanged)     |
| deli       | deli (unchanged)       |
| beverages  | beverages (unchanged)  |
| other      | other (unchanged)      |

New additions (no data to migrate): breakfast, condiments-and-dressings, cooking-and-baking, grains-and-pasta, health-and-personal-care, household-and-cleaning, pet-supplies, snacks, soups-and-canned-goods, wine-beer-and-spirits

## Success Criteria
- [ ] `IngredientCategory` enum has exactly 19 cases with correct string values and human-readable labels
- [ ] Migration runs cleanly on both SQLite (test) and MariaDB (production)
- [ ] All existing `pantry` grocery items remapped to `soups-and-canned-goods`
- [ ] All `pantry` references in JSON exclusion columns updated
- [ ] All existing tests pass
- [ ] New tests cover enum labels, data migration logic, and JSON column migration

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001  | Update IngredientCategory enum + all PANTRY references (code, tests, factories, seeders, Blade templates) | -   | completed |
| 002  | Create database migration (4 ENUM tables + JSON columns + data migration) | 001 | completed |
| 003  | Full suite verification and regression check | 002 | completed |

## Architecture Notes
- String values use lowercase hyphenated slugs (e.g. `soups-and-canned-goods`) consistent with existing single-word values
- `label()` must be updated: replace hyphens with spaces, then apply `ucwords()` (e.g. `soups-and-canned-goods` → `Soups And Canned Goods`)
- MariaDB ENUM alteration: use `DB::statement("ALTER TABLE ... MODIFY COLUMN category ENUM(...)")` for all four tables -- Laravel's `change()` doesn't support ENUM on MariaDB reliably
- **Four tables** have `category` ENUM columns: `grocery_items`, `ingredients`, `common_item_templates`, `user_item_templates`
- SQLite doesn't enforce ENUM at the DB level, so the SQLite path just needs the data update; wrap each MODIFY COLUMN in a DB driver check
- JSON column migration: iterate rows using `chunkById()` and replace values using PHP (not raw SQL JSON functions, for cross-driver compatibility and memory safety)
- Blade templates that use `ucfirst($category->value)` must be updated to use `$category->label()` to handle multi-word categories correctly

## Risks & Mitigations
- MariaDB ENUM alteration locks the table briefly: acceptable for a low-traffic app; note in migration comment
- SQLite ignores ENUM constraints: the PHP enum cast enforces valid values at app level, so this is fine
- JSON column updates must handle null values gracefully (both columns are nullable)
