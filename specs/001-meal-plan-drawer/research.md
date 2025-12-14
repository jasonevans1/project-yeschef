# Research & Technical Decisions

**Feature**: Multi-Recipe Meal Slots with Recipe Drawer
**Date**: 2025-12-14
**Branch**: `001-meal-plan-drawer`

This document captures research findings and technical decisions made during Phase 0 planning.

---

## 1. Alpine.js Drawer Pattern with Livewire

### Research Question
How to implement a custom Alpine.js slide-out drawer integrated with Livewire state management?

### Findings

**Livewire 3 includes Alpine.js by default** (no manual installation needed)
- Alpine.js v3 ships with Livewire 3
- Plugins included: `persist`, `intersect`, `collapse`, `focus`
- Use `@entangle` directive for two-way reactive state binding between Livewire and Alpine

**Drawer Pattern**:
```blade
<div
    x-data="{ show: @entangle('showRecipeDrawer') }"
    x-show="show"
    @keydown.escape.window="$wire.closeRecipeDrawer()"
    style="display: none;"
>
    <!-- Backdrop -->
    <div x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>

    <!-- Panel -->
    <div x-show="show"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full">
    </div>
</div>
```

### Decision

**Use `@entangle` for reactive state management**
- Rationale: Two-way binding keeps Alpine and Livewire in sync automatically
- Implementation: `x-data="{ show: @entangle('showRecipeDrawer') }"`

**Server-side drawer state control**
- Rationale: Authorization checks must happen server-side
- Implementation: Livewire methods `openRecipeDrawer()` and `closeRecipeDrawer()` manage state
- Alpine reacts to state changes via `@entangle`

**Transition timing: 300ms enter, 200ms leave**
- Rationale: Meets SC-003 requirement (drawer opens/closes within 300ms)
- Implementation: Use Alpine's `x-transition` directives

**Escape key handling**
- Rationale: Meets FR-010 (close drawer with Escape key)
- Implementation: `@keydown.escape.window="$wire.closeRecipeDrawer()"`

---

## 2. Database Constraint Removal Impact

### Research Question
What are the implications of removing the unique constraint on `meal_assignments` table?

### Findings

**Current Constraint** (from `2025_10_12_190501_create_meal_assignments_table.php:25`):
```php
$table->unique(['meal_plan_id', 'date', 'meal_type']);
```

**Impact Analysis**:
- ✅ **No data migration needed** - Existing records remain valid (one recipe per slot is subset of multiple)
- ✅ **No query performance impact** - Existing index on `(meal_plan_id, date)` remains for query optimization
- ✅ **Controller error handling** - Can remove constraint violation catch block in `MealAssignmentController::store()`
- ⚠️ **Application logic change** - Must update `assignRecipe()` to always create (never update existing)

**Migration Safety**:
- Dropping constraint is non-destructive
- Rollback via `down()` method: `$table->unique(['meal_plan_id', 'date', 'meal_type']);`
- Safe to run in production (existing data unaffected)

### Decision

**Remove constraint via migration**
- Rationale: Enables core feature requirement (FR-001: multiple recipes per slot)
- Implementation: `Schema::table()->dropUnique(['meal_plan_id', 'date', 'meal_type'])`

**No data cleanup required**
- Rationale: Existing single-recipe slots are valid multi-recipe slots (with count=1)
- Implementation: No `up()` data transformations needed

**Remove duplicate-catching logic in controller**
- Rationale: Constraint no longer enforced at database level, catching is unnecessary
- Implementation: Remove try-catch block from `MealAssignmentController::store()` (lines 40-54)

**Update Livewire component assignment logic**
- Rationale: Current code updates existing assignment (lines 74-86 of `Show.php`), should always create new
- Implementation: Remove conditional "if assignment exists, update" - always create new record

---

## 3. Ingredient Scaling Display

### Research Question
Should ingredient scaling logic be server-side (Livewire computed property) or client-side (Alpine.js)?

### Findings

**Existing Pattern** (`RecipeIngredient::getDisplayQuantityAttribute()`):
```php
public function getDisplayQuantityAttribute(): string
{
    $formatted = number_format((float) $this->quantity, 3, '.', '');
    $formatted = rtrim($formatted, '0');
    $formatted = rtrim($formatted, '.');
    return $formatted;
}
```

**Existing Scaling Service** (`ServingSizeScaler.php`):
```php
public function scale(float $quantity, float $multiplier): float
{
    return $quantity * $multiplier;
}
```

**Current Usage**:
- Scaling used in `GroceryListGenerator` service (server-side)
- Display formatting used in recipe views (server-side accessor)
- No client-side calculation precedent in codebase

**Options Evaluated**:
1. **Server-side computed property** - Calculate during Livewire render
2. **Client-side Alpine.js** - Calculate in browser using JavaScript
3. **Hybrid** - Pre-calculate server-side, Alpine handles display only

### Decision

**Use server-side Livewire computed property**
- Rationale:
  - Consistent with existing codebase patterns (scaling in PHP, not JS)
  - Testable via Pest tests (FR-007, FR-008 require calculation accuracy)
  - No JavaScript precision issues with decimal math
  - Simpler - leverages existing `ServingSizeScaler` service
- Implementation: Add `getScaledIngredientsProperty()` to `Show.php`

**Quantity Formatting**
- Rationale: Reuse existing `RecipeIngredient::display_quantity` pattern (max 3 decimals, strip trailing zeros)
- Implementation:
```php
public function getScaledIngredientsProperty(): array
{
    if (!$this->selectedAssignment) {
        return [];
    }

    return $this->selectedAssignment->recipe->recipeIngredients->map(function ($recipeIngredient) {
        $scaledQuantity = $recipeIngredient->quantity * $this->selectedAssignment->serving_multiplier;

        // Format using same pattern as RecipeIngredient::getDisplayQuantityAttribute()
        $formatted = number_format($scaledQuantity, 3, '.', '');
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, '.');

        return [
            'name' => $recipeIngredient->ingredient->name,
            'quantity' => $formatted,
            'unit' => $recipeIngredient->unit->value,
            'notes' => $recipeIngredient->notes,
        ];
    })->toArray();
}
```

**Alternative Rejected**: Client-side Alpine.js calculation
- Reason: Adds JavaScript complexity, harder to test, precision issues with decimals

---

## 4. Collection Grouping with Multiple Items

### Research Question
How to group meal assignments by slot and handle multiple items per group?

### Findings

**Current Pattern** (Show.php:155-157):
```php
$assignments = $mealPlan->mealAssignments->groupBy(function ($assignment) {
    return $assignment->date->format('Y-m-d').'_'.$assignment->meal_type->value;
});
```

Returns: `Collection<string, Collection<MealAssignment>>`
- Key: `"2025-12-14_lunch"`
- Value: `Collection` with single item (due to unique constraint)

**After Constraint Removal**:
- Same structure, but each group can have multiple items
- Need to sort items within each group by `created_at` (FR-003)

**Options Evaluated**:
1. `groupBy()->map(fn($group) => $group->sortBy('created_at'))` - Map over groups
2. `groupBy()->transform(fn($group) => $group->sortBy('created_at'))` - Transform in place
3. Nested loops in Blade - Group in PHP, sort in Blade

### Decision

**Use `groupBy()->map()` pattern with sorting**
- Rationale:
  - Clear, readable, follows Laravel conventions
  - Sorting happens once during render, not per view iteration
  - Returns immutable sorted collections (safe for Blade iteration)
- Implementation:
```php
$assignments = $mealPlan->mealAssignments
    ->groupBy(fn($a) => $a->date->format('Y-m-d').'_'.$a->meal_type->value)
    ->map(fn($group) => $group->sortBy('created_at'));
```

**View Pattern Change**:
- Old: `$assignments->get($key)?->first()` - Gets first (only) item
- New: `$assignments->get($key) ?? collect()` - Gets collection of items
- Blade: Change from `@if` to `@forelse` loop

**Alternative Rejected**: Sorting in Blade with `->sortBy()`
- Reason: Less efficient (sorts every render iteration), violates separation of concerns

---

## 5. Keyboard Accessibility for Drawer

### Research Question
How to implement ARIA attributes and keyboard navigation for accessible slide-out panels?

### Findings

**ARIA Roles for Slide-Out Drawers**:
- Use `role="dialog"` on drawer panel
- Use `aria-modal="true"` to indicate modal behavior
- Use `aria-labelledby` pointing to drawer title
- Use `aria-describedby` for drawer description (optional)

**Focus Management**:
- Trap focus within drawer when open (prevent tabbing to background)
- Focus first interactive element when drawer opens
- Return focus to trigger element when drawer closes
- Use Alpine's `$focus` magic for focus management

**Keyboard Navigation Requirements**:
- Escape key: Close drawer (✅ Already researched in #1)
- Tab key: Cycle through interactive elements in drawer
- Enter/Space: Activate buttons and links
- Recipe cards: Make focusable with `tabindex="0"`, activate with Enter/Space

### Decision

**ARIA Attributes on Drawer**
- Rationale: Screen reader accessibility (meets WCAG 2.1 AA standards)
- Implementation:
```blade
<div role="dialog"
     aria-modal="true"
     aria-labelledby="drawer-title"
     x-show="show"
     x-trap="show">
```

**Focus Management with Alpine's `$focus` magic**
- Rationale: Alpine provides `x-trap` directive for focus trapping
- Implementation:
  - `x-trap="show"` on drawer container
  - `x-init="$watch('show', value => { if (value) $focus.focus($refs.closeBtn) })"` to focus close button on open
  - Automatic focus return on close (Alpine handles this)

**Recipe Card Keyboard Activation**
- Rationale: Meets FR-016 (keyboard-accessible and focusable)
- Implementation:
```blade
<button
    type="button"
    wire:click="openRecipeDrawer({{ $assignment->id }})"
    @keydown.enter="$wire.openRecipeDrawer({{ $assignment->id }})"
    @keydown.space.prevent="$wire.openRecipeDrawer({{ $assignment->id }})"
    class="...focus:outline-none focus:ring-2 focus:ring-blue-500"
>
```

**Alternative Rejected**: Manual focus management with JavaScript
- Reason: Alpine's `x-trap` and `$focus` handle 90% of cases, simpler implementation

---

## Technology Stack Decisions Summary

| Area | Technology Choice | Rationale |
|------|------------------|-----------|
| Drawer State | Livewire + Alpine `@entangle` | Two-way reactive binding, server-side authorization |
| Transitions | Alpine `x-transition` | Meets 300ms performance goal, native to Alpine |
| Scaling Logic | Server-side Livewire computed property | Testable, consistent with codebase, no decimal precision issues |
| Grouping | `groupBy()->map()->sortBy()` | Readable, efficient, immutable collections |
| Accessibility | ARIA + Alpine `x-trap` | WCAG 2.1 AA compliance, built-in focus management |
| Quantity Formatting | Reuse `RecipeIngredient` pattern | Consistency, proven implementation |

---

## Open Questions (None)

All research questions have been resolved. Proceed to Phase 1 design artifacts.

---

## References

- Livewire 3 Documentation: Alpine.js Integration
- Alpine.js Documentation: `x-trap`, `x-transition`, `@entangle`
- Laravel Collections: `groupBy()`, `map()`, `sortBy()`
- WCAG 2.1: Dialog (Modal) Accessibility
- Project Constitution: Livewire-First Architecture, Test-First Development
