# Research: Format Ingredient Quantities Display

**Feature**: 007-format-ingredient-quantities
**Date**: 2025-12-06
**Purpose**: Resolve technical unknowns and design decisions for quantity formatting

## Research Questions & Decisions

### 1. How should quantity formatting be implemented?

**Decision**: Eloquent accessor method (`getDisplayQuantityAttribute()`)

**Rationale**:
- Laravel best practice for computed/formatted attributes
- Encapsulates formatting logic in the model (single responsibility)
- Accessible via `$ingredient->display_quantity` in Blade templates
- Can be used anywhere the model is accessed (consistent formatting)
- Similar pattern already exists in GroceryItem model (`getDisplayQuantityAttribute()`)

**Alternatives Considered**:
1. **Blade helper function** - Rejected because:
   - Scatters business logic outside the model
   - Not reusable if RecipeIngredient displayed in multiple views
   - Harder to test in isolation

2. **Custom Blade directive** - Rejected because:
   - Overcomplicated for simple number formatting
   - Adds unnecessary framework extension
   - Not discoverable via IDE autocomplete on model

3. **JavaScript formatting** - Rejected because:
   - Server-side rendering means formatting should happen in PHP
   - Adds client-side dependency for simple display logic
   - Doesn't work if JavaScript disabled or SSR context

### 2. What formatting rules should be applied?

**Decision**: Remove trailing zeros while preserving significant decimals

**Formatting Logic**:
```php
// Pseudo-code for accessor
public function getDisplayQuantityAttribute(): ?string
{
    if ($this->quantity === null) {
        return null;
    }

    // Convert to float, format to remove trailing zeros
    // Example: 2.000 -> "2", 1.500 -> "1.5", 0.750 -> "0.75"
    return rtrim(rtrim(number_format($this->quantity, 3, '.', ''), '0'), '.');
}
```

**Rationale**:
- Handles whole numbers (2.000 → "2")
- Handles fractional with trailing zeros (1.500 → "1.5")
- Preserves necessary decimals (0.333 → "0.333", 2.75 → "2.75")
- Simple PHP string manipulation (no external dependencies)
- Consistent with user expectations from spec

**Alternatives Considered**:
1. **PHP's `floatval()` with string cast** - Rejected because:
   - Scientific notation for very small/large numbers (0.001 → "1.0E-3")
   - Loss of control over decimal precision

2. **Custom fraction conversion (like GroceryItem)** - Out of scope per spec:
   - Feature spec explicitly excludes fraction symbols (½, ¼, etc.)
   - Could be future enhancement but adds complexity
   - Not requested in initial requirement

### 3. How should null quantities be handled?

**Decision**: Return `null` from accessor, maintain existing view logic

**Rationale**:
- Spec requirement FR-004: "MUST maintain existing behavior for null or missing quantity values"
- Current view already handles null quantities correctly (displays ingredient name only)
- Accessor returning null allows view to continue using `@if ($ingredient->display_quantity)` pattern
- No breaking changes to existing recipes with null quantities

**Current View Pattern** (from exploration):
```blade
@if ($recipeIngredient->quantity && $recipeIngredient->unit)
    <span class="font-medium">
        {{ $recipeIngredient->quantity }}  <!-- Replace with display_quantity -->
        {{ $recipeIngredient->unit->value }}
    </span>
    <span class="ml-1">{{ $recipeIngredient->ingredient->name }}</span>
@else
    <span>{{ $recipeIngredient->notes ?? $recipeIngredient->ingredient->name }}</span>
@endif
```

### 4. Should GroceryItem's existing displayQuantity logic be reused?

**Decision**: Implement similar pattern but WITHOUT fraction conversion

**Rationale**:
- GroceryItem has `getDisplayQuantityAttribute()` and `convertToFraction()` methods
- Fraction conversion (0.5 → "½") is explicitly out of scope for this feature
- Can adopt the basic number formatting logic but skip fraction symbols
- Maintains consistency in accessor naming convention across models

**Pattern to Follow**:
```php
// GroceryItem pattern (simplified):
public function getDisplayQuantityAttribute(): string {
    if ($this->quantity === null) return '';
    $fractional = $this->convertToFraction($quantity); // Skip this part
    return trim("{$quantity} {$unit}");
}

// RecipeIngredient pattern (this feature):
public function getDisplayQuantityAttribute(): ?string {
    if ($this->quantity === null) return null;
    $formatted = rtrim(rtrim(number_format($this->quantity, 3, '.', ''), '0'), '.');
    return $formatted;
}
```

**Note**: Unit is NOT included in the accessor because the view already handles unit separately. Accessor returns quantity string only.

### 5. What testing strategy should be used?

**Decision**: Three-tier testing (Unit → Feature → E2E)

**Test Coverage**:

**Tier 1 - Unit Tests** (`tests/Unit/Models/RecipeIngredientTest.php`):
- Test accessor with whole numbers (2.000 → "2", 5.0 → "5")
- Test accessor with fractional (1.5 → "1.5", 0.75 → "0.75")
- Test accessor with precise decimals (0.333 → "0.333", 2.125 → "2.125")
- Test accessor with null quantity (null → null)
- Test accessor with zero (0.000 → "0")
- Test accessor with very small (0.001 → "0.001")
- Test accessor with very large (1000.000 → "1000")

**Tier 2 - Feature Tests** (`tests/Feature/Livewire/RecipeShowTest.php`):
- Create recipe with whole number quantities, assert view shows "2 cups" not "2.000 cups"
- Create recipe with fractional quantities, assert view shows "1.5 tbsp" not "1.500 tbsp"
- Create recipe with null quantity, assert view shows ingredient name only
- Assert no regression in existing recipe display logic

**Tier 3 - E2E Tests** (Playwright or Pest Browser - `tests/Browser/RecipeDisplayTest.php`):
- Load recipe page in browser
- Verify formatted quantities rendered correctly in actual DOM
- Verify no console errors or visual regressions
- Test across different measurement units

**Rationale**:
- Follows constitution Principle III (Test-First Development)
- Unit tests validate logic in isolation (fast, focused)
- Feature tests validate integration with Livewire view rendering
- E2E tests validate complete user-facing behavior (browser rendering)

### 6. What are the edge cases to handle?

**Edge Cases Identified** (from spec):

1. **Exactly 0.000**: Format as "0" (empty string would be confusing)
2. **Repeating decimals (0.333333)**: Preserve as stored (up to 3 decimal places from DB)
3. **Null/missing quantity**: Return null (maintains current behavior)
4. **Very small (0.001)**: Preserve precision, don't round to "0"
5. **Very large (1000.000)**: Format as "1000"

**Handling Strategy**:
- All cases handled by `number_format($qty, 3, '.', '')` + `rtrim()` approach
- Database stores decimal(8,3) so precision capped at 3 places
- Edge case tests included in unit test suite

## Best Practices Applied

### Laravel Eloquent Patterns
- **Accessor naming**: `getDisplayQuantityAttribute()` → `$model->display_quantity`
- **Return type hints**: `?string` to indicate nullable return
- **Null safety**: Explicit null check before processing
- **Single responsibility**: Accessor only formats, doesn't mutate data

### Testing Patterns (Pest 4)
- **Test-first**: Write failing tests before implementation
- **Arrange-Act-Assert**: Clear test structure
- **Factory usage**: Use RecipeIngredient factory for test data
- **Isolation**: Unit tests don't hit database (factory-only), feature tests use RefreshDatabase

### View Patterns (Livewire + Blade)
- **Minimal changes**: Only swap `quantity` for `display_quantity` in template
- **Backward compatibility**: Existing null checks still work
- **No JavaScript required**: Pure server-side rendering

## Implementation Sequence

Based on research findings, implementation should follow this order:

1. **Write failing tests** (Principle III - Test-First):
   - Unit tests for RecipeIngredient display accessor
   - Feature test for recipe view
   - E2E/browser test for visual verification

2. **Implement accessor**:
   - Add `getDisplayQuantityAttribute()` to RecipeIngredient model
   - Add return type hint and null safety check
   - Implement formatting logic with `number_format()` + `rtrim()`

3. **Update view**:
   - Change `{{ $recipeIngredient->quantity }}` to `{{ $recipeIngredient->display_quantity }}`
   - Verify null handling still works with existing `@if` logic

4. **Verify tests pass**:
   - Run `composer test` - all tests should pass
   - Run `vendor/bin/pint` - format code
   - Run E2E tests - visual validation

## Technical Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Breaking existing recipes with null quantities | High | Accessor returns null (not empty string), maintains compatibility |
| Rounding errors with number_format() | Low | Using 3 decimal precision (matches DB), testing edge cases |
| Performance impact of accessor calls | Very Low | Simple string operation, no DB queries or heavy computation |
| Inconsistent formatting across different views | Medium | Centralize in model accessor, reusable anywhere RecipeIngredient displayed |

## Dependencies

**No new dependencies required** - Uses built-in PHP functions:
- `number_format()` - Built-in PHP function
- `rtrim()` - Built-in PHP string function
- Laravel Eloquent accessors - Framework feature

## Conclusion

Research complete. All NEEDS CLARIFICATION items resolved:
- ✅ Implementation approach: Eloquent accessor
- ✅ Formatting rules: Remove trailing zeros, preserve precision
- ✅ Null handling: Return null, maintain existing behavior
- ✅ Testing strategy: Three-tier (unit/feature/E2E)
- ✅ Edge cases: Identified and handling strategy defined

**Ready for Phase 1: Design & Contracts**
