# Devil's Advocate Review: recipe-import-paste-html

Reviewed against: `app/Services/RecipeImporter/RecipeImportService.php`, `app/Services/RecipeImporter/MicrodataParser.php`, `app/Livewire/Recipes/Import.php`, `app/Livewire/Recipes/ImportPreview.php`, `routes/web.php`, `config/recipe-import.php`, `tests/Feature/Recipe/ImportHardeningTest.php`, `tests/Feature/RecipeImporter/MicrodataParserTest.php`.

## Critical (Must fix before building)

### C1 — `MicrodataParser` will reject the exact pages this feature exists to import (Tasks 001, 002, 003)
`MicrodataParser::parse()` runs `isCloudflareChallenge($html)` against the **entire** HTML *before* looking for JSON-LD, and the indicator list includes `challenge-platform` and `Just a moment...`. Cloudflare injects `/cdn-cgi/challenge-platform/...` script tags into **normal, successfully-served pages** on Cloudflare-fronted sites — a large share of recipe sites. A user who pastes a fully rendered DOM from such a site gets `CloudflareBlockedException` ("this site cannot be imported automatically") even though the recipe JSON-LD is sitting right there in the payload. That defeats the entire premise of the plan (client-side capture to get past bot protection).

The same latent bug already affects the URL path, so this is a root-cause fix in one shared place, not a paste-only workaround.

**Fix**: in `MicrodataParser::parse()`, move the challenge detection to *after* the JSON-LD scan — if a Recipe is found, return it; only when nothing is found does the challenge heuristic decide between `CloudflareBlockedException` and `MissingRecipeDataException`. All three existing `MicrodataParserTest` Cloudflare tests use challenge HTML with no JSON-LD, so they stay green. Assigned to Task 001.

### C2 — Task 003's stated implementation of the size cap is not valid PHP
Task 003 says the `max_html_length` rule "needs to be added to `ImportPaste`'s `#[Validate]` rule". PHP attribute arguments must be compile-time constant expressions — `#[Validate('max:'.config('recipe-import.max_html_length'))]` is a fatal error. Also, splitting one property's rule string across two tasks guarantees rework.

**Fix**: `ImportPaste` defines a `rules(): array` method (no `#[Validate]` attribute on `html`) that reads config at runtime, and Task 002 owns the complete rule including `max`. Task 003 owns only the *tests* for the cap.

### C3 — Error-bag key for the paste component is undefined (Tasks 002, 003, 004)
`Import.php` uses `addError('url', ...)` everywhere and the view renders `<flux:error name="url" />`. Nothing in the plan says what the paste component's key is. Task 003's tests (`assertHasErrors([...])`) cannot be written against an undefined key, and the Task 002 worker could plausibly pick `html`, `paste`, or `import`.

**Fix**: pin it — all `ImportPaste` errors use the `html` key; view renders `<flux:error name="html" />`. Stated in Task 002, referenced by Task 003.

### C4 — Task 002 never names its test file
Task 002 lists six test descriptions but no file path, while Task 003 names `ImportPasteHardeningTest.php`. Two parallel workers could create colliding/duplicate files, and the cross-link test has no obvious home.

**Fix**: Task 002 → `tests/Feature/Recipe/ImportPasteTest.php`; the "URL import page links to paste import" assertion goes in that same file (not in the existing `ImportRecipeTest.php`, which stays untouched).

## Important (Should fix before building)

### I1 — A 5,000,000-char cap will produce 419/413/500s in production, not a friendly validation error
Livewire sends the component snapshot **and** the property update in the same POST, so once `html` is populated any subsequent request carries the payload twice. PHP's default `post_max_size` is 8M and nginx's default `client_max_body_size` is 1M (nothing in `.ddev/` overrides either). A 5MB paste that fails validation and is resubmitted blows past both, and the failure surfaces as a dead Livewire request with no user-facing message — the opposite of the plan's "friendly error handling" success criterion.

**Fix**: default `max_html_length` to `2_000_000`; require `wire:model` (deferred — never `.live` or `.blur`) on the textarea; `$this->reset('html')` after the cache write succeeds and before the redirect so the redirect response doesn't echo the payload back. Note the server-limit ceiling in the plan's risk list.

### I2 — Logging leaks / bloat on the paste path (Task 002)
`Import.php` logs `'url' => $this->url` in three places. A literal mirror would log the entire pasted HTML document into `laravel.log` — megabytes per attempt, and it may contain the user's logged-in page state (names, addresses, cart contents from the source site).

**Fix**: log `html_length` only. Never log `$this->html` contents, including in the generic `Exception` handler.

### I3 — The bookmarklet's `window.open` will be popup-blocked, and the plan's stated rationale is wrong (Task 004)
Task 004 says to "build the URL to open *before* the async clipboard write resolves" — but building a string was never the issue. The real constraint is **transient user activation**: `window.open()` called from a promise `.then()` is blocked by Firefox and by Chrome once activation has expired. Opening synchronously first isn't a fix either, since `navigator.clipboard.writeText()` rejects with `NotAllowedError: Document is not focused` after focus moves to the new tab.

**Fix**: keep `window.open` in the `.then()` (clipboard resolves in ms, inside the activation window) but handle the blocked case: `var w = window.open(dest,'_blank'); if (!w) { location.href = dest; }`. Clipboard already holds the HTML at that point, so falling back to same-tab navigation is harmless.

### I4 — Bookmarklet built inline in Blade risks Blade-parser and escaping bugs (Task 004)
Blade's echo compiler runs over inline HTML including `@php` block bodies; any `{{` that appears in minified JS becomes a Blade expression. Escaping choice also matters: `{!! !!}` would emit raw `'` into the attribute and is the wrong default for a user-visible URI.

**Fix**: build the full `javascript:` URI as a string in `ImportPaste::render()` and pass it to the view; echo with `{{ }}` (entity-encoded quotes are decoded by the browser before URI parsing, so it works). Keep the JS on one line with no `%` and no `#` characters (both change bookmarklet URI semantics).

### I5 — Task 003's size-cap test would allocate a multi-megabyte string (Task 003)
`str_repeat('a', 2_000_001)` set through `Livewire::test()->set()` serializes megabytes per assertion and is slow/flaky. It also fails to prove the rule is config-driven.

**Fix**: `config(['recipe-import.max_html_length' => 100])` in the test and paste ~200 chars. This doubles as proof that `rules()` reads config at runtime rather than hardcoding a literal (which C2's fix requires).

### I6 — "Shared rate limit" test is asserted but not specified (Task 003)
The acceptance criterion says "a test proves attempts on `Import` and `ImportPaste` deplete the same counter" but gives no mechanism. Both components resolve `RecipeImportService` via method injection, so a single `app()->instance()` mock must stub **both** `fetchAndParse` and `parseHtml`.

**Fix**: spell out the procedure — clear the bucket, drive `max_attempts` successful `Import` calls against a mock stubbing `fetchAndParse`, then assert the next `ImportPaste` call errors and `parseHtml` is never called.

### I7 — Route placement / guest access unstated (Task 002)
`ImportPaste` calls `auth()->id()` for both the cache and rate-limit keys; if the route lands outside the `auth` group in `routes/web.php` those keys become `recipe_import_preview:` (shared across all guests) — a cross-user data leak. The plan says only "alongside the existing import routes".

**Fix**: explicit acceptance criterion that the route is registered **inside** the existing `Route::middleware(['auth'])->group()`, plus a guest-redirect test.

### I8 — Copying `Import.php`'s catch block verbatim carries dead code (Task 002)
The `str_contains($e->getMessage(), 'Could not connect')` branch in the generic `Exception` handler exists for `RecipeFetcher` connection failures. No HTTP request happens on the paste path, so that branch is unreachable.

**Fix**: drop that branch; keep the generic `Exception` catch with logging (length only, per I2).

## Minor (Nice to address)

- **M1** — `MicrodataParser::$foundJsonLd` is assigned and never read (pre-existing dead code). Task 001 touches this method; removing it is free.
- **M2** — `DOMDocument::loadHTML()` assumes ISO-8859-1 when no `<meta charset>` is present, so UTF-8 recipe names can come back as mojibake rather than an exception. Shared with the URL path; `document.documentElement.outerHTML` normally includes the meta tag, so low impact.
- **M3** — `_plan.md` success criteria mention a "placeholder" payload producing friendly errors; no task covers a "placeholder" case (only missing/malformed/oversized). Either drop the word or define it.
- **M4** — Firefox blocks `javascript:` bookmarklets on pages with a restrictive CSP. The Task 004 alert text only fires on clipboard failure, not on "the bookmarklet never ran". A static "if the bookmarklet doesn't run on a site: view-source, select all, copy" line on the page covers it (folded into the instructions requirement).
- **M5** — Task 001's acceptance criterion "no comments beyond the existing PHPDoc style already in the file" contradicts the file, which is full of inline `//` step comments. `RecipeImportService.php` also lacks `declare(strict_types=1)` unlike the Livewire components. Leave as-is for consistency, but the wording will confuse a worker.
- **M6** — `ImportPreview::mount()` (cache-lost path) and `cancel()` both redirect to `route('recipes.import')`. A user who came from the paste page is bounced to the URL page and their pasted HTML is gone. Fixing properly means storing the origin in the cached payload; the cross-link on the URL page makes it a one-click recovery, so probably not worth it.

## Questions for the Team

- **Q1** — Should pasted imports capture a `source_url` after all? The pasted DOM almost always contains `<link rel="canonical">` or `og:url`, so one line in `parseHtml()` would preserve recipe attribution instead of storing `null`. Deliberate omission or oversight?
- **Q2** — Is `2_000_000` chars the right cap, or should the server limits (`post_max_size`, `client_max_body_size`) be raised instead so 5MB pages work? Some heavy recipe sites exceed 2MB of rendered DOM.
- **Q3** — Should the bookmarklet live anywhere more discoverable than the paste-import page (e.g. Settings), given a user only needs to install it once but has to navigate to an import page to find it?
