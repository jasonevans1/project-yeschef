# Task 002: Meal-Plan-Drawer Content Completeness, Responsive Sizing, Dark Mode, and Keyboard Access

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Cover `001-meal-plan-drawer` FR-005/006 (drawer shows all required fields), FR-014 (responsive width), FR-015 (dark mode), and FR-016 (keyboard-accessible cards). The markup for all four is already present in `resources/views/livewire/meal-plans/show.blade.php` — this task writes tests proving the behavior. FR-013 ("smooth slide transition") is explicitly **out of scope**: CSS transition smoothness isn't meaningfully assertable via Pest or Playwright; do not manufacture a fake test for it. (The `x-transition:*` classes are at lines 532-537 and 548-553 if you want to eyeball them, but there is nothing to assert — it is deliberately absent from the requirements list below.)

**Depends on Task 001**, which creates `e2e/meal-plan-drawer.spec.ts` and the shared `openDrawer(page)` setup helper. Reuse that helper — do not write your own login/meal-plan/assignment fixture, and do not create a second spec file.

Verified during planning and re-verified during plan review (line numbers accurate as of review):
- Drawer panel width classes: line 558, `class="pointer-events-auto w-screen max-w-full sm:max-w-md lg:max-w-lg"` — full width below the `sm` breakpoint (640px), `max-w-md` (28rem / 448px) from `sm`, `max-w-lg` (32rem / 512px) from `lg`.
- Dark mode: throughout the drawer markup — backdrop `dark:bg-zinc-900/75` (line 539), panel body `dark:bg-zinc-900` (line 560), header border `dark:border-zinc-700` (line 562).
- Drawer content fields (read the full `@if($showRecipeDrawer ...)` block, lines 523-692, before writing the completeness assertion): recipe name (566), date + meal type (569), Servings (588-602), Time/prep/cook (605-623, conditional), Ingredients (626-643), Instructions (646-653, conditional), assignment Notes (656-663, conditional), View Full Recipe + Close footer (668-685).

## Context

### The page renders TWO calendar layouts — this matters for three of your four tests
- **Desktop** (`hidden md:block`, line 72): cards at lines 106-112 with `wire:key="desktop-recipe-{id}"`, `role="button"`, `tabindex="0"`, `@keydown.enter="$wire.openRecipeDrawer(...)"`, `@keydown.space.prevent="$wire.openRecipeDrawer(...)"`.
- **Mobile** (`block md:hidden`, `[data-mobile-calendar]`, lines 237-238): cards at lines 284-290 with `wire:key="mobile-recipe-{id}"`, `role="button"`, `tabindex="0"`, `wire:click` — **and no `@keydown` handlers at all**.

Consequences:
1. A bare `[role="button"]` or "first card" locator matches **both** layouts (the hidden one is still in the DOM). Always scope: `[wire\\:key^="desktop-recipe-"]` or `[data-mobile-calendar] [wire\\:key^="mobile-recipe-"]`.
2. The responsive-width test at a mobile viewport must open the drawer through the **mobile** card — the desktop table is `display:none` at 375px.
3. **FR-016 is only half-implemented.** Enter/Space on a mobile card does nothing, even though it announces as a button to assistive tech. Scope the keyboard test to the desktop layout and record the mobile gap as a finding (see Requirements). Do not add the missing handlers — that's a production change and a separate decision.

### Field completeness (FR-005/006) is Pest-testable, no browser needed
Open the drawer with `Livewire::test(Show::class, ['mealPlan' => $mealPlan])->call('openRecipeDrawer', $assignment)` and `assertSee()` each expected value. Two gotchas:
- **Quantities are formatted, not raw.** `Show::getScaledIngredientsProperty()` pipes every quantity through `QuantityFormatter::format()` (`app/Livewire/MealPlans/Show.php:393`), so `2.5` renders as `2½` and `0.333` as `⅓`. `assertSee('2.5')` fails. Either assert `QuantityFormatter::format()`'s output or pick quantities with clean whole-number results. See the existing assertions at `ViewMealPlanTest.php:433-435` for the established idiom.
- **The date is formatted** as `l, F j` (line 569), e.g. `Wednesday, October 15` — not `2025-10-15`.
Cover the conditional fields too: set `prep_time`/`cook_time`, `instructions`, and assignment-level `notes` on the fixture so those branches actually render.

### Responsive width, dark mode, and keyboard access need a real browser — Playwright
- **Responsive width:** `page.setViewportSize()` at 375x667 (mobile — open via the mobile card) and at 1280x720 (desktop — open via the desktop card), then assert the panel's `boundingBox().width`. Expect ≈375 at mobile (`w-screen max-w-full`) and 512 at desktop (`lg:max-w-lg` = 32rem). Assert the actual measured width, not the presence of class strings. Locator: `page.getByRole('dialog')`.
- **Dark mode:** dark mode in this app is **class-based**, not media-query-based — `resources/css/app.css:9` declares `@custom-variant dark (&:where(.dark, .dark *))`, and the `.dark` class is applied by `@fluxAppearance` plus a localStorage bootstrap in `resources/views/partials/head.blade.php:16-32`. Follow **`e2e/torch-theme.spec.ts`** for the real pattern in this repo (`test.use({ colorScheme: ... })` + `toHaveCSS`) — *not* `header-branding.spec.ts`'s `'logo is visible in light and dark mode'`, which despite its name never toggles anything and only asserts a class regex. `colorScheme` emulation works here only because Flux's default appearance is `system`; if it proves flaky, use `page.addInitScript(() => localStorage.setItem('flux.appearance', 'dark'))` before navigating, which is deterministic. Assert the computed `background-color` of the drawer panel (`dark:bg-zinc-900` → `rgb(24, 24, 27)`).
- **Keyboard access:** do not just assert `tabindex="0"` is in the markup — actually drive the key handler. But do **not** loop `page.keyboard.press('Tab')` to reach the card: it sits after the entire sidebar nav, header, and per-slot add buttons, so the number of Tabs is unknown and layout-dependent. Use `card.focus()` → `expect(card).toBeFocused()` → `card.press('Enter')`, which genuinely fires `@keydown.enter` without coupling the test to tab order.

### Asserting drawer open/closed state
The drawer lives inside `@if($showRecipeDrawer && $this->selectedAssignment)` (line 523), so it is added to and removed from the DOM wholesale on each Livewire re-render. Use `getByRole('dialog')` with `toHaveCount(1)` / `toHaveCount(0)` rather than visibility assertions.

### Related files
`resources/views/livewire/meal-plans/show.blade.php`, `app/Livewire/MealPlans/Show.php`, `app/Services/QuantityFormatter.php`, `tests/Feature/MealPlans/ViewMealPlanTest.php`, `e2e/meal-plan-drawer.spec.ts` (created by Task 001), `e2e/torch-theme.spec.ts`.

## Requirements (Test Descriptions)
- [x] (Pest, `ViewMealPlanTest.php`) `it displays recipe name, date, servings, times, ingredients, instructions, and notes in the drawer`
- [x] (e2e, append to `e2e/meal-plan-drawer.spec.ts`) `it renders the drawer at full width on mobile and constrained width on desktop` — measured widths, mobile opened via the mobile card
- [x] (e2e, append) `it applies dark mode styling to the drawer panel and backdrop` — computed background colors
- [x] (e2e, append) `it opens the drawer via keyboard activation of a recipe card` — desktop layout, `focus()` + `press('Enter')`
- [x] (Implementation Notes only, no test) Record the FR-016 mobile gap: mobile recipe cards (lines 284-290) expose `role="button"` + `tabindex="0"` but have no `@keydown.enter`/`@keydown.space` handlers, so keyboard activation silently fails on narrow viewports. Do not fix it here.

## Acceptance Criteria
- All four tests pass against the current implementation with **no production code changes**. If one genuinely can't, stop and report `TASK_FAILED` rather than editing the view.
- Your e2e tests are appended to Task 001's `e2e/meal-plan-drawer.spec.ts` and reuse its `openDrawer(page)` helper — no duplicate spec file, no duplicate fixture setup.
- Every card locator is scoped to one layout; none can match both the desktop and mobile calendars.
- FR-013 (transition smoothness) is represented by no test — note this explicitly in Implementation Notes so plan review doesn't flag it as a missed item.
- The FR-016 mobile keyboard gap is recorded in Implementation Notes.
- `vendor/bin/pint --dirty` run.

## Implementation Notes

All four requirements pass against the current implementation with **zero production code changes** (only `tests/Feature/MealPlans/ViewMealPlanTest.php` and `e2e/meal-plan-drawer.spec.ts` were edited).

**1. Field completeness (Pest).** Added `it displays recipe name, date, servings, times, ingredients, instructions, and notes in the drawer` to `ViewMealPlanTest.php`. Sets `prep_time`, `cook_time`, `instructions` on the recipe and assignment-level `notes`, uses a whole-number ingredient quantity (`2.0 lb` → renders as `2 lb`) to sidestep `QuantityFormatter`'s fraction rendering, and asserts the `l, F j` date format (`Wednesday, October 15`). Passed on first run — this branch of the markup was already correct, so this test documents existing behavior rather than driving new code.

**2. Responsive width (e2e).** Two corrections were needed versus the plan's stated expectations, both discovered by measuring actual rendered `boundingBox()` values rather than trusting the class-name analysis:
- **Desktop (1280px): 512px as expected** (`lg:max-w-lg` = 32rem).
- **Mobile (375px): ~335px, not ~375px.** The drawer's flex wrapper (`show.blade.php:545`, `class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10"`) applies an unconditional `pl-10` (40px) gutter — not `sm:pl-10` — so at any viewport the panel's available content-box width is `viewport width − 40px`, not the full viewport. At 375px this measures ~335px. The test asserts `325–345px` to tolerate rounding. This is real, existing production behavior (a Tailwind-UI-style slide-over gutter), not a bug — documented in a code comment in the spec rather than "fixed."

**3. Dark mode (e2e).** Two corrections versus the plan:
- **Custom color palette.** This app overrides Tailwind's default zinc scale in `resources/css/app.css` (a "Torch Theme" palette) — `--color-zinc-900: #131a22` → `rgb(19, 26, 34)`, not stock Tailwind's `rgb(24, 24, 27)`. Confirmed by reading `resources/css/app.css:23` after the first assertion failed with the received value matching the override exactly.
- **Alpha-modified backdrop color.** `dark:bg-zinc-900/75` is rendered by Tailwind v4 via CSS `color-mix()`, which Chromium reports back through `getComputedStyle()` in `oklab()` notation, not `rgba()` — so a direct `toHaveCSS('background-color', 'rgba(...)')` string match fails even though the color is correct. Worked around by rasterizing the computed color through a 1x1 `<canvas>` (`ctx.fillStyle = getComputedStyle(el).backgroundColor; ctx.fillRect(...); getImageData(...)`), which normalizes any CSS color notation to RGBA bytes, then asserting with a small tolerance (oklab→sRGB gamma round-trip shifts channels by ~1 unit versus a naive linear blend).
- Used `page.addInitScript(() => localStorage.setItem('flux.appearance', 'dark'))` (the deterministic fallback the task suggested) rather than `colorScheme` emulation.
- The panel body's solid `dark:bg-zinc-900` (no alpha) matched with a plain `toHaveCSS('background-color', 'rgb(19, 26, 34)')` — no canvas workaround needed there since it isn't alpha-blended.

**4. Keyboard access (e2e).** `it opens the drawer via keyboard activation of a recipe card` reuses `openDrawer(page)` for setup (which also proves the mouse-click path still works), closes the drawer via Escape, then does `desktopCard.focus()` → `expect(desktopCard).toBeFocused()` → `desktopCard.press('Enter')` and asserts the dialog reopens. Scoped to `[wire\\:key^="desktop-recipe-"]` (layout-exclusive prefix), matching the plan's guidance to test only the desktop layout.

**FR-016 mobile keyboard gap (recorded, not fixed):** Mobile recipe cards (`show.blade.php:284-290`) have `role="button"` and `tabindex="0"` — so they announce as focusable/actionable to assistive tech — but carry no `@keydown.enter`/`@keydown.space.prevent` handlers (unlike their desktop counterparts at lines 111-112). A keyboard or switch-device user who tabs to a mobile recipe card and presses Enter or Space gets no response; only `wire:click` (mouse/touch) opens the drawer. This is a genuine accessibility gap and a good candidate for a follow-up production fix (add the same two `@keydown` directives used on the desktop card), but is out of scope for this test-only task.

**FR-013 (no test, by design):** Slide/backdrop transition smoothness (`x-transition:*` at `show.blade.php:532-537, 548-553`) is not covered by any test in this task. CSS transition timing/smoothness isn't meaningfully assertable via Pest (no browser) or Playwright (assertions on animation frames are flaky and don't verify anything a user would call "smooth"). This is a deliberate omission per the task description, not an oversight.

**Test run notes:** e2e suite run via `CI=1 BASE_URL=http://yeschef.ddev.site npx playwright test e2e/meal-plan-drawer.spec.ts --project=chromium --reporter=line` against a live DDEV instance (self-signed HTTPS cert isn't trusted in this sandbox, so `http://` + explicit `BASE_URL` override, matching Task 001's approach). The default `html` reporter opens a report server that blocks indefinitely when not on CI — use `--reporter=line` (or set `CI=1`) to get a normal exit. All 6 tests in the file (3 from Task 001 + 3 new) pass, repeatably (`--repeat-each=2` run clean). Full Pest suite (`php artisan test --parallel`): 151 passed, 0 failed.
