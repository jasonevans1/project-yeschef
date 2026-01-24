# Livewire Component Contract: GroceryLists\Show

**Feature**: Delete Grocery List
**Component**: `app/Livewire/GroceryLists/Show.php`
**Date**: 2025-11-30

## Overview

This contract defines the public interface for delete functionality added to the existing `Show` component. The component follows Livewire 3 conventions and integrates with Flux UI components.

## Public Properties

### Existing Properties (No Changes)
```php
public GroceryList $groceryList; // Injected via route model binding
```

### New Properties

```php
/**
 * Controls visibility of delete confirmation modal
 *
 * @var bool
 */
public bool $showDeleteConfirm = false;
```

**Usage**:
- Set to `true` to display confirmation modal
- Set to `false` to hide confirmation modal
- Bound to Flux modal via `wire:model="showDeleteConfirm"`

---

## Public Methods

### confirmDelete()

**Purpose**: Display delete confirmation modal

**Signature**:
```php
public function confirmDelete(): void
```

**Behavior**:
- Sets `$showDeleteConfirm = true`
- Triggers Flux modal to display
- No authorization check (viewing confirmation is harmless)

**Wire Directive**:
```blade
<flux:button wire:click="confirmDelete" variant="danger">
    Delete List
</flux:button>
```

**Livewire Events**: None

**Exceptions**: None

---

### cancelDelete()

**Purpose**: Close delete confirmation modal without deleting

**Signature**:
```php
public function cancelDelete(): void
```

**Behavior**:
- Sets `$showDeleteConfirm = false`
- Closes Flux modal
- No data changes

**Wire Directive**:
```blade
<flux:button wire:click="cancelDelete" variant="ghost">
    Cancel
</flux:button>
```

**Livewire Events**: None

**Exceptions**: None

---

### delete()

**Purpose**: Permanently delete the grocery list and redirect

**Signature**:
```php
public function delete(): \Illuminate\Http\RedirectResponse
```

**Authorization**:
- Requires `delete` permission via `GroceryListPolicy`
- Throws `AuthorizationException` if unauthorized (403 Forbidden)

**Behavior**:
1. Authorize user can delete this grocery list
2. Soft delete the grocery list (`$groceryList->delete()`)
3. Cascade soft delete all related grocery items (automatic via DB constraint)
4. Flash success message to session
5. Redirect to grocery lists index

**Wire Directive**:
```blade
<flux:button wire:click="delete" variant="danger">
    Confirm Delete
</flux:button>
```

**Return Value**:
```php
return redirect()->route('grocery-lists.index');
```

**Session Flash**:
```php
session()->flash('success', 'Grocery list deleted successfully.');
```

**Exceptions**:
- `AuthorizationException`: User not authorized to delete (403)
- Database exceptions propagate (rare, logged)

**Side Effects**:
- Sets `grocery_lists.deleted_at = now()`
- Sets `grocery_items.deleted_at = now()` for all related items
- Redirects user (leaves component lifecycle)

---

## Authorization

### Policy Method: GroceryListPolicy::delete()

**Contract**:
```php
public function delete(User $user, GroceryList $groceryList): bool
```

**Authorization Logic**:
```php
return $user->id === $groceryList->user_id;
```

**Rules**:
- User must be authenticated (enforced by Livewire middleware)
- User must own the grocery list
- No other restrictions (any owned list can be deleted)

---

## Component Lifecycle

### Mount Phase
```php
public function mount(GroceryList $groceryList): void
{
    // Existing mount logic (no changes)
    $this->groceryList = $groceryList;

    // Soft deleted lists are excluded by route model binding
    // User receives 404 if accessing deleted list
}
```

### Delete Flow
```
1. User action: wire:click="confirmDelete"
   ↓
2. Component: $showDeleteConfirm = true
   ↓
3. UI: Modal displays
   ↓
4. User action: wire:click="delete"
   ↓
5. Component: authorize('delete', $groceryList)
   ↓
6. Component: $groceryList->delete()
   ↓
7. Database: Set deleted_at timestamp
   ↓
8. Component: session()->flash('success')
   ↓
9. Component: return redirect()
   ↓
10. Browser: Navigate to grocery-lists.index
```

---

## Blade Template Changes

### New UI Elements

**Delete Button** (in existing toolbar/actions area):
```blade
<flux:button
    wire:click="confirmDelete"
    variant="danger"
    icon="trash"
>
    Delete List
</flux:button>
```

**Confirmation Modal**:
```blade
<flux:modal wire:model="showDeleteConfirm" variant="flyout">
    <flux:heading>Delete Grocery List?</flux:heading>

    <flux:text>
        This will permanently delete <strong>"{{ $groceryList->name }}"</strong>
        and all {{ $groceryList->total_items }} item(s).
        This action cannot be undone.
    </flux:text>

    <div class="flex gap-2">
        <flux:button
            wire:click="delete"
            variant="danger"
            icon="trash"
        >
            Delete Permanently
        </flux:button>

        <flux:button
            wire:click="cancelDelete"
            variant="ghost"
        >
            Cancel
        </flux:button>
    </div>
</flux:modal>
```

---

## Error Handling

### Authorization Failure
```
User clicks delete (not owner)
  ↓
Policy::delete() returns false
  ↓
AuthorizationException thrown
  ↓
Laravel error handler displays 403 page
```

### Already Deleted List
```
User accesses /grocery-lists/{id} where list is soft deleted
  ↓
Route model binding excludes trashed models
  ↓
ModelNotFoundException thrown
  ↓
Laravel error handler displays 404 page
```

### Database Errors
```
$groceryList->delete() fails (rare)
  ↓
Exception propagates
  ↓
Laravel error handler logs and displays 500 page
```

---

## Testing Contract

### Feature Tests

**Test: Authorized Delete**
```php
test('owner can delete their grocery list', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $list])
        ->call('delete')
        ->assertRedirect(route('grocery-lists.index'))
        ->assertSessionHas('success');

    expect($list->fresh()->trashed())->toBeTrue();
});
```

**Test: Unauthorized Delete**
```php
test('non-owner cannot delete another user\'s list', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $list = GroceryList::factory()->for($owner)->create();

    Livewire::actingAs($attacker)
        ->test(Show::class, ['groceryList' => $list])
        ->call('delete')
        ->assertForbidden();

    expect($list->fresh()->trashed())->toBeFalse();
});
```

**Test: Cascade Delete**
```php
test('deleting list cascades to grocery items', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();
    $items = GroceryItem::factory()->count(3)->for($list)->create();

    Livewire::actingAs($user)
        ->test(Show::class, ['groceryList' => $list])
        ->call('delete');

    foreach ($items as $item) {
        expect($item->fresh()->trashed())->toBeTrue();
    }
});
```

### E2E Tests

**Test: Complete User Flow**
```typescript
test('user can delete grocery list with confirmation', async ({ page }) => {
    // Navigate to grocery list show page
    await page.goto('/grocery-lists/1');

    // Click delete button
    await page.click('button:has-text("Delete List")');

    // Verify modal appears
    await expect(page.locator('text=Delete Grocery List?')).toBeVisible();

    // Click confirm
    await page.click('button:has-text("Delete Permanently")');

    // Verify redirect to index
    await expect(page).toHaveURL('/grocery-lists');

    // Verify success message
    await expect(page.locator('text=Grocery list deleted successfully')).toBeVisible();

    // Verify list no longer in index
    await expect(page.locator('text=Test List')).not.toBeVisible();
});
```

---

## Backwards Compatibility

**Breaking Changes**: None

**Additions**:
- New public property: `$showDeleteConfirm`
- New public methods: `confirmDelete()`, `cancelDelete()`, `delete()`
- New UI elements: Delete button, confirmation modal

**Existing Functionality**: Unchanged
- All existing Show component features continue to work
- No changes to mount, render, or other methods
- No changes to existing properties

---

## Performance Considerations

**Query Performance**:
- Single query: `$groceryList->delete()` (sets deleted_at)
- Cascade handled by database constraint (efficient)
- No N+1 queries

**UI Performance**:
- Modal state managed by Livewire (no custom JavaScript)
- Single round-trip for delete action
- Redirect is final navigation (component unmounts)

**Database Impact**:
- Soft delete: UPDATE query (fast)
- Hard delete alternative would be DELETE (similar performance)
- Deleted records retained (slight storage increase)

---

## Security Considerations

**Authorization**:
- Policy-based authorization (centralized, testable)
- No client-side bypass possible (server validates)

**CSRF Protection**:
- Livewire includes CSRF token automatically
- No custom CSRF handling needed

**Input Validation**:
- No user input for delete operation
- Only groceryList ID from route (validated by model binding)

**Session Security**:
- Flash message uses Laravel's encrypted session
- Redirect uses Laravel's secure redirect response

---

## Summary

**New Methods**: 3 (`confirmDelete`, `cancelDelete`, `delete`)
**New Properties**: 1 (`$showDeleteConfirm`)
**Authorization**: Policy-based (owner only)
**Return Type**: RedirectResponse
**Side Effects**: Soft delete + cascade, session flash, redirect
**Backwards Compatible**: Yes
