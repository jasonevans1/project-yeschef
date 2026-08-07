# Task 002: Torch Branding Polish & Verification

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Apply the two remaining brand-color touchpoints (`theme-color` meta tag, PWA manifest) and the logo wordmark styling, then prove the retinted accent token actually renders — both via a fast HTML assertion and a real browser check. Depends on Task 001 because the Playwright assertion reads the *compiled* CSS produced from those token values.

## Context
- Related files:
  - `resources/views/partials/head.blade.php` — line 10 is exactly `<meta name="theme-color" content="#000000">`
  - `public/site.webmanifest` — line 8 is `"theme_color": "#000000",`
  - `resources/views/components/app-logo.blade.php` — line 5 is exactly `<span class="mb-0.5 truncate leading-tight font-semibold">{{ config('app.name') }}</span>`
  - `tests/Feature/BrandingTest.php` — existing home for the Feature assertions; already uses the `actingAs($user)->get('/dashboard')` pattern
  - `e2e/torch-theme.spec.ts` — NEW Playwright spec (see requirement 5)
  - `.github/workflows/e2e.yml` — line 69 enumerates which specs CI runs; the new spec must be added there
- Patterns to follow:
  - Existing Feature tests hit routes directly and assert on the raw response content with escaping disabled: `$response->assertSee('...', false)`.
  - Playwright specs live in `e2e/`, use `import { test, expect } from '@playwright/test';` and a `test.describe(...)` block — see `e2e/header-branding.spec.ts`.

### IMPORTANT — do NOT write a Pest browser test
`pestphp/pest-plugin-browser` is **not installed** in this project. It appears in `composer.lock` only inside the `require-dev` block of the `pestphp/pest` package itself, which composer never installs; `vendor/composer/installed.json` has no package entry for it. Calling `visit()` without the plugin runs `PluginBrowser::install(); exit(0);` (`vendor/pestphp/pest/src/Functions.php:303`), which terminates the entire `./vendor/bin/pest` process and would break CI. The two existing files in `tests/Browser/` are green only because they `markTestSkipped()` before reaching `visit()`. Do not add the plugin — dependency changes need user approval. Use Playwright instead, already installed with working CI in `.github/workflows/e2e.yml`.

### Exact values to apply

`resources/views/partials/head.blade.php`: change `<meta name="theme-color" content="#000000">` to `<meta name="theme-color" content="#06377e">`.

`public/site.webmanifest`: change `"theme_color": "#000000",` to `"theme_color": "#06377e",`. Leave `"background_color"` unchanged.

`resources/views/components/app-logo.blade.php`: the wordmark span becomes exactly:
```
<span class="mb-0.5 truncate leading-tight font-semibold uppercase tracking-wide">{{ config('app.name') }}</span>
```

## Requirements (Test Descriptions)
- [x] `it sets the theme-color meta tag to the torch steel blue brand color` (Feature — add to `tests/Feature/BrandingTest.php`; `$this->get('/login')` then `assertSee('<meta name="theme-color" content="#06377e">', false)`. Escaping MUST be disabled.)
- [x] `it sets the pwa manifest theme color to the torch steel blue brand color` (Unit — decode `public_path('site.webmanifest')` with `json_decode(..., true)` and assert `theme_color` is `#06377e`)
- [x] `it renders the app name wordmark in uppercase with wide letter spacing` (Feature — add to `tests/Feature/BrandingTest.php`; must hit `/dashboard`, NOT `/login`: `$this->actingAs(User::factory()->create())->get('/dashboard')` then `assertSee('class="mb-0.5 truncate leading-tight font-semibold uppercase tracking-wide"', false)`. `x-app-logo` is only rendered by the authenticated `layouts/app/header` + `layouts/app/sidebar`; the `/login` auth layout renders only `x-app-logo-icon` and an `sr-only` span, so a `/login` assertion can never pass. `/dashboard` is `auth` + `verified`; `User::factory()` sets `email_verified_at` by default.)
- [x] `it renders the login submit button bound to the accent token` (Feature — `$this->get('/login')` then `assertSee('var(--color-accent)', false)`; this is the fast, always-runnable proof that the Flux primary button consumes the token, independent of any browser or compiled CSS. Flux renders `bg-[var(--color-accent)]` for `variant="primary"`.)
- [x] `login submit button renders with the steel blue accent background` (Playwright — NEW file `e2e/torch-theme.spec.ts`, NOT a Pest browser test. Must pin light mode with `test.use({ colorScheme: 'light' });` because `@fluxAppearance` defaults to `system` and a dark-scheme runner would resolve `rgb(41, 149, 222)` instead. Then `await page.goto('/login')` and `await expect(page.locator('[data-test="login-button"]')).toHaveCSS('background-color', 'rgb(6, 55, 126)')`. Use the existing `data-test` hook — do not use a positional `button[type=submit]` selector, the password field renders a `viewable` toggle button too.)

## Implementation Steps (ordered)
1. Apply the two `theme-color` edits and the wordmark class change.
2. Write/run the Unit + Feature tests (these need no build and no browser — they must pass first).
3. Confirm `public/hot` does NOT exist (kill any running `npm run dev` / `composer dev`), then run `npm run build`. The Playwright assertion reads the **compiled** stylesheet from `public/build`, which is gitignored and otherwise stale — without this step the browser assertion reads the pre-change bundle and fails, or resolves to `rgba(0, 0, 0, 0)` if `public/hot` is present.
4. Add `e2e/torch-theme.spec.ts` to the CI spec list in `.github/workflows/e2e.yml` line 69, i.e. `npx playwright test e2e/dashboard.spec.ts e2e/header-branding.spec.ts e2e/torch-theme.spec.ts --project=chromium`. The workflow enumerates specs explicitly; an unlisted spec is silently never run.
5. Run the Playwright spec locally. Either against DDEV (`ddev start`, default `baseURL` https://yeschef.ddev.site) or against a local server:
   ```
   php artisan serve --host=127.0.0.1 --port=8000 &
   BASE_URL=http://127.0.0.1:8000 npx playwright test e2e/torch-theme.spec.ts --project=chromium
   ```
   `npx playwright install chromium` may be needed once. If no browser can be run in this environment, note it in the implementation notes — CI will run it — but do NOT delete or skip the spec.
6. Run `vendor/bin/pint --dirty`.

## Acceptance Criteria
- All requirements have passing tests (Unit + Feature must pass locally; the Playwright spec must at minimum be committed and wired into `.github/workflows/e2e.yml`)
- `npm run build` was run after Task 001's CSS change (step 3) — the compiled output itself is gitignored and not committed
- No `markTestSkipped()` added anywhere, and no new files in `tests/Browser/`
- No new composer or npm dependencies
- Code follows code standards (`vendor/bin/pint --dirty` clean)

## Implementation Notes
- Applied the three edits exactly as specified: `theme-color` meta tag, `site.webmanifest` `theme_color`, and the app-logo wordmark span classes.
- `it renders the login submit button bound to the accent token` passed immediately on first run (no RED phase) — this is pre-existing Flux `variant="primary"` behavior (`bg-[var(--color-accent)]`), not something implemented in this task. Confirmed and moved on per TDD rules for already-passing requirements.
- The `it sets the pwa manifest theme color...` Unit test reads the file via `dirname(__DIR__, 2).'/public/site.webmanifest'` rather than the `public_path()` helper, matching the existing pattern in `TorchThemeTokensTest.php`. `tests/Unit` only bootstraps `vendor/autoload.php` (see `phpunit.xml`), so the Laravel container/helpers are not available there.
- Added the new test to the existing `tests/Unit/TorchThemeTokensTest.php` file (natural home for Torch-branding CSS/manifest token assertions) rather than creating a new Unit test file.
- Ran `npm run build` after confirming no `public/hot` file and no running dev/serve processes, producing the compiled `public/build` assets the Playwright spec reads.
- Added `e2e/torch-theme.spec.ts` and wired it into `.github/workflows/e2e.yml`'s "Run Playwright Dashboard and Header Tests" step alongside the existing specs.
- **The Playwright browser check was run locally and passed** (1 passed). Since the committed `.env` is configured for DDEV/MariaDB and DDEV/Docker were unavailable in this environment, verification was done by temporarily swapping to a local SQLite `.env` (copied from `.env.example`, same approach CI uses), running `php artisan serve` on `127.0.0.1:8000`, and running `BASE_URL=http://127.0.0.1:8000 npx playwright test e2e/torch-theme.spec.ts --project=chromium`. The original `.env` was restored afterward and the temporary `database/database.sqlite` was deleted; `.env` is gitignored so no tracked files were affected. CI will also run this spec via the updated `e2e.yml`.
- Full Pest suite: 151 passed. `vendor/bin/pint --dirty`: clean (2 files, no changes needed).
