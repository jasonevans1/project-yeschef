# Quickstart: Content Sharing

**Feature**: 011-share-content
**Date**: 2026-02-10

## Prerequisites

- Laravel 12 application running via DDEV (`ddev start`)
- Existing models: User, Recipe, MealPlan, GroceryList
- Existing policies: RecipePolicy, MealPlanPolicy, GroceryListPolicy
- Existing settings layout with navlist

## Implementation Order

### Phase 1: Data Layer

1. **Create migration**: `content_shares` table with polymorphic columns, nullable `shareable_id` for share_all, `recipient_email` for pending invitations
2. **Create enums**: `SharePermission` (Read, Write) and `ShareableType` (Recipe, MealPlan, GroceryList)
3. **Create model**: `ContentShare` with relationships (owner, recipient, shareable morphTo)
4. **Create factory**: `ContentShareFactory` with states for each content type, share_all, and pending (no recipient)
5. **Add relationships to User**: `outgoingShares()` and `incomingShares()`
6. **Add relationships to shareable models**: `contentShares()` morphMany on Recipe, MealPlan, GroceryList
7. **Add `scopeAccessibleBy` to shareable models**: Query scope returning owned + shared items
8. **Run migration**: `php artisan migrate`

### Phase 2: Authorization

9. **Add `share()` method** to RecipePolicy, MealPlanPolicy, GroceryListPolicy (owner-only)
10. **Extend `view()` methods** to check ContentShare access (read or write permission)
11. **Extend `update()` methods** to check ContentShare access (write permission only)
12. **Keep `delete()` methods** owner-only (no change)
13. **Write policy tests**: Test all permission combinations for each content type

### Phase 3: Share Action (Item-Level)

14. **Add share modal Blade component**: `resources/views/components/share-modal.blade.php` with email input, permission select, submit button
15. **Add `shareWith()` action** to Recipes\Show, MealPlans\Show, GroceryLists\Show
16. **Handle upsert logic**: Update existing share or create new one
17. **Handle self-sharing prevention**: Validate email != owner's email
18. **Write Livewire component tests**: Test share creation from each Show page

### Phase 4: Invitation System

19. **Create mailable**: `ShareInvitation` with owner name, content description, register URL
20. **Create email template**: `resources/views/mail/share-invitation.blade.php`
21. **Send invitation**: When sharing with non-registered email
22. **Create event listener**: `ResolvePendingShares` on `Registered` event
23. **Register listener**: In `EventServiceProvider` or via attribute
24. **Write invitation tests**: Test email sending and pending share resolution

### Phase 5: Share Management

25. **Create Livewire component**: `Settings\Sharing` with outgoing shares list
26. **Create Blade view**: `resources/views/livewire/settings/sharing.blade.php`
27. **Add `shareAll()` action**: Share all items of a content type
28. **Add `updatePermission()` action**: Change read/write on existing share
29. **Add `revokeShare()` action**: Delete a share record
30. **Add route**: `settings/sharing` in `routes/web.php`
31. **Add navlist entry**: "Sharing" in `components/settings/layout.blade.php`
32. **Write management tests**: Test CRUD operations on shares

### Phase 6: Shared Content Display

33. **Update index queries**: Replace `where('user_id', auth()->id())` with `accessibleBy(auth()->user())` in Recipe\Index, MealPlan\Index, GroceryList\Index
34. **Update Dashboard queries**: Include shared items in upcoming meal plans and recent grocery lists
35. **Add owner labels**: Badge/text showing "Shared by {owner name}" on shared items in list views
36. **Conditionally hide edit/delete**: For read-only shared items, hide mutation controls
37. **Write display tests**: Test shared items appear with correct labels and permissions

## Key Files to Reference

| Purpose | File |
|---------|------|
| Existing policy pattern | `app/Policies/RecipePolicy.php` |
| Existing factory pattern | `database/factories/RecipeFactory.php` |
| Settings page pattern | `app/Livewire/Settings/Profile.php` |
| Settings layout + navlist | `resources/views/components/settings/layout.blade.php` |
| Show page with modal | `app/Livewire/Recipes/Show.php` |
| Existing share tests | `tests/Feature/GroceryLists/ShareGroceryListTest.php` |
| Migration pattern | `database/migrations/2026_01_11_215030_create_meal_plan_notes_table.php` |
| Index query pattern | `app/Livewire/Recipes/Index.php` |
| Dashboard query pattern | `app/Livewire/Dashboard.php` |
| Registration event | Fortify dispatches `Registered` on user creation |

## Testing Strategy

- **Unit**: ContentShare model relationships, scopes, enum values
- **Feature**: Policy checks for all 3 content types x 3 permission states (owner, read-shared, write-shared)
- **Feature**: Livewire component tests for share creation, management, display
- **Feature**: Invitation sending and pending share resolution on registration
- **E2E**: Share a recipe with another user, log in as recipient, verify access

## Run Tests

```bash
# Run sharing-specific tests
php artisan test tests/Feature/Sharing/

# Run all tests to verify no regressions
php artisan test
```
