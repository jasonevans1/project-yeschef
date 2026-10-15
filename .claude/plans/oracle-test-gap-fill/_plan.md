# Plan: Oracle-Test Gap Fill

## Created
2026-08-20

## Status
completed

## Objective
Close every identified gap between `specs/001-012`'s Functional Requirements/acceptance scenarios and the existing Pest/e2e test suite, plus add three cross-cutting invariant tests, so the suite functions as a real "deletion test" oracle — a correctness bar independent of the current implementation that a future from-scratch rewrite can be judged against.

## Related Issues
none

## Discovery Notes
- A background gap-analysis pass read all 12 spec directories' `spec.md` FRs/acceptance scenarios/edge cases and cross-referenced them against the ~99 existing Pest/Browser/e2e test files. Full report available in this session's history; summarized findings below.
- **Coverage is strong overall.** Of ~140 unique FRs, only 6 are genuine gaps (no test at all) and 6 are partial (something tests the area but not the specific edge/behavior the spec describes). Everything else is already covered.
- **`003-merge-the-2` is a verbatim consolidation** of `001-build-an-application` (FR-001–033) + `002-update-the-spec` (FR-034–048) — same requirements, same wording. Treated as canonical; `001-build-an-application` and `002-update-the-spec` are not separately gap-checked to avoid triplicate work. All three have complete coverage regardless.
- **Gaps and partials cluster almost entirely in `001-meal-plan-drawer`** (6 of 12 items): closing via backdrop/button/Escape isn't distinguished, no "View Full Recipe" link test, no drawer-open permission check test, no responsive-sizing test, no dark-mode test, no keyboard-accessibility test, and drawer field-completeness is only partially asserted. This is UI-interaction-layer coverage that predates this repo's later e2e conventions.
- The remaining 6 gap/partial items are scattered one-per-spec across `001-grocery-item-lookup` (registration seeding gap, fuzzy-match-distance partial), `010-meal-plan-notes` (notes-leak-into-grocery-list gap, note-policy-test partial), `009-recipe-servings-multiplier` (3-decimal-rounding-boundary partial), and `011-share-content` (anonymous+user-share-coexistence partial).
- **User decisions from Phase 2 clarification:** fix gaps AND tighten partials (not gaps-only); include the 3 cross-cutting invariant tests; **exclude `008-recipe-ingredient-checkboxes`** from this workstream — it has no `spec.md` (only `plan.md`/`tasks.md`), so there's nothing spec-shaped to hold its existing tests accountable to. Its current Browser/e2e tests are left as-is; backfilling a spec for it is explicitly out of scope here.
- **Stale-doc note, not a task:** `004-rebrand-header`'s `spec.md` says the app name is "Project Table Top"; the shipped app, and every test, says "YesChef". The tests are right; the spec prose is stale. Noted so nobody "fixes" a passing test to match outdated spec text.
- Three cross-cutting invariants were flagged PARTIAL — not owned by any single spec, but exactly the kind of implicit system-wide rule a from-scratch rewrite could silently get wrong: (1) `Recipe` deletion uses `ON DELETE RESTRICT` on `meal_assignments.recipe_id` while `GroceryList`/`GroceryItem` use `SoftDeletes` — no single test asserts this split is deliberate policy rather than an accident; (2) N+1 prevention is explicitly tested for grocery list generation but not as a blanket check across Recipe/MealPlan index views; (3) auth-guard-on-every-route is consistently true but only spot-checked per feature, never asserted as a single regression test over all of `routes/web.php`.

## Scope

### In Scope
- 6 genuine test gaps (5 in `001-meal-plan-drawer`, 1 in `010-meal-plan-notes`).
- 6 partial items tightened to assert the exact spec'd behavior/edge case (2 in `001-meal-plan-drawer`, 2 in `001-grocery-item-lookup`, 1 in `009-recipe-servings-multiplier`, 1 in `011-share-content`).
- 3 cross-cutting invariant tests not owned by any single spec.

### Out of Scope
- `008-recipe-ingredient-checkboxes` — no spec.md exists; backfilling one is a separate future decision.
- Fixing the stale "Project Table Top" text in `004-rebrand-header`'s spec.md (documentation only, not a test).
- Any spec's FRs not listed above — the gap analysis found them already covered; do not re-verify or add redundant tests for FRs already marked COVERED.
- `001-build-an-application` / `002-update-the-spec` as separate targets (superseded by `003-merge-the-2`, already fully covered).

## Success Criteria
- [ ] All 6 gap items have a passing test that did not exist before this plan.
- [ ] All 6 partial items have a test asserting the specific spec'd edge/behavior, not just the surrounding area.
- [ ] All 3 cross-cutting invariant tests exist and pass.
- [ ] No existing test was weakened or removed to make a new one pass.
- [ ] All tests passing (full suite).
- [ ] Code follows project standards (`vendor/bin/pint --dirty`).

## Task Overview
| Task | Description | Depends On | Status |
|------|-------------|------------|--------|
| 001 | Meal-plan-drawer close behavior, link, and permission gating | - | completed |
| 002 | Meal-plan-drawer content completeness and accessibility | 001 | completed |
| 003 | Scattered spec gap/partial fixes (4 specs) | - | completed |
| 004 | Cross-cutting invariant oracle tests | - | completed |

Tasks 001, 003 and 004 are independent and can run in parallel. **002 depends on 001**: both need the same
non-trivial e2e fixture (logged in → meal plan created → recipe assigned to a slot → drawer opened) and both
write to `e2e/meal-plan-drawer.spec.ts`. Task 001 authors the file and a shared `openDrawer(page)` helper;
002 appends and reuses it. This replaces the earlier "check whether the other task created the file first"
approach, which handled the filename collision but not the duplicated fixture.

## Architecture Notes
- **Plan-wide rule: report, never fix.** Every task in this plan is test-only. If any task discovers the current implementation doesn't actually do what a spec/invariant claims (the drawer backdrop is unreachable due to a CSS pointer-events gap, mobile cards have no keyboard handlers, the PHP and JS quantity formatters round differently), the correct move is always to write the test against *actual current behavior* and record the finding in Implementation Notes — never to patch production code, however small the fix looks. This was explicitly decided during Phase 2 clarification after the devil's-advocate review found Task 001's original draft permitted one bounded CSS fix; that permission was removed in favor of this uniform rule so the whole plan's diff stays purely additive and every real bug found gets its own separately reviewed fix later, not one riding along inside a "just tests" plan.
- Follow this repo's existing test-placement conventions: Livewire/Volt-component behavior (drawer open/close state, permission checks) as Pest `Livewire::test()`/`Volt::test()` feature tests in `tests/Feature/MealPlans/`; visual/interaction behavior that can't be asserted through Livewire's testing API alone (actual Escape-keypress handling, responsive width, dark-mode class application, focus/tab order) as Playwright specs in `e2e/`, following `e2e/meal-plans.spec.ts` and `e2e/header-branding.spec.ts` as the closest existing patterns.
- FR-013 ("smooth slide transition") is not meaningfully assertable via Pest or Playwright — no task should manufacture a fake assertion for CSS transition smoothness. Task 002 documents this explicitly rather than skipping it silently.
- Task 004's invariant tests are regression guards over existing behavior, not new features — they must not change any runtime code, only add tests. If any of them fails on first run (i.e., the assumed invariant doesn't actually hold), that's a real finding to surface, not a bug to silently "fix" by changing behavior.

## Expected Findings (not fixed by this plan — see report-only rule above)
- Meal-plan-drawer backdrop click likely doesn't close the drawer in a real browser (pointer-events interception at `show.blade.php:543`). One-line fix identified in `_devils_advocate.md`, not applied.
- Mobile meal-plan-drawer cards (`data-mobile-calendar` layout) have `role="button"`/`tabindex="0"` but no `@keydown` handlers — FR-016 keyboard access only works on the desktop layout.
- `QuantityFormatter::format()` (PHP, meal-plan drawer) has no 3-decimal clamp equivalent to `formatQuantity()` (JS, recipe page) — they can disagree on the same value.
- These three are legitimate small bug-fix candidates for a future plan; do not fold them into this one.

## Risks & Mitigations
- **A blanket "every route requires auth" test could be brittle** if new public routes are legitimately added later (e.g. a future public share-link route). Mitigation: Task 004 asserts against an explicit exclusion list of known-public routes rather than assuming zero exceptions — the test's job is to catch accidental exposure, not to forbid intentional public routes. Plan review added the concrete mechanics (the `livewire_component` discriminator, per-route HTTP verbs, and the `test.recipe.*` routes that only exist under `APP_ENV=testing`) directly to the task.
- **Task 001/002 e2e specs run against a real browser** — slower and more prone to environment flakiness than Pest feature tests. Mitigation: keep Livewire-state assertions (open/close, permission, field completeness) in Pest where possible; reserve e2e only for what genuinely requires a rendered browser (Escape key, backdrop hit-testing, responsive width, dark mode, keyboard activation).
- **Playwright runs against live DDEV, not a test database.** `playwright.config.ts` has no `webServer` block and defaults `baseURL` to `https://yeschef.ddev.site`, with a seeded `test@example.com` / `password` user. Every e2e fixture must be built through the UI. Mitigation: Task 001 owns the one shared setup helper; Tasks 002/003 reuse existing spec `beforeEach` patterns rather than writing new ones.
- **`tests/Browser/` is not a viable target.** Both files there are entirely `markTestSkipped('Browser test placeholder - use Playwright E2E tests instead')`. Pest v4 browser testing is not operational in this repo despite CLAUDE.md advertising it. Anything needing a real browser goes to `e2e/`.

## Plan Review Findings
A devil's-advocate pass (`_devils_advocate.md`) verified every "verified during planning" claim against source. All line numbers held. Three semantic claims did not, and the affected tasks have been rewritten:
- **Backdrop click (Task 001)** is very likely unreachable in a real browser — the drawer's panel container (`show.blade.php:543-544`) covers the viewport above the backdrop without `pointer-events-none`. Task 001 now requires empirical verification (no `force: true`) and permits exactly one bounded production fix if confirmed.
- **FR-016 mobile keyboard access (Task 002)** is only half-implemented — mobile recipe cards (`show.blade.php:284-290`) expose `role="button"` + `tabindex="0"` but have no `@keydown` handlers. Recorded as a finding, not fixed.
- **FR-009 3-decimal rounding (Task 003 item 5)** is implemented in `resources/js/app.js::formatQuantity()`, not in `ServingSizeScaler` (which does no rounding) or the DB column. The item was retargeted to a Playwright test; the PHP `QuantityFormatter` lacks the equivalent clamp and that asymmetry is recorded as a finding.

Consistent with this plan's stated rule for Task 004 ("a failing invariant is a finding to report, not a bug to fix"), Tasks 001-003 now carry the same rule for the findings above.
