# Task 006: E2E Playwright Tests for Fraction Display

**Status**: completed
**Depends on**: 002, 003, 004, 005
**Retry count**: 0

## Description
Add Playwright E2E tests to verify fraction display end-to-end in the browser. Covers the recipe show page (server-rendered fractions and live multiplier fractions), fraction input on create/edit, and meal plan ingredient display.

## Context
- Related files:
  - `e2e/recipe-servings-multiplier.spec.ts` — existing multiplier tests; update the `displays formatted quantities without trailing zeros` test (line 159) to instead assert fraction format (e.g., `½` not `0.5`)
  - `e2e/recipes-create.spec.ts` — existing create tests; the test at line 101 fills `'0.5'` as quantity and the test at line 189 fills `'0.25'`. After task 004, these inputs become `type="text"`, so they still work. Add a new test for fraction string input (e.g., typing `1/2`).
  - `e2e/recipe-ingredient-checkboxes.spec.ts` — may show ingredient quantities; check if any assertions reference decimal values and update them
  - New file: `e2e/fraction-quantity-display.spec.ts` — primary file for fraction-specific E2E tests
- Seed data: the tests use `test@example.com` / `password` (see `e2e/seed.spec.ts` pattern). Rely on existing seeded recipes rather than creating new ones where possible; create a recipe inline when a specific quantity is needed.
- Login helper: each existing spec uses a manual `page.goto('/login')` + fill/click block in `beforeEach`. Follow the same pattern — do not introduce a shared helper unless one already exists.

## Requirements (Test Descriptions)
- [ ] `recipe show page displays ingredient quantity as fraction (½) not decimal (0.5)`
- [ ] `recipe show page displays mixed number (1½) not decimal (1.5) for ingredient quantity`
- [ ] `servings multiplier shows fraction quantities when multiplier is applied`
- [ ] `servings multiplier shows fraction after scaling (2x of 0.25 shows ½)`
- [ ] `recipe create form accepts fraction string input (1/2) and saves correctly`
- [ ] `recipe create form accepts mixed fraction input (1 1/2) and saves correctly`
- [ ] `recipe show page displays created recipe fraction quantity correctly after fraction input`
- [ ] `meal plan ingredient list displays fraction quantity not decimal`
- [ ] update `displays formatted quantities without trailing zeros` in `recipe-servings-multiplier.spec.ts` to assert fraction format instead

## Implementation Notes
To test a specific fraction value end-to-end, create a recipe with a known quantity in the test (e.g., 0.5 or `1/2` typed in the input), then navigate to the show page and assert the fraction character appears.

## Acceptance Criteria
- All requirements have passing tests
- Tests live in `e2e/fraction-quantity-display.spec.ts`
- The updated assertion in `recipe-servings-multiplier.spec.ts` passes
- `npx playwright test e2e/fraction-quantity-display.spec.ts` passes on Chromium
