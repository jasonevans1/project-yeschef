# Plan: Import Recipe Edit Before Save

## Created
2026-04-14

## Status
completed

## Objective
Replace the read-only recipe import preview page with an editable form so users can review and correct all recipe fields — including a structured ingredient editor — before saving the imported recipe.

## Scope

### In Scope
- Convert `ImportPreview` PHP component to populate editable form properties from cached import data
- Parse raw ingredient strings into structured ingredient rows (quantity, unit, name, notes) on mount using `IngredientParser`
- Full ingredient editor: add/remove ingredients, edit quantity/unit/name/notes
- All other editable fields: name, description, prep_time, cook_time, servings, meal_type, cuisine, difficulty, dietary_tags, instructions, image_url
- Replace read-only Blade view with an editable form matching the style of `edit.blade.php`
- Rename `confirmImport()` to `save()` and drive it from form properties (not raw `$recipeData`)
- Update all existing ImportPreview tests and add tests for new editing behavior

### Out of Scope
- Changes to the Import URL entry step
- Changes to the `RecipeImportService` or parsing pipeline
- Editing the `source_url` (preserved from cache, not user-editable)
- Any UI changes to the recipe show or edit pages

## Success Criteria
- [ ] Preview page shows all fields in editable inputs pre-populated from parsed import data
- [ ] Ingredient strings are parsed into structured rows (quantity, unit, name, notes)
- [ ] Users can add/remove/edit ingredient rows
- [ ] Saving creates a recipe using the (potentially edited) form values
- [ ] Validation errors display inline for all fields
- [ ] Cancel clears cache and returns to import URL page
- [ ] All existing import tests updated and passing
- [ ] New tests cover editing scenarios
- [ ] Code follows project standards (Pint clean)

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Update ImportPreview PHP component with editable form properties, ingredient parsing on mount, and save logic | - | pending |
| 002 | Update ImportPreview Blade view with editable form | 001 | pending |

## Architecture Notes
- `ImportPreview` gains the same public properties (with `#[Validate]` attributes) as `Edit` plus a `$source_url` string to carry the import source through to save
- `mount()` calls `IngredientParser::parse()` on each raw ingredient string to build the structured `$ingredients` array
  - **Important**: Parser returns `name` (not `ingredient_name`) and `unit` as `MeasurementUnit` enum (not string). Map accordingly: `'ingredient_name' => $parsed['name']`, `'unit' => $parsed['unit']?->value`
  - Parser does NOT return `notes`. Set notes to `$parsed['original']` when no quantity/unit was parsed, otherwise `null`
- `save()` mirrors `Edit::update()`: calls `parseIngredientQuantities()`, `validate()`, `validateIngredients()`, then creates the recipe + ingredients in a DB transaction. These helper methods must be copied from `Edit`.
- `addIngredient()` and `removeIngredient()` must be copied from `Edit` -- they are called by `<x-ingredient-input>` Blade component
- `render()` must pass `mealTypes` and `measurementUnits` to the view (same as `Edit::render()`)
- The raw `$recipeData` array can be dropped from the component after mount (only `$source_url` needs to persist)
- Blade view mirrors `edit.blade.php` with heading "Review & Edit Imported Recipe" and button label "Save Recipe"

## Risks & Mitigations
- Ingredient parser may not produce a clean `ingredient_name` for all inputs: handled gracefully — parser falls back to original text, user can correct in the form before saving
- Cached data fields may be `null` (optional fields not present in scraped data): all optional fields default to `null`, consistent with `Edit` component behavior
