# Quickstart Guide: Format Ingredient Quantities Display

**Feature**: 007-format-ingredient-quantities
**For**: Developers implementing or reviewing this feature
**Estimated Time**: 30-45 minutes

## Overview

This feature improves recipe ingredient display by formatting quantities without trailing zeros (e.g., "2 cups" instead of "2.000 cups"). It's a simple display enhancement that requires:
1. Adding an Eloquent accessor to the RecipeIngredient model
2. Updating one Blade template to use the accessor
3. Writing comprehensive tests

## Prerequisites

- [x] DDEV running (`ddev start`)
- [x] On feature branch `007-format-ingredient-quantities`
- [x] Fresh database or test data available
- [x] Composer dependencies installed
- [x] Development server running (`composer dev`)

## Quick Implementation Steps

### Step 1: Write Failing Tests (Test-First) ⏱️ 10 min

#### 1a. Unit Test for Accessor

Create `tests/Unit/Models/RecipeIngredientTest.php`:

```bash
php artisan make:test --unit Models/RecipeIngredientTest
```

Add test cases (see `contracts/model-accessor.md` for TC-001 to TC-010):

```php
<?php

use App\Models\{RecipeIngredient, Recipe, Ingredient};
use App\Enums\MeasurementUnit;

test('display_quantity formats whole numbers without decimals', function () {
    $ingredient = RecipeIngredient::factory()->make(['quantity' => 2.000]);
    expect($ingredient->display_quantity)->toBe('2');
});

test('display_quantity formats fractional with minimal precision', function () {
    $ingredient = RecipeIngredient::factory()->make(['quantity' => 1.500]);
    expect($ingredient->display_quantity)->toBe('1.5');
});

test('display_quantity preserves necessary decimal precision', function () {
    $ingredient = RecipeIngredient::factory()->make(['quantity' => 0.333]);
    expect($ingredient->display_quantity)->toBe('0.333');
});

test('display_quantity returns null for null quantity', function () {
    $ingredient = RecipeIngredient::factory()->make(['quantity' => null]);
    expect($ingredient->display_quantity)->toBeNull();
});

test('display_quantity formats zero as "0"', function () {
    $ingredient = RecipeIngredient::factory()->make(['quantity' => 0.000]);
    expect($ingredient->display_quantity)->toBe('0');
});

// Add remaining test cases (see contracts/model-accessor.md)
```

**Run tests** (should FAIL):
```bash
php artisan test --filter=RecipeIngredientTest
```

#### 1b. Feature Test for View Rendering

Create `tests/Feature/Livewire/RecipeShowTest.php`:

```bash
php artisan make:test Feature/Livewire/RecipeShowTest
```

Add integration test:

```php
<?php

use App\Models\{Recipe, RecipeIngredient, Ingredient};
use App\Enums\MeasurementUnit;

test('recipe page displays formatted quantities without trailing zeros', function () {
    $recipe = Recipe::factory()
        ->has(RecipeIngredient::factory()
            ->for(Ingredient::factory(['name' => 'Flour']))
            ->state(['quantity' => 2.000, 'unit' => MeasurementUnit::cup])
        )
        ->has(RecipeIngredient::factory()
            ->for(Ingredient::factory(['name' => 'Sugar']))
            ->state(['quantity' => 1.500, 'unit' => MeasurementUnit::tbsp])
        )
        ->create();

    $response = $this->get(route('recipes.show', $recipe));

    $response->assertSee('2 cup'); // Not "2.000 cup"
    $response->assertSee('1.5 tbsp'); // Not "1.500 tbsp"
    $response->assertDontSee('2.000');
    $response->assertDontSee('1.500');
});
```

**Run tests** (should FAIL):
```bash
php artisan test --filter=RecipeShowTest
```

### Step 2: Implement Accessor ⏱️ 5 min

Edit `app/Models/RecipeIngredient.php`:

```php
/**
 * Format quantity for display without trailing zeros.
 *
 * Examples:
 * - 2.000 → "2"
 * - 1.500 → "1.5"
 * - 0.333 → "0.333"
 * - null → null
 */
public function getDisplayQuantityAttribute(): ?string
{
    if ($this->quantity === null) {
        return null;
    }

    $formatted = number_format((float) $this->quantity, 3, '.', '');
    $formatted = rtrim($formatted, '0');
    $formatted = rtrim($formatted, '.');

    return $formatted;
}
```

**Run unit tests** (should PASS now):
```bash
php artisan test --filter=RecipeIngredientTest
```

### Step 3: Update View ⏱️ 5 min

Edit `resources/views/livewire/recipes/show.blade.php`:

**Find this:**
```blade
@if ($recipeIngredient->quantity && $recipeIngredient->unit)
    <span class="font-medium">
        {{ $recipeIngredient->quantity }}
        {{ $recipeIngredient->unit->value }}
    </span>
    <span class="ml-1">{{ $recipeIngredient->ingredient->name }}</span>
@else
    <span>{{ $recipeIngredient->notes ?? $recipeIngredient->ingredient->name }}</span>
@endif
```

**Change to:**
```blade
@if ($recipeIngredient->quantity && $recipeIngredient->unit)
    <span class="font-medium">
        {{ $recipeIngredient->display_quantity }}
        {{ $recipeIngredient->unit->value }}
    </span>
    <span class="ml-1">{{ $recipeIngredient->ingredient->name }}</span>
@else
    <span>{{ $recipeIngredient->notes ?? $recipeIngredient->ingredient->name }}</span>
@endif
```

**Only change**: `quantity` → `display_quantity` (one word)

**Run feature tests** (should PASS now):
```bash
php artisan test --filter=RecipeShowTest
```

### Step 4: Add E2E Test ⏱️ 10 min

#### Option A: Pest Browser Test (Recommended)

Create `tests/Browser/RecipeDisplayTest.php`:

```php
<?php

use App\Models\{Recipe, RecipeIngredient, Ingredient};
use App\Enums\MeasurementUnit;

test('recipe page displays formatted ingredient quantities', function () {
    $recipe = Recipe::factory()
        ->has(RecipeIngredient::factory()
            ->for(Ingredient::factory(['name' => 'All-Purpose Flour']))
            ->state(['quantity' => 2.000, 'unit' => MeasurementUnit::cup])
        )
        ->create();

    $page = visit(route('recipes.show', $recipe));

    $page->assertSee('2 cup')
        ->assertDontSee('2.000')
        ->assertNoJavascriptErrors();
});
```

**Run E2E test**:
```bash
php artisan test --filter=RecipeDisplayTest
```

#### Option B: Playwright Test (Alternative)

Create `e2e/recipe-display.spec.ts`:

```typescript
import { test, expect } from '@playwright/test';

test('recipe displays formatted quantities', async ({ page }) => {
  // Assumes a recipe with ID 1 exists in test database
  await page.goto('/recipes/1');

  // Check that "2 cups" appears, not "2.000 cups"
  await expect(page.locator('text=2 cup')).toBeVisible();
  await expect(page.locator('text=2.000')).not.toBeVisible();
});
```

**Run Playwright**:
```bash
npx playwright test e2e/recipe-display.spec.ts
```

### Step 5: Format Code & Run Full Test Suite ⏱️ 5 min

```bash
# Format code
vendor/bin/pint

# Run all tests
composer test

# If all pass, you're done! ✅
```

## Verification Checklist

After implementation, verify:

- [ ] All unit tests pass (`php artisan test --filter=RecipeIngredientTest`)
- [ ] All feature tests pass (`php artisan test --filter=RecipeShowTest`)
- [ ] E2E tests pass (Pest browser or Playwright)
- [ ] Code formatted (`vendor/bin/pint`)
- [ ] Full test suite passes (`composer test`)
- [ ] Manual verification in browser (optional):
  1. Visit `https://project-tabletop.ddev.site/recipes/[id]`
  2. Check ingredient quantities display without trailing zeros
  3. Check fractional quantities still show decimals (e.g., 1.5)
  4. Check recipes with null quantities still work

## Common Issues & Solutions

### Issue: Tests fail with "Undefined property: display_quantity"
**Solution**: Make sure the accessor method name is exactly `getDisplayQuantityAttribute()` (Laravel convention).

### Issue: View still shows "2.000" instead of "2"
**Solution**: Clear view cache with `php artisan view:clear` and refresh browser.

### Issue: Null quantities cause errors
**Solution**: Verify accessor returns `null` (not empty string) when quantity is null.

### Issue: Decimals rounded incorrectly
**Solution**: Check `number_format()` precision is set to 3 (matches DB column decimal(8,3)).

## Files Modified

| File | Type | Change |
|------|------|--------|
| `app/Models/RecipeIngredient.php` | Modified | Added `getDisplayQuantityAttribute()` accessor |
| `resources/views/livewire/recipes/show.blade.php` | Modified | Changed `quantity` to `display_quantity` |
| `tests/Unit/Models/RecipeIngredientTest.php` | Created | Unit tests for accessor |
| `tests/Feature/Livewire/RecipeShowTest.php` | Created | Feature tests for view rendering |
| `tests/Browser/RecipeDisplayTest.php` | Created | E2E browser tests |

**Total**: 2 files modified, 3 test files created

## Next Steps

After completing this quickstart:

1. **Run full test suite**: `composer test` (all tests should pass)
2. **Manual testing**: Visit a recipe in browser and verify formatting
3. **Code review**: Have another developer review the changes
4. **Commit**: `git add .` and `git commit -m "Format ingredient quantities display"`
5. **Push**: `git push origin 007-format-ingredient-quantities`
6. **PR**: Create pull request to `main` branch

## Estimated Timings

- **Test writing**: 10 minutes
- **Implementation**: 10 minutes
- **Verification**: 5 minutes
- **Total**: ~25-30 minutes (experienced developer)

## References

- **Spec**: [spec.md](spec.md)
- **Research**: [research.md](research.md)
- **Data Model**: [data-model.md](data-model.md)
- **Contract**: [contracts/model-accessor.md](contracts/model-accessor.md)
- **Implementation Plan**: [plan.md](plan.md)

---

**Questions?** Check the research and contract documents for detailed technical decisions and edge case handling.
