# Plan: Recipe Source URL Display

## Created
2026-04-09

## Status
completed

## Objective
Display the already-stored `source_url` on the recipe show page as a clickable link so users can navigate back to the original recipe source.

## Scope

### In Scope
- Display `source_url` as a link on the recipe show page when present
- Style consistently with existing recipe show page elements

### Out of Scope
- Changing how source_url is stored (already implemented)
- Displaying source_url on the recipe index/list page
- Editing source_url after import

## Success Criteria
- [ ] Source URL link appears on recipe show page when recipe has a source_url
- [ ] Link is not rendered when source_url is null
- [ ] Link opens in a new tab
- [ ] All tests passing

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Display source URL link on recipe show page | - | pending |

## Architecture Notes
- `source_url` already exists in `Recipe::$fillable` and DB migration
- `ImportPreview::confirmImport()` already saves `source_url`
- The recipe show view is at `resources/views/livewire/recipes/show.blade.php`
- The recipe show Livewire component is `app/Livewire/Recipes/Show.php` (no changes needed — `$recipe` is already public)
- New tests should be added to `tests/Feature/Recipes/ViewRecipeTest.php` (NOT `tests/Feature/Recipe/ImportRecipeTest.php`, which covers import flows)
- `Recipe::factory()` already supports `source_url` via mass-assignment

## Risks & Mitigations
- None significant — purely additive view change
