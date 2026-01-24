# Multi-Recipe Meal Slots with Slide-Out Drawer

## Overview
Enable meal plan slots to support multiple recipes with a custom Alpine.js slide-out drawer for viewing recipe details including scaled ingredients and a link to the full recipe page.

## User Requirements
- Allow multiple recipes per meal slot (removing current 1-per-slot limitation)
- Display recipes as cards within meal slots
- Click recipe card to open slide-out drawer from right
- Drawer shows: recipe name, servings/multiplier, prep/cook time, scaled ingredients, instructions
- Add "View Full Recipe" button linking to `/recipes/{recipe}`
- Recipes ordered chronologically (by created_at)
- Ingredients displayed with scaled quantities (quantity × serving_multiplier)

## Implementation Steps

### 1. Database Migration
**Create:** `database/migrations/YYYY_MM_DD_HHMMSS_remove_unique_constraint_from_meal_assignments.php`

Remove unique constraint on `meal_assignments(meal_plan_id, date, meal_type)` to allow multiple recipes per slot.

```php
public function up(): void
{
    Schema::table('meal_assignments', function (Blueprint $table) {
        $table->dropUnique(['meal_plan_id', 'date', 'meal_type']);
    });
}

public function down(): void
{
    Schema::table('meal_assignments', function (Blueprint $table) {
        $table->unique(['meal_plan_id', 'date', 'meal_type']);
    });
}
```

### 2. Livewire Component Updates
**File:** `/Users/jasonevans/projects/project-tabletop/app/Livewire/MealPlans/Show.php`

#### Add New State Properties
```php
public ?int $selectedAssignmentId = null;
public bool $showRecipeDrawer = false;
```

#### Add New Methods

**openRecipeDrawer(MealAssignment $assignment)**
- Authorize view on meal plan
- Eager load recipe with ingredients: `$assignment->load(['recipe.recipeIngredients.ingredient'])`
- Set `selectedAssignmentId` and `showRecipeDrawer = true`

**closeRecipeDrawer()**
- Reset drawer state: `showRecipeDrawer = false`, `selectedAssignmentId = null`

#### Add Computed Properties

**getSelectedAssignmentProperty()**
- Return MealAssignment with eager loaded relationships
- Returns null if no assignment selected

**getScaledIngredientsProperty()**
- Calculate scaled quantities: `ingredient.quantity × serving_multiplier`
- Format without trailing zeros (pattern from RecipeIngredient::display_quantity)
- Return array of: name, quantity, unit, notes

#### Modify Existing Methods

**assignRecipe() - Lines 54-100**
- Remove "update existing" logic (lines 74-86)
- Always create new assignment (allow multiple recipes per slot)
- Keep validation and date range checks

**render() - Lines 142-164**
- Update grouping to handle collections (not single items)
- Add sorting by created_at within groups:
```php
$assignments = $mealPlan->mealAssignments
    ->groupBy(fn($a) => $a->date->format('Y-m-d').'_'.$a->meal_type->value)
    ->map(fn($group) => $group->sortBy('created_at'));
```

### 3. View Restructuring
**File:** `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/meal-plans/show.blade.php`

#### Update Meal Slot Cells (Lines 74-130)

Change from single assignment to collection:
```blade
@php
    $key = $date->format('Y-m-d') . '_' . $mealType->value;
    $assignmentCollection = $assignments->get($key) ?? collect();
@endphp
```

**For each assignment in collection:**
- Display recipe card with: name, servings, multiplier
- Make card clickable: `wire:click="openRecipeDrawer({{ $assignment->id }})"`
- Add remove button (top-right, shows on hover)
- Add `wire:key="assignment-{{ $assignment->id }}"` for Livewire

**After recipe cards:**
- Display "Add Recipe" button (always visible)
- If empty slot, add `min-h-[60px]` class
- If has recipes, button text shows "Add Another"

#### Add Drawer Component (After line 226)

**Structure:**
1. Backdrop overlay (click to close, Alpine transitions)
2. Drawer panel (slides from right, max-w-md sm:max-w-lg)
3. Sticky header (recipe name, date, meal type, close button)
4. Scrollable content:
   - Servings info box (with multiplier if ≠ 1.0)
   - Time info grid (prep/cook)
   - Scaled ingredients list
   - Instructions (if available)
   - Notes (if available)
5. Sticky footer (View Full Recipe button + Close button)

**Alpine.js Pattern:**
```blade
<div
    x-data="{ show: @entangle('showRecipeDrawer') }"
    x-show="show"
    @keydown.escape.window="$wire.closeRecipeDrawer()"
>
```

**Transitions:**
- Backdrop: opacity fade (300ms enter, 200ms leave)
- Panel: slide from right (translate-x-full → translate-x-0)

**Ingredients Display:**
```blade
@foreach($this->scaledIngredients as $ingredient)
    <li>
        <span class="font-medium">
            {{ $ingredient['quantity'] }} {{ $ingredient['unit'] }}
        </span>
        {{ $ingredient['name'] }}
    </li>
@endforeach
```

**View Full Recipe Button:**
```blade
<flux:button
    href="{{ route('recipes.show', $this->selectedAssignment->recipe) }}"
    variant="primary"
    icon="arrow-top-right-on-square"
>
    View Full Recipe
</flux:button>
```

### 4. Testing Updates

#### Update `/Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/AssignRecipesTest.php`

**Replace "prevents duplicate assignments" test (lines 61-89):**
- New test: "allows multiple recipes in same meal slot"
- Assign 2 different recipes to same date/meal type
- Assert both assignments exist in database

**Remove "reassigns recipe" test (lines 91-117):**
- Logic no longer exists

#### Add to `/Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ViewMealPlanTest.php`

**New tests:**
1. Displays multiple recipes in same meal slot
2. Displays recipes in chronological order
3. Can open recipe drawer (sets state correctly)
4. Can close recipe drawer (resets state)
5. Calculates scaled ingredient quantities correctly
6. Formats scaled quantities without trailing zeros

### 5. Run Tests & Formatting

- Run migration: `php artisan migrate`
- Format code: `vendor/bin/pint`
- Run tests: `php artisan test --filter=MealPlans`
- Manual QA: Dark mode, mobile, keyboard nav (ESC, Tab)

## Critical Files

- `/Users/jasonevans/projects/project-tabletop/app/Livewire/MealPlans/Show.php`
- `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/meal-plans/show.blade.php`
- `/Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/AssignRecipesTest.php`
- `/Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ViewMealPlanTest.php`

## Edge Cases Handled

- Empty meal slots: Show larger "Add Recipe" button
- Recipe scaling: Format to 3 decimals max, remove trailing zeros
- Authorization: Check view permission in openRecipeDrawer()
- Mobile: Responsive drawer (full width on small screens)
- Dark mode: Support throughout drawer with `dark:` classes
- Keyboard: ESC closes drawer, cards are focusable

## Design Decisions

- **Chronological order:** Use `created_at` (no display_order column needed)
- **Scaling logic:** Server-side (PHP) for consistency and testability
- **Drawer pattern:** Custom Alpine.js (not Flux modal) per user preference
- **Ingredients:** Show scaled quantities only (not original)
- **Recipe link:** Navigate to existing `/recipes/{id}` page

## Estimated Time
3-4 hours total
