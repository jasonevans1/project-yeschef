# Plan: Secure Recipe Import (SSRF + Rate Limiting)

## Created
2026-06-11

## Status
completed

## Objective
Close the Server-Side Request Forgery hole in the recipe importer by validating the
destination of every outbound fetch (including redirects), and add per-user rate limiting
to the import action so it cannot be abused as an internal network scanner / request amplifier.

## Related Issues
none

## Discovery Notes
- The importer fetches arbitrary user-supplied URLs server-side at
  `app/Services/RecipeImporter/RecipeFetcher.php:23` via `Http::get($url)`. The only gate is
  `#[Validate('required|url|max:2048')]` in `app/Livewire/Recipes/Import.php:21`, which validates
  URL *format*, not destination — allowing `http://169.254.169.254/...`, `http://localhost`,
  and private/loopback/link-local ranges. Parsed page content is reflected back to the user on the
  import-preview screen, making this an exfiltration-capable read-SSRF.
- The `url` rule does not restrict the scheme, so non-`http(s)` schemes pass format validation.
- `RecipeFetcher` currently relies on Guzzle's automatic redirect following
  (test: "follows redirects automatically"), so a public URL can 302 to an internal one — the
  redirect target must also be validated.
- There is **no rate limiting** on the import action (contrast with `app/Livewire/Auth/Login.php`,
  which uses the `RateLimiter` facade keyed by email+IP — the pattern to mirror here).
- `RecipeFetcher` has an existing `local`/`testing` bypass for `/test/` routes
  (`isLocalTestRoute`) that must be preserved.
- Existing tests: `tests/Feature/RecipeImporter/RecipeFetcherTest.php` constructs `new RecipeFetcher`
  and uses `Http::fake()` against `example.com`. To keep tests hermetic (no real DNS), the SSRF guard
  must accept an **injectable DNS resolver** so internal/public hosts can be stubbed.
- CRITICAL hermeticity gap (beyond `RecipeFetcherTest`): the feature tests in `tests/Feature/Recipe/`
  (`ImportRecipeTest`, `RecipeImportServiceTest`, `ImportRecipeDuplicateIngredientsTest`,
  `ImportPreviewCategorizationTest`) build the importer via the container
  (`app(RecipeImportService::class)` / `Livewire::test(Import::class)`) and `Http::fake()` against
  `example.com`. Once the guard is wired in, these resolve `UrlSafetyValidator` with the PRODUCTION DNS
  resolver and would make a real DNS lookup for `example.com`. Task 001 must add a shared test-only
  container binding (stub resolver mapping `example.com` -> public IP) so these stay hermetic; Tasks
  002/003 must run those existing suites to confirm.
- `BlockedUrlException` will extend `Exception`; the `Import` component's catch chain ends in a generic
  `catch (Exception $e)`, so the new `catch (BlockedUrlException $e)` MUST be placed before it.

## Scope

### In Scope
- A reusable `UrlSafetyValidator` service that blocks non-http(s) schemes and hosts resolving to
  private/reserved/loopback/link-local/multicast IP ranges (IPv4 and IPv6), with an injectable
  DNS resolver for testability.
- A `BlockedUrlException` raised on unsafe URLs and surfaced as a friendly form error.
- Integrating the guard into `RecipeFetcher`, validating the initial URL **and every redirect hop**
  (manual, bounded redirect following replacing implicit Guzzle auto-redirect).
- Restricting the import URL validation to the `http`/`https` schemes.
- Per-user rate limiting on the `Import::import()` action, mirroring the existing `Login` pattern.
- A small `config/recipe-import.php` for rate-limit values (attempts/decay) and max redirects.

### Out of Scope
- Issue #3 from the review (wiring `RecipeSanitizer` into the import pipeline) — separate plan.
- Changing output escaping / Blade rendering.
- An allowlist of permitted recipe domains (block-by-IP-range is sufficient; allowlist can come later).
- Egress proxy / network-level controls.
- Any change to authentication, sharing, or grocery-list code.

## Success Criteria
- [ ] A request to a private/loopback/link-local/reserved IP or hostname is rejected before any
      socket to that host is opened, and the user sees a friendly error.
- [ ] A public URL that redirects to an internal address is blocked at the redirect hop.
- [ ] Non-`http(s)` schemes are rejected at validation time.
- [ ] The import action is rate limited per user; exceeding the limit returns a throttle error
      without performing a fetch.
- [ ] Existing legitimate imports (public hosts, normal redirects) still succeed.
- [ ] The existing `/test/` route bypass still works in local/testing.
- [ ] All tests passing
- [ ] Code follows project standards

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | SSRF URL safety guard service + BlockedUrlException + literal-IP handling + shared test-stub resolver binding | - | completed |
| 002 | Integrate guard into RecipeFetcher with bounded manual redirect re-validation (preserve status/header/timeout behaviour) | 001 | completed |
| 003 | Harden import endpoint: http(s) scheme + per-user rate limit (validate-before-throttle) + BlockedUrlException catch ordering | 001 | completed |

## Architecture Notes
- New service lives in `app/Services/RecipeImporter/UrlSafetyValidator.php` alongside the other
  importer services (follows existing Service-class convention).
- IP-range checks use PHP's `filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)`
  as the core test, plus explicit loopback/link-local handling for both IPv4 and IPv6.
- The guard takes an injectable `Closure $resolver` (host => array of IP strings). The production
  default resolves A and AAAA records; tests inject a deterministic stub so no real DNS is used.
- Redirects are followed manually (Guzzle auto-redirect disabled) up to `config('recipe-import.max_redirects')`,
  re-running the guard on each `Location` before the next request.
- Rate limiting uses `Illuminate\Support\Facades\RateLimiter` inside `Import::import()`, keyed by
  `auth()->id()`, mirroring `app/Livewire/Auth/Login.php`. Limits read from `config/recipe-import.php`.

## Risks & Mitigations
- **DNS rebinding (TOCTOU between validation and connect):** the guard resolves and validates IPs,
  but Guzzle re-resolves on connect. Mitigation: validate every redirect hop and resolve once,
  documenting the residual risk; a full fix (pinning the validated IP into the connection) is noted
  as a follow-up but out of scope for this plan.
- **Breaking legitimate redirects:** many recipe sites redirect (http→https, trailing slash).
  Mitigation: keep manual redirect following (bounded), only blocking hops that resolve internal.
- **Test hermeticity / flakiness from real DNS:** mitigated by the injectable resolver stub AND a
  shared test-only container binding (Task 001) so container-resolved feature tests never hit real DNS.
- **False positives blocking valid public hosts:** covered by explicit public-IP passes in tests.
