# Quickstart: Delete Grocery List

**Feature**: Delete Grocery List with Confirmation
**Branch**: `005-delete-grocery-list`
**Date**: 2025-11-30

## TL;DR

Add delete button with confirmation modal to grocery list show page. Uses Livewire + Flux, soft deletes, policy authorization. Test-first development required.

**Estimated Time**: 2-3 hours (including tests)

---

## Prerequisites

✅ DDEV running (`ddev start`)
✅ Current branch: `005-delete-grocery-list`
✅ Composer dependencies installed
✅ Database migrated and seeded

**Verify Environment**:
```bash
ddev start
git checkout 005-delete-grocery-list
composer install
php artisan migrate
php artisan db:seed
```

---

## Development Workflow (Test-First)

### Phase 1: Write Failing Tests (30 min)

**1. Create Feature Test**:
```bash
php artisan make:test --pest GroceryList/DeleteGroceryListTest
```

**2. Write Test Cases** in `tests/Feature/GroceryList/DeleteGroceryListTest.php`:
```php
test('owner can delete their grocery list', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\GroceryLists\Show::class, ['groceryList' => $list])
        ->call('delete')
        ->assertRedirect(route('grocery-lists.index'))
        ->assertSessionHas('success');

    expect($list->fresh()->trashed())->toBeTrue();
});

test('non-owner cannot delete another user\'s list', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $list = GroceryList::factory()->for($owner)->create();

    Livewire::actingAs($attacker)
        ->test(\App\Livewire\GroceryLists\Show::class, ['groceryList' => $list])
        ->call('delete')
        ->assertForbidden();
});

test('deleting list cascades to items', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();
    $items = \App\Models\GroceryItem::factory()->count(3)->for($list)->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\GroceryLists\Show::class, ['groceryList' => $list])
        ->call('delete');

    foreach ($items as $item) {
        expect($item->fresh()->trashed())->toBeTrue();
    }
});
```

**3. Create Policy Test**:
```bash
php artisan make:test --pest --unit Policies/GroceryListPolicyTest
```

**4. Write Policy Tests** in `tests/Unit/Policies/GroceryListPolicyTest.php`:
```php
test('owner can delete their list', function () {
    $user = User::factory()->create();
    $list = GroceryList::factory()->for($user)->create();
    $policy = new \App\Policies\GroceryListPolicy();

    expect($policy->delete($user, $list))->toBeTrue();
});

test('non-owner cannot delete another user\'s list', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $list = GroceryList::factory()->for($owner)->create();
    $policy = new \App\Policies\GroceryListPolicy();

    expect($policy->delete($attacker, $list))->toBeFalse();
});
```

**5. Run Tests (should fail)**:
```bash
php artisan test --filter=DeleteGroceryList
php artisan test --filter=GroceryListPolicy
```

Expected: ❌ All tests fail (methods don't exist yet)

---

### Phase 2: Database Migrations (15 min)

**1. Create Migrations**:
```bash
php artisan make:migration add_soft_deletes_to_grocery_lists_table
php artisan make:migration add_soft_deletes_to_grocery_items_table
```

**2. Edit Grocery Lists Migration**:
```php
public function up()
{
    Schema::table('grocery_lists', function (Blueprint $table) {
        $table->softDeletes();
        $table->index('deleted_at'); // Performance
    });
}

public function down()
{
    Schema::table('grocery_lists', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });
}
```

**3. Edit Grocery Items Migration**:
```php
public function up()
{
    Schema::table('grocery_items', function (Blueprint $table) {
        $table->softDeletes();
        $table->index('deleted_at'); // Performance
    });
}

public function down()
{
    Schema::table('grocery_items', function (Blueprint $table) {
        $table->dropSoftDeletes();
    });
}
```

**4. Run Migrations**:
```bash
php artisan migrate
```

---

### Phase 3: Update Models (10 min)

**1. Update GroceryList Model** (`app/Models/GroceryList.php`):
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class GroceryList extends Model
{
    use HasFactory, SoftDeletes; // Add SoftDeletes

    protected $casts = [
        'generated_at' => 'datetime',
        'regenerated_at' => 'datetime',
        'share_expires_at' => 'datetime',
        'deleted_at' => 'datetime', // Add this
    ];

    // ... rest unchanged
}
```

**2. Update GroceryItem Model** (`app/Models/GroceryItem.php`):
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class GroceryItem extends Model
{
    use HasFactory, SoftDeletes; // Add SoftDeletes

    protected $casts = [
        // ... existing casts
        'deleted_at' => 'datetime', // Add this
    ];

    // ... rest unchanged
}
```

---

### Phase 4: Add Policy Method (5 min)

**1. Open Policy** (`app/Policies/GroceryListPolicy.php`)

**2. Add Delete Method**:
```php
public function delete(User $user, GroceryList $groceryList): bool
{
    return $user->id === $groceryList->user_id;
}
```

**3. Run Policy Tests**:
```bash
php artisan test --filter=GroceryListPolicy
```

Expected: ✅ Policy tests pass

---

### Phase 5: Add Livewire Component Methods (20 min)

**1. Open Component** (`app/Livewire/GroceryLists/Show.php`)

**2. Add Property** (after existing properties):
```php
// Properties for delete confirmation
public bool $showDeleteConfirm = false;
```

**3. Add Methods** (after existing methods):
```php
public function confirmDelete(): void
{
    $this->showDeleteConfirm = true;
}

public function cancelDelete(): void
{
    $this->showDeleteConfirm = false;
}

public function delete()
{
    $this->authorize('delete', $this->groceryList);

    $this->groceryList->delete();

    session()->flash('success', 'Grocery list deleted successfully.');

    return redirect()->route('grocery-lists.index');
}
```

**4. Run Feature Tests**:
```bash
php artisan test --filter=DeleteGroceryList
```

Expected: ✅ Backend tests pass

---

### Phase 6: Add UI Components (20 min)

**1. Open Blade View** (`resources/views/livewire/grocery-lists/show.blade.php`)

**2. Add Delete Button** (in header/toolbar area, near existing buttons):
```blade
<div class="flex gap-2">
    {{-- Existing buttons (share, regenerate, etc.) --}}

    <flux:button
        wire:click="confirmDelete"
        variant="danger"
        icon="trash"
    >
        Delete List
    </flux:button>
</div>
```

**3. Add Confirmation Modal** (at bottom of template, with other modals):
```blade
{{-- Delete Confirmation Modal --}}
<flux:modal wire:model="showDeleteConfirm" variant="flyout">
    <flux:heading>Delete Grocery List?</flux:heading>

    <flux:text>
        This will permanently delete <strong>"{{ $groceryList->name }}"</strong>
        and all {{ $groceryList->total_items }} item(s).
        This action cannot be undone.
    </flux:text>

    <div class="flex gap-2 mt-4">
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

### Phase 7: Manual Testing (15 min)

**1. Start Development Server**:
```bash
composer dev
# Or separately:
# ddev ssh
# php artisan serve
# npm run dev
```

**2. Test in Browser**:
1. Navigate to https://project-tabletop.ddev.site
2. Log in as test user
3. Go to a grocery list (create one if needed)
4. Click "Delete List" button
5. Verify modal appears with correct content
6. Click "Cancel" → modal closes, list remains
7. Click "Delete List" again
8. Click "Delete Permanently" → redirect to index
9. Verify success message appears
10. Verify list no longer in index
11. Try accessing deleted list URL → should get 404

---

### Phase 8: E2E Tests (30 min)

**1. Create Playwright Test**:
```bash
mkdir -p e2e/grocery-lists
touch e2e/grocery-lists/delete-grocery-list.spec.ts
```

**2. Write E2E Test**:
```typescript
import { test, expect } from '@playwright/test';

test.describe('Delete Grocery List', () => {
    test('user can delete grocery list with confirmation', async ({ page }) => {
        // Login
        await page.goto('/login');
        await page.fill('input[name="email"]', 'test@example.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');

        // Navigate to grocery list
        await page.goto('/grocery-lists/1');

        // Click delete button
        await page.click('button:has-text("Delete List")');

        // Verify modal appears
        await expect(page.locator('text=Delete Grocery List?')).toBeVisible();

        // Click confirm
        await page.click('button:has-text("Delete Permanently")');

        // Verify redirect
        await expect(page).toHaveURL(/\/grocery-lists$/);

        // Verify success message
        await expect(page.locator('text=deleted successfully')).toBeVisible();
    });

    test('user can cancel deletion', async ({ page }) => {
        await page.goto('/login');
        await page.fill('input[name="email"]', 'test@example.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');

        await page.goto('/grocery-lists/1');

        // Click delete
        await page.click('button:has-text("Delete List")');

        // Click cancel
        await page.click('button:has-text("Cancel")');

        // Verify modal closed
        await expect(page.locator('text=Delete Grocery List?')).not.toBeVisible();

        // Verify still on show page
        await expect(page).toHaveURL(/\/grocery-lists\/1$/);
    });
});
```

**3. Run E2E Tests**:
```bash
npx playwright test e2e/grocery-lists/delete-grocery-list.spec.ts
```

Expected: ✅ E2E tests pass

---

### Phase 9: Code Quality (10 min)

**1. Run Pint (Code Formatter)**:
```bash
vendor/bin/pint
```

**2. Run All Tests**:
```bash
php artisan test
```

**3. Run E2E Tests (all browsers)**:
```bash
npx playwright test
```

Expected: ✅ All tests pass, code formatted

---

## Verification Checklist

Before committing:

- [ ] All Pest tests pass (`php artisan test`)
- [ ] All Playwright tests pass (`npx playwright test`)
- [ ] Code formatted with Pint (`vendor/bin/pint`)
- [ ] Manual testing completed successfully
- [ ] Delete button appears on show page
- [ ] Confirmation modal displays correctly
- [ ] Cancel works (no deletion)
- [ ] Confirm works (deletes and redirects)
- [ ] Unauthorized users get 403
- [ ] Deleted lists return 404
- [ ] Related items cascade delete
- [ ] No console errors in browser
- [ ] DDEV environment still works (`ddev start`)

---

## Common Issues & Solutions

### Issue: Tests Fail with "Method not found"
**Solution**: Make sure you added methods to correct component (`Show.php`)

### Issue: Modal doesn't appear
**Solution**: Check `wire:model="showDeleteConfirm"` matches property name exactly

### Issue: Authorization fails (403 for owner)
**Solution**: Verify policy method returns `true` for owner

### Issue: Items not cascade deleted
**Solution**: Check foreign key constraint in migration, ensure GroceryItem has SoftDeletes

### Issue: Redirect doesn't work
**Solution**: Use `return redirect()` not `$this->redirect()`

### Issue: Playwright can't find elements
**Solution**: Use `page.locator('button:has-text("Delete List")')` for Flux buttons

---

## Performance Notes

**Database Queries**:
- Delete operation: 1 UPDATE query (soft delete)
- Cascade: Database handles automatically (efficient)
- No N+1 queries

**UI Performance**:
- Modal uses Livewire reactivity (no custom JS)
- Single round-trip for delete action
- Instant feedback on button clicks

---

## Next Steps

After implementation:

1. ✅ Commit changes
2. ✅ Push branch
3. ✅ Create pull request
4. ✅ Request code review
5. ✅ Merge to main after approval

Optional future enhancements:
- Add "restore deleted list" feature
- Add bulk delete for multiple lists
- Add "delete confirmation" checkbox
- Add scheduled cleanup of old soft-deleted records

---

## Key Files Modified

```
app/
├── Models/
│   ├── GroceryList.php          # Added SoftDeletes trait
│   └── GroceryItem.php          # Added SoftDeletes trait
├── Livewire/GroceryLists/
│   └── Show.php                 # Added delete methods
└── Policies/
    └── GroceryListPolicy.php    # Added delete method

resources/views/livewire/grocery-lists/
└── show.blade.php               # Added delete button & modal

database/migrations/
├── YYYY_MM_DD_add_soft_deletes_to_grocery_lists_table.php
└── YYYY_MM_DD_add_soft_deletes_to_grocery_items_table.php

tests/
├── Feature/GroceryList/
│   └── DeleteGroceryListTest.php
└── Unit/Policies/
    └── GroceryListPolicyTest.php

e2e/grocery-lists/
└── delete-grocery-list.spec.ts
```

---

## Reference Documentation

- [Laravel Soft Deletes](https://laravel.com/docs/12.x/eloquent#soft-deleting)
- [Livewire 3 Components](https://livewire.laravel.com/docs/components)
- [Flux UI Modal](https://fluxui.dev/components/modal)
- [Pest Testing](https://pestphp.com/docs/writing-tests)
- [Playwright Testing](https://playwright.dev/docs/writing-tests)
- [Feature Spec](./spec.md)
- [Research Decisions](./research.md)
- [Data Model](./data-model.md)
- [Component Contract](./contracts/livewire-component.md)

---

**Ready to implement? Start with Phase 1: Write Failing Tests!**
