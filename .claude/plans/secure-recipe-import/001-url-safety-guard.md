# Task 001: SSRF URL Safety Guard Service

**Status**: complete
**Depends on**: none
**Retry count**: 0

## Description
Create a reusable `UrlSafetyValidator` service that determines whether an outbound URL is safe to
fetch. It blocks non-`http(s)` schemes and any host that resolves to a private, reserved, loopback,
link-local, or multicast IP address (IPv4 and IPv6). DNS resolution is performed through an
injectable closure so the guard is fully unit-testable without real network access. Unsafe URLs
raise a new `BlockedUrlException`.

## Context
- Create `app/Services/RecipeImporter/UrlSafetyValidator.php` (follows the existing Service-class
  convention used by the other importer services in that directory).
- Create `app/Exceptions/BlockedUrlException.php` following the existing custom exceptions in
  `app/Exceptions/` (e.g. `NetworkTimeoutException`, `InvalidHTTPStatusException`) — give it a
  user-friendly default message such as "This URL is not allowed."
- Core IP test: `filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)`
  returns false for private/reserved ranges; also explicitly reject loopback (`127.0.0.0/8`, `::1`),
  link-local (`169.254.0.0/16`, `fe80::/10`, including the cloud metadata IP `169.254.169.254`),
  and unspecified `0.0.0.0`/`::`.
- Constructor signature: `__construct(private ?Closure $resolver = null)`. When null, default to a
  resolver that returns A + AAAA records (e.g. via `gethostbynamel()` plus `dns_get_record(..., DNS_AAAA)`).
  The public API method should be `validate(string $url): void` (throws `BlockedUrlException`) — and/or
  an `isSafe(string $url): bool` helper if convenient.
- IMPORTANT (host parsing): parse the host with `parse_url($url, PHP_URL_HOST)`. PHP returns IPv6
  literal hosts wrapped in brackets (e.g. `[::1]`) — strip the brackets before resolving/validating.
  If the host is already a valid IP literal (`filter_var($host, FILTER_VALIDATE_IP)`), validate it
  directly WITHOUT calling the resolver (so `http://127.0.0.1`, `http://[::1]`, `http://169.254.169.254`
  are blocked even though they are not hostnames). A URL with no host (e.g. `mailto:`, malformed) must
  be rejected.
- IMPORTANT (resolver default): the default closure must be a property the container can override,
  not hard-wired inline, because feature tests resolve this class from the container (see below). When
  `$resolver` is null, fall back to a private `defaultResolver()` method. Do NOT silently treat a
  resolver returning an empty array as safe — an unresolvable host must be rejected (fail closed).
- Tests live at `tests/Unit/Services/RecipeImporter/UrlSafetyValidatorTest.php` (mirror source structure).
  Inject a stub resolver in every test so no real DNS lookup occurs. Use a Pest dataset for the
  blocked-range cases.
- TEST-HERMETICITY CONTRACT (consumed by Tasks 002 and 003): existing feature tests
  (`tests/Feature/Recipe/ImportRecipeTest.php`, `RecipeImportServiceTest.php`,
  `ImportRecipeDuplicateIngredientsTest.php`, `ImportPreviewCategorizationTest.php`) construct the
  importer via the container (`app(RecipeImportService::class)` / `Livewire::test(Import::class)`) and
  `Http::fake()` against `example.com`. After the guard is wired in (Task 002), those tests would hit
  the real DNS resolver for `example.com` — non-hermetic and CI-fragile. To prevent this, register a
  test-only binding in `tests/Pest.php` (or a shared `beforeEach` in `tests/TestCase.php`) that binds
  `UrlSafetyValidator` in the container with a stub resolver mapping `example.com` (and any other test
  hosts used) to a fixed public IP such as `93.184.216.34`. This task must add that shared binding so
  the existing `example.com`-based feature tests stay green and hermetic. Document the exact host=>IP
  map used.

## Requirements (Test Descriptions)
- [x] `it allows a public http url whose host resolves to a public ip`
- [x] `it allows a public https url whose host resolves to a public ip`
- [x] `it rejects urls with a non http or https scheme`
- [x] `it rejects a host resolving to a loopback address`
- [x] `it rejects a host resolving to a private network range`
- [x] `it rejects a host resolving to the link local cloud metadata address`
- [x] `it rejects a host resolving to an ipv6 loopback or link local address`
- [x] `it rejects a url given as a literal private ip without dns lookup`
- [x] `it rejects an ipv6 literal loopback host given in brackets without dns lookup`
- [x] `it rejects the cloud metadata ip given as a literal without dns lookup`
- [x] `it rejects a host that fails to resolve to any address`
- [x] `it rejects a host that resolves to a mix of public and private ips`
- [x] `it rejects a malformed url with no host`
- [x] `it allows a literal public ip without dns lookup`

## Acceptance Criteria
- All requirements have passing tests
- No test performs a real DNS or network call (resolver is always stubbed)
- A literal-IP host (IPv4 or bracketed IPv6) is validated directly without invoking the resolver
- An empty/failed resolver result is rejected (fail closed), and a host resolving to any internal IP
  is rejected even if it also resolves to a public IP
- The shared test-only container binding (stub resolver for `example.com` etc.) is added so existing
  feature tests remain hermetic
- `BlockedUrlException` carries a user-friendly message
- Code follows code standards (strict types, explicit return types, constructor promotion)
- No decrease in test coverage

## Implementation Notes
- `app/Exceptions/BlockedUrlException.php` — extends `Exception` with user-friendly default message "This URL is not allowed."
- `app/Services/RecipeImporter/UrlSafetyValidator.php` — constructor-injected `?Closure $resolver`, `validate(string $url): void` public API.
  - Non-http/https schemes rejected immediately.
  - IPv6 bracket-stripped before `filter_var` check.
  - Literal IP hosts validated directly (no DNS call) using `isPublicIp()`.
  - DNS hostnames resolved via injected resolver; empty result fails closed.
  - `isPublicIp()` uses `filter_var(..., FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)` plus explicit checks for 127.x.x.x loopback, 169.254.x.x link-local, fe80::/10 IPv6 link-local, ::1 loopback, and 0.0.0.0/:: unspecified.
  - Any single IP in the resolved list failing the public-IP check causes rejection (fail-closed for mixed results).
- `tests/Unit/Services/RecipeImporter/UrlSafetyValidatorTest.php` — 29 tests (14 requirements, some with datasets), all DNS stubbed via injected closure.
- `tests/Pest.php` — `beforeEach` binding in `'Feature', 'Browser'` scope registers `UrlSafetyValidator` with stub resolver mapping: `example.com => 93.184.216.34`, `slow-site.com => 93.184.216.34`, `unreachable.com => 93.184.216.34`, `yeschef.ddev.site => 93.184.216.34`.
