# Task 001: Display Source URL Link on Recipe Show Page

**Status**: pending
**Depends on**: none
**Retry count**: 0

## Description
Add a conditional source URL link to the recipe show page. When a recipe has a `source_url` (i.e. it was imported), display it as a clickable link that opens in a new tab. The link should be placed near the recipe title/description area and styled consistently with the existing page.

## Context
- `source_url` is already stored on the `Recipe` model (see `app/Models/Recipe.php` — `source_url` in `$fillable`) and in the database
- Related files:
  - `resources/views/livewire/recipes/show.blade.php` — add the link here, after the description block (around line 43, inside the `<div class="flex-1">` wrapper)
  - `app/Livewire/Recipes/Show.php` — Livewire component (no changes expected; `$recipe` is already accessible)
  - `tests/Feature/Recipes/ViewRecipeTest.php` — existing recipe show page tests (use this file and pattern; NOT `tests/Feature/Recipe/ImportRecipeTest.php`, which covers the import flow, not the show page)
- The show page already uses Flux components (`flux:text`, `flux:badge`, `flux:icon`, etc.) for consistency
- The recipe description is conditionally rendered at lines 41-43; place the source URL link directly below it inside the same `<div class="flex-1">` so it flows with the header content
- `Recipe::factory()` supports `source_url` via mass-assignment (`Recipe::factory()->create(['source_url' => 'https://example.com/recipe'])`) — no factory change required
- Tests should use `$response->assertSee(..., false)` (escape = false) when asserting on raw HTML attributes like `target="_blank"` and the href value, because `assertSee` escapes by default and would miss attribute matches

## Requirements (Test Descriptions)
All tests should be added to `tests/Feature/Recipes/ViewRecipeTest.php` following the existing `actingAs($user)->get(route('recipes.show', $recipe))` pattern.

- [ ] `it displays source url as a link on the recipe show page when source_url is present` — create a recipe with a known `source_url` (e.g. `https://example.com/original-recipe`), visit the show route, and assert the URL appears in the rendered HTML via `assertSee($url, false)`
- [ ] `it does not display source url on the recipe show page when source_url is null` — create a recipe with `source_url => null`, visit the show route, and assert the page does NOT contain any marker identifying the source link (e.g. `assertDontSee('View Original')` or the href — choose a stable marker that the implementation uses)
- [ ] `it renders source url link with target blank and rel noopener noreferrer` — assert `assertSee('target="_blank"', false)` and `assertSee('rel="noopener noreferrer"', false)` are present when `source_url` is set

## Acceptance Criteria
- All requirements have passing tests
- Link only renders when `source_url` is not null (use `@if ($recipe->source_url)` — an empty string is unlikely since the import stores a full URL, but null is the canonical "no source" state)
- Link opens in a new tab with `target="_blank"` AND `rel="noopener noreferrer"` (security: prevent reverse tabnabbing)
- Link uses a visible label (e.g. "View Original Recipe" or similar) plus optionally the domain or the raw URL — do NOT render the raw `source_url` as the only text if it is long, but DO include it somewhere the test can assert on (either as href or link text)
- Styled consistently with existing show page elements (use `flux:text` / anchor with Tailwind `underline` + `text-blue-600 dark:text-blue-400` to match other inline links on the page)
- Placement: inside the `<div class="flex-1">` header block, directly below the description `@if` block (after line 43), so it does not interfere with the Alpine.js `x-data="recipeShowPage()"` scope that starts at line 86
- No changes required to `app/Livewire/Recipes/Show.php` — this is a view-only change

## Implementation Notes
(Left blank - filled in by programmer during implementation)
