# Implementation Plan: Multi-Recipe Meal Slots with Recipe Drawer

**Branch**: `001-meal-plan-drawer` | **Date**: 2025-12-14 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/Users/jasonevans/projects/project-tabletop/specs/001-meal-plan-drawer/spec.md`

## Summary

Enable meal plan slots to support multiple recipes by removing the unique database constraint, refactoring the Livewire component to handle collections, and implementing a custom Alpine.js slide-out drawer for viewing recipe details with scaled ingredients. This enhancement removes the one-recipe-per-slot limitation while adding quick access to recipe details without navigation.

**Technical Approach**: Database migration to drop unique constraint → Livewire component refactoring to handle recipe collections → Custom Alpine.js drawer component with ingredient scaling logic → Comprehensive test updates.

## Technical Context

**Language/Version**: PHP 8.3 (Laravel 12)
**Primary Dependencies**: Laravel Framework 12, Livewire 3, Livewire Flux (UI components)
**Storage**: MariaDB (production via DDEV), SQLite (development/testing)
**Testing**: Pest (PHP feature/unit tests), Playwright (E2E tests)
**Target Platform**: Web application (desktop + mobile responsive)
**Project Type**: Web (Laravel monolith with Livewire frontend)
**Performance Goals**: Drawer open/close within 300ms, ingredient scaling calculations < 100ms
**Constraints**: Must support dark mode, keyboard navigation, mobile responsiveness (< 640px width)
**Scale/Scope**: Existing meal plan system with ~4 models (MealPlan, MealAssignment, Recipe, RecipeIngredient), adding drawer UI component

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

### ✅ I. Livewire-First Architecture
- **Status**: COMPLIANT
- **Evidence**: Feature uses existing Livewire component (`app/Livewire/MealPlans/Show.php`) as primary architecture
- **Implementation**: Adding methods to existing Livewire component, not creating controllers

### ✅ II. Component-Driven Development
- **Status**: COMPLIANT
- **Evidence**: Using Flux components for buttons, icons, badges; drawer uses Alpine.js integration (Livewire-compatible)
- **Implementation**: Recipe cards will use Flux components, drawer follows Livewire/Alpine patterns
- **Note**: Custom drawer (not Flux modal) is specified in requirements, but uses Alpine.js which ships with Livewire 3

### ✅ III. Test-First Development
- **Status**: COMPLIANT
- **Evidence**: Plan includes updating existing tests in `tests/Feature/MealPlans/` before implementation
- **Implementation**: Will modify `AssignRecipesTest.php` and `ViewMealPlanTest.php` with new test cases before coding

### ✅ IV. Full-Stack Integration Testing
- **Status**: COMPLIANT
- **Evidence**: Tests cover database constraint changes, Livewire component state, ingredient scaling logic
- **Implementation**: Feature/integration tests will validate full request-response cycle including authorization

### ✅ V. Developer Experience & Observability
- **Status**: COMPLIANT
- **Evidence**: Following standard Laravel/Livewire development workflow with Pint formatting
- **Implementation**: Will run `vendor/bin/pint` and `php artisan test` before completion

**Constitution Compliance**: ✅ ALL GATES PASSED - No violations, proceed with Phase 0

## Project Structure

### Documentation (this feature)

```text
specs/001-meal-plan-drawer/
├── plan.md              # This file (/speckit.plan command output)
├── spec.md              # Feature specification (created by /speckit.specify)
├── checklists/
│   └── requirements.md  # Spec quality checklist (created by /speckit.specify)
├── research.md          # Phase 0 output (created below)
├── data-model.md        # Phase 1 output (created below)
├── quickstart.md        # Phase 1 output (created below)
└── contracts/           # Phase 1 output (API contracts if needed)
```

### Source Code (repository root)

```text
Laravel 12 Application Structure:

app/
├── Livewire/
│   └── MealPlans/
│       └── Show.php                    # [MODIFY] Add drawer methods, scaling logic
├── Models/
│   ├── MealAssignment.php              # [EXISTS] No changes needed
│   ├── MealPlan.php                    # [EXISTS] No changes needed
│   ├── Recipe.php                      # [EXISTS] No changes needed
│   └── RecipeIngredient.php            # [EXISTS] Has display_quantity accessor
├── Services/
│   └── ServingSizeScaler.php           # [EXISTS] Scaling logic already implemented
└── Enums/
    ├── MealType.php                    # [EXISTS] No changes needed
    └── MeasurementUnit.php             # [EXISTS] No changes needed

resources/views/livewire/meal-plans/
└── show.blade.php                      # [MODIFY] Add drawer component, update slot rendering

database/migrations/
└── YYYY_MM_DD_HHMMSS_remove_unique_constraint_from_meal_assignments.php  # [CREATE]

tests/Feature/MealPlans/
├── AssignRecipesTest.php               # [MODIFY] Update duplicate prevention test
└── ViewMealPlanTest.php                # [MODIFY] Add drawer and multi-recipe tests
```

**Structure Decision**: This is a Laravel 12 monolith application using Livewire 3 for frontend interactivity. The feature modifies existing components rather than creating new architectural layers. All changes are within established patterns: database migration, Livewire component enhancement, Blade view updates, and Pest test modifications.

## Complexity Tracking

**No constitutional violations** - This table is empty as all constitution gates passed.

---

## Phase 0: Research & Technical Decisions

### Research Tasks

Based on Technical Context unknowns and feature requirements, the following areas require research:

1. **Alpine.js Drawer Pattern with Livewire**
   - Research: Best practices for custom Alpine.js slide-out drawers integrated with Livewire state
   - Question: How to use `@entangle` for reactive drawer state management
   - Question: Transition patterns for smooth slide-in/slide-out animations

2. **Database Constraint Removal Impact**
   - Research: Implications of removing unique constraint on `meal_assignments`
   - Question: Does removing constraint require data migration or cleanup?
   - Question: Impact on existing queries and indexes

3. **Ingredient Scaling Display**
   - Research: Existing `RecipeIngredient::getDisplayQuantityAttribute()` pattern
   - Question: Should scaling logic be server-side (Livewire computed property) or client-side?
   - Decision needed: Computed property vs. method call pattern

4. **Collection Grouping with Multiple Items**
   - Research: Laravel collection `groupBy()` behavior when returning multiple items per group
   - Question: Best pattern for grouping and sorting: `groupBy()->map()` vs. nested loops

5. **Keyboard Accessibility for Drawer**
   - Research: ARIA attributes and keyboard navigation patterns for slide-out panels
   - Question: Focus management when drawer opens/closes
   - Question: Escape key handling with Alpine.js

**Next Step**: Create `research.md` document with findings and decisions for each area above.

---

## Phase 1: Design & Contracts

**Prerequisites**: `research.md` complete with all decisions documented

### Data Model Changes

**Migration**: Remove unique constraint from `meal_assignments` table
- Table: `meal_assignments`
- Change: Drop unique index on `['meal_plan_id', 'date', 'meal_type']`
- Reason: Allow multiple recipe assignments to same meal slot
- Impact: No data migration needed (existing data remains valid)
- Rollback: `down()` method adds constraint back

**No Model Changes**: Existing models (`MealAssignment`, `MealPlan`, `Recipe`, `RecipeIngredient`) require no modifications.

### Component State Design

**New Livewire Properties** (Add to `Show.php`):
```php
public ?int $selectedAssignmentId = null;
public bool $showRecipeDrawer = false;
```

**New Livewire Methods** (Add to `Show.php`):
- `openRecipeDrawer(MealAssignment $assignment)` - Authorization, eager loading, state management
- `closeRecipeDrawer()` - Reset drawer state
- `getSelectedAssignmentProperty()` - Computed property for current assignment with relationships
- `getScaledIngredientsProperty()` - Computed property for ingredient calculations

**Modified Methods** (Update in `Show.php`):
- `assignRecipe(Recipe $recipe)` - Remove "update existing" logic, always create new assignment
- `render()` - Update grouping to handle collections, add sorting by `created_at`

### View Structure Design

**Meal Slot Cell Pattern** (Update in `show.blade.php`):
```blade
@php
    $key = $date->format('Y-m-d') . '_' . $mealType->value;
    $assignmentCollection = $assignments->get($key) ?? collect();
@endphp

@forelse($assignmentCollection as $assignment)
    {{-- Recipe card with remove button --}}
@empty
    {{-- Empty slot with "Add Recipe" button --}}
@endforelse
```

**Drawer Component Structure** (Add to `show.blade.php`):
- Alpine.js `x-data` with `@entangle('showRecipeDrawer')`
- Backdrop overlay (click to close, transitions)
- Drawer panel (slide from right, responsive sizing)
- Sticky header (recipe info, close button)
- Scrollable content (servings, times, ingredients, instructions)
- Sticky footer ("View Full Recipe" link, close button)

### API/Contracts

**No external APIs** - This feature is entirely internal to the Livewire component.

**Livewire Wire Protocol**:
- `wire:click="openRecipeDrawer({{ $assignment->id }})"` - Open drawer
- `wire:click="closeRecipeDrawer"` - Close drawer
- `wire:click="removeAssignment({{ $assignment->id }})"` - Delete assignment
- `@entangle('showRecipeDrawer')` - Reactive drawer visibility

**Component Computed Properties** (Laravel accessor pattern):
- `$this->selectedAssignment` - Returns `MealAssignment` with eager loaded `recipe.recipeIngredients.ingredient`
- `$this->scaledIngredients` - Returns array: `[['name' => ..., 'quantity' => ..., 'unit' => ..., 'notes' => ...], ...]`

### Quickstart Implementation Guide

**Step 1: Database Migration**
```bash
php artisan make:migration remove_unique_constraint_from_meal_assignments
# Edit migration file, add constraint drop in up(), add back in down()
php artisan migrate
```

**Step 2: Update Tests (Test-First)**
```bash
# Modify tests/Feature/MealPlans/AssignRecipesTest.php
# - Replace "prevents duplicate assignments" with "allows multiple recipes"
# - Remove "reassigns recipe" test

# Modify tests/Feature/MealPlans/ViewMealPlanTest.php
# - Add drawer state tests
# - Add scaling calculation tests
# - Add multi-recipe display tests

php artisan test --filter=MealPlans  # Should FAIL before implementation
```

**Step 3: Livewire Component Updates**
```bash
# Edit app/Livewire/MealPlans/Show.php
# - Add new properties: selectedAssignmentId, showRecipeDrawer
# - Add new methods: openRecipeDrawer, closeRecipeDrawer
# - Add computed properties: getSelectedAssignmentProperty, getScaledIngredientsProperty
# - Modify assignRecipe() - remove update logic
# - Modify render() - update grouping and sorting
```

**Step 4: Blade View Updates**
```bash
# Edit resources/views/livewire/meal-plans/show.blade.php
# - Update meal slot cells to loop over collections
# - Add drawer component with Alpine.js transitions
# - Add recipe cards with remove buttons
# - Add scaled ingredients display
```

**Step 5: Code Quality & Testing**
```bash
vendor/bin/pint                      # Format code
php artisan test --filter=MealPlans  # All tests should PASS
```

**Step 6: Manual QA**
- Test dark mode styling
- Test mobile responsiveness (< 640px)
- Test keyboard navigation (Tab, Enter, Escape)
- Test drawer animations
- Test ingredient scaling accuracy

---

## Next Steps

After `/speckit.plan` command completes:

1. **Review this plan** - Verify technical approach aligns with requirements
2. **Run `/speckit.tasks`** - Generate actionable task breakdown from this plan
3. **Run `/speckit.implement`** - Execute implementation following test-first workflow

**Note**: This plan ends after Phase 1 design. The `/speckit.tasks` command will create the detailed task list (`tasks.md`) for execution.
