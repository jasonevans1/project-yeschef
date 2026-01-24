# Data Model & Schema Changes

**Feature**: Multi-Recipe Meal Slots with Recipe Drawer
**Date**: 2025-12-14
**Branch**: `001-meal-plan-drawer`

This document defines the data model changes required for the feature.

---

## Database Schema Changes

### Migration: Remove Unique Constraint

**File**: `database/migrations/YYYY_MM_DD_HHMMSS_remove_unique_constraint_from_meal_assignments.php`

**Purpose**: Allow multiple recipe assignments to the same meal slot (date + meal type combination)

**Change Type**: Schema modification (constraint removal)

**Migration Code**:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_assignments', function (Blueprint $table) {
            // Remove unique constraint that prevented multiple recipes per slot
            $table->dropUnique(['meal_plan_id', 'date', 'meal_type']);
        });
    }

    public function down(): void
    {
        Schema::table('meal_assignments', function (Blueprint $table) {
            // Restore unique constraint for rollback
            $table->unique(['meal_plan_id', 'date', 'meal_type']);
        });
    }
};
```

**Impact Analysis**:
- ✅ **Existing data**: No changes needed (current single-recipe slots remain valid)
- ✅ **Query performance**: Existing index on `(meal_plan_id, date)` remains for optimization
- ✅ **Rollback safety**: `down()` method restores constraint (fails if multiple recipes exist per slot)
- ⚠️ **Application logic**: Must update `assignRecipe()` to create (not update) assignments

**Testing**:
```php
// Test: Can assign multiple recipes to same slot
it('allows multiple recipes in same meal slot', function () {
    $user = User::factory()->create();
    $mealPlan = MealPlan::factory()->for($user)->create();
    $recipe1 = Recipe::factory()->create();
    $recipe2 = Recipe::factory()->create();

    // Assign first recipe
    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe1->id,
        'date' => '2025-12-14',
        'meal_type' => MealType::LUNCH,
        'serving_multiplier' => 1.0,
    ]);

    // Assign second recipe to same slot (should succeed after migration)
    MealAssignment::create([
        'meal_plan_id' => $mealPlan->id,
        'recipe_id' => $recipe2->id,
        'date' => '2025-12-14',
        'meal_type' => MealType::LUNCH,
        'serving_multiplier' => 1.5,
    ]);

    expect(MealAssignment::count())->toBe(2);
});
```

---

## Existing Models (No Changes Required)

### MealAssignment

**File**: `app/Models/MealAssignment.php`

**Schema** (from existing migration):
```php
id                 - bigint, primary key
meal_plan_id       - bigint, foreign key -> meal_plans.id
recipe_id          - bigint, foreign key -> recipes.id
date               - date (meal date)
meal_type          - string (enum: breakfast, lunch, dinner, snack)
serving_multiplier - decimal(5,2), default 1.00
notes              - text, nullable
created_at         - timestamp
updated_at         - timestamp
```

**Indexes**:
- Primary key: `id`
- Foreign keys: `meal_plan_id`, `recipe_id`
- Composite index: `(meal_plan_id, date)` - for efficient slot queries
- ~~Unique constraint: `(meal_plan_id, date, meal_type)`~~ - **REMOVED by migration**

**Relationships**:
- `belongsTo(MealPlan::class)` - Parent meal plan
- `belongsTo(Recipe::class)` - Assigned recipe

**Casts**:
- `date` → `'date'` (Carbon instance)
- `meal_type` → `MealType::class` (enum)
- `serving_multiplier` → `'decimal:2'`

**Validation Rules** (from FormRequest):
- `meal_plan_id`: required, exists:meal_plans,id
- `recipe_id`: required, exists:recipes,id
- `date`: required, date, within meal plan date range
- `meal_type`: required, in:breakfast,lunch,dinner,snack
- `serving_multiplier`: required, numeric, between:0.25,10
- `notes`: nullable, string, max:1000

**No Model Changes**: This model remains unchanged. The migration only affects database constraints.

---

### MealPlan

**File**: `app/Models/MealPlan.php`

**Schema**:
```php
id          - bigint, primary key
user_id     - bigint, foreign key -> users.id
name        - string
start_date  - date
end_date    - date
description - text, nullable
created_at  - timestamp
updated_at  - timestamp
```

**Relationships**:
- `belongsTo(User::class)` - Owner
- `hasMany(MealAssignment::class)` - Multiple assignments (can now be multiple per slot)
- `belongsToMany(Recipe::class, 'meal_assignments')` - Recipes through pivot

**Computed Attributes**:
- `duration_days`: `end_date - start_date + 1`
- `is_active`: Current date within range
- `is_past`: End date is past
- `is_future`: Start date is future
- `assignment_count`: Count of meal assignments

**No Model Changes**: This model remains unchanged.

---

### Recipe

**File**: `app/Models/Recipe.php`

**Relationships Used in Feature**:
- `hasMany(RecipeIngredient::class)` - Ingredients with quantities
- `belongsToMany(Ingredient::class, 'recipe_ingredients')` - Ingredients via pivot

**Computed Attributes Used in Feature**:
- `total_time`: `prep_time + cook_time`
- `ingredient_count`: Count of recipe ingredients

**No Model Changes**: This model remains unchanged.

---

### RecipeIngredient

**File**: `app/Models/RecipeIngredient.php`

**Schema**:
```php
id            - bigint, primary key
recipe_id     - bigint, foreign key -> recipes.id
ingredient_id - bigint, foreign key -> ingredients.id
quantity      - decimal(8,3)
unit          - string (enum: MeasurementUnit)
notes         - string, nullable
sort_order    - integer, default 0
created_at    - timestamp
updated_at    - timestamp
```

**Relationships**:
- `belongsTo(Recipe::class)`
- `belongsTo(Ingredient::class)`

**Casts**:
- `unit` → `MeasurementUnit::class` (enum)
- `quantity` → `'decimal:3'`

**Accessor Used in Feature**:
```php
public function getDisplayQuantityAttribute(): string
{
    $formatted = number_format((float) $this->quantity, 3, '.', '');
    $formatted = rtrim($formatted, '0');
    $formatted = rtrim($formatted, '.');
    return $formatted;
}
```

**No Model Changes**: This model remains unchanged. The `display_quantity` accessor pattern will be replicated in Livewire for scaled quantities.

---

## Livewire Component State Model

### Show Component State

**File**: `app/Livewire/MealPlans/Show.php`

**New Properties** (to be added):
```php
public ?int $selectedAssignmentId = null;  // ID of assignment in open drawer
public bool $showRecipeDrawer = false;     // Drawer visibility state
```

**Computed Properties** (to be added):
```php
// Returns MealAssignment with eager loaded recipe, ingredients, and ingredient details
public function getSelectedAssignmentProperty(): ?MealAssignment
{
    if (!$this->selectedAssignmentId) {
        return null;
    }

    return MealAssignment::with([
        'recipe.recipeIngredients.ingredient'
    ])->find($this->selectedAssignmentId);
}

// Returns array of scaled ingredients for drawer display
public function getScaledIngredientsProperty(): array
{
    if (!$this->selectedAssignment) {
        return [];
    }

    return $this->selectedAssignment->recipe->recipeIngredients->map(function ($recipeIngredient) {
        $scaledQuantity = $recipeIngredient->quantity * $this->selectedAssignment->serving_multiplier;

        // Format using RecipeIngredient display pattern
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

**State Transitions**:
1. **Drawer Closed** (Initial State)
   - `selectedAssignmentId = null`
   - `showRecipeDrawer = false`

2. **User Clicks Recipe Card**
   - `wire:click="openRecipeDrawer({{ $assignment->id }})"`
   - Component validates authorization
   - Sets `selectedAssignmentId = $assignment->id`
   - Sets `showRecipeDrawer = true`
   - Eager loads recipe data

3. **Drawer Open**
   - Alpine.js shows drawer via `@entangle('showRecipeDrawer')`
   - Computed properties `selectedAssignment` and `scaledIngredients` provide data
   - User can view details, click "View Full Recipe", or close

4. **User Closes Drawer**
   - `wire:click="closeRecipeDrawer"` or ESC key
   - Sets `showRecipeDrawer = false`
   - Resets `selectedAssignmentId = null`
   - Alpine.js hides drawer with transition

---

## Entity Relationships Diagram

```text
User
  └── hasMany: MealPlan
        ├── hasMany: MealAssignment (NOW ALLOWS MULTIPLE PER SLOT)
        │     ├── belongsTo: Recipe
        │     │     └── hasMany: RecipeIngredient
        │     │           └── belongsTo: Ingredient
        │     └── date + meal_type (slot identifier, no longer unique)
        └── belongsToMany: Recipe (via meal_assignments pivot)

Key Changes:
- MealAssignment: Removed unique constraint on (meal_plan_id, date, meal_type)
- Grouping: Multiple MealAssignments can share same (date, meal_type) combination
- Sorting: Within groups, assignments sorted by created_at (chronological order)
```

---

## Data Access Patterns

### Fetching Meal Plan with Assignments

**Current Pattern** (Show.php:render()):
```php
$mealPlan->load(['mealAssignments.recipe']);
```

**Updated Pattern** (for drawer):
```php
// When opening drawer, eager load full ingredient details
$assignment->load(['recipe.recipeIngredients.ingredient']);
```

**Grouping Pattern** (Show.php:render()):
```php
$assignments = $mealPlan->mealAssignments
    ->groupBy(fn($a) => $a->date->format('Y-m-d').'_'.$a->meal_type->value)
    ->map(fn($group) => $group->sortBy('created_at'));

// Result: Collection<string, Collection<MealAssignment>>
// Key: "2025-12-14_lunch"
// Value: Collection([assignment1, assignment2, ...]) sorted by created_at
```

### Creating Assignments

**Old Logic** (Show.php:assignRecipe(), lines 74-86):
```php
// Find existing assignment and update
$assignment = MealAssignment::where([
    'meal_plan_id' => $this->mealPlan->id,
    'date' => $this->selectedDate,
    'meal_type' => $this->selectedMealType,
])->first();

if ($assignment) {
    $assignment->update([...]);
} else {
    MealAssignment::create([...]);
}
```

**New Logic** (to be implemented):
```php
// Always create new assignment (no checking for existing)
MealAssignment::create([
    'meal_plan_id' => $this->mealPlan->id,
    'recipe_id' => $recipe->id,
    'date' => $this->selectedDate,
    'meal_type' => $this->selectedMealType,
    'serving_multiplier' => $this->servingMultiplier,
]);
```

---

## Data Validation

### Existing Validation (FormRequest)

**StoreAssignmentRequest** (used by MealAssignmentController):
```php
public function rules(): array
{
    return [
        'meal_plan_id' => 'required|exists:meal_plans,id',
        'recipe_id' => 'required|exists:recipes,id',
        'date' => 'required|date|after_or_equal:meal_plan.start_date|before_or_equal:meal_plan.end_date',
        'meal_type' => 'required|in:breakfast,lunch,dinner,snack',
        'serving_multiplier' => 'required|numeric|between:0.25,10',
        'notes' => 'nullable|string|max:1000',
    ];
}
```

**Livewire Validation** (Show.php:assignRecipe()):
```php
$this->validate([
    'servingMultiplier' => 'required|numeric|min:0.25|max:10',
]);
```

**No Validation Changes**: Same rules apply, just removing the unique constraint enforcement.

---

## Migration Rollback Strategy

**Rollback Scenario**: If migration needs to be reversed:

1. **Check for Multi-Recipe Slots**:
```php
// Before running down(), check if any slots have multiple recipes
$duplicateSlots = DB::table('meal_assignments')
    ->select('meal_plan_id', 'date', 'meal_type', DB::raw('COUNT(*) as count'))
    ->groupBy('meal_plan_id', 'date', 'meal_type')
    ->having('count', '>', 1)
    ->get();

if ($duplicateSlots->isNotEmpty()) {
    throw new Exception('Cannot rollback: some meal slots have multiple recipes');
}
```

2. **Run Rollback**:
```bash
php artisan migrate:rollback --step=1
```

3. **Result**: Unique constraint restored, single-recipe-per-slot enforced again

**Data Cleanup** (if needed before rollback):
```php
// Keep only the oldest assignment per slot
DB::table('meal_assignments')
    ->whereNotIn('id', function ($query) {
        $query->select(DB::raw('MIN(id)'))
              ->from('meal_assignments')
              ->groupBy('meal_plan_id', 'date', 'meal_type');
    })
    ->delete();
```

---

## Summary

**Schema Changes**: 1 migration (remove unique constraint)
**Model Changes**: 0 (no model code modifications)
**Livewire State**: 2 new properties, 2 new computed properties
**Data Migration**: None required
**Rollback**: Safe with data cleanup option

All data model changes are non-destructive and backward-compatible with existing data.
