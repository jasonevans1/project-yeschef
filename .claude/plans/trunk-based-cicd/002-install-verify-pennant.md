# Task 002: Install and Verify `laravel/pennant`

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Install Laravel's first-party feature-flag package so work that lands on `main` via a short-lived plan branch before it's fully ready can be dark-launched instead of hidden on a long-lived branch. This task only proves the package is installed and boots — it does not define any actual flags, since none are needed yet.

## Context
- Related files: `composer.json`, `composer.lock`, `config/pennant.php` (published by the package), the published Pennant migration under `database/migrations/`, and a new minimal Pest test at `tests/Feature/PennantInstallationTest.php`.
- Patterns to follow: this repo already has `tests/Feature/ComposerConstraintsTest.php` and `tests/Feature/LaravelThirteenBreakingChangesTest.php` — read these first, they're the existing convention for "prove a dependency/upgrade is correctly wired" tests. Match their style (`declare(strict_types=1);`, plain Pest `test(...)`/`it(...)`, no unnecessary setup).
- Laravel version is `^13.0` (confirmed in `composer.json`) — use `search-docs` (Laravel Boost MCP tool) to confirm the Pennant release that allows `illuminate/* ^13`.
- **Pennant's default store is `database`, not `array`.** `config/pennant.php` ships `'default' => env('PENNANT_STORE', 'database')`, and the `database` store reads/writes a `features` table. `tests/Pest.php` applies `RefreshDatabase` to the entire `Feature` suite against `:memory:` SQLite, so the migration must be published and committed or the flag test dies with `no such table: features`. Publish and migrate — do NOT "fix" this by switching the default store to `array` in `config/pennant.php`; the acceptance criteria forbid driver changes and the whole point is to prove the real configured path works.
  - Install sequence: `composer require laravel/pennant`, then `php artisan vendor:publish --provider="Laravel\Pennant\PennantServiceProvider" --no-interaction`, then `php artisan migrate --no-interaction`. If that provider/tag name has changed in the installed release, read `vendor/laravel/pennant/src/PennantServiceProvider.php` and use the `publishes()` tags it actually registers — do not guess.
- **If `composer require laravel/pennant` fails on a version conflict against `laravel/framework ^13.0`: STOP.** Do NOT use `-W`/`--with-all-dependencies`, `--ignore-platform-reqs`, or an explicit older Pennant constraint — any of those can cascade a framework downgrade across the whole lockfile and undo the Laravel 13 upgrade this repo just completed. Instead capture `composer why-not laravel/pennant '*'` output into Implementation Notes and output `TASK_FAILED: no Laravel 13-compatible laravel/pennant release`.

## Requirements (Test Descriptions)
- [x] `it registers the Pennant service provider`
- [x] `it resolves the Pennant Feature facade without error`
- [x] `it has a features table from the published Pennant migration` (assert `Schema::hasTable('features')` — this is what proves the publish+migrate step actually happened and guards the requirement below from failing for an unrelated reason)
- [x] `it defines and resolves a throwaway feature flag using the default store` (define a flag inline in the test itself — e.g. `Feature::define('scratch-test-flag', fn () => true);` then assert `Feature::active('scratch-test-flag')` — do not leave any flag definition in application code, this is purely to prove the package works end to end. Resolve through the default store; do not pass an explicit `Feature::store('array')`, that would sidestep the configured path this test exists to verify.)

## Acceptance Criteria
- `composer require laravel/pennant` succeeds with no dependency conflicts against the existing `^13.0` Laravel constraint, using none of the banned flags listed in Context.
- The package's config and migration are published and committed; `php artisan migrate` runs clean.
- No `config/pennant.php` changes beyond what the package publishes by default (don't switch drivers, don't add app-specific flags).
- All four tests pass.
- `vendor/bin/pint --dirty` run on the new test file.
- No existing tests regress: `php artisan test --parallel --testsuite=Unit,Feature`. (The `Browser` suite needs a real browser locally; CI runs the full `./vendor/bin/pest` anyway.)

## Implementation Notes
- `composer require laravel/pennant` resolved cleanly to `laravel/pennant ^1.26` against `laravel/framework ^13.0` — no conflicts, no banned flags needed.
- Actual provider class is `Laravel\Pennant\PennantServiceProvider`; the `--provider=` publish flag name in the task's suggested command doesn't match Pennant's registered tags, so used the tags read directly from `vendor/laravel/pennant/src/PennantServiceProvider.php`: `php artisan vendor:publish --tag=pennant-config` and `--tag=pennant-migrations`.
- Published migration: `database/migrations/2026_08_19_030732_create_features_table.php` (creates `features` table per the `database` store). Ran `ddev exec php artisan migrate` against the local MariaDB DB (host `.env` points at DDEV's `db` service, not sqlite — sqlite is only used by the test suite per `phpunit.xml`).
- `config/pennant.php` published unmodified (default store still `database`, no app flags added).
- New test file `tests/Feature/PennantInstallationTest.php` — 4 tests, all pass under the suite's `RefreshDatabase` + in-memory SQLite setup (the published migration runs automatically per-test).
- `vendor/bin/pint --dirty` clean (4 files, no changes needed).
- `php artisan test --parallel --testsuite=Unit,Feature`: 151 passed, 0 failed (the "879 deprecated" count is a pre-existing, unrelated PHP 8.4 `PDO::MYSQL_ATTR_SSL_CA` deprecation notice from `config/database.php`, not a test failure).
