# Task 003: Paste-import hardening (errors, size cap, shared rate limit)

**Status**: pending
**Depends on**: 002
**Retry count**: 0

## Description
Add the error-path and abuse-prevention coverage for the paste-import flow, mirroring `tests/Feature/Recipe/ImportHardeningTest.php`'s coverage of the URL path: missing/malformed recipe data, oversized input, and rate limiting shared with URL import.

## Context
- Reference: `tests/Feature/Recipe/ImportHardeningTest.php` (rate-limit tests use `mock(RecipeImportService::class)` + `app()->instance(...)` and `RateLimiter::hit()`/`RateLimiter::clear('recipe-import:'.$user->id)` directly — reuse that exact key since Task 002 wired paste to the same bucket).
- New test file: `tests/Feature/Recipe/ImportPasteHardeningTest.php`.
- This task is **tests only** for the validation rules — Task 002 already defines the complete `rules()` method including `max:`. All errors assert against the `html` error-bag key.
- **Size-cap test**: do not build a 2,000,000-character string (megabytes serialized through `Livewire::test()->set()` per assertion). Instead override the config in the test — `config(['recipe-import.max_html_length' => 100]);` — and set ~200 characters of HTML. This also proves the rule reads config at runtime rather than hardcoding a literal.
- **Shared-bucket test procedure**: `RateLimiter::clear('recipe-import:'.$user->id)`, register one `app()->instance(RecipeImportService::class, $mock)` whose mock stubs **both** `fetchAndParse` (returning valid data, `times(max_attempts)`) and `parseHtml` (`shouldNotReceive`). Drive `max_attempts` successful `Import` calls, then assert the next `ImportPaste` call has errors on `html` and that `parseHtml` was never invoked. Both components resolve the service by method injection, so the single container instance covers both.
- `MissingRecipeDataException` / `MalformedRecipeDataException` cases: construct HTML strings directly in the test (no `Http::fake()` needed, unlike the URL-path hardening tests) — e.g. HTML with no `<script type="application/ld+json">` tag, HTML with invalid JSON in that tag. Note these must be at least 50 characters to clear the `min:50` rule.
- Do **not** add a Cloudflare-challenge test for the paste path. Task 001 deliberately reorders that heuristic so it only fires when no recipe JSON-LD is found; a challenge page pasted by the user simply has no recipe data. Asserting the old behavior here would contradict Task 001.

## Requirements (Test Descriptions)
- [ ] `it shows an error when pasted html has no recipe data`
- [ ] `it shows an error when pasted html contains malformed json-ld`
- [ ] `it rejects pasted html exceeding the maximum allowed length`
- [ ] `it does not count a failed validation attempt against the shared rate limit`
- [ ] `it blocks a paste import that exceeds the shared rate limit and does not call the import service`
- [ ] `it counts a paste import attempt toward the same rate limit bucket as url import`

## Acceptance Criteria
- All requirements have passing tests.
- Rate limit is verifiably shared: a test proves attempts on `Import` and `ImportPaste` deplete the same counter.
- No test allocates a multi-megabyte string.
- Code follows code standards.

## Implementation Notes
(Left blank - filled in by programmer during implementation)
