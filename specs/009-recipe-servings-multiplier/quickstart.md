# Quickstart Guide: Recipe Servings Multiplier

**Feature**: 009-recipe-servings-multiplier
**Branch**: `009-recipe-servings-multiplier`
**Audience**: Developers implementing this feature

## Overview

This guide walks you through implementing a client-side recipe servings multiplier that allows users to scale ingredient quantities dynamically (0.25x to 10x range) using Alpine.js for reactivity.

## Prerequisites

- Laravel 12 application with Livewire 3
- Existing Recipe and RecipeIngredient models
- Alpine.js (bundled with Livewire)
- Flux UI components
- DDEV development environment running

## Quick Setup (5 Steps)

### 1. Register Alpine.js Component

**File**: `resources/js/app.js`

Add the `servingsMultiplier` component after the existing `ingredientCheckboxes` component:

```javascript
Alpine.data('servingsMultiplier', () => ({
    multiplier: 1,
    originalServings: 0,

    // Computed property for adjusted servings count
    get scaledServings() {
        return Math.round(this.originalServings * this.multiplier);
    },

    // Scale individual ingredient quantity
    scaleQuantity(originalQuantity) {
        if (!originalQuantity) return null;
        const scaled = parseFloat(originalQuantity) * this.multiplier;
        return this.formatQuantity(scaled);
    },

    // Format quantity (remove trailing zeros)
    formatQuantity(value) {
        if (value === null) return null;
        let formatted = value.toFixed(3);
        formatted = formatted.replace(/\.?0+$/, '');
        return formatted;
    },

    // Set multiplier with validation
    setMultiplier(value) {
        const numValue = parseFloat(value);
        if (isNaN(numValue)) return;
        this.multiplier = Math.max(0.25, Math.min(10, numValue));
    }
}));
```

### 2. Update Recipe Show View

**File**: `resources/views/livewire/recipes/show.blade.php`

**Step 2a**: Wrap the servings info card with Alpine.js component (around line 87):

```blade
<div x-data="servingsMultiplier()" x-init="originalServings = {{ $recipe->servings }}">
    <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <flux:heading size="sm" class="text-gray-500 dark:text-gray-400 mb-1">Servings</flux:heading>

        {{-- Multiplier Controls --}}
        <div class="flex items-center justify-center gap-2 mb-2">
            <flux:button
                @click="multiplier = Math.max(0.25, multiplier - 0.25)"
                variant="ghost"
                icon="minus"
                size="sm"
                aria-label="Decrease serving size"
            ></flux:button>

            <flux:input
                type="number"
                x-model.number="multiplier"
                min="0.25"
                max="10"
                step="0.25"
                @input="setMultiplier($event.target.value)"
                class="w-20 text-center text-xl font-semibold"
                aria-label="Serving size multiplier"
            />

            <flux:button
                @click="multiplier = Math.min(10, multiplier + 0.25)"
                variant="ghost"
                icon="plus"
                size="sm"
                aria-label="Increase serving size"
            ></flux:button>
        </div>

        {{-- Servings Display --}}
        <div>
            <template x-if="multiplier === 1">
                <flux:text class="text-xl font-semibold dark:text-white">{{ $recipe->servings }}</flux:text>
            </template>
            <template x-if="multiplier !== 1">
                <div>
                    <flux:text class="text-xl font-semibold dark:text-white" x-text="scaledServings"></flux:text>
                    <flux:text class="text-xs text-gray-500 dark:text-gray-400">
                        (from <span x-text="originalServings"></span>)
                    </flux:text>
                </div>
            </template>
        </div>
    </div>
</div>
```

**Step 2b**: Update ingredient quantities to use scaling (around line 126):

```blade
@if ($recipeIngredient->quantity)
    <span class="font-medium">
        <span x-text="scaleQuantity({{ $recipeIngredient->quantity }})"></span>
        @if ($recipeIngredient->unit)
            {{ $recipeIngredient->unit->value }}
        @endif
    </span>
    <span class="ml-1">{{ $recipeIngredient->ingredient->name }}</span>
@else
    <span>{{ $recipeIngredient->notes ?? $recipeIngredient->ingredient->name }}</span>
@endif
```

**Step 2c**: Add ARIA live region for screen reader announcements (before closing `</div>` of Alpine component):

```blade
{{-- Accessibility: Announce multiplier changes to screen readers --}}
<div
    aria-live="polite"
    aria-atomic="true"
    class="sr-only"
    x-text="`Recipe scaled to ${multiplier} times original, making ${scaledServings} servings`"
></div>
```

### 3. Build Frontend Assets

```bash
npm run build
# OR for development with hot reload:
npm run dev
```

### 4. Write Tests

**File**: `tests/Feature/Livewire/RecipeShowTest.php`

Add tests for multiplier functionality:

```php
test('recipe show page displays servings multiplier control', function () {
    $recipe = Recipe::factory()
        ->has(RecipeIngredient::factory()->count(3))
        ->create(['servings' => 4]);

    $response = $this->get(route('recipes.show', $recipe));

    $response->assertOk()
        ->assertSee('Servings')
        ->assertSeeLivewire('recipes.show');
});

test('ingredient quantities can be calculated with different multipliers', function () {
    $recipeIngredient = RecipeIngredient::factory()->create([
        'quantity' => 2.000,
    ]);

    // Multiplier logic is client-side, but we can test the underlying data structure
    expect($recipeIngredient->display_quantity)->toBe('2');
    expect($recipeIngredient->quantity)->toBe(2.000);
});
```

**File**: `tests/Browser/RecipeServingsMultiplierTest.php` (Pest 4 browser test):

```php
<?php

use App\Models\Recipe;
use App\Models\RecipeIngredient;

test('user can adjust recipe servings multiplier', function () {
    $recipe = Recipe::factory()->create(['servings' => 4]);
    RecipeIngredient::factory()->for($recipe)->create([
        'quantity' => 2.000,
    ]);

    $page = visit("/recipes/{$recipe->id}");

    $page->assertSee('4')
        ->click('[aria-label="Increase serving size"]')
        ->pause(500)
        ->assertSee('5')
        ->assertNoJavascriptErrors();
});
```

### 5. Run Tests

```bash
# Run feature tests
php artisan test --filter=RecipeShow

# Run browser tests (Pest 4)
php artisan test tests/Browser/RecipeServingsMultiplierTest.php

# Run all tests
php artisan test
```

## Development Workflow

1. **Start development environment**:
   ```bash
   composer dev
   # This runs: Laravel server, Vite, queue worker, pail (logs)
   ```

2. **View the recipe page**:
   - Navigate to: https://project-tabletop.ddev.site/recipes/{id}
   - Replace `{id}` with any recipe ID from your database

3. **Test multiplier functionality**:
   - Click +/- buttons to adjust multiplier
   - Type custom values in the input field
   - Verify ingredient quantities update in real-time
   - Check browser console for errors

4. **Code formatting** (before commit):
   ```bash
   vendor/bin/pint
   ```

## Verification Checklist

- [ ] Alpine.js component registered in `resources/js/app.js`
- [ ] Servings card updated with multiplier controls
- [ ] Ingredient quantities use `x-text="scaleQuantity()"`
- [ ] ARIA live region added for accessibility
- [ ] Frontend assets built (`npm run build`)
- [ ] Feature tests pass
- [ ] Browser tests pass
- [ ] Code formatted with Pint
- [ ] No console errors when adjusting multiplier
- [ ] Multiplier resets to 1x on page reload

## Troubleshooting

### Issue: Ingredient quantities not updating

**Solution**: Ensure the `x-data="servingsMultiplier()"` wrapper includes both the servings card AND the ingredients list. The Alpine.js component needs to be accessible where `scaleQuantity()` is called.

### Issue: Alpine.js component not found

**Solution**:
1. Verify `Alpine.data('servingsMultiplier', ...)` is in `resources/js/app.js`
2. Rebuild assets: `npm run build`
3. Hard refresh browser: Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)

### Issue: Numbers showing as "[object Object]"

**Solution**: Ensure you're using `x-text` not `x-html`, and that `scaleQuantity()` returns a string, not an object.

### Issue: Multiplier accepts values outside 0.25-10 range

**Solution**: Check that `setMultiplier()` method is called on `@input` event:
```blade
@input="setMultiplier($event.target.value)"
```

## Next Steps

After implementation:

1. **Add E2E tests** (Playwright):
   ```bash
   npx playwright test
   ```

2. **Test accessibility**:
   - Navigate using keyboard only (Tab, Arrow keys, Enter)
   - Test with screen reader (VoiceOver on Mac, NVDA on Windows)
   - Verify ARIA live regions announce changes

3. **Performance testing**:
   - Test with recipes containing 50+ ingredients
   - Verify no lag when adjusting multiplier
   - Check memory usage in browser dev tools

## Reference Documentation

- [Alpine.js Data Directive](https://alpinejs.dev/directives/data)
- [Alpine.js Reactivity](https://alpinejs.dev/advanced/reactivity)
- [Livewire 3 Docs](https://livewire.laravel.com/docs)
- [Flux UI Components](https://fluxui.dev)
- [Pest 4 Browser Tests](https://pestphp.com/docs/browser-testing)

## Getting Help

- **Logs**: Check `storage/logs/laravel.log` for backend errors
- **Browser console**: Check for JavaScript errors
- **Pail**: Run `php artisan pail` for real-time log monitoring
- **Debugbar**: Enable Laravel Debugbar for query analysis

## File Summary

Files created/modified for this feature:

```text
Modified:
  resources/js/app.js                                    # Alpine.js component
  resources/views/livewire/recipes/show.blade.php        # UI and bindings
  tests/Feature/Livewire/RecipeShowTest.php              # Feature tests

Created:
  tests/Browser/RecipeServingsMultiplierTest.php         # Browser tests
  e2e/recipe-servings-multiplier.spec.ts                 # E2E tests (Playwright)
```

Total changes: 3 modified files, 2 new test files.
