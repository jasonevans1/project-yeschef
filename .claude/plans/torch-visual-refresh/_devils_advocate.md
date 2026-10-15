# Devil's Advocate Review: torch-visual-refresh

Reviewed: `_plan.md`, `001-apply-torch-visual-refresh.md`, cross-referenced against
`resources/css/app.css`, `resources/views/partials/head.blade.php`,
`resources/views/components/app-logo.blade.php`, the auth/app layouts, `composer.lock`,
`vendor/composer/installed.json`, `phpunit.xml`, `.github/workflows/tests.yml`,
`.github/workflows/e2e.yml`, `playwright.config.ts`.

Verified correct as specified:
- `resources/css/app.css` `@theme` block is lines 11-29, the `.dark` override is lines 31-37. Both exist.
- `resources/views/partials/head.blade.php:10` is exactly `<meta name="theme-color" content="#000000">`.
- `resources/views/components/app-logo.blade.php:5` is exactly
  `<span class="mb-0.5 truncate leading-tight font-semibold">{{ config('app.name') }}</span>`.
- Flux's primary button really does consume the token:
  `vendor/livewire/flux/stubs/resources/views/flux/button/index.blade.php:95`
  → `'primary' => 'bg-[var(--color-accent)] hover:bg-[color-mix(...)]'`, so the computed
  `background-color` would indeed be `rgb(6, 55, 126)` for `#06377e`. Hex→RGB conversions in the
  task are arithmetically correct.
- The login submit button already carries a stable hook: `data-test="login-button"`
  (`resources/views/livewire/auth/login.blade.php:42`).

---

## Critical (Must fix before building)

### C1 — `pestphp/pest-plugin-browser` is NOT installed; the browser test would abort the whole suite
**Task 001, requirement 7. Plan discovery note (line 21) is factually wrong.**

The plan states the plugin is "genuinely installed, confirmed in `composer.lock`". It is not.
The only occurrence in `composer.lock` (line 8547) and `vendor/composer/installed.json` (line 4650)
is inside the **`require-dev` block of the `pestphp/pest` package itself** — a locked package's own
dev requirements are never installed. `vendor/composer/installed.json` has no package entry named
`pestphp/pest-plugin-browser`; the installed pest packages are only `pest`, `pest-plugin`,
`pest-plugin-arch`, `pest-plugin-laravel`, `pest-plugin-mutate`, `pest-plugin-profanity`.

Consequence — `vendor/pestphp/pest/src/Functions.php:303`:
```php
function visit(array|string $url, array $options = []): ArrayablePendingAwaitablePage|PendingAwaitablePage
{
    if (! class_exists(Pest\Browser\Configuration::class)) {
        PluginBrowser::install();

        exit(0);
    }
```
Calling `visit()` without the plugin triggers an interactive installer and then `exit(0)` — it does
not fail one test, it **terminates the entire `./vendor/bin/pest` process**. CI
(`.github/workflows/tests.yml:47`) runs `./vendor/bin/pest` across all three suites including
`tests/Browser`. The suite is green today only because both existing browser tests call
`markTestSkipped()` as their first statement, before `visit()` is ever reached. The very first
non-skipped browser test breaks CI. There is also no `npx playwright install` step in `tests.yml`.

Installing the plugin is a new composer dependency, which CLAUDE.md forbids without user approval.

**Fix applied:** requirement 7 is re-specified as a Playwright spec in `e2e/` — Playwright
(`@playwright/test`) is already a devDependency, and `.github/workflows/e2e.yml` already builds
assets, installs chromium, and serves the app. Spec must also be registered in the workflow (see I6).
A fast, always-runnable Feature assertion (button markup references `var(--color-accent)`) is added
so the token→button wiring is covered even when no browser is available.

### C2 — the wordmark Feature test targets a page that never renders the wordmark
**Task 001, requirement 6.**

`x-app-logo` (the component being edited) is referenced only in
`resources/views/components/layouts/app/sidebar.blade.php:11` and
`resources/views/components/layouts/app/header.blade.php:11,81` — i.e. **authenticated** layouts.
`/login` renders through `components/layouts/auth/simple.blade.php`, which uses
`<x-app-logo-icon>` plus a `<span class="sr-only">` — there is no wordmark span on `/login` at all.
`GET /login` asserting `uppercase tracking-wide` fails 100% of the time.

**Fix applied:** the wordmark test now hits `/dashboard` via `actingAs()`. `/dashboard` is
`auth` + `verified` (`routes/web.php:38-40`); `UserFactory` sets `email_verified_at` by default, so
`User::factory()->create()` is sufficient. `App\Livewire\Dashboard` declares
`#[Layout('components.layouts.app')]` → `layouts/app/header` → `x-app-logo`. Existing precedent:
`tests/Feature/BrandingTest.php` already does exactly `actingAs($user)->get('/dashboard')`, so the
new tests belong in that file.

### C3 — the real-browser assertion reads compiled CSS, which the task defers to an afterthought
**Task 001, acceptance criteria.**

`/public/build` is gitignored (`.gitignore:3`). The browser assertion resolves
`bg-[var(--color-accent)]` from the **compiled** stylesheet, not from `resources/css/app.css`.
The task currently lists `npm run build` as "a manual step, not itself a test requirement" — but
without it the assertion reads the stale pre-change bundle and fails, which will read as a broken
implementation to a TDD worker. Additionally, if `public/hot` exists (a `npm run dev` / `composer dev`
session was left running or died), `@vite` points at the dev server and the stylesheet may not load
at all, giving `rgba(0, 0, 0, 0)`.

**Fix applied:** `npm run build` (and "ensure `public/hot` is absent") is now an explicit ordered
implementation step that must precede the browser verification, not a trailing acceptance note.

---

## Important (Should fix before building)

### I1 — the `.dark` block is nested inside `@layer theme`, not top-level
`resources/css/app.css:31-37` is:
```css
@layer theme {
    .dark {
        --color-accent: var(--color-white);
```
Requirements 1-4 say "assert the `@theme` block contains…" / "the `.dark { … }` block contains…",
which invites a worker to write block-extraction regex that trips over the extra nesting level
(and over the `@theme` reference in `flux.css`). Since every light/dark value pair is distinct
(`#06377e` vs `#2995de`, `#ffffff` vs `#05070c`, `#a4842f` vs `#c9a54a`), plain substring assertions
on the file contents are unambiguous and non-brittle.

**Fix applied:** requirements re-worded to plain `assertStringContainsString`-style substring checks
against `file_get_contents(resource_path('css/app.css'))`.

### I2 — `public/site.webmanifest` still declares the old black brand color
`public/site.webmanifest:7-8` has `"background_color": "#000000"` and `"theme_color": "#000000"`.
Changing only the `<meta name="theme-color">` leaves the installed-PWA / Android task-switcher chrome
black while the browser chrome turns steel-blue. Same one-value concern the task already owns.

**Fix applied:** `"theme_color"` added to scope with its own assertion. `background_color` (splash
screen) left alone — see Questions.

### I3 — HTML assertions need `assertSee($needle, false)`
Requirements 5 and 6 assert on raw markup containing `<`, `>`, `"` and Tailwind bracket classes.
Laravel's `assertSee()` HTML-escapes by default and would not match. `tests/Feature/BrandingTest.php`
already demonstrates the `assertSee('…', false)` form. The wordmark requirement also says "with both
`uppercase` and `tracking-wide` classes present" without pinning the string, which leaves a worker
guessing class order.

**Fix applied:** both requirements now name the exact expected literal, with escaping disabled.

### I4 — retinting only `--color-zinc-*` leaves warm-grey surfaces next to steel-blue ones
The plan claims the token change "cascades through every Flux component and page automatically".
16 usages of `neutral-*` in views will not move:
- `resources/views/dashboard.blade.php` — 8 (the placeholder skeleton panels; the single most visible
  authenticated screen)
- `resources/views/components/layouts/auth/split.blade.php` — 3
- `resources/views/components/layouts/auth/simple.blade.php:14` — `dark:from-neutral-950 dark:to-neutral-900`
  (the dark-mode body gradient behind every auth page, including the login page the plan screenshots)
- `resources/views/components/layouts/app/sidebar.blade.php` — 2
- `resources/views/components/layouts/auth/card.blade.php` — 1
- `resources/views/components/layouts/app/header.blade.php` — 1

In dark mode this reads as a warm-grey background under steel-blue chrome. Retinting
`--color-neutral-*` with the same 11 values is one extra block in the same `@theme`, no new risk,
and stays inside "color tokens only".

**Fix applied:** `--color-neutral-*` retint added to scope, values, and requirements. Flagged in
Questions in case the user wants the warm/cool mix intentionally.

### I5 — the browser assertion is color-scheme dependent and currently unpinned
`@fluxAppearance` defaults to `system`, and `partials/head.blade.php:17-32` only forces a class when
`localStorage['flux.appearance']` is explicitly `light`/`dark`. So a runner whose
`prefers-color-scheme` is `dark` gets `rgb(41, 149, 222)`, not `rgb(6, 55, 126)`, and the test flakes
by machine. Must pin the color scheme explicitly.

**Fix applied:** spec requires `test.use({ colorScheme: 'light' })`.

### I6 — a new e2e spec will not run in CI unless the workflow is edited
`.github/workflows/e2e.yml:68-86` enumerates spec files/globs explicitly
(`e2e/dashboard.spec.ts e2e/header-branding.spec.ts`, `e2e/grocery*`, `e2e/recipe*`,
`e2e/meal-plan*`). A file named `e2e/torch-theme.spec.ts` matches none of them and would be silently
skipped forever.

**Fix applied:** implementation steps now include appending the new spec to the existing
"Run Playwright Dashboard and Header Tests" step.

### I7 — "locate the primary submit button" is under-specified
Use the existing hook `[data-test="login-button"]` (`resources/views/livewire/auth/login.blade.php:42`).
A `button[type=submit]` selector is fine today but the login form also renders a Flux password
"viewable" toggle button, so a positional selector is a trap.

**Fix applied:** selector pinned in the requirement.

---

## Minor (Nice to address — not applied)

- **M1 — `--color-gold` may not reach the compiled CSS.** Tailwind v4 omits `@theme` variables that
  are never referenced (unless `@theme static`). Since `--color-gold` is deliberately unused this
  pass, the built bundle will likely contain it only under the `.dark` rule (a plain CSS rule, always
  emitted) and not in `:root`. Harmless while unused, but whoever wires it to `flux:badge` next pass
  should re-check light mode. The Unit tests read source, so they are unaffected.
- **M2 — icons stay black.** `public/favicon.svg:2` is `fill="#000000"`, and
  `public/icons/icon-192.png`, `icon-512.png`, `apple-touch-icon.png` are pre-rendered black. After
  this change the tab icon is the only black brand surface left. Out of scope (needs asset
  regeneration, not a hex edit) but now conspicuous.
- **M3 — global border color is `--color-gray-200`.** `resources/css/app.css:46` sets every element's
  default border color from the untouched `gray` scale, so hairlines stay neutral.
- **M4 — "No decrease in test coverage" is a no-op criterion here.** `phpunit.xml:18-22` scopes
  coverage to `app/`; this task touches no PHP source.
- **M5 — line-reference nit.** The task calls `.dark` "the `.dark { … }` override" — accurate line
  range, but it is wrapped in `@layer theme { }` (see I1).

## Questions for the Team

1. Should `"background_color"` in `public/site.webmanifest` also move off `#000000`? A black PWA
   splash behind a steel-blue app is defensible; left unchanged for now.
2. Is retinting `--color-neutral-*` (I4) wanted, or is the warm-grey/steel-blue mix intentional? This
   review applied the retint; reverting it is a single block deletion.
3. Should `public/favicon.svg` and the PNG icon set be regenerated in a follow-up pass (M2)?
4. Do you want `pestphp/pest-plugin-browser` added as an explicitly approved dependency? It would
   unblock real Pest browser tests project-wide and let the two stale `markTestSkipped()` placeholders
   in `tests/Browser/` be revived — but it also needs an `npx playwright install` step added to
   `.github/workflows/tests.yml`. Out of scope for this plan; the Playwright route reuses what is
   already installed.
