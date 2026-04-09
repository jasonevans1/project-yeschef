# Task 003: Update JS formatQuantity for Servings Multiplier

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Update `formatQuantity()` in `resources/js/app.js` (used by both `servingsMultiplier` and `recipeShowPage` Alpine components) to produce fraction strings instead of trimmed decimals. This is the client-side equivalent of the PHP `QuantityFormatter`, used live as the user adjusts the servings multiplier.

## Context
- Related file: `resources/js/app.js` — `formatQuantity` is defined twice (once in `servingsMultiplier` at line 63, once in `recipeShowPage` at line 101). Both must be updated identically.
- The fraction lookup table must match the PHP `QuantityFormatter`:
  - 0 -> `''`, 0.125 -> `1/8`, 0.25 -> `1/4`, 0.333 -> `1/3`, 0.375 -> `3/8`, 0.5 -> `1/2`, 0.625 -> `5/8`, 0.667 -> `2/3`, 0.75 -> `3/4`, 0.875 -> `7/8`
- **IMPORTANT: 1/3 and 2/3 special-case handling**: A naive round-to-nearest-1/8 would map 0.333 to 0.375 (3/8) and 0.667 to 0.625 (5/8), which is wrong. The implementation MUST check for 1/3 and 2/3 BEFORE rounding to nearest 1/8, using an epsilon check (e.g., `Math.abs(frac - 0.333) < 0.02` and `Math.abs(frac - 0.667) < 0.02`).
- Handle floating-point imprecision: round to nearest 1/8 (with epsilon for 1/3 and 2/3)
- Mixed numbers: whole part + fraction char with no space (e.g., `1.5` -> `"1 1/2"`)
- Whole numbers: no fraction suffix (e.g., `2.0` -> `"2"`)
- Unrecognized fractional parts: fall back to decimal string (e.g., `"0.1"`)
- Extract `formatQuantity` into a module-level function to avoid duplication in the two Alpine components. Note: call sites currently use `this.formatQuantity(scaled)` and must be updated to `formatQuantity(scaled)` after extraction.
- **Deploy note**: This task and task 002 (display_quantity) should be deployed together. The JS-rendered display (multiplier != 1x) and server-rendered fallback (multiplier == 1x) must produce consistent fraction formats.

## Requirements (Test Descriptions)
- [ ] `it formats 0.5 as half unicode character`
- [ ] `it formats 1.5 as one and half unicode`
- [ ] `it formats 0.333 as one third unicode`
- [ ] `it formats 2.667 as two and two thirds unicode`
- [ ] `it formats whole numbers without fraction suffix`
- [ ] `it falls back to decimal for unmappable values like 0.1`
- [ ] `it handles floating point near half (0.4999) correctly as half`
- [ ] `it formats 0.333 as one third (not three eighths from naive rounding)`
- [ ] `it formats 0.667 as two thirds (not five eighths from naive rounding)`

Note: JS tests are Playwright browser tests in `e2e/` or inline unit tests. If no JS test infrastructure exists, write assertions as part of a Pest browser test that visits the recipe show page and verifies the displayed quantities via DOM inspection.

## Acceptance Criteria
- Both `servingsMultiplier` and `recipeShowPage` use the updated `formatQuantity`
- `formatQuantity` is defined once (not duplicated)
- Call sites updated from `this.formatQuantity(...)` to `formatQuantity(...)`
- 1/3 and 2/3 are correctly detected via epsilon check before nearest-1/8 rounding
- Fraction display matches PHP formatter output for standard cooking values
- Run `npm run build` after changes to confirm no build errors
