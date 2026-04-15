# Task 001: Update ImportPreview PHP Component

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Replace the read-only `ImportPreview` component with an editable form component. In `mount()`, populate individual form properties from cached import data and parse each raw ingredient string into a structured row using `IngredientParser`. Rename `confirmImport()` to `save()` and drive recipe creation from the validated form properties rather than the raw `$recipeData` array.

## Context
- File to modify: `app/Livewire/Recipes/ImportPreview.php`
- Test file to update: `tests/Feature/Recipe/ImportRecipeTest.php` (the ImportPreview tests starting at `T038`)
- Reference for form properties and save logic: `app/Livewire/Recipes/Edit.php`
- Ingredient parser: `App\Services\RecipeImporter\IngredientParser` — `parse(string $text): array` returns `[name, quantity, unit, original]`
- **KEY**: Parser returns `name` (not `ingredient_name`) and `unit` as a `MeasurementUnit` enum (not a string). When building the `$ingredients` array, map: `'ingredient_name' => $parsed['name']` and `'unit' => $parsed['unit']?->value`
- **KEY**: Parser does NOT return a `notes` key. Set `notes` to `$parsed['original']` when parsing produced no quantity/unit (fallback), otherwise `null` — matching current `createIngredients()` behavior
- `MeasurementUnit` enum is at `App\Enums\MeasurementUnit`
- `MealType` enum is at `App\Enums\MealType`
- `IngredientCategorizationService` is at `App\Services\IngredientCategorizationService` — must be injected into `save()` for `Ingredient::firstOrCreate` category resolution
- The `$source_url` value comes from `$recipeData['source_url']` in cache and must be stored as a separate public property (not user-editable) so it survives after `$recipeData` is no longer needed; include it in the `create()` call within `save()`
- `render()` must be updated to pass `mealTypes` and `measurementUnits` to the view (same as `Edit::render()`)

## Requirements (Test Descriptions)
- [ ] `it populates name from cached import data on mount`
- [ ] `it populates description from cached import data on mount`
- [ ] `it populates prep time from cached import data on mount`
- [ ] `it populates cook time from cached import data on mount`
- [ ] `it populates servings from cached import data on mount`
- [ ] `it populates cuisine from cached import data on mount`
- [ ] `it populates meal type from cached import data on mount`
- [ ] `it populates instructions from cached import data on mount`
- [ ] `it populates image url from cached import data on mount`
- [ ] `it parses raw ingredient strings into structured ingredient rows on mount`
- [ ] `it redirects to import page if cache is missing on mount`
- [ ] `it saves recipe using edited name when name is changed before saving`
- [ ] `it saves recipe using edited servings when servings are changed before saving`
- [ ] `it saves recipe with edited ingredients when ingredients are modified before saving`
- [ ] `it saves recipe with added ingredient row`
- [ ] `it saves recipe after removing an ingredient row`
- [ ] `it stores source url on the recipe when saving`
- [ ] `it clears the cache after saving`
- [ ] `it redirects to the recipe show page after saving`
- [ ] `it shows validation error when name is cleared before saving`
- [ ] `it shows validation error when instructions are cleared before saving`
- [ ] `it shows validation error when all ingredient rows are removed before saving`
- [ ] `it cancel clears cache and redirects to import page`

### Existing tests to update
The following existing tests in `tests/Feature/Recipe/ImportRecipeTest.php` must be rewritten to work with the new component structure:
- `preview page loads cache data` -- currently asserts `assertSet('recipeData.name', ...)`, must change to `assertSet('name', ...)`
- `confirming import creates recipe in database` -- calls `->call('confirmImport')`, must change to `->call('save')`
- `confirming import creates recipe ingredients` -- calls `->call('confirmImport')`, must change to `->call('save')`
- `confirming import clears cache data` -- calls `->call('confirmImport')`, must change to `->call('save')`
- `cancel clears cache without creating recipe` -- should still work but verify

## Acceptance Criteria
- All requirements have passing tests
- Component no longer uses `$recipeData` to drive recipe creation (only used during mount to populate form fields)
- `save()` uses the same validation + DB transaction pattern as `Edit::update()`, including `parseIngredientQuantities()` and `validateIngredients()` methods (copy from `Edit`)
- `addIngredient()` and `removeIngredient()` methods are present (copy from `Edit`) -- required by `<x-ingredient-input>` Blade component
- All form properties have `#[Validate]` attributes matching `Edit`'s validation rules
- `render()` passes `mealTypes` and `measurementUnits` to the view
- `$source_url` is stored as a public property (not editable by the user) and included in recipe creation
- Existing passing tests remain passing (update method names from `confirmImport` to `save`, update property assertions from `recipeData.X` to individual properties)
- Code follows project standards (run `vendor/bin/pint --dirty`)

## Implementation Notes
(Left blank - filled in by programmer during implementation)
