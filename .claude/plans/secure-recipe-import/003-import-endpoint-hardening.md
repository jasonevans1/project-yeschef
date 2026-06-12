# Task 003: Harden Import Endpoint (Scheme Restriction + Rate Limiting + Error Handling)

**Status**: complete
**Depends on**: 001
**Retry count**: 0

## Description
Harden the `Recipes\Import` Livewire component: restrict the submitted URL to the `http`/`https`
schemes at validation time, add per-user rate limiting to the `import()` action so it cannot be
driven as a fast internal scanner / request amplifier, and surface `BlockedUrlException` as a
friendly field error. Rate-limit values live in a new `config/recipe-import.php`.

## Context
- Modify `app/Livewire/Recipes/Import.php`. Change the validation attribute from
  `#[Validate('required|url|max:2048')]` to restrict the scheme:
  `#[Validate('required|url:http,https|max:2048')]`. NOTE: the `url:http,https` parameterized form is
  Laravel 11.x+/12 syntax (confirmed available in this app's Laravel 12). Verify the existing
  `import component validates URL format` test (uses `not-a-valid-url`) still asserts a `url` error,
  and that the new `it rejects a non http or https url at validation` test uses a scheme like
  `ftp://example.com` or `javascript:alert(1)` to exercise the restriction.
- ORDERING MATTERS. The flow must be: (1) `$this->validate()` first; (2) only after validation passes,
  check the rate limiter and `hit()` it; (3) then call the import service. This guarantees the
  `it does not count a failed validation attempt against the fetch rate limit` requirement — a request
  that fails validation must return before `RateLimiter::hit()` is ever called. Place the rate-limit
  check between `$this->validate()` and the `try { $importService->fetchAndParse(...) }` block.
- Add rate limiting using `Illuminate\Support\Facades\RateLimiter`, mirroring the `tooManyAttempts` /
  `hit` / `availableIn` pattern in `app/Livewire/Auth/Login.php`. Key by `auth()->id()`
  (e.g. `'recipe-import:'.auth()->id()`). Decide and document the surfacing mechanism: the existing
  `Login` component throws `ValidationException::withMessages(['email' => ...])`, but this component's
  existing error pattern uses `$this->addError('url', ...)`. For consistency with THIS component, use
  `$this->addError('url', $throttleMessage)` and `return` on limit exceeded (do NOT mix in a thrown
  ValidationException, which would bypass the component's existing catch structure). Call
  `RateLimiter::hit($key, config('recipe-import.rate_limit.decay_seconds'))` BEFORE the fetch so the
  attempt is counted even on success; optionally `RateLimiter::clear($key)` is NOT needed here. Read
  `max_attempts` and `decay_seconds` from config.
- Add a `catch (BlockedUrlException $e)` branch. IMPORTANT: place it BEFORE the existing
  `catch (Exception $e)` branch (since `BlockedUrlException extends Exception`, a more-general catch
  first would swallow it). It calls `$this->addError('url', $e->getMessage())` with the exception's
  friendly message. Also add `use App\Exceptions\BlockedUrlException;` to the imports.
- Create `config/recipe-import.php` returning `['rate_limit' => ['max_attempts' => 10, 'decay_seconds' => 60], 'max_redirects' => 5]`
  (used here and by Task 002). Use `config(...)`, never `env(...)`, in the component.
- Tests: add/extend `tests/Feature/Recipe/` Livewire tests using `Livewire::test(Import::class)`.
  Mock/fake the import service so no real fetch happens (e.g. bind a mock `RecipeImportService` in the
  container, or use `Http::fake()` together with Task 001's stub-resolver binding). Assert validation,
  rate-limit, and blocked-url error behaviours.
- IMPORTANT (limiter isolation): `RateLimiter` state persists across tests within a process. Call
  `RateLimiter::clear('recipe-import:'.$user->id)` in a `beforeEach`/at the start of each rate-limit
  test, OR ensure the cache store is reset by `RefreshDatabase` is NOT sufficient (the limiter uses the
  cache store, not the DB). Explicitly clear the limiter key per test to avoid cross-test bleed.
- For `it surfaces a friendly url error when the import service throws BlockedUrlException`: make the
  mocked/faked import service throw `BlockedUrlException` and assert the friendly message appears on the
  `url` field. Do not rely on a real internal-IP fetch.
- DEPENDS ON Task 001's shared stub-resolver binding for any test that exercises the real
  container-resolved import path with `Http::fake()` against `example.com`.

## Requirements (Test Descriptions)
- [x] `it rejects a non http or https url at validation`
- [x] `it accepts a well formed https url at validation`
- [x] `it allows imports up to the configured rate limit`
- [x] `it blocks an import that exceeds the rate limit and does not call the import service`
- [x] `it shows a friendly throttle message on the url field when rate limited`
- [x] `it surfaces a friendly url error when the import service throws BlockedUrlException`
- [x] `it does not count a failed validation attempt against the fetch rate limit`

## Acceptance Criteria
- All requirements have passing tests
- Rate limit and redirect settings come from `config/recipe-import.php` (no `env()` in the component)
- Exceeding the limit prevents the outbound fetch entirely
- `BlockedUrlException` renders as a user-friendly field error, consistent with existing catches
- Code follows code standards

## Implementation Notes
- Created `config/recipe-import.php` with `rate_limit.max_attempts = 10`, `rate_limit.decay_seconds = 60`, `max_redirects = 5`.
- Updated `#[Validate]` on `Import::$url` from `url` to `url:http,https` to restrict schemes.
- Added `RateLimiter` check (tooManyAttempts → addError + return) and `RateLimiter::hit()` call between `$this->validate()` and the try block, ensuring validation failures never count against the limit.
- Added `catch (BlockedUrlException $e)` before the generic `catch (Exception $e)` to surface the friendly message as a `url` field error.
- New tests live in `tests/Feature/Recipe/ImportHardeningTest.php` using `mock(RecipeImportService::class)` bound via `app()->instance()`. Each rate-limit test calls `RateLimiter::clear()` to prevent cross-test bleed.
