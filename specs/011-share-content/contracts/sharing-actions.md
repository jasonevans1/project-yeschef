# Contracts: Sharing Actions

**Feature**: 011-share-content
**Date**: 2026-02-10

This document defines the contracts for all sharing-related actions. Since this is a Livewire-first application, actions are implemented as Livewire component methods rather than REST API endpoints.

---

## Livewire Actions (on Show Components)

### `shareWith()` — Share a specific item

**Components**: `Recipes\Show`, `MealPlans\Show`, `GroceryLists\Show`

**Input** (component properties):
| Property | Type | Validation |
|----------|------|------------|
| shareEmail | string | required, email, max:255 |
| sharePermission | string | required, in:read,write |

**Behavior**:
1. Authorize: current user must own the item (`$this->authorize('share', $model)`)
2. Validate inputs
3. Prevent self-sharing: `shareEmail !== auth()->user()->email`
4. Lookup recipient by email: `User::where('email', $shareEmail)->first()`
5. Upsert ContentShare:
   - If share exists for this owner+email+item: update permission
   - Otherwise: create new ContentShare with `shareable_type`, `shareable_id`, `owner_id`, `recipient_id` (nullable), `recipient_email`, `permission`, `share_all = false`
6. If recipient is null (no registered user): send `ShareInvitation` mailable
7. Flash success message
8. Close share modal

**Success response**: Flash message "Shared with {email}" + modal closes
**Error responses**:
- Validation error on email/permission
- "You cannot share with yourself" if self-sharing
- 403 if not the owner

---

## Livewire Actions (on Settings\Sharing Component)

### `shareAll()` — Share all items of a content type

**Component**: `Settings\Sharing`

**Input** (component properties):
| Property | Type | Validation |
|----------|------|------------|
| shareAllEmail | string | required, email, max:255 |
| shareAllType | string | required, in:recipe,meal_plan,grocery_list |
| shareAllPermission | string | required, in:read,write |

**Behavior**:
1. Validate inputs
2. Prevent self-sharing
3. Map `shareAllType` to model class (e.g., `recipe` → `App\Models\Recipe`)
4. Lookup recipient by email
5. Upsert ContentShare:
   - If share_all exists for this owner+email+type: update permission
   - Otherwise: create new ContentShare with `shareable_type`, `shareable_id = null`, `owner_id`, `recipient_id` (nullable), `recipient_email`, `permission`, `share_all = true`
6. If recipient is null: send `ShareInvitation` mailable
7. Refresh shares list

**Success response**: Flash message + shares list refreshes
**Error responses**: Same as shareWith

---

### `updatePermission(int $shareId)` — Change permission level

**Component**: `Settings\Sharing`

**Input**:
| Parameter | Type | Validation |
|-----------|------|------------|
| shareId | int | required, exists:content_shares,id |
| newPermission | string | required, in:read,write |

**Behavior**:
1. Find ContentShare by ID
2. Authorize: current user must be the owner (`$share->owner_id === auth()->id()`)
3. Update `permission` field
4. Refresh shares list

**Success response**: Permission updated, list refreshes
**Error responses**: 403 if not owner, 404 if share not found

---

### `revokeShare(int $shareId)` — Remove a share

**Component**: `Settings\Sharing`

**Input**:
| Parameter | Type | Validation |
|-----------|------|------------|
| shareId | int | required, exists:content_shares,id |

**Behavior**:
1. Find ContentShare by ID
2. Authorize: current user must be the owner
3. Delete the ContentShare record
4. Refresh shares list

**Success response**: Share removed, list refreshes
**Error responses**: 403 if not owner, 404 if share not found

---

## Event Listener

### `ResolvePendingShares` — On user registration

**Event**: `Illuminate\Auth\Events\Registered`

**Behavior**:
1. Query `ContentShare::whereNull('recipient_id')->where('recipient_email', $event->user->email)`
2. Update all matching records: set `recipient_id = $event->user->id`

**No user-facing response** — runs silently on registration.

---

## Mailable

### `ShareInvitation`

**Trigger**: Called when sharing with a non-registered email
**Recipient**: The `recipient_email` from the ContentShare
**Data passed**:
| Field | Description |
|-------|-------------|
| ownerName | Name of the user sharing content |
| contentDescription | What was shared (e.g., "a recipe" or "all recipes") |
| registerUrl | URL to the registration page |

**Template**: Plain text/HTML email inviting the recipient to register to see shared content.

---

## Policy Methods (additions)

### `share(User $user, Model $model): bool`

Added to `RecipePolicy`, `MealPlanPolicy`, `GroceryListPolicy`.

**Returns** `true` only if `$model->user_id === $user->id`.

### `view()` and `update()` modifications

Existing methods extended to also check ContentShare access:
- `view()`: Returns true if owner, OR has any share (read or write), OR covered by share_all
- `update()`: Returns true if owner, OR has write share, OR covered by share_all with write permission

---

## Query Scopes

### `scopeAccessibleBy(Builder $query, User $user): Builder`

Added to `Recipe`, `MealPlan`, `GroceryList` models.

Returns items where:
1. `user_id = $user->id` (owned), OR
2. Specific ContentShare exists with `recipient_id = $user->id`, OR
3. Share_all ContentShare exists with `recipient_id = $user->id` and matching `shareable_type` and the item's `user_id` matches the share's `owner_id`

---

## Routes

| Method | URI | Component | Name |
|--------|-----|-----------|------|
| GET | `/settings/sharing` | `Settings\Sharing` | `settings.sharing` |

Added to the authenticated middleware group in `routes/web.php`, alongside existing settings routes. Added to settings navlist in `components/settings/layout.blade.php`.
