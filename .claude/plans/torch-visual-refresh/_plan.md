# Plan: Torch Visual Refresh

## Created
2026-07-31

## Status
completed

## Objective
Retint YesChef's UI from the current neutral/zinc Flux theme to "Torch" — a steel-blue and spark-blue palette pulled from a laser-cut-steel reference image — without changing navigation, layout, or page structure.

## Related Issues
none

## Discovery Notes
- The app themes almost entirely through three CSS custom properties (`--color-accent`, `--color-accent-content`, `--color-accent-foreground`) defined in `resources/css/app.css`, which Flux's own compiled CSS (`vendor/livewire/flux/dist/flux.css`) consumes directly for buttons, focus rings, and other primary UI. Retinting those three tokens plus the `zinc-*` scale (also overridden in the same `@theme` block) cascades through every Flux component and page automatically — no Livewire/Blade component files need to change except small polish spots.
- The cascade is not quite total: 16 view usages reference `neutral-*` directly (8 in `resources/views/dashboard.blade.php`, plus the dark-mode body gradient in `components/layouts/auth/simple.blade.php:14` and the other auth/app layouts). Retinting only `zinc-*` would leave warm grey next to steel blue in dark mode, so `--color-neutral-*` is retinted with the same 11 values.
- The `theme-color` brand value lives in two places, not one: `resources/views/partials/head.blade.php:10` and `public/site.webmanifest:8`. Both are updated. `public/favicon.svg` and the PNG icon set remain black — regenerating raster icons is out of scope for a token pass.
- Four visual directions were reviewed with the user via a design-comparison artifact (Hearth, Ledger, Skillet, Torch). Torch's palette was extracted programmatically (stdlib PNG decode + pixel clustering, no external tools) from a promotional image for "The Bear" season 3: a laser-cut steel bear on brushed blue metal with welding sparks and a small gold badge.
- This exact token change was hand-prototyped earlier in this session (zinc scale + accent tokens retinted, `theme-color` meta updated, logo wordmark set to uppercase/tracked) and confirmed visually via a screenshot of the login page — steel-blue focus ring, button, and links rendered correctly. It was then rolled back pending a formal plan; this plan re-applies and formalizes that same change under TDD.
- Scope was explicitly narrowed with the user to color tokens + the logo wordmark only — no extension of the uppercase/tracked heading treatment to other page headings (`flux:heading`), and no page-layout changes.
- The `--color-gold` accent (from the reference image's badge) is being added as a defined-but-unused theme token this pass, per user decision — a home for it (e.g. `flux:badge` variants) is deferred to a future pass.
- **Correction (devil's advocate review):** `pestphp/pest-plugin-browser` is NOT installed. Its only appearance in `composer.lock`/`vendor/composer/installed.json` is inside the `require-dev` block of the `pestphp/pest` package itself, which composer never installs — there is no package entry for it. `vendor/pestphp/pest/src/Functions.php:303` shows `visit()` calls `PluginBrowser::install(); exit(0);` when the plugin is missing, which would terminate the whole `./vendor/bin/pest` run in CI. The two existing `tests/Browser/` files are green only because they `markTestSkipped()` before reaching `visit()`. No Pest browser test is used in this plan.
- Real-browser verification instead uses Playwright, which IS installed (`@playwright/test` devDependency, `e2e/` directory, working `.github/workflows/e2e.yml` that builds assets, installs chromium and serves the app). Everything else is covered by fast Unit tests (substring assertions on `resources/css/app.css` source) and Feature tests (rendered HTML) — appropriate given this change has no business logic, only configuration values.

## Scope

### In Scope
- Retint `--color-accent`, `--color-accent-content`, `--color-accent-foreground` (light + dark) in `resources/css/app.css` to the Torch steel-blue / spark-blue palette
- Retint the `--color-zinc-*` scale with a steel-blue hue bias
- Retint the `--color-neutral-*` scale with the same values (16 views reference `neutral-*` directly)
- Add a `--color-gold` theme token (light + dark), defined but not yet applied to any component
- Update the `theme-color` meta tag (`partials/head.blade.php`) and the PWA manifest `theme_color` (`public/site.webmanifest`) to the Torch brand color
- Set the app logo wordmark to uppercase with wide letter-spacing
- Add `e2e/torch-theme.spec.ts` and register it in `.github/workflows/e2e.yml`

### Out of Scope
- Applying the gold token to any component (e.g. `flux:badge`)
- Extending uppercase/tracked typography to page headings or other text
- Any navigation, layout, or page-structure changes
- Any Livewire component, route, or data-model changes
- Regenerating `public/favicon.svg` / `public/icons/*.png` / `apple-touch-icon.png` (still black)
- Changing `background_color` in `public/site.webmanifest`
- Adding `pestphp/pest-plugin-browser` or any other dependency

## Success Criteria
- [x] All Torch color tokens defined correctly in both light and dark mode
- [x] Login page primary button renders with the Torch accent color (Playwright, light mode pinned)
- [x] `theme-color` meta, PWA manifest `theme_color`, and logo wordmark styling updated
- [x] All tests passing; no new `markTestSkipped()`, no new dependencies
- [x] Code follows project standards

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Retint theme tokens (`resources/css/app.css`) | - | completed |
| 002 | Branding polish & verification (meta/manifest/wordmark + Feature/Playwright checks) | 001 | completed |

## Architecture Notes
- Two-task plan, split from an initial single task that had grown to 10 test requirements (past the 7-item sizing guideline): 001 is the pure CSS token change (5 requirements, Unit-tested from source, no dependencies); 002 is the Blade polish plus verification that depends on 001's compiled output (5 requirements, Feature + one Playwright spec). Sequential, not parallel — 002 needs 001's values built before its Playwright assertion can pass.
- No new dependencies, no new base directories.

## Risks & Mitigations
- **Contrast/accessibility regression from retinted tokens**: mitigated by mirroring the exact light/dark "pole" structure the original neutral-800/white pair already used (dark pole in light mode, light pole in dark mode) — only the hues change, not the contrast relationship.
- **CSS build not picked up**: `public/build` is gitignored and the Playwright assertion reads the compiled stylesheet, so `npm run build` is an ordered implementation step (step 3) that must run before the browser check — not a trailing manual note. `public/hot` must also be absent, otherwise `@vite` points at a dev server and the stylesheet may not load at all.
- **Unlisted e2e spec never runs**: `.github/workflows/e2e.yml` enumerates spec files explicitly, so the new spec is added to that list in step 4.
- **Color-scheme flakiness**: `@fluxAppearance` defaults to `system`, so the Playwright spec pins `colorScheme: 'light'`; otherwise a dark-scheme runner resolves `rgb(41, 149, 222)`.
- **`--color-gold` may be tree-shaken**: Tailwind v4 omits unused `@theme` variables from the compiled bundle, so the unused gold token will likely appear only under the `.dark` rule in `public/build`. Harmless this pass (tests read source); re-check when the token is actually wired to a component.
