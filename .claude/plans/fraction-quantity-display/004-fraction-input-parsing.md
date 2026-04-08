# Task 004: Accept Fraction Input on Recipe Create/Edit

**Status**: completed
**Depends on**: 001
**Retry count**: 0

## Description
Update recipe ingredient quantity input handling to accept fraction strings typed by the user (e.g., `1/2`, `1 1/2`, `½`) and convert them to the decimal value before saving. This keeps the storage decimal while letting the user work in fractions naturally.

## Context
- Related files:
  - `resources/views/components/ingredient-input.blade.php` — the quantity input field. **IMPORTANT**: The input currently uses `type="number"` with `step="0.01"` and `min="0.01"` (line 19-26). HTML number inputs reject non-numeric characters (`/`, spaces, unicode fractions) at the browser level. This MUST be changed to `type="text"` with `inputmode="decimal"` and the `step`/`min` attributes removed, so users can type fraction strings.
  - `app/Livewire/Recipes/Create.php` — `save()` method (line 82), `validateIngredients()` (lines 132-151), and ingredient save logic (line 118-124 where `$ingredientData['quantity'] ?? 1` is used)
  - `app/Livewire/Recipes/Edit.php` — `update()` method (line 118), `validateIngredients()` (lines 170-189), and ingredient save logic (line 156-162 where `$ingredientData['quantity'] ?? 1` is used). **This file MUST also be updated** — it has identical quantity handling.
  - `app/Services/QuantityFormatter.php` — add a `parse()` static method here (or a companion `parseFraction()` utility)
- Input formats to support:
  - ASCII fractions: `1/2`, `3/4`, `1/3`, `2/3`
  - Mixed ASCII: `1 1/2`, `2 3/4`
  - Unicode fraction characters: `½`, `¼`, `⅓`, `¾`
  - Plain decimals continue to work: `0.5`, `1.5`
- **Parsing must happen before validation**: Create a `parseIngredientQuantities()` method that converts each `$this->ingredients[$index]['quantity']` from string to float. Call it at the start of `save()` (Create.php) and `update()` (Edit.php), BEFORE `$this->validate()` and `$this->validateIngredients()`. This is critical because `validateIngredients()` checks `$ingredient['quantity'] <= 0` which will behave incorrectly with string fraction values (PHP coerces `"1/2"` to `1`).
- Validation error message should mention fractions are accepted: `"Quantity must be a number or fraction (e.g. 1/2, 1 1/2)"`
- The `quantity` field in the ingredient row array stays as a float/null after parsing

## Requirements (Test Descriptions)
- [ ] `it parses ascii fraction one slash two to zero point five`
- [ ] `it parses mixed number one space one slash two to one point five`
- [ ] `it parses unicode half character to zero point five`
- [ ] `it parses unicode mixed one and half to one point five`
- [ ] `it parses plain decimal string unchanged`
- [ ] `it rejects invalid fraction string with validation error`
- [ ] `it saves ingredient with fraction quantity correctly`
- [ ] `it creates recipe with fraction quantity that stores decimal in database`
- [ ] `it edits recipe with fraction quantity that stores decimal in database`

## Acceptance Criteria
- All requirements have passing tests
- Parsing logic lives in `QuantityFormatter` (or a dedicated method) — not inline in the Livewire component
- Both `Create.php` and `Edit.php` are updated with the parsing step
- The `ingredient-input.blade.php` component quantity input is changed from `type="number"` to `type="text"` with `inputmode="decimal"`
- Tests live in `tests/Feature/Recipe/FractionInputTest.php`
