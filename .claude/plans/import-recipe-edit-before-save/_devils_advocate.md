# Devil's Advocate Review: import-recipe-edit-before-save

## Critical (Must fix before building)

### C1: IngredientParser returns `name` but Edit expects `ingredient_name` (Task 001)
The `IngredientParser::parse()` method returns an array with key `name`, but the `$ingredients` array structure used by `Edit` (and the `<x-ingredient-input>` Blade component) expects the key `ingredient_name`. Task 001 must explicitly document this mapping in mount: `'ingredient_name' => $parsed['name']`.

### C2: IngredientParser returns `MeasurementUnit` enum but `$ingredients` array expects string value (Task 001)
`IngredientParser::parse()` returns `unit` as a `MeasurementUnit|null` enum instance. The `$ingredients` array used by `Edit` and the `<x-ingredient-input>` component expects `unit` as a nullable string (e.g., `'cup'`, not `MeasurementUnit::CUP`). The mount code must call `->value` on the enum: `'unit' => $parsed['unit']?->value`.

### C3: Missing `parseIngredientQuantities()` and `validateIngredients()` methods (Task 001)
Task 001 says `save()` should "use the same validation + DB transaction pattern as `Edit::update()`", which calls `parseIngredientQuantities()` and `validateIngredients()`. These are private/protected methods on `Edit`. Task 001 must explicitly state that these methods need to be duplicated onto `ImportPreview` (or extracted to a trait/shared concern). The plan says to mirror `Edit::update()` but never mentions copying these two methods.

### C4: Missing `addIngredient()` and `removeIngredient()` methods (Task 001)
The Blade view (Task 002) will use `<x-ingredient-input>` which calls `wire:click="removeIngredient({{ $index }})"`, and the "Add Ingredient" button calls `wire:click="addIngredient"`. Task 001's test list includes "saves recipe with added ingredient row" and "saves recipe after removing an ingredient row" but the requirements never explicitly state these methods must be added to `ImportPreview`. They need to be copied from `Edit`.

### C5: `render()` must pass `mealTypes` and `measurementUnits` (Task 001/002)
Task 002 mentions this in context but Task 001 never lists updating `render()` as a requirement. The `<x-ingredient-input>` component hardcodes units, but the meal type dropdown in `edit.blade.php` uses `$mealTypes`. The `render()` method must be updated in Task 001 since that is the PHP component task.

## Important (Should fix before building)

### I1: Existing tests call `confirmImport()` -- must be updated or aliased (Task 001)
The plan says to rename `confirmImport()` to `save()`, but existing tests (`confirming import creates recipe in database`, `confirming import creates recipe ingredients`, `confirming import clears cache data`) call `->call('confirmImport')`. Task 001 must explicitly update these tests (or the test file must be listed as a file to modify). The task does mention "update all existing ImportPreview tests" but should be specific that the method name in `->call()` must change.

### I2: Existing test `preview page loads cache data` asserts on `$recipeData` (Task 001)
The test `assertSet('recipeData.name', 'Preview Test Recipe')` will break because `$recipeData` is being removed (or at least no longer used to drive the component). This test must be rewritten to assert on the new individual properties like `assertSet('name', 'Preview Test Recipe')`.

### I3: `IngredientCategorizationService` still needed in `save()` (Task 001)
The `Edit::update()` method receives `IngredientCategorizationService` via method injection. The plan's `save()` must also inject this service for `Ingredient::firstOrCreate` category resolution. This is implied but not stated.

### I4: `source_url` should not be user-editable but must be included in recipe creation (Task 001)
The plan's "Out of Scope" says source_url is not user-editable. Task 001 must store `$source_url` from cache in mount and include it in the `create()` call within `save()`. This is mentioned in the plan but should be an explicit requirement in Task 001.

### I5: Validation attributes must be defined on `ImportPreview` (Task 001)
Task 001 says to add the same properties as `Edit`, but the `#[Validate]` attributes on those properties are critical. The task should explicitly state that validation rules must match `Edit`'s rules (e.g., `#[Validate('required|string|min:3|max:255')]` on `$name`).

### I6: Task 002 says "No new tests needed" but has a test requirement (Task 002)
Task 002 lists `it renders the import preview page with a form for authenticated users` as a test requirement, contradicting the context note. This test likely already exists in some form and just needs updating, but the task should be clear about what test work is needed.

### I7: Missing `$notes` field mapping from parser (Task 001)
`IngredientParser::parse()` does not return a `notes` key. The mount code must construct `notes` from the parser output. Looking at the current `createIngredients()` logic: notes are set to `$parsed['original']` when parsing fails (no quantity or unit). The same logic should be replicated when building the `$ingredients` array, or `notes` should default to `null`.

## Minor (Nice to address)

### M1: Duplicate code between `Edit` and `ImportPreview`
Both components will have nearly identical `parseIngredientQuantities()`, `validateIngredients()`, `addIngredient()`, `removeIngredient()` methods. A shared trait (e.g., `HasIngredientEditor`) would reduce duplication. Not blocking but worth noting for tech debt.

### M2: The `ingredientCategories` variable passed by `Edit::render()` is unused in the view
`Edit::render()` passes `ingredientCategories` but the edit Blade view never uses it. `ImportPreview` does not need to pass it either.

### M3: `navigate: true` on redirect
`Edit::update()` uses `$this->redirect(route(...), navigate: true)` but `ImportPreview::confirmImport()` uses `$this->redirect(route(...))` without `navigate: true`. The new `save()` should decide whether to use navigate or not -- either is fine but consistency with `Edit` is preferred.

## Questions for the Team

### Q1: Should the `createIngredients()` method with duplicate-handling logic be reused or simplified?
The current `ImportPreview::createIngredients()` has sophisticated duplicate ingredient merging (appending "Also listed as:" notes). When switching to a form-based approach where users can manually remove duplicates, is this logic still needed in `save()`? The Edit component simply skips duplicates silently.

### Q2: Should the ingredient parser's `original` text be shown in the notes field?
When an ingredient string cannot be fully parsed (no quantity/unit detected), the current code stores the original text as notes. Should this carry over to the editable form's notes column, letting the user see and edit it?
