# Quickstart: Meal Plan Notes

**Feature Branch**: `010-meal-plan-notes`
**Date**: 2026-01-11

## Overview

This guide provides quick implementation steps for the Meal Plan Notes feature. Follow these steps in order for a smooth implementation.

---

## Step 1: Database Migration

Create the migration for the `meal_plan_notes` table:

```bash
php artisan make:migration create_meal_plan_notes_table
```

Migration contents:
```php
Schema::create('meal_plan_notes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('meal_plan_id')->constrained()->cascadeOnDelete();
    $table->date('date');
    $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack']);
    $table->string('title', 255);
    $table->text('details')->nullable();
    $table->timestamps();

    $table->index(['meal_plan_id', 'date']);
});
```

Run the migration:
```bash
php artisan migrate
```

---

## Step 2: Create Model

```bash
php artisan make:model MealPlanNote --factory
```

Key model setup:
- Add `$fillable` array with: meal_plan_id, date, meal_type, title, details
- Add `casts()` method for date and meal_type (MealType enum)
- Add `mealPlan()` belongsTo relationship

---

## Step 3: Create Policy

```bash
php artisan make:policy MealPlanNotePolicy --model=MealPlanNote
```

Authorization should delegate to MealPlan:
```php
public function update(User $user, MealPlanNote $note): bool
{
    return $user->can('update', $note->mealPlan);
}
```

Register in `AuthServiceProvider` or via auto-discovery.

---

## Step 4: Update MealPlan Model

Add the `mealPlanNotes()` relationship:
```php
public function mealPlanNotes(): HasMany
{
    return $this->hasMany(MealPlanNote::class);
}
```

---

## Step 5: Update MealPlans\Show Component

Add new properties:
- `showNoteForm`, `noteTitle`, `noteDetails`, `editingNoteId`
- `showNoteDrawer`, `selectedNoteId`

Add new methods:
- `openNoteForm($date, $mealType)`
- `closeNoteForm()`
- `saveNote()`
- `editNote(MealPlanNote $note)`
- `deleteNote(MealPlanNote $note)`
- `openNoteDrawer(MealPlanNote $note)`
- `closeNoteDrawer()`

Add computed property:
- `getSelectedNoteProperty()`

Modify `render()` to load and group notes alongside assignments.

---

## Step 6: Update Blade View

### Calendar Cell Updates
- Show both recipes and notes in each date/mealType slot
- Notes have distinct styling (amber background, document icon)
- Add "Add Note" option to the add button/dropdown

### New Note Form Modal
- Title input (required)
- Details textarea (optional)
- Save and Cancel buttons

### New Note Drawer
- Similar to recipe drawer but simpler
- Shows title, date/meal type, and details
- Edit and Delete buttons in footer

---

## Step 7: Write Tests

### Feature Tests (Pest)
```bash
php artisan make:test MealPlans/MealPlanNotesTest --pest
```

Test cases:
- User can add note to meal plan
- User can edit existing note
- User can delete note
- User cannot add note to another user's meal plan
- Title validation (required, max length)
- Notes excluded from grocery list generation

### E2E Tests (Playwright)
```bash
# Create e2e/meal-plans-notes.spec.ts
```

Test flows:
- Add note flow (empty slot)
- Add note flow (slot with existing recipe)
- Edit note flow
- Delete note flow
- View note drawer

---

## Step 8: Verify Grocery List Exclusion

Run existing grocery list tests to confirm notes don't interfere:
```bash
php artisan test --filter=GroceryList
```

Create a specific test that:
1. Creates a meal plan with both recipes and notes
2. Generates a grocery list
3. Asserts only recipe ingredients appear

---

## Quick Commands

```bash
# Run all tests
composer test

# Run only meal plan tests
php artisan test tests/Feature/MealPlans/

# Format code
vendor/bin/pint

# Run E2E tests
npx playwright test e2e/meal-plans-notes.spec.ts

# Fresh database with seed
php artisan migrate:fresh --seed
```

---

## Verification Checklist

- [ ] Migration runs successfully in both SQLite and MariaDB
- [ ] Model relationships work correctly
- [ ] Policy authorization is enforced
- [ ] Add note from empty slot works
- [ ] Add note from slot with recipe works
- [ ] Edit note works
- [ ] Delete note works
- [ ] Notes display distinctly from recipes
- [ ] Note drawer shows full details
- [ ] Grocery list excludes notes
- [ ] All tests pass
- [ ] Pint formatting passes
