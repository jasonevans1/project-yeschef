# Task 004: Update Show Component and grocery-category View

**Status**: pending
**Depends on**: 001, 003
**Retry count**: 0

## Description
Efficiently load source recipe names in the `Show` Livewire component and display them as clickable links in `grocery-category.blade.php` for generated items on meal-plan-linked grocery lists.

## Context
- Livewire component: `app/Livewire/GroceryLists/Show.php`
- Blade component: `resources/views/components/grocery-category.blade.php` (only caller is `resources/views/livewire/grocery-lists/show.blade.php` — the public/shared view at `shared.blade.php` does NOT use this component and is out of scope)
- Route helper for recipe links: `route('recipes.show', $recipe)` — route exists at `routes/web.php:48`
- Item display section is at lines 103–157 in `grocery-category.blade.php`
- Current sub-line display: `display_quantity`, `notes`, and badges (Edited / Manual) — recipe links go in this area
- Recipe model: `app/Models/Recipe.php` — has `id`, `name` fields

### Loading Strategy (No N+1)
`itemsByCategory` is a **computed property** (`getItemsByCategoryProperty()`) accessed from `render()` as `$this->itemsByCategory`. Do NOT try to return multiple values from the computed property. Instead, build the recipe map in `render()`:

```php
public function render(): mixed
{
    $itemsByCategory = $this->itemsByCategory;

    // Collect unique recipe IDs across all items
    $recipeIds = $itemsByCategory
        ->flatten(1)
        ->pluck('recipe_ids')
        ->filter() // drop null
        ->flatten()
        ->unique()
        ->values();

    // Batch-load (avoid N+1), filter to recipes the current user can view
    $recipesById = Recipe::whereIn('id', $recipeIds)
        ->get()
        ->filter(fn (Recipe $r) => auth()->user()->can('view', $r))
        ->keyBy('id');

    return view('livewire.grocery-lists.show', [
        'itemsByCategory' => $itemsByCategory,
        'recipesById' => $recipesById,
        'categories' => IngredientCategory::cases(),
        'units' => MeasurementUnit::cases(),
    ]);
}
```

Import `App\Models\Recipe` at the top of `Show.php`.

### Passing to grocery-category
- Add `$recipesById` as a new prop on `grocery-category.blade.php` with a sensible default: `'recipesById' => collect()`
- In `show.blade.php`, pass it to the component: `:recipesById="$recipesById"`

### Display Logic
Only show recipe links when ALL of the following are true:
- `$groceryList->is_meal_plan_linked` is `true`
- `$item->is_generated` is `true`
- `$item->recipe_ids` is a non-empty array

For each recipe ID in `$item->recipe_ids`:
- Look up `$recipesById[$id]` — **skip silently if not found**. This covers two cases:
  1. The recipe was hard-deleted
  2. The current user (e.g., a shared-list recipient) does not have permission to view that recipe
- Render a small link with `href="{{ route('recipes.show', $recipe) }}"`, styled consistent with the existing meal plan link in `show.blade.php` (blue with hover underline, dark-mode aware). If rendering multiple links, comma-separate them.
- Wrap the link row in an `@if(count($visibleRecipes) > 0)` guard so we don't render an empty container when all IDs were filtered out.

## Requirements (Test Descriptions)
- [ ] `it displays recipe name as a link on a generated item from a meal plan grocery list`
- [ ] `it displays multiple recipe links when an item was aggregated from multiple recipes`
- [ ] `it does not display recipe links on manual items`
- [ ] `it does not display recipe links on a standalone grocery list`
- [ ] `it does not display a recipe link when the recipe has been hard-deleted`
- [ ] `it does not display a recipe link when the current user lacks permission to view that recipe (shared grocery list recipient)`
- [ ] `it renders the recipe link with the correct route('recipes.show') href`
- [ ] `it batch-loads recipes in a single query regardless of item count (no N+1)`
- [ ] `it does not render an empty recipe links container when every referenced recipe is filtered out`

## Acceptance Criteria
- All requirements have passing tests
- Recipe links render with correct `href` (route to recipe show page)
- No N+1 query regression — verified with `DB::listen` or an assertion on query count
- Dark mode styles applied (consistent with existing link styling)
- Works correctly when viewed by a shared-list recipient: recipe links only render for recipes the recipient can view

## Implementation Notes
(Left blank — filled in by programmer during implementation)
