# Model Accessor Contract: RecipeIngredient.display_quantity

**Feature**: 007-format-ingredient-quantities
**Date**: 2025-12-06
**Purpose**: Define the contract for the display_quantity accessor method

## Accessor Signature

```php
public function getDisplayQuantityAttribute(): ?string
```

## Contract Specification

### Input
**Source**: `$this->quantity` (decimal(8,3) from database)

### Output
**Type**: `?string` (nullable string)

### Behavior Rules

1. **Null Input → Null Output**
   - When `$this->quantity === null`
   - Return `null`
   - Preserves existing null-handling behavior in views

2. **Whole Numbers → Integer String**
   - Input: `2.000`, `5.0`, `10.000`
   - Output: `"2"`, `"5"`, `"10"`
   - No decimal point or trailing zeros

3. **Fractional Numbers → Minimal Precision String**
   - Input: `1.500`, `0.750`, `2.250`
   - Output: `"1.5"`, `"0.75"`, `"2.25"`
   - Trailing zeros removed, decimal point preserved if needed

4. **Precise Decimals → Preserved Precision String**
   - Input: `0.333`, `2.125`, `0.001`
   - Output: `"0.333"`, `"2.125"`, `"0.001"`
   - All significant digits preserved (up to DB precision of 3 decimals)

5. **Zero → "0"**
   - Input: `0.000`, `0.0`, `0`
   - Output: `"0"`
   - Not empty string, not null

## Test Cases

### Acceptance Criteria

| Test Case | Input (quantity) | Expected Output (display_quantity) | Pass Criteria |
|-----------|------------------|-----------------------------------|---------------|
| TC-001 | `2.000` | `"2"` | No decimal point |
| TC-002 | `1.500` | `"1.5"` | One decimal place |
| TC-003 | `0.750` | `"0.75"` | Two decimal places |
| TC-004 | `0.333` | `"0.333"` | Three decimal places |
| TC-005 | `null` | `null` | Null preserved |
| TC-006 | `0.000` | `"0"` | Zero as string |
| TC-007 | `0.001` | `"0.001"` | Very small number |
| TC-008 | `1000.000` | `"1000"` | Large whole number |
| TC-009 | `5.0` | `"5"` | Single decimal zero removed |
| TC-010 | `2.125` | `"2.125"` | Mixed precision |

## Error Handling

### Invalid States (Should Not Occur)

These states violate database constraints and should never occur in practice, but the accessor should handle gracefully:

| Invalid State | Expected Behavior | Rationale |
|---------------|-------------------|-----------|
| `quantity` is string | PHP type juggling converts to float | DB column is decimal, but defense in depth |
| `quantity` is negative | Format as-is (e.g., "-2") | DB validation should prevent, but display if present |
| `quantity` > 99999.999 | Format as-is (e.g., "100000") | DB column is decimal(8,3), fits within precision |

**Note**: The accessor assumes database constraints are enforced. It does NOT perform validation.

## Usage Examples

### Blade Template Usage

```blade
<!-- Before (displays raw decimal) -->
<span>{{ $recipeIngredient->quantity }} {{ $recipeIngredient->unit->value }}</span>
<!-- Output: "2.000 cups" -->

<!-- After (displays formatted quantity) -->
<span>{{ $recipeIngredient->display_quantity }} {{ $recipeIngredient->unit->value }}</span>
<!-- Output: "2 cups" -->
```

### Null Handling in Views

```blade
<!-- Existing pattern (still works) -->
@if ($recipeIngredient->quantity && $recipeIngredient->unit)
    <span>{{ $recipeIngredient->display_quantity }} {{ $recipeIngredient->unit->value }}</span>
@else
    <span>{{ $recipeIngredient->ingredient->name }}</span>
@endif

<!-- Alternative pattern (also works) -->
@if ($recipeIngredient->display_quantity)
    <span>{{ $recipeIngredient->display_quantity }} {{ $recipeIngredient->unit->value }}</span>
@else
    <span>{{ $recipeIngredient->ingredient->name }}</span>
@endif
```

### PHP/Controller Usage (if needed)

```php
// Access formatted quantity
$formattedQty = $recipeIngredient->display_quantity;

// Access raw quantity (unchanged)
$rawQty = $recipeIngredient->quantity;

// Build display string
$display = $recipeIngredient->display_quantity
    ? "{$recipeIngredient->display_quantity} {$recipeIngredient->unit->value}"
    : $recipeIngredient->ingredient->name;
```

## Performance Contract

- **Time Complexity**: O(1) - constant time string operations
- **Space Complexity**: O(1) - single string allocation
- **Side Effects**: None - read-only accessor
- **Database Queries**: None - operates on loaded attribute
- **Caching**: Not required - computation is trivial

## Implementation Algorithm

```php
public function getDisplayQuantityAttribute(): ?string
{
    // Step 1: Handle null case
    if ($this->quantity === null) {
        return null;
    }

    // Step 2: Format to 3 decimal places (matches DB precision)
    // number_format($number, $decimals, $decimal_separator, $thousands_separator)
    $formatted = number_format((float) $this->quantity, 3, '.', '');
    // Example: 2.000 -> "2.000"

    // Step 3: Remove trailing zeros
    $formatted = rtrim($formatted, '0');
    // Example: "2.000" -> "2."

    // Step 4: Remove trailing decimal point (if no decimals remain)
    $formatted = rtrim($formatted, '.');
    // Example: "2." -> "2"

    // Step 5: Return formatted string
    return $formatted;
}
```

## Versioning & Compatibility

**Version**: 1.0.0 (Initial implementation)

**Backward Compatibility**:
- ✅ Raw `quantity` attribute unchanged
- ✅ Existing null checks continue to work
- ✅ No breaking changes to existing code
- ✅ Opt-in via `display_quantity` accessor call

**Future Enhancements** (Out of Scope):
- Fraction symbol conversion (0.5 → "½", 0.25 → "¼")
- Localization/i18n (European decimal separators)
- Unit abbreviation (cup → "c", tablespoon → "T")

## Security Considerations

**XSS Protection**:
- Output is a plain string (numbers and decimal point only)
- No HTML, JavaScript, or special characters
- Safe for Blade template output without escaping concerns
- No user input involved (quantity comes from validated DB field)

**Data Integrity**:
- Accessor does not modify `quantity` attribute
- Read-only operation (no mutations)
- Cannot introduce data inconsistencies

## Acceptance Checklist

Before marking this contract as complete, verify:

- [ ] All 10 test cases (TC-001 to TC-010) pass
- [ ] Null handling preserves existing view behavior
- [ ] No performance degradation (< 1ms per accessor call)
- [ ] Backward compatibility maintained (raw quantity still accessible)
- [ ] Code follows Laravel accessor naming convention
- [ ] Return type hint matches specification (?string)
- [ ] Implementation algorithm matches contract specification

---

**Contract Status**: Draft → Pending Implementation
**Next Phase**: Write failing tests, implement accessor, verify contract compliance
