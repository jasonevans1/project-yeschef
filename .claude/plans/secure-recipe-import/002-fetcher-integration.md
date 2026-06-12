# Task 002: Integrate Guard into RecipeFetcher with Redirect Re-validation

**Status**: complete
**Depends on**: 001
**Retry count**: 0

## Description
Wire the `UrlSafetyValidator` into `RecipeFetcher` so the destination is validated before any
outbound request, and so each redirect hop is re-validated (a public URL must not be able to 302 to
an internal address). Guzzle's implicit auto-redirect is replaced with bounded manual redirect
following that runs the guard on every `Location` before fetching it. The existing `/test/` route
bypass for local/testing is preserved.

## Context
- Modify `app/Services/RecipeImporter/RecipeFetcher.php`. It currently calls `Http::get($url)` with
  auto-redirect (see existing test "follows redirects automatically").
- Inject `UrlSafetyValidator` via the constructor. Keep the existing `isLocalTestRoute()` /
  `fetchLocalRoute()` bypass — guard validation should be skipped only for those local test routes
  in local/testing env, exactly as today.
- Disable Guzzle automatic redirects with `->withOptions(['allow_redirects' => false])` (do NOT rely
  on a `withoutRedirecting()` helper — confirm the method exists before using it; `allow_redirects`
  is the safe option). Then implement a bounded loop:
    1. Validate the current URL with the guard.
    2. Fetch with redirects disabled.
    3. If the response status is a 3xx (300–399) AND has a `Location` header, resolve the `Location`
       (relative values resolved against the current absolute URL — use a URL resolver, not naive
       concatenation; `League\Uri` is not a dependency, so implement resolution with `parse_url` +
       manual base-merging or document a helper), increment the hop counter, and loop. Otherwise stop.
    4. If hops exceed `config('recipe-import.max_redirects')`, throw (reuse the existing generic
       `Exception` "Could not connect..." path or a clear redirect-limit message — pick one and make
       the matching test assert it).
- Status-code semantics: the existing `InvalidHTTPStatusException` is thrown on non-2xx via
  `! $response->successful()`. With manual redirects, a 3xx is NOT a failure and must NOT throw — only
  a non-2xx, non-3xx final response (or a 3xx with no `Location`) should raise
  `InvalidHTTPStatusException`. Make sure the existing 404/500 tests still throw and the redirect test
  still succeeds.
- IMPORTANT (Http::fake redirect behavior): `Http::fake()` does NOT process redirects itself — with
  `allow_redirects` disabled, the faked 302 response is returned verbatim, so the manual loop is what
  drives the existing "follows redirects automatically" test. Verify that test still passes against the
  manual loop; if `Http::fake` strips/handles the `Location` differently, adjust the fake in the test
  rather than the production logic.
- On `BlockedUrlException`, let it propagate (it is caught and surfaced in Task 003's `Import` component).
- Preserve existing behaviour: 30s `timeout(30)` AND `connectTimeout(10)`, all browser-like headers on
  EVERY hop, `InvalidHTTPStatusException` on non-2xx final response, `NetworkTimeoutException` / generic
  `Exception` on connection failure. The existing "sends browser-like headers" and "respects 30 second
  timeout" tests must still pass, so headers/timeout config must be applied to each request in the loop.
- DEPENDS ON Task 001's shared test binding: the existing feature tests in `tests/Feature/Recipe/`
  (`ImportRecipeTest`, `RecipeImportServiceTest`, `ImportRecipeDuplicateIngredientsTest`,
  `ImportPreviewCategorizationTest`) resolve `RecipeFetcher` -> `UrlSafetyValidator` from the container.
  They must NOT make real DNS calls after this change. Confirm Task 001's shared container binding maps
  `example.com` to a public IP; if any of those tests use a host other than `example.com`, extend the
  stub map. Run the full `tests/Feature/Recipe/` and `tests/Feature/RecipeImporter/` suites as part of
  this task, not just the new tests.
- Update `tests/Feature/RecipeImporter/RecipeFetcherTest.php`: it constructs `new RecipeFetcher` (no
  args) — update construction to pass a `UrlSafetyValidator` built with a **stub resolver** (do not
  remove existing tests; update them so `example.com` resolves to a public IP and redirect targets
  resolve public). Add the new blocking tests below. Also update the stale contract reference at
  `specs/006-import-recipe/contracts/livewire-components.md:368` if it instantiates `new RecipeFetcher()`
  with no args, or leave a note that it is illustrative only.

## Requirements (Test Descriptions)
- [x] `it validates the url with the safety guard before fetching`
- [x] `it throws BlockedUrlException when the url resolves to an internal address`
- [x] `it throws BlockedUrlException when a redirect points to an internal address`
- [x] `it follows a safe redirect to a public address and returns the final body` (existing "follows redirects automatically" preserved)
- [x] `it follows a safe relative redirect resolved against the current url`
- [x] `it stops and errors after exceeding the maximum redirect count`
- [x] `it still throws InvalidHTTPStatusException on a non-2xx final response` (existing 404/500 preserved)
- [x] `it still sends browser-like headers on a redirected request` (headers applied on every hop)
- [x] `it still fetches a valid public recipe url successfully` (existing behaviour preserved)
- [x] `it still serves local test routes without invoking the guard in testing env`

## Acceptance Criteria
- All requirements have passing tests, including the updated existing `RecipeFetcherTest` cases
- No internal host is ever fetched; the guard runs on the initial URL and every redirect hop
- No real DNS/network calls in tests (stub resolver injected)
- Existing public-fetch, timeout, status-code, header, and empty/large-response tests still pass
- Code follows code standards

## Implementation Notes
(Left blank - filled in by programmer during implementation)
