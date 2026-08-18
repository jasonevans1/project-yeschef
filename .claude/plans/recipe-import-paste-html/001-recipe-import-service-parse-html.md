# Task 001: Add RecipeImportService::parseHtml() + fix Cloudflare false positive

**Status**: pending
**Depends on**: none
**Retry count**: 0

## Description
Add a `parseHtml(string $html): array` method to `RecipeImportService` that runs the same parse → transform → validate pipeline as `fetchAndParse()`, but skips the fetch step entirely (takes HTML directly instead of a URL). This is the shared building block the paste-import flow will use in Task 002.

Also fix a blocking bug in `MicrodataParser::parse()`: its Cloudflare-challenge heuristic runs before the JSON-LD scan and rejects perfectly good pages (see "Cloudflare detection ordering" below). Without this fix the paste path fails on exactly the sites this feature exists to serve.

## Context
- Files to modify: `app/Services/RecipeImporter/RecipeImportService.php`, `app/Services/RecipeImporter/MicrodataParser.php`
- `fetchAndParse()` currently does: `$this->fetcher->fetch($url)` → `$this->parser->parse($html)` → `$this->transform($recipeData)` → `$this->validateRequiredFields($transformed)`.
- Extract the last three steps (parse/transform/validate) into a private method, e.g. `parseAndTransform(string $html): array`, and have both `fetchAndParse()` and the new `parseHtml()` call it. Do not duplicate the transform/validation logic.
- `parseHtml()` throws the same exceptions as the parse/transform stage already does: `MissingRecipeDataException`, `MalformedRecipeDataException`, `CloudflareBlockedException` (from `MicrodataParser::parse()`). It does not throw `NetworkTimeoutException`, `InvalidHTTPStatusException`, or `BlockedUrlException` since no HTTP request is made.
- Existing test files to extend: `tests/Feature/Recipe/RecipeImportServiceTest.php` (service; the suite builds it via `app(RecipeImportService::class)` in `beforeEach`) and `tests/Feature/RecipeImporter/MicrodataParserTest.php` (parser). Follow their existing style — plain Pest `test()`/`expect()`, `Http::fake()` not needed for `parseHtml()` since no HTTP call happens.

### Cloudflare detection ordering (must fix)
`MicrodataParser::parse()` currently calls `isCloudflareChallenge($html)` against the whole document **before** scanning for JSON-LD. Its indicator list includes `challenge-platform` and `Just a moment...`. Cloudflare injects `/cdn-cgi/challenge-platform/...` scripts into **normal, successfully-served pages** on Cloudflare-fronted sites, so a user pasting a fully rendered recipe DOM from such a site gets `CloudflareBlockedException` even though the recipe JSON-LD is present. (The URL path has the same latent bug.)

Fix it in the one shared place: keep the `empty(trim($html))` guard first, then scan for a Recipe in the JSON-LD and return it if found. Only when **no** recipe was found does the challenge heuristic decide the exception: `isCloudflareChallenge($html)` → `CloudflareBlockedException`, otherwise `MissingRecipeDataException`. Do not add a flag/parameter to `parse()` — both callers want this behavior.

All three existing `MicrodataParserTest` Cloudflare tests use challenge HTML with no JSON-LD, so they must stay green unchanged. Same for `test('import shows helpful error for Cloudflare-protected sites')` in `tests/Feature/Recipe/ImportRecipeTest.php`.

While in `parse()`, drop the `$foundJsonLd` variable — it is assigned and never read.

## Requirements (Test Descriptions)
- [ ] `it parses html directly and returns transformed recipe data when valid json-ld recipe is present`
- [ ] `it throws missing recipe data exception when parsing html with no recipe data`
- [ ] `it throws malformed recipe data exception when parsing html with a recipe missing required fields`
- [ ] `it does not make any http request when parsing html directly`
- [ ] `it parses a recipe from html that also contains a cloudflare challenge-platform script tag` (MicrodataParserTest — regression guard for the ordering fix)
- [ ] `it still throws cloudflare blocked exception for a challenge page with no recipe data` (MicrodataParserTest)

## Acceptance Criteria
- All requirements have passing tests.
- `fetchAndParse()` behavior and its existing tests are unaffected by the refactor.
- The full existing `MicrodataParserTest.php`, `RecipeImportServiceTest.php`, `ImportRecipeTest.php`, and `ImportHardeningTest.php` suites still pass.
- Code follows code standards (explicit return types). Match the surrounding file's existing comment style rather than introducing a new one.

## Implementation Notes
(Left blank - filled in by programmer during implementation)
