# Task 002: Update Create.php and Edit.php to use IngredientCategorizationService

**Status**: completed
**Depends on**: [001]
**Retry count**: 0

## Description
Replace the duplicated `guessIngredientCategory()` method in both `Create.php` and `Edit.php` with calls to the new `IngredientCategorizationService`. This eliminates duplicated keyword-matching code and ensures that manually created/edited recipe ingredients benefit from `CommonItemTemplate` lookup in addition to keyword matching.

## Context
- `app/Livewire/Recipes/Create.php` — has `guessIngredientCategory()` at line 146, called at line 108
- `app/Livewire/Recipes/Edit.php` — has identical `guessIngredientCategory()` method, called at line 145
- Inject `IngredientCategorizationService` via **method-level injection** on the `save()` / `update()` action methods — this matches the existing pattern in `app/Livewire/Recipes/Import.php` where `RecipeImportService` is injected as a method parameter. Do not use `boot()` or constructor injection; neither pattern exists in these components.
- The `Ingredient::firstOrCreate()` call passes the guessed category as a *default* (only used when creating a new ingredient). Existing ingredients retain their stored category.

## Requirements (Test Descriptions)
- [ ] `it assigns a category from the categorization service when creating a new ingredient via the create form`
- [ ] `it assigns a category from the service when a new ingredient is introduced via the edit form (ingredient not previously in the database)`
- [ ] `it retains an existing ingredient's stored category when the ingredient already exists in the database (create form)`
- [ ] `it retains an existing ingredient's stored category when the ingredient already exists in the database (edit form)`

## Acceptance Criteria
- `guessIngredientCategory()` method removed from both `Create.php` and `Edit.php`
- `IngredientCategorizationService` injected and used in both components
- All existing Create/Edit recipe tests still pass
- Code formatted with `vendor/bin/pint --dirty`
