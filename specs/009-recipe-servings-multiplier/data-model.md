# Data Model: Recipe Servings Multiplier

**Feature**: 009-recipe-servings-multiplier
**Type**: Read-Only (No Database Schema Changes)
**Created**: 2025-12-14

## Overview

This feature is entirely **display-only** with no database writes. It reads existing recipe data and applies client-side scaling calculations to ingredient quantities. All multiplier state exists only in the browser session and resets on page reload.

## Existing Database Entities (Read-Only)

### Recipe Model

**Table**: `recipes`
**Model Class**: `App\Models\Recipe`

**Attributes Used by This Feature**:

| Attribute | Type | Description | Usage in Feature |
|-----------|------|-------------|-----------------|
| `id` | integer | Primary key | Recipe identification |
| `name` | string | Recipe name | Display only |
| `servings` | integer | Original serving count | Base value for scaling (e.g., "serves 4") |
| `user_id` | integer nullable | Recipe owner | Authorization only |

**Relationships Used**:
- `recipeIngredients` → `hasMany(RecipeIngredient::class)` - List of ingredients with quantities to scale

**Not Modified**: Recipe data is never updated by this feature. The `servings` field remains unchanged in the database.

### RecipeIngredient Model

**Table**: `recipe_ingredients`
**Model Class**: `App\Models\RecipeIngredient`

**Attributes Used by This Feature**:

| Attribute | Type | Description | Usage in Feature |
|-----------|------|-------------|-----------------|
| `id` | integer | Primary key | Ingredient identification |
| `recipe_id` | integer | Foreign key to recipes | Relationship binding |
| `ingredient_id` | integer | Foreign key to ingredients | Relationship binding |
| `quantity` | decimal(8,3) nullable | Original ingredient quantity | **Base value for scaling** |
| `unit` | string (enum) nullable | Measurement unit (e.g., "cup", "tbsp") | Display with scaled quantity |
| `sort_order` | integer | Display order | Sorting ingredients in list |
| `notes` | text nullable | Special instructions | Display alongside quantity |

**Computed Attributes (Laravel Accessor)**:
- `display_quantity` → `getDisplayQuantityAttribute()`: Formats quantity without trailing zeros (e.g., "2.000" → "2")
  - **Usage**: This existing accessor provides the pattern for client-side formatting

**Relationships Used**:
- `ingredient` → `belongsTo(Ingredient::class)` - Ingredient name for display

**Not Modified**: RecipeIngredient quantities are never updated. All scaling happens client-side.

### Ingredient Model (Transitive Relationship)

**Table**: `ingredients`
**Model Class**: `App\Models\Ingredient`

**Attributes Used**:
- `name` (string) - Ingredient name for display

**Usage**: Read-only display of ingredient names alongside scaled quantities.

## Client-Side State (Alpine.js)

### Servings Multiplier Data

**Storage**: Browser memory only (Alpine.js reactive state)
**Persistence**: None - resets on page reload
**Location**: `<script>` tag in Blade view or external JavaScript component

**State Schema**:

```javascript
{
  // Core multiplier state
  multiplier: 1.0,                    // Float, range 0.25-10.0 (default: 1x)

  // Original recipe data (hydrated from Livewire)
  originalServings: recipe.servings,  // Integer from database

  // Ingredients data (mapped from recipe.recipeIngredients)
  ingredients: [
    {
      id: recipeIngredient.id,                      // Integer
      originalQuantity: recipeIngredient.quantity,  // Decimal(8,3) or null
      unit: recipeIngredient.unit?.value,           // String or null
      name: recipeIngredient.ingredient.name,       // String
      notes: recipeIngredient.notes                 // String or null
    }
  ]
}
```

### Computed Properties (Alpine.js Getters)

**Not Stored in Database** - Calculated on-demand in the browser:

```javascript
{
  // Adjusted servings count (scaled from original)
  get adjustedServings() {
    return Math.round(this.originalServings * this.multiplier);
  },

  // Check if multiplier has been changed from default
  get isMultiplierChanged() {
    return this.multiplier !== 1.0;
  },

  // Scale a single ingredient quantity
  getScaledQuantity(originalQty) {
    if (originalQty === null) return null;
    const scaled = originalQty * this.multiplier;
    return this.formatQuantity(scaled);
  },

  // Format quantity for display (remove trailing zeros)
  formatQuantity(value) {
    if (value === null) return null;
    return parseFloat(value.toFixed(3)).toString();
  }
}
```

## Data Flow Diagram

```text
┌─────────────────────────────────────────────────────────────────┐
│  Server (Laravel + Livewire)                                    │
│                                                                  │
│  ┌─────────────┐    ┌──────────────────┐   ┌─────────────────┐│
│  │   Recipe    │───>│RecipeIngredient  │──>│   Ingredient    ││
│  │   Model     │    │     Model        │   │     Model       ││
│  └─────────────┘    └──────────────────┘   └─────────────────┘│
│        │                     │                                  │
│        └─────────────────────┘                                  │
│                │                                                 │
│      Livewire Component                                         │
│      (App\Livewire\Recipes\Show)                               │
│                │                                                 │
└────────────────│─────────────────────────────────────────────────┘
                 │ [ONE-TIME PAGE LOAD]
                 ▼
┌─────────────────────────────────────────────────────────────────┐
│  Client (Browser - Alpine.js)                                   │
│                                                                  │
│  ┌─────────────────────────────────────────────┐               │
│  │ Alpine.data('servingsMultiplier') {         │               │
│  │   multiplier: 1.0                           │               │
│  │   originalServings: 4  ◄────────────────────┼── From Blade  │
│  │   ingredients: [...]  ◄─────────────────────┼── From Blade  │
│  │ }                                           │               │
│  └─────────────────────────────────────────────┘               │
│          │                                                      │
│          │ [USER ADJUSTS MULTIPLIER]                           │
│          ▼                                                      │
│  ┌─────────────────────────────────────────────┐               │
│  │ Reactive Calculations (No Server Calls)     │               │
│  │                                             │               │
│  │ • adjustedServings = 4 × 2.0 = 8            │               │
│  │ • flour: 2 cups × 2.0 = 4 cups              │               │
│  │ • sugar: 1.5 cups × 2.0 = 3 cups            │               │
│  └─────────────────────────────────────────────┘               │
│          │                                                      │
│          ▼                                                      │
│  ┌─────────────────────────────────────────────┐               │
│  │ DOM Updates (Alpine.js Reactivity)          │               │
│  │                                             │               │
│  │ • Display "Adjusted to 8 servings"          │               │
│  │ • Update all ingredient quantities          │               │
│  │ • Show visual indicator (badge/highlight)   │               │
│  └─────────────────────────────────────────────┘               │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

## No Database Migrations Required

**Schema Changes**: None

This feature requires **zero database schema changes** because:
1. All existing recipe data is sufficient (no new fields needed)
2. Multiplier state is transient (session-only, no persistence)
3. Calculations are client-side (no server-side stored values)
4. Original recipe data remains unchanged

## Data Validation

### Server-Side (Laravel)

**Not Applicable** - This feature performs no server-side data writes.

**Existing Validation** (from Recipe/RecipeIngredient models):
- Recipe `servings`: integer, >= 1 (existing validation)
- RecipeIngredient `quantity`: decimal(8,3), >= 0 or nullable (existing validation)
- RecipeIngredient `unit`: enum value from `MeasurementUnit::class` (existing validation)

**Used By Feature**: Existing validated data is read and displayed.

### Client-Side (JavaScript/Alpine.js)

**Multiplier Input Validation**:

```javascript
setMultiplier(value) {
  // Type validation
  const numValue = parseFloat(value);
  if (isNaN(numValue)) {
    return; // Reject non-numeric input
  }

  // Range validation
  if (numValue < 0.25) {
    this.multiplier = 0.25; // Clamp to minimum
  } else if (numValue > 10.0) {
    this.multiplier = 10.0; // Clamp to maximum
  } else {
    this.multiplier = numValue;
  }
}
```

**Quantity Calculation Validation**:
- Null quantities remain null (no scaling applied)
- Scaled values clamped to 3 decimal places via `toFixed(3)`
- Very small results (< 0.001) display as "< 0.001" or round to 0

## Edge Cases & Data Handling

| Scenario | Database Value | Multiplier | Client Display |
|----------|----------------|-----------|----------------|
| **Normal ingredient** | `quantity: 2.000` | 2.0 | "4 cups" |
| **Fractional ingredient** | `quantity: 1.500` | 0.5 | "0.75 cups" |
| **No quantity specified** | `quantity: null` | any | "(no quantity)" |
| **Very small result** | `quantity: 0.250` | 0.25 | "0.063 cups" |
| **Repeating decimal** | `quantity: 0.333` | 3.0 | "0.999 cups" (rounded to 3 decimals) |
| **Maximum multiplier** | `quantity: 100.000` | 10.0 | "1000 cups" |
| **Minimum multiplier** | `quantity: 4.000` | 0.25 | "1 cup" |

## Performance Considerations

**Database Queries**:
- **Initial page load**: 1 query for recipe + eager loaded relationships (`recipeIngredients.ingredient`)
- **Multiplier adjustments**: 0 queries (all client-side calculations)

**Memory Footprint**:
- Alpine.js state: ~1-5KB per recipe (depending on ingredient count)
- Typical recipe: 10-50 ingredients = ~2KB state
- No memory leaks (state garbage collected on page navigation)

**Calculation Complexity**:
- O(1) per ingredient quantity calculation
- O(n) total recalculation on multiplier change (where n = ingredient count)
- Typical: 10-50 ingredients = 10-50 multiplications (<1ms on modern hardware)

## Security Considerations

**No Security Risks** - This is a read-only feature:
- No user input persisted to database
- No server-side state changes
- No authorization changes needed (uses existing `RecipesShow` component auth)
- Client-side calculations cannot corrupt server data

**Existing Authorization** (from `RecipesShow` component):
```php
$this->authorize('view', $recipe);
```
- Users can only view recipes they have permission to see
- Multiplier feature respects existing authorization

## Future Enhancements (Out of Scope)

**Not Implemented in This Version** (potential future features):
1. **Persist Multiplier Preference**: Save user's preferred multiplier in browser localStorage or database
2. **Multiplier History**: Track recently used multipliers for quick access
3. **Recipe Variants**: Save scaled recipes as new recipe variations
4. **Shopping List Integration**: Generate shopping lists with scaled quantities
5. **Unit Conversion**: Auto-convert between units when scaling (e.g., 16 tbsp → 1 cup)

## Summary Table

| Aspect | Implementation | Location |
|--------|---------------|----------|
| **Data Source** | Existing Recipe + RecipeIngredient models | Database (read-only) |
| **State Management** | Alpine.js reactive state | Browser memory |
| **Calculations** | JavaScript arithmetic with rounding | Client-side |
| **Persistence** | None (resets on page reload) | N/A |
| **Database Writes** | None | N/A |
| **Migrations** | None required | N/A |
| **Authorization** | Existing recipe view policy | `RecipePolicy@view` |
| **Performance** | O(n) calculations where n = ingredient count | <1ms typical |
