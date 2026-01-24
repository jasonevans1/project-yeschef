# Research: Delete Grocery List

**Feature**: Delete Grocery List with Confirmation
**Date**: 2025-11-30
**Status**: Complete

## Overview

This research document consolidates technical decisions for implementing the delete grocery list feature. All decisions align with the Project Tabletop constitution and leverage existing Laravel/Livewire patterns in the codebase.

## Research Areas

### 1. Deletion Strategy: Hard Delete vs Soft Delete

**Decision**: Use **Soft Deletes** (Laravel's `SoftDeletes` trait)

**Rationale**:
- Provides audit trail and data recovery option (even though spec says "permanent")
- Follows Laravel best practices for user-generated content
- Prevents accidental permanent data loss
- Allows for future "undo delete" feature without architectural changes
- Existing GroceryList model can easily adopt `SoftDeletes` trait
- Cascade behavior can be configured for related GroceryItems

**Alternatives Considered**:
- **Hard Delete**: Permanently removes records from database
  - Rejected because: No recovery option, no audit trail, risky for user data
- **Archive Pattern**: Move to separate archived_grocery_lists table
  - Rejected because: Over-engineering for simple requirement, soft deletes achieve same goal with less complexity

**Implementation**:
```php
// In GroceryList model
use Illuminate\Database\Eloquent\SoftDeletes;

class GroceryList extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'deleted_at' => 'datetime', // Added to existing casts
    ];
}
```

**Migration**:
```php
Schema::table('grocery_lists', function (Blueprint $table) {
    $table->softDeletes(); // Adds deleted_at column
});
```

---

### 2. Authorization Pattern

**Decision**: Use **Laravel Policy** (`GroceryListPolicy::delete()` method)

**Rationale**:
- Policy file already exists at `app/Policies/GroceryListPolicy.php`
- Consistent with existing authorization patterns in the application
- Integrates with Livewire's `AuthorizesRequests` trait (already used in Show.php)
- Provides centralized authorization logic
- Easy to test in isolation

**Alternatives Considered**:
- **Inline Authorization**: Check `$groceryList->user_id === auth()->id()` in component
  - Rejected because: Scatters authorization logic, harder to test, violates SRP
- **Gates**: Define a custom gate for deletion
  - Rejected because: Policies are preferred for model-specific authorization

**Implementation**:
```php
// In app/Policies/GroceryListPolicy.php
public function delete(User $user, GroceryList $groceryList): bool
{
    return $user->id === $groceryList->user_id;
}
```

**Usage in Livewire Component**:
```php
// In app/Livewire/GroceryLists/Show.php
public function delete()
{
    $this->authorize('delete', $this->groceryList);

    $this->groceryList->delete(); // Soft delete

    session()->flash('success', 'Grocery list deleted successfully.');

    return redirect()->route('grocery-lists.index');
}
```

---

### 3. Confirmation Dialog Pattern

**Decision**: Use **Flux Modal Component** with Livewire property to control visibility

**Rationale**:
- Flux UI library already included in project (see constitution)
- Existing patterns in Show.php use boolean properties for modal visibility
  - `$showRegenerateConfirm` (line 38)
  - `$showShareDialog` (line 43)
- Consistent user experience across all confirmation dialogs
- No custom JavaScript needed - pure Livewire reactivity

**Alternatives Considered**:
- **Browser Confirm**: `onclick="return confirm('Are you sure?')"`
  - Rejected because: Ugly browser-native dialog, not customizable, poor UX
- **Alpine.js Modal**: Build custom modal with Alpine
  - Rejected because: Reinventing wheel, Flux provides this functionality
- **SweetAlert2**: Third-party JavaScript library
  - Rejected because: Adds unnecessary dependency, violates Livewire-first principle

**Implementation**:
```php
// Component property
public bool $showDeleteConfirm = false;

// Show modal
public function confirmDelete()
{
    $this->showDeleteConfirm = true;
}

// Cancel deletion
public function cancelDelete()
{
    $this->showDeleteConfirm = false;
}

// Perform deletion
public function delete()
{
    $this->authorize('delete', $this->groceryList);
    $this->groceryList->delete();

    session()->flash('success', 'Grocery list deleted successfully.');
    return redirect()->route('grocery-lists.index');
}
```

**Blade Template**:
```blade
<flux:button wire:click="confirmDelete" variant="danger">
    Delete List
</flux:button>

<flux:modal wire:model="showDeleteConfirm" variant="flyout">
    <flux:heading>Delete Grocery List?</flux:heading>
    <flux:text>
        This will permanently delete "{{ $groceryList->name }}" and all its items.
        This action cannot be undone.
    </flux:text>

    <flux:button wire:click="delete" variant="danger">
        Delete
    </flux:button>
    <flux:button wire:click="cancelDelete" variant="ghost">
        Cancel
    </flux:button>
</flux:modal>
```

---

### 4. Cascade Deletion for Related Items

**Decision**: **Database-Level Cascade** via foreign key constraint

**Rationale**:
- GroceryList has `groceryItems()` relationship (hasMany)
- When list is soft deleted, items should also be soft deleted
- Database constraint ensures referential integrity
- Performance: Single query vs N+1 queries for item deletion

**Alternatives Considered**:
- **Model Events**: Use `deleting` event to soft delete items in PHP
  - Rejected because: Less performant, database constraints are more reliable
- **Manual Deletion**: Loop through items and delete each
  - Rejected because: Unnecessary complexity, N+1 queries

**Implementation**:

Check if GroceryItem already has soft deletes:
```php
// In GroceryItem model (if not already present)
use Illuminate\Database\Eloquent\SoftDeletes;

class GroceryItem extends Model
{
    use HasFactory, SoftDeletes;
}
```

Update foreign key constraint:
```php
// In grocery_items migration (modify existing or create new)
$table->foreignId('grocery_list_id')
    ->constrained('grocery_lists')
    ->onDelete('cascade'); // Cascade soft deletes
```

**Note**: Laravel soft deletes work with cascade constraints. When grocery_list is soft deleted, the foreign key constraint triggers cascade behavior for grocery_items.

---

### 5. Redirect After Deletion

**Decision**: Redirect to **Grocery Lists Index** (`route('grocery-lists.index')`)

**Rationale**:
- User is on show page, which no longer exists after deletion
- Index page shows all user's grocery lists (logical destination)
- Matches user expectation (where else would they go?)
- Flash message confirms deletion success

**Alternatives Considered**:
- **Dashboard**: Redirect to main dashboard
  - Rejected because: Less contextual, user was managing grocery lists
- **Meal Plans**: Redirect to meal plans (if list was linked)
  - Rejected because: Not all lists are meal plan linked, adds complexity

**Implementation**:
```php
public function delete()
{
    $this->authorize('delete', $this->groceryList);
    $this->groceryList->delete();

    session()->flash('success', 'Grocery list deleted successfully.');

    return redirect()->route('grocery-lists.index');
}
```

---

### 6. Error Handling: Already Deleted Lists

**Decision**: Use **Laravel Model Binding with Scopes**

**Rationale**:
- Route model binding should exclude soft deleted models by default
- If user accesses deleted list URL, Laravel returns 404 automatically
- Proper HTTP status code (404 Not Found)
- No custom error handling needed in component

**Alternatives Considered**:
- **Custom Exception**: Check if deleted and throw custom exception
  - Rejected because: Laravel handles this automatically
- **Redirect**: Check and redirect to index with error message
  - Rejected because: 404 is semantically correct for non-existent resource

**Implementation**:
```php
// Route definition (in routes/web.php)
Route::get('/grocery-lists/{groceryList}', Show::class)
    ->name('grocery-lists.show');

// Laravel automatically scopes out soft deleted models
// If user tries to access deleted list, they get 404
```

---

### 7. Testing Strategy

**Decision**: **Test-First Development** with Pest feature tests and Playwright E2E tests

**Rationale**:
- Constitution mandates test-first development (Principle III)
- Feature tests validate authorization, deletion logic, redirects
- E2E tests validate complete user journey and UI interactions
- Both test types provide comprehensive coverage

**Test Files**:

1. **Feature Test**: `tests/Feature/GroceryList/DeleteGroceryListTest.php`
   - Test authorized user can delete their list
   - Test unauthorized user cannot delete another user's list
   - Test soft delete actually soft deletes (deleted_at is set)
   - Test cascade: related items are soft deleted
   - Test redirect to index page after deletion
   - Test flash message appears
   - Test 404 when accessing deleted list

2. **Policy Test**: `tests/Unit/Policies/GroceryListPolicyTest.php`
   - Test owner can delete their list
   - Test non-owner cannot delete another's list

3. **E2E Test**: `e2e/grocery-lists/delete-grocery-list.spec.ts`
   - Test delete button appears on show page
   - Test clicking delete opens confirmation modal
   - Test clicking cancel closes modal without deleting
   - Test clicking confirm deletes list and redirects
   - Test deleted list no longer appears in index
   - Test accessing deleted list URL shows 404

**Pest Example**:
```php
test('authenticated user can delete their own grocery list', function () {
    $user = User::factory()->create();
    $groceryList = GroceryList::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route('grocery-lists.show', $groceryList))
        ->assertSeeLivewire(Show::class);

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $groceryList])
        ->call('delete')
        ->assertRedirect(route('grocery-lists.index'))
        ->assertSessionHas('success');

    expect($groceryList->fresh()->trashed())->toBeTrue();
});
```

---

## Summary of Decisions

| Area | Decision | Key Rationale |
|------|----------|---------------|
| Deletion Type | Soft Deletes | Audit trail, data safety, Laravel best practice |
| Authorization | Policy (`GroceryListPolicy::delete`) | Centralized logic, testable, follows Laravel patterns |
| Confirmation UI | Flux Modal | Existing component, consistent with app patterns |
| Cascade Behavior | Database FK Cascade | Performance, referential integrity |
| Post-Delete | Redirect to Index | Logical user flow, contextual navigation |
| Deleted Access | 404 (Model Binding) | Semantically correct, automatic handling |
| Testing | Pest + Playwright | Test-first (constitutional requirement) |

All decisions align with constitutional principles and leverage existing patterns in the Project Tabletop codebase.
