# Task 001: Retint Theme Tokens

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Change the CSS custom property values in `resources/css/app.css` that drive the app's theme — the Flux accent tokens and the `zinc`/`neutral` color scales — from the current neutral/black-and-white palette to "Torch": a steel-blue and spark-blue palette. This task only touches `resources/css/app.css`; no Blade/route changes.

## Context
This app themes almost entirely through 3 CSS custom properties that Flux's own compiled CSS (`vendor/livewire/flux/dist/flux.css`) already consumes directly: `--color-accent`, `--color-accent-content`, `--color-accent-foreground`. Changing their values — plus the `--color-zinc-*` and `--color-neutral-*` scales, defined in the same block — re-themes every Flux button, focus ring, and page site-wide with no Blade template changes required for those. This exact change was hand-verified once already this session (screenshot of the login page showed the button/focus-ring/links correctly retinted).

The `--color-neutral-*` scale must be retinted too, alongside `--color-zinc-*`: 16 view usages reference `neutral-*` directly (8 in `resources/views/dashboard.blade.php`, plus the dark-mode body gradient in `resources/views/components/layouts/auth/simple.blade.php:14`, `dark:from-neutral-950 dark:to-neutral-900`). Retinting only `zinc-*` would leave warm grey next to steel blue in dark mode.

- Related files:
  - `resources/css/app.css` — the `@theme { ... }` block (lines ~11-29) and the `.dark { ... }` rule (nested inside `@layer theme { ... }`, lines ~31-37)
- Patterns to follow:
  - The existing `.dark { --color-accent: ...; }` override already establishes a "dark pole in light mode / light pole in dark mode" contrast structure for `--color-accent`/`--color-accent-content`/`--color-accent-foreground` — preserve that structure, only change which hues fill each pole.
  - Unit tests live in `tests/Unit/` and assert on values directly without HTTP. For this task, read `resources/css/app.css` via `file_get_contents(resource_path('css/app.css'))` and use plain substring assertions — NOT block-extraction regex, since the `.dark` rule is nested inside `@layer theme { ... }` and every light/dark value pair is already textually distinct.

### Exact values to apply

`resources/css/app.css` — `@theme` block, light mode (replace the existing `--color-zinc-*` values, add a `--color-neutral-*` block, replace the three accent lines, add gold):
```
--color-zinc-50: #f7f9fb;
--color-zinc-100: #eef2f6;
--color-zinc-200: #dde4ea;
--color-zinc-300: #c3ced9;
--color-zinc-400: #94a5b5;
--color-zinc-500: #6b7d8f;
--color-zinc-600: #4f6070;
--color-zinc-700: #384654;
--color-zinc-800: #232d38;
--color-zinc-900: #131a22;
--color-zinc-950: #0a0e13;

--color-neutral-50: #f7f9fb;
--color-neutral-100: #eef2f6;
--color-neutral-200: #dde4ea;
--color-neutral-300: #c3ced9;
--color-neutral-400: #94a5b5;
--color-neutral-500: #6b7d8f;
--color-neutral-600: #4f6070;
--color-neutral-700: #384654;
--color-neutral-800: #232d38;
--color-neutral-900: #131a22;
--color-neutral-950: #0a0e13;

--color-accent: #06377e;
--color-accent-content: #06377e;
--color-accent-foreground: #ffffff;
--color-gold: #a4842f;
```

`resources/css/app.css` — the `.dark { ... }` rule inside `@layer theme`:
```
--color-accent: #2995de;
--color-accent-content: #2995de;
--color-accent-foreground: #05070c;
--color-gold: #c9a54a;
```

## Requirements (Test Descriptions)
- [x] `it sets the light mode accent tokens to the torch steel blue palette` (Unit — `tests/Unit/TorchThemeTokensTest.php`; read `file_get_contents(resource_path('css/app.css'))` and assert it contains the substrings `--color-accent: #06377e;`, `--color-accent-content: #06377e;`, `--color-accent-foreground: #ffffff;`)
- [x] `it sets the dark mode accent tokens to the torch spark blue palette` (Unit — assert the same file contains `--color-accent: #2995de;`, `--color-accent-content: #2995de;`, `--color-accent-foreground: #05070c;`)
- [x] `it retints the zinc scale with a steel blue hue bias` (Unit — assert all 11 `--color-zinc-*` declarations listed above are present)
- [x] `it retints the neutral scale to match the zinc scale` (Unit — assert all 11 `--color-neutral-*` declarations listed above are present)
- [x] `it defines a gold accent token for light and dark mode` (Unit — assert the file contains both `--color-gold: #a4842f;` and `--color-gold: #c9a54a;`)

## Acceptance Criteria
- All requirements have passing tests
- Code follows code standards (`vendor/bin/pint --dirty` clean)
- No decrease in test coverage

## Implementation Notes
- `resources/css/app.css` `@theme` block: retinted `--color-zinc-*` (11 stops), added a matching `--color-neutral-*` scale (11 stops), replaced the three accent tokens, and added `--color-gold: #a4842f;`.
- `.dark { ... }` rule inside `@layer theme`: replaced the three accent tokens and added `--color-gold: #c9a54a;`, preserving the existing light/dark contrast-pole structure.
- Deviation from the task's suggested test helper: `resource_path()` requires the Laravel container to be bootstrapped as `Illuminate\Foundation\Application`, but `tests/Pest.php` only binds `Tests\TestCase` (which boots the framework) to the `Feature` and `Browser` suites — `Unit` tests run against a bare `Illuminate\Container\Container`, so `resource_path()` throws `Call to undefined method Container::resourcePath()`. Used `dirname(__DIR__, 2) . '/resources/css/app.css'` instead, which resolves the same file without requiring app bootstrap, consistent with how other `tests/Unit/` tests avoid Laravel helpers.
- `vendor/bin/pint --dirty` reformatted the new test file's string concatenation (`.` spacing); tests still pass afterward.
- Full suite: `php artisan test --parallel` → 150 passed (2621 assertions), no failures (PDO deprecation notices are pre-existing/unrelated, from PHP 8.5 vs `PDO::MYSQL_ATTR_SSL_CA`).
