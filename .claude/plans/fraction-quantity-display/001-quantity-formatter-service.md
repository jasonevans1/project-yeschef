# Task 001: PHP QuantityFormatter Service

**Status**: completed
**Depends on**: none
**Retry count**: 0

## Description
Create `app/Services/QuantityFormatter.php` — a service that converts a decimal quantity (float) into a human-readable fraction string for cooking display. This is the authoritative PHP implementation that all server-side display will use.

## Context
- Related files: `app/Services/ServingSizeScaler.php` (pattern to follow for service class structure)
- Fraction rounding: round the fractional part to the nearest 1/8
- Supported fractions and their unicode characters:
  - 1/8 → ⅛
  - 1/4 → ¼
  - 1/3 → ⅓  (nearest-1/8 won't reach this exactly; handle ⅓ and ⅔ as special cases at 0.333 and 0.667 ± epsilon)
  - 3/8 → ⅜
  - 1/2 → ½
  - 5/8 → ⅝
  - 2/3 → ⅔
  - 3/4 → ¾
  - 7/8 → ⅞
- Mixed numbers: `1.5` → `1½` (whole + fraction, no space between)
- Whole numbers: `2.0` → `2`
- Pure fractions: `0.5` → `½`
- Values that don't map cleanly: return decimal string (e.g., `0.1` → `"0.1"`)
- `null` input → `null` output

## Requirements (Test Descriptions)
- [ ] `it formats zero point five as one half unicode fraction`
- [ ] `it formats zero point two five as one quarter unicode fraction`
- [ ] `it formats zero point three three three as one third unicode fraction`
- [ ] `it formats zero point seven five as three quarters unicode fraction`
- [ ] `it formats zero point six six seven as two thirds unicode fraction`
- [ ] `it formats one point five as mixed number one and one half`
- [ ] `it formats two point three three three as mixed number two and one third`
- [ ] `it formats whole numbers without fraction part`
- [ ] `it formats zero point one two five as one eighth`
- [ ] `it formats zero point eight seven five as seven eighths`
- [ ] `it returns decimal string for values that do not map to clean fractions`
- [ ] `it returns null for null input`
- [ ] `it handles floating point imprecision near fraction boundaries`

## Acceptance Criteria
- All requirements have passing tests
- Service is injectable / can be used as a static utility
- Tests live in `tests/Unit/Services/QuantityFormatterTest.php`
