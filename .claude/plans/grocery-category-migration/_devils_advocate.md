# Devil's Advocate Review: grocery-category-migration

## Critical (Must fix before building)

### C1. Migration only covers `grocery_items` -- misses 3 other ENUM tables (Task 002)
The plan's migration only alters the `grocery_items.category` ENUM column and remaps data there. But `IngredientCategory` is also used as a DB-level ENUM on three additional tables:
- `ingredients.category` (migration `2025_10_12_190403`)
- `common_item_templates.category` (migration `2026_01_02_215251`)
- `user_item_templates.category` (migration `2026_01_02_215346`)

All three currently have the old 10-value ENUM including `pantry`. After the enum PHP change, any attempt to insert a new category value into these tables on MariaDB will fail with a DB-level constraint error. Existing `pantry` rows in `ingredients` and `common_item_templates` also need data migration.

**Fix:** Task 002 must include ALTER TABLE statements for all four tables (not just `grocery_items`), plus data migration of `pantry` rows in `ingredients`, `common_item_templates`, and `user_item_templates`.

### C2. Factories still produce `pantry` -- tests will randomly fail (Task 001, 003)
`GroceryItemFactory` and `IngredientFactory` hardcode `randomElement(['produce', 'dairy', ... 'pantry', ...])`. After removing `PANTRY` from the PHP enum, the enum cast on the model will throw a `ValueError` whenever the factory randomly picks `'pantry'`. This will cause random test failures across the entire suite.

**Fix:** Task 001 must update both `database/factories/GroceryItemFactory.php` and `database/factories/IngredientFactory.php` to use the new category values (or `IngredientCategory::cases()` like `UserItemTemplateFactory` does).

### C3. `IngredientCategory::PANTRY` referenced in app code -- not just tests (Task 001)
Task 001 mentions searching the codebase, but the plan doesn't explicitly account for these production code references that will cause fatal errors:
- `app/Livewire/Recipes/Edit.php:205` -- `IngredientCategory::PANTRY->value` in keyword map
- `app/Livewire/Recipes/Create.php:164` -- same
- `database/seeders/DevelopmentSeeder.php:224` -- same

These are not test files -- they are runtime code. The worker must replace `PANTRY` with appropriate new categories in each file.

**Fix:** Task 001 requirements must explicitly list these files and specify the replacement (e.g., replace `PANTRY` with `COOKING_AND_BAKING` or `SOUPS_AND_CANNED_GOODS` and redistribute keywords to appropriate new categories).

### C4. Seeders use hardcoded `'pantry'` string values -- will fail on MariaDB (Task 001/002)
`CommonItemTemplateSeeder` and `RecipeSeeder` contain dozens of rows with `'category' => 'pantry'`. After the ENUM column is altered, running seeders on MariaDB will fail. Even without the ENUM constraint, the PHP enum cast will reject `'pantry'`.

**Fix:** Task 001 must update `database/seeders/CommonItemTemplateSeeder.php`, `database/seeders/RecipeSeeder.php`, and `database/seeders/DevelopmentSeeder.php` to use new category values instead of `'pantry'`.

## Important (Should fix before building)

### I1. `ucfirst($category->value)` in Blade templates bypasses `label()` (Task 001)
Three locations display category names using `ucfirst($category->value)` instead of `$category->label()`:
- `resources/views/components/grocery-category.blade.php:11`
- `resources/views/components/grocery-category.blade.php:88`
- `resources/views/livewire/grocery-lists/show.blade.php:262`

After adding multi-word hyphenated values, these will render as "Soups-and-canned-goods" instead of "Soups And Canned Goods". While the plan says UI changes are out of scope, this is a functional bug introduced by the enum change, not a new UI feature.

**Fix:** Task 001 should update these three template locations to use `$category->label()` instead of `ucfirst($category->value)`.

### I2. `down()` method is underspecified for 4 tables (Task 002)
The task mentions the `down()` method should reverse changes, but given C1 above, the `down()` must also reverse ALTER TABLE on `ingredients`, `common_item_templates`, and `user_item_templates`. The `down()` must also handle the case where new category values (e.g., `breakfast`, `snacks`) have been assigned to rows -- those rows cannot be migrated back to the old ENUM without a mapping for every new value.

**Fix:** Add explicit guidance in task 002 that `down()` must map all new category values to `other` (or another appropriate old value) before reverting the ENUM definition, for all four tables.

### I3. JSON migration pseudo-code uses `->each()` which loads all rows into memory (Task 002)
`DB::table('grocery_lists')->whereNotNull('excluded_categories')->each(...)` is fine for small tables, but `->each()` calls `->get()` under the hood. Should use `->chunkById()` or `->lazyById()` instead.

**Fix:** Update the pseudo-code in task 002 to use `chunkById(100, ...)` for the JSON migration loops.

### I4. Test task 003 duplicates all test requirements from 001 and 002 (Task 003)
Tasks 001 and 002 each define their own test requirements. Task 003 then re-lists all the same tests. The TDD workers for tasks 001 and 002 will write their own tests as part of those tasks (per the TDD workflow). Task 003 then becomes either redundant or confusing -- does the worker re-write the tests, or just run the existing ones?

**Fix:** Redefine task 003 as a "verification and regression" task: its job is to run the full test suite, confirm no regressions from the PANTRY removal across the ~60+ existing test references, and fix any remaining `IngredientCategory::PANTRY` references in test files. Remove the duplicated test requirement lists.

### I5. Existing tests with `IngredientCategory::PANTRY` will fail immediately (Task 001, 003)
There are 50+ references to `IngredientCategory::PANTRY` across test files. Once task 001 removes the PANTRY case, all these tests break. Task 001 says "search the codebase and remove/replace those references" but this is a massive amount of work that should be explicitly scoped. The worker needs clear guidance on what to replace PANTRY with.

**Fix:** Task 001 should specify: replace `IngredientCategory::PANTRY` with `IngredientCategory::SOUPS_AND_CANNED_GOODS` in all test files (since that is the migration mapping). List the affected test files explicitly so the worker knows the scope.

## Minor (Nice to address)

### M1. Label method produces "Soups And Canned Goods" -- consider "Soups and Canned Goods"
The plan specifies `ucwords(str_replace('-', ' ', $this->value))` which produces title case for every word including articles/conjunctions. "Soups and Canned Goods" or "Health and Personal Care" reads more naturally. This is a design decision, not a bug.

### M2. Category ordering
The 19 enum cases are listed alphabetically. Some grocery apps order categories by store aisle layout. The plan doesn't address sort order, which is fine for now but worth noting.

### M3. `common_item_templates` seeder has items that could map to more specific new categories
Many items in `CommonItemTemplateSeeder` currently mapped to `pantry` would better fit new categories like `condiments-and-dressings` (ketchup, mustard, mayo), `grains-and-pasta` (pasta, rice, flour), `snacks` (chips, crackers, popcorn), `breakfast` (cereal, oatmeal). The plan only maps `pantry` to `soups-and-canned-goods`, which is a lossy mapping.

## Questions for the Team

1. **Should `pantry` items be redistributed to more specific categories?** The plan maps all `pantry` to `soups-and-canned-goods`, but flour is not a soup, and chips are not canned goods. Should the migration include a keyword-based redistribution, at least for `common_item_templates` and `ingredients`?

2. **Should the `down()` migration actually be implemented?** Given the complexity of reversing a 19-to-10 mapping with data loss, it may be more practical to make this a non-reversible migration and document that clearly.

3. **How should the `guessIngredientCategory()` method in `Recipes/Edit.php` and `Recipes/Create.php` be updated?** The current keyword map should presumably be expanded to include the new categories (e.g., rice/pasta keywords map to `grains-and-pasta` instead of the removed `pantry`). This is arguably out of scope per the plan, but will produce incorrect categorization if not updated.
