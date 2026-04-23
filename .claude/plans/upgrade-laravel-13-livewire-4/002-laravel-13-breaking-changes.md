# Task 002: Apply Laravel 13 Configuration Breaking Changes

**Status**: completed
**Depends on**: [001]
**Retry count**: 0

## Description
Address the Laravel 13 breaking changes that affect this application's configuration. The most critical is setting explicit cache prefix and session cookie names in `.env.example` to preserve production continuity, and adding the new `serializable_classes` cache config option.

## Context
- Files to modify: `config/cache.php`, `.env.example`
- Files to review (may not exist): `app/Listeners/` for `JobAttempted` event usage
- Related files: `bootstrap/app.php` (review for CSRF middleware references)

### Laravel 13 Breaking Changes Applicable to This Project

**Medium Impact:**
1. **Cache `serializable_classes`**: New security hardening option in `config/cache.php`. Restricts which classes can be deserialized from cache. If not set, defaults to allowing all (backward compatible) but should be explicitly declared.

**Low Impact (but production-critical):**
2. **Cache prefix / Session cookie naming**: Laravel 13 changes suffix from `_` to `-` for cache prefix and session cookie names. This would invalidate existing production sessions and cached values unless explicit values are set.
   - Old cache prefix: `{APP_NAME}_cache_` → New: `{APP_NAME}-cache-`
   - Old session cookie: `{APP_NAME}_session` → New: `{APP_NAME}-session`
   - Fix: Add `CACHE_PREFIX=project_table_top_cache_` and `SESSION_COOKIE=project_table_top_session` to `.env.example`

3. **Pagination Bootstrap view names**: `pagination::default` → `pagination::bootstrap-3`. This project uses Tailwind pagination (not Bootstrap), so likely no impact, but verify.

4. **`Str` factories reset between tests**: Custom UUID/ULID factories set with `Str::createUuidsUsing()` are now reset between tests automatically. Beneficial change, but verify no tests depend on bleeding state.

**Probably Unused (verify before skipping):**
- `JobAttempted::$exceptionOccurred` (bool) → `JobAttempted::$exception` (Exception|null): Check `app/Listeners/` for any listener using this event.
- `QueueBusy::$connection` → `QueueBusy::$connectionName`: Check `app/Listeners/` for any listener using this event.

## Requirements (Test Descriptions)
- [x] `it has serializable_classes option in cache config`
- [x] `it has CACHE_PREFIX defined in env example to preserve production continuity`
- [x] `it has SESSION_COOKIE defined in env example to preserve production continuity`
- [x] `it passes php artisan config:clear without errors`
- [x] `it passes php artisan optimize:clear without errors`
- [x] `it runs existing cache-related feature tests successfully`

## Acceptance Criteria
- All requirements have passing tests
- `config/cache.php` has `serializable_classes` key
- `.env.example` documents `CACHE_PREFIX` and `SESSION_COOKIE`
- No artisan configuration errors
- Existing tests continue to pass

## Implementation Notes
- Publish the cache config if not already published: `php artisan config:publish cache`
- For `serializable_classes`, start with an empty array `[]` since this app doesn't store serialized objects in cache directly — the team can populate it as needed
- The `CACHE_PREFIX` and `SESSION_COOKIE` values should match the Laravel 12 naming convention to avoid invalidating production data
