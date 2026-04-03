# Task 003: Update ImportPreview.php to use IngredientCategorizationService

**Status**: completed
**Depends on**: [001]
**Retry count**: 0

## Description
Replace the hardcoded `IngredientCategory::OTHER` default in `ImportPreview.php` with a call to `IngredientCategorizationService`. This is the highest-impact change — imported recipe ingredients currently always land as `OTHER`, meaning every imported recipe degrades grocery list category accuracy.

## Context
- `app/Livewire/Recipes/ImportPreview.php` line 93: `['category' => IngredientCategory::OTHER]`
- The comment on that line reads: `// Default to OTHER for imported ingredients`
- Inject `IngredientCategorizationService` via method-level injection on `confirmImport()`, matching the injection pattern in `Import.php`
- `ImportPreview` is a fully authenticated component — pass `auth()->id()` so user templates are consulted during import

## Requirements (Test Descriptions)
- [ ] `it assigns a non-other category to a recognized imported ingredient`
- [ ] `it assigns OTHER to an unrecognized imported ingredient`
- [ ] `it uses the existing ingredient category when the ingredient was previously imported`

## Acceptance Criteria
- Hardcoded `IngredientCategory::OTHER` default removed from `ImportPreview.php`
- `IngredientCategorizationService` injected and used
- Existing import preview tests still pass
- Code formatted with `vendor/bin/pint --dirty`
