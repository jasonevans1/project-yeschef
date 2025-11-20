# T144: N+1 Query Prevention Analysis

## Summary

Completed comprehensive review of all Livewire components to verify proper eager loading and prevent N+1 query issues. Found and fixed N+1 queries in Dashboard and GroceryLists/Index components.

## Components Reviewed

### ✅ Recipe Components

**Recipes/Show.php**
- Status: ✅ Proper eager loading
- Uses `$this->recipe->load(['recipeIngredients.ingredient', 'user'])` in mount()
- Prevents N+1 when displaying recipe details

**Recipes/Index.php**
- Status: ✅ Proper eager loading
- Uses `->with(['recipeIngredients.ingredient'])` on query
- Efficiently loads ingredients for recipe cards

### ✅ MealPlan Components

**MealPlans/Show.php**
- Status: ✅ Proper eager loading
- Uses `->load(['mealAssignments.recipe'])` in render()
- Prevents N+1 when displaying calendar

**MealPlans/Index.php**
- Status: ✅ Proper eager loading
- Uses `->withCount('mealAssignments')` on query
- Efficiently counts assignments without N+1

### ✅ GroceryList Components

**GroceryLists/Show.php**
- Status: ✅ Proper eager loading
- Uses `->with('recipe.recipeIngredients.ingredient')` for regeneration
- Single model load uses model accessors with fallback

**GroceryLists/Index.php**
- Status: ⚠️ **Fixed** - Added eager loading
- **Issue Found**: Was not using withCount for total_items and completed_items
- **Fix Applied**: Added `->withCount(['groceryItems as total_items', 'groceryItems as completed_items' => ...])` at line 17-22
- Now prevents N+1 when listing grocery lists

### ✅ Dashboard Component

**Dashboard.php**
- Status: ⚠️ **Fixed** - Added eager loading
- **Issues Found**:
  - `upcomingMealPlans()` was not using withCount for assignment_count
  - `recentGroceryLists()` was not using withCount for total_items and completed_items
- **Fixes Applied**:
  - Added `->withCount('mealAssignments')` at line 36 for meal plans
  - Added `->withCount(['groceryItems as total_items', 'groceryItems as completed_items' => ...])` at lines 50-54 for grocery lists
- Now prevents N+1 queries on dashboard

## Model Accessor Optimizations

### MealPlan Model (`app/Models/MealPlan.php`)

Updated `getAssignmentCountAttribute()` to use eager loaded count when available:

```php
public function getAssignmentCountAttribute(): int
{
    // Use eager loaded count if available to prevent N+1 queries
    return $this->meal_assignments_count ?? $this->mealAssignments()->count();
}
```

This allows the accessor to:
1. Use `meal_assignments_count` from `withCount()` when available (no query)
2. Fall back to relationship query when accessing single model (acceptable)

### GroceryList Model (`app/Models/GroceryList.php`)

Updated both count accessors to use eager loaded counts when available:

**getTotalItemsAttribute():**
```php
public function getTotalItemsAttribute(): int
{
    // Use eager loaded count if available to prevent N+1 queries
    if (isset($this->attributes['total_items'])) {
        return $this->attributes['total_items'];
    }

    return $this->groceryItems()->count();
}
```

**getCompletedItemsAttribute():**
```php
public function getCompletedItemsAttribute(): int
{
    // Use eager loaded count if available to prevent N+1 queries
    if (isset($this->attributes['completed_items'])) {
        return $this->attributes['completed_items'];
    }

    return $this->groceryItems()->where('purchased', true)->count();
}
```

These optimizations allow the accessors to:
1. Use eager loaded counts from `withCount()` when available (no query)
2. Fall back to relationship queries when accessing single model (acceptable)
3. Maintain backward compatibility with existing code

## Files Modified

1. `app/Livewire/Dashboard.php` - Added withCount for meal plans and grocery lists
2. `app/Livewire/GroceryLists/Index.php` - Added withCount for grocery items
3. `app/Models/MealPlan.php` - Optimized assignment_count accessor
4. `app/Models/GroceryList.php` - Optimized total_items and completed_items accessors

## Testing Results

✅ All tests passed (212 passed, 8 skipped)
- Feature tests for Dashboard, GroceryLists, and MealPlans all passing
- No performance regressions detected
- Eager loading working correctly

## Best Practices Applied

1. **Use `withCount()` for relationship counts** instead of accessing count in views
2. **Use `->with()` for loading relationships** to prevent N+1 on related data
3. **Check for eager loaded data in accessors** before falling back to queries
4. **Eager load nested relationships** using dot notation (e.g., 'recipe.recipeIngredients.ingredient')
5. **Use conditional counts in withCount** for filtered counts (e.g., purchased items)

## Conclusion

All Livewire components now properly implement eager loading to prevent N+1 queries. Model accessors have been optimized to use eager loaded counts when available, providing both performance and backward compatibility.

**Task Status**: ✅ Complete
