# Plan: Recipe Import — Paste HTML + Bookmarklet

## Created
2026-08-16

## Status
ready

## Objective
Add a second recipe-import path that works when a site blocks server-side fetching (e.g. Mayo Clinic's Akamai bot protection): the user pastes the recipe page's HTML directly, and a bookmarklet automates capturing + copying that HTML from the user's own browser session.

## Related Issues
none

## Discovery Notes
- Recipe import via URL is **already fully built** (`006-import-recipe`): `RecipeFetcher` → `MicrodataParser` (JSON-LD only) → `RecipeImportService::fetchAndParse()` → cached preview → editable `ImportPreview` Livewire component → saves `Recipe` + `RecipeIngredient`. This plan does not rebuild that path.
- Confirmed live: the example URL (Mayo Clinic) returns HTTP 403 from Akamai on *every* request from this environment, including the homepage — not a headers/JS/JSON-LD problem, it's IP-reputation bot blocking. No fetcher change fixes this, and a production server (also a datacenter IP) would very likely hit the same wall. Building a stealth/evasion fetcher (headless browser + residential proxies) was explicitly ruled out.
- `RecipeSanitizer` exists but is **not currently wired into** `RecipeImportService` or `Import.php` — the URL path caches the transformed data unsanitized (Blade escaping + save-time validation are the actual safety net today). This plan follows that same existing convention for the paste path rather than introducing new sanitization the URL path doesn't have (out of scope; not this plan's bug to fix).
- Prior art confirmed via research: Mealie (comparable self-hosted recipe manager) ships a community Chrome extension ("Mini Mealie") with an "HTML mode" specifically for JS-heavy/paywalled/blocked sites — it captures the page's live DOM client-side and sends it to the server, exactly the pattern this plan implements. Mealie's own bookmarklet only passes the URL for server-side re-fetch, which would not solve the Akamai case — not used as a model here.
- Key simplification found during discovery: the bookmarklet does **not** need to duplicate any JSON-LD extraction logic in JavaScript. It only needs to capture `document.documentElement.outerHTML` (the live, already-rendered DOM) and hand it to the existing server-side `MicrodataParser`, which already does 100% of the JSON-LD-finding work. This also means client-side-rendered JSON-LD (injected after page load) gets picked up, which a raw HTTP fetch would miss.
- Existing rate limiting for imports uses key `'recipe-import:'.auth()->id()` (config `recipe-import.rate_limit.*`). The paste path reuses this exact bucket so URL and paste attempts share one limit.

## Scope

### In Scope
- `RecipeImportService::parseHtml(string $html): array` — parses/transforms/validates pasted HTML without fetching (extracted shared logic from `fetchAndParse`).
- `MicrodataParser::parse()` Cloudflare-detection reordering — the challenge heuristic currently runs before the JSON-LD scan and matches `challenge-platform`, a string Cloudflare injects into **normal** pages on CF-fronted sites. Left as-is, pasted HTML from a large share of recipe sites is rejected with "blocked by Cloudflare" despite containing the recipe. Detection moves to after the scan, so it only fires when no recipe was found. (Fixes the same latent bug on the URL path.)
- New `Recipes\ImportPaste` Livewire component + `recipes/import/paste` route + Blade view: textarea for pasted HTML, same exception handling and cache-key convention as `Import.php` (`recipe_import_preview:{userId}`) so the existing `ImportPreview` component consumes it unmodified.
- Config: `recipe-import.max_html_length` cap on pasted HTML size.
- Cross-links between the URL-import page and the paste-import page.
- A bookmarklet (plain `javascript:` link, no browser-extension packaging) rendered on the paste-import page: captures `document.documentElement.outerHTML`, copies it to the clipboard, opens the paste-import page in a new tab. Generated via `route('recipes.import.paste', absolute: true)` so it's correct per environment.
- Tests for all of the above, following existing test-file conventions (`ImportRecipeTest.php` / `ImportHardeningTest.php` split).

### Out of Scope
- Full browser extension (manifest, packaging, store submission).
- Legacy HTML microdata (`itemprop`) parsing — `MicrodataParser` stays JSON-LD-only, matching current scope.
- Any server-side technique to bypass bot/anti-scraping protection (headless browser stealth, residential proxies, third-party scraping APIs).
- Retrofitting `RecipeSanitizer` into either import path.
- Automated end-to-end testing of the bookmarklet's runtime JS on third-party origins — inherently impossible from Pest (it must run against real external pages outside this app's control). Only href-generation is automated; runtime behavior is a manual check.

## Success Criteria
- [ ] User can paste a recipe page's raw HTML and get the same preview/edit/save flow as URL import.
- [ ] Pasted HTML without recipe JSON-LD, with malformed JSON-LD, or with a placeholder/oversized payload all produce the same friendly error handling as the URL path.
- [ ] URL and paste import attempts share one rate-limit bucket.
- [ ] A bookmarklet link on the paste-import page, when dragged to the bookmarks bar and clicked on any recipe page, copies that page's HTML to the clipboard and opens the paste-import page.
- [ ] All tests passing.
- [ ] Code follows project standards.

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | `RecipeImportService::parseHtml()` + `MicrodataParser` Cloudflare-detection reorder | - | pending |
| 002 | `ImportPaste` component, route, view, config, validation rules | 001 | pending |
| 003 | Paste-path hardening tests (errors, size cap, shared rate limit) | 002 | pending |
| 004 | Bookmarklet link on paste-import page | 002 | pending |

## Architecture Notes
- Mirror `Import.php`'s exception-handling and caching pattern in `ImportPaste.php` — same cache key prefix, same rate-limit key, same subset of caught exceptions — so `ImportPreview` needs zero changes. Two deliberate divergences: log `html_length` instead of the payload, and drop the unreachable `Could not connect` branch.
- `parseHtml()` and `fetchAndParse()` should share a private helper for the parse→transform→validate steps rather than duplicating them.
- All `ImportPaste` errors use the `html` error-bag key; the view renders `<flux:error name="html" />`.
- `html` validation lives in a `rules()` method, not a `#[Validate]` attribute — the `max:` bound comes from config, and PHP attribute arguments must be compile-time constants.

## Risks & Mitigations
- Pasted HTML could be very large (full page source): capped via `recipe-import.max_html_length` (2,000,000 chars). Ceiling to be aware of: Livewire ships the property in both the snapshot and the update, so the request is roughly 2× the string; PHP's default `post_max_size` is 8M and nginx's default `client_max_body_size` is 1M, and exceeding either produces a dead Livewire request rather than a validation error. Raising the cap means raising those server limits too.
- Textarea must use deferred `wire:model` (never `.live`/`.blur`), and `reset('html')` runs before the redirect so the payload isn't shipped on every keystroke or echoed back on success.
- Pasted HTML must never be written to the logs — it can contain the user's logged-in state from the source site.
- Clipboard write in the bookmarklet requires a secure context + user gesture: both are satisfied (bookmarklet click on an https recipe page is a user gesture); falls back to an alert + manual copy if `navigator.clipboard` is unavailable, and to same-tab navigation if `window.open` is popup-blocked.
- Firefox blocks `javascript:` bookmarklets outright on pages with a restrictive CSP; the paste page carries a static "View Source and copy manually" fallback instruction for that case.
