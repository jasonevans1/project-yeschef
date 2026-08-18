# Task 002: ImportPaste Livewire component, route, view, and cross-links

**Status**: pending
**Depends on**: 001
**Retry count**: 0

## Description
Build the user-facing "paste recipe HTML" import path: a new full-page Livewire component that takes pasted HTML, runs it through `RecipeImportService::parseHtml()` (Task 001), and hands off to the existing `ImportPreview` component exactly the way `Import.php` does today for URL imports. Add navigation links between the two import modes.

## Context
- Reference implementation to mirror closely: `app/Livewire/Recipes/Import.php` and `resources/views/livewire/recipes/import.blade.php`.
- New files:
  - `app/Livewire/Recipes/ImportPaste.php`
  - `resources/views/livewire/recipes/import-paste.blade.php`
- New test file: `tests/Feature/Recipe/ImportPasteTest.php` (all of this task's tests live here, including the cross-link assertion — do not add tests to the existing `ImportRecipeTest.php`).
- Route: add to `routes/web.php` **inside the existing `Route::middleware(['auth'])->group()`**, alongside the other import routes:
  `Route::livewire('recipes/import/paste', RecipesImportPaste::class)->name('recipes.import.paste');`
  The component keys its cache and rate limiter off `auth()->id()`, so a route outside the auth group would produce a shared `recipe_import_preview:` key across all guests — a cross-user data leak.
- **Critical**: use the exact same cache key format as `Import.php` — `'recipe_import_preview:'.auth()->id()` — and the same cached array shape (including `source_url`, which should be `null` for pasted imports since there's no URL) so `ImportPreview::mount()` (unchanged) works with either import path.
- Reuse the same rate-limit key as `Import.php` (`'recipe-import:'.auth()->id()`, same `config('recipe-import.rate_limit.*')` values) — this task and Task 003 together should result in URL and paste imports sharing one bucket. Wire the rate-limit check into this task's happy path; the exhaustion/throttle-message tests belong to Task 003.
- **Error bag key**: every `addError()` call in `ImportPaste` (validation, throttle, cache-verification, and exception handlers) uses the key `html`, and the view renders `<flux:error name="html" />`. Task 003's tests assert against this key.

### Validation rules (own the whole rule here)
- Add `'max_html_length' => 2_000_000,` to `config/recipe-import.php`.
- Do **not** use a `#[Validate]` attribute for `html` — PHP attribute arguments must be compile-time constants, so a config-derived `max:` is a fatal error there. Define a `rules(): array` method on the component instead, read at runtime:
  ```php
  protected function rules(): array
  {
      return ['html' => 'required|string|min:50|max:'.config('recipe-import.max_html_length')];
  }
  ```
  (Task 003 tests the cap by overriding the config value, so it must be read at call time, not hardcoded.)

### Payload size handling
Livewire sends the component snapshot **and** the property update in the same POST, so once `html` is populated it travels twice per request. PHP's default `post_max_size` is 8M and nginx's default `client_max_body_size` is 1M; blowing past either yields a dead Livewire request with no user-facing error. Therefore:
- Textarea uses plain deferred `wire:model="html"` — never `.live`, `.blur`, or `.debounce`.
- After the cache write is verified and before `$this->redirect(...)`, call `$this->reset('html')` so the redirect response doesn't carry the payload back.

### Logging
`Import.php` logs `'url' => $this->url` in three places. Do **not** mirror that with the HTML — it would write megabytes per attempt into `laravel.log` and can contain the user's logged-in page state from the source site. Log `'html_length' => strlen($this->html)` instead, in every log call including the generic exception handler.

### Exception handling
- Catch the same exception set `Import.php` catches, minus the ones `parseHtml()` can't throw (see Task 001 context) — no need to catch `BlockedUrlException`, `NetworkTimeoutException`, or `InvalidHTTPStatusException` here.
- Keep the generic `Exception` catch (log + friendly message) but drop `Import.php`'s `str_contains($e->getMessage(), 'Could not connect')` branch — no HTTP request happens on this path, so it is unreachable.

### View
A `flux:textarea` for pasting HTML (with `flux:field` / `flux:label` / `flux:description` / `flux:error` structure matching `import.blade.php`), plus a link to switch to URL import. Add the reverse link ("Paste HTML instead") on `import.blade.php`.

## Requirements (Test Descriptions)
- [ ] `it renders the paste html import form`
- [ ] `it redirects guests to login from the paste html import page`
- [ ] `it imports a recipe from pasted html containing valid json-ld recipe data`
- [ ] `it caches recipe data under the same cache key format used by url import`
- [ ] `it caches a null source url for a pasted import`
- [ ] `it redirects to the import preview page after a successful paste import`
- [ ] `it shows a validation error when pasted html is empty`
- [ ] `it shows a link on the url import page to switch to paste html import`

## Acceptance Criteria
- All requirements have passing tests.
- `Import.php` and its existing tests are unaffected except for the added cross-link in its Blade view.
- Route registered and named `recipes.import.paste`, inside the `auth` middleware group.
- `html` validation lives in a `rules()` method that reads `config('recipe-import.max_html_length')` at runtime.
- Pasted HTML content is never written to the logs.
- Code follows code standards.

## Implementation Notes
(Left blank - filled in by programmer during implementation)
