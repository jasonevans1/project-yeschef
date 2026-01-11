# Research: Meal Plan Notes

**Feature Branch**: `010-meal-plan-notes`
**Date**: 2026-01-11

## Summary

This document consolidates research findings for the Meal Plan Notes feature. All technical decisions are based on existing codebase patterns and established Laravel/Livewire best practices.

---

## Decision 1: Data Model Architecture

**Decision**: Create a new `MealPlanNote` model as a sibling to `MealAssignment`, not a polymorphic relationship.

**Rationale**:
- `MealAssignment` has a required `recipe_id` foreign key that cannot be nullable (constraint prevents deletion of recipes with assignments)
- Notes have fundamentally different fields (title, details) vs assignments (recipe_id, serving_multiplier)
- A separate model provides clear separation of concerns and simpler queries
- Follows Laravel convention of one model per database table
- Easier to maintain and test independently

**Alternatives Considered**:
1. **Polymorphic `meal_plan_items` table**: Would require nullable foreign keys, complex polymorphic queries, and complicate the existing grocery list generation logic that specifically queries `mealAssignments`. Rejected because it adds complexity without clear benefit.
2. **Add `type` column to `meal_assignments`**: Would require nullable `recipe_id`, break existing constraints, and mix unrelated data in one table. Rejected because it violates single responsibility principle.

---

## Decision 2: Visual Distinction for Notes

**Decision**: Use a distinct visual style for notes in the meal plan calendar - different background color and icon indicator.

**Rationale**:
- Notes need to be instantly recognizable as "not a recipe" in the calendar view
- Users should not accidentally click a note expecting recipe details
- Consistent with UI/UX best practice of visual affordance for different item types
- Flux UI supports badge and icon components for clear differentiation

**Implementation Approach**:
- Notes will use a slightly different background color (e.g., amber/yellow tint vs white)
- Notes will display a document/note icon instead of recipe servings
- Title displayed prominently; details shown on click in drawer

---

## Decision 3: Grocery List Exclusion

**Decision**: Notes are automatically excluded from grocery list generation - no code changes needed to `GroceryListGenerator`.

**Rationale**:
- `GroceryListGenerator::collectIngredientsFromMealPlan()` only queries `mealAssignments` relationship
- Notes stored in separate `meal_plan_notes` table are not queried
- Natural exclusion through data model separation
- No risk of accidental inclusion in future changes

**Verification**:
```php
// GroceryListGenerator.php line 158
$mealAssignments = $mealPlan->mealAssignments()
    ->with('recipe.recipeIngredients.ingredient')
    ->get();
```
This query only fetches `MealAssignment` records, not notes.

---

## Decision 4: Authorization Pattern

**Decision**: Create `MealPlanNotePolicy` that delegates authorization to `MealPlanPolicy`.

**Rationale**:
- Notes inherit access from their parent meal plan (if you can edit the meal plan, you can edit its notes)
- Consistent with Laravel policy patterns
- Simple and predictable authorization logic
- Matches existing `MealAssignment` authorization (done via `$this->authorize('update', $this->mealPlan)`)

**Implementation**:
```php
class MealPlanNotePolicy
{
    public function update(User $user, MealPlanNote $note): bool
    {
        return $user->can('update', $note->mealPlan);
    }
}
```

---

## Decision 5: UI Integration Pattern

**Decision**: Extend the existing `MealPlans\Show` Livewire component rather than creating a separate component.

**Rationale**:
- Notes are integral to the meal plan view experience
- Users should add/view/edit notes without page navigation
- Follows existing pattern for recipe assignment (modal-based)
- Keeps all meal plan slot interactions in one component for consistency
- Reduces component proliferation

**Implementation**:
- Add note-specific modal for add/edit
- Add note drawer for viewing details (similar to recipe drawer)
- Reuse existing modal/drawer patterns from recipe selector

---

## Decision 6: Form Fields and Validation

**Decision**: Title (required, max 255 chars), Details (optional, max 2000 chars).

**Rationale**:
- Title is essential for calendar display and identification
- Details are optional for quick notes ("Eating out")
- 255 chars for title matches standard database varchar
- 2000 chars for details allows substantial notes without encouraging essays
- Matches typical text/textarea field patterns in the application

---

## Best Practices Applied

### Livewire 3 Patterns
- Use `wire:model.live` for real-time form validation feedback
- Use `wire:loading` states for async operations
- Use Alpine.js for drawer animations (already in recipe drawer)

### Flux UI Components
- `flux:modal` for add/edit note form
- `flux:input` for title field
- `flux:textarea` for details field
- `flux:button` for actions
- `flux:badge` for note indicator in calendar

### Testing Approach
- Pest feature tests for CRUD operations
- Authorization tests (can edit own notes, cannot edit others')
- Validation tests (empty title rejected)
- Playwright E2E for full user flows

---

## No Outstanding Clarifications

All technical decisions have been resolved based on:
1. Existing codebase patterns
2. Laravel/Livewire best practices
3. Flux UI component availability
4. Clear separation of concerns
