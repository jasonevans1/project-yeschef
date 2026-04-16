# Task 002: Update ImportPreview Blade View

**Status**: complete
**Depends on**: [001]
**Retry count**: 0

## Description
Replace the read-only preview template with an editable form that mirrors `edit.blade.php`. All fields should be wired to the component properties added in Task 001. The form submits via `wire:submit="save"`. The heading should read "Review & Edit Imported Recipe" to make the step's purpose clear.

## Context
- File to modify: `resources/views/livewire/recipes/import-preview.blade.php`
- Reference template: `resources/views/livewire/recipes/edit.blade.php` — copy the full form structure from this file
- Ingredient row component: `<x-ingredient-input>` — already used in `edit.blade.php`
- One smoke test required (see Requirements) to verify the page renders with the form. Task 001 covers all behavioral/interaction tests.
- The view expects `$mealTypes` and `$measurementUnits` variables — these are passed from `render()` which is updated in Task 001
- The Cancel button should call `wire:click="cancel"` (not link to a recipe show page, since no recipe exists yet)
- Button label: "Save Recipe" (not "Update Recipe")
- Do not include the difficulty or dietary_tags sections if it keeps the view simpler — they are not returned by the importer. Wait — actually include them so users can set them during import review; they default to empty/null just as they do in the edit form.

## Requirements (Test Descriptions)
- [x] `it renders the import preview page with a form for authenticated users`

## Acceptance Criteria
- Page heading reads "Review & Edit Imported Recipe"
- All fields from `edit.blade.php` are present: name, description, prep_time, cook_time, servings, meal_type, cuisine, difficulty, dietary_tags, ingredients, instructions, image_url
- Ingredient editor matches `edit.blade.php` (uses `<x-ingredient-input>` component with `wire:key`)
- Form submits via `wire:submit="save"`
- Cancel button calls `wire:click="cancel"`
- Existing smoke test (`it renders the import preview page with a form for authenticated users`) passes
- Dark mode classes present (matching existing edit.blade.php)

## Implementation Notes
- Replaced the import-preview.blade.php with a full form structure matching edit.blade.php
- Changed `wire:submit` from `wire:click` on the button to `wire:submit="save"` on the form element
- Changed the Cancel button to use `wire:click="cancel"` (no href, since no recipe exists yet)
- Changed button label from "Update Recipe" to "Save Recipe"
- Added difficulty and dietary_tags sections matching edit.blade.php
- Used `<x-ingredient-input>` component with `wire:key` for ingredient rows
- Added `difficulty` and `dietary_tags` properties to the ImportPreview component (were missing)
- Kept source URL read-only display and `<flux:error name="import">` for transaction errors
- Removed the `<img>` preview tag that was in the old view
