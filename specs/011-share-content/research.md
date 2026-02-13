# Research: Content Sharing

**Feature**: 011-share-content
**Date**: 2026-02-10

## Research Task 1: Polymorphic Table vs Dedicated Tables

**Context**: ContentShare needs to reference three different model types (Recipe, MealPlan, GroceryList). Should we use a single polymorphic table or three separate share tables?

**Decision**: Single polymorphic table (`content_shares`) with `shareable_type` and `shareable_id` columns.

**Rationale**:
- One model (`ContentShare`) handles all sharing logic, reducing code duplication
- Single management query to show "all my shares" without UNION across three tables
- Laravel's `morphTo`/`morphMany` provides clean API for polymorphic relationships
- The share record structure is identical across all three content types (owner, recipient, permission, share_all flag)
- Queries are simple: `ContentShare::where('owner_id', $user->id)` returns all shares regardless of type

**Alternatives considered**:
- **Three separate tables** (`recipe_shares`, `meal_plan_shares`, `grocery_list_shares`): Would require three models, three factories, three sets of tests, and UNION queries for the management screen. Rejected because the share structure is identical across types.
- **JSON column on User model**: Would be denormalized and hard to query from the recipient's side. Rejected.

---

## Research Task 2: "Share All" Query Pattern

**Context**: When "share all" is enabled, the recipient should see all current AND future items of that content type. How should queries resolve this?

**Decision**: Use a nullable `shareable_id` combined with a `share_all` boolean flag. When `share_all = true` and `shareable_id = null`, the share applies to all items of the `shareable_type` owned by the `owner_id`.

**Rationale**:
- Policies check: "Is there a ContentShare where `shareable_id` matches this item OR where `share_all = true` and `shareable_type` matches and `owner_id` matches the item's `user_id`?"
- No need to create individual share records for each item (which would require a listener on model creation)
- Future items are automatically covered because the policy checks ownership at query time
- List queries use a scope: `Recipe::where('user_id', auth()->id())->orWhereHas('contentShares', ...)` combined with checking share_all records

**Alternatives considered**:
- **Create individual share records on item creation** (via model observer): Would require an observer on each shareable model and create potentially many records. More complex and harder to revoke ("share all" revocation would need to delete many records). Rejected.
- **Separate `share_all_settings` table**: Unnecessary; the ContentShare table with nullable `shareable_id` handles both cases cleanly. Rejected.

---

## Research Task 3: Policy Extension Approach

**Context**: Existing policies (RecipePolicy, MealPlanPolicy, GroceryListPolicy) check `$model->user_id === $user->id`. How should we extend them for shared access?

**Decision**: Add a private helper method to each policy that checks for an active ContentShare. Modify `view()` and `update()` methods to also check share permissions. Keep `delete()` and `create()` owner-only. Add a new `share()` method that only owners can use.

**Rationale**:
- Minimal change to existing policies; just adds an `OR` condition to view/update
- Helper method avoids code duplication within each policy: `$this->hasSharePermission($user, $model, SharePermission::ReadOnly)`
- `delete()` remains owner-only per spec (FR-009)
- `share()` method is new and owner-only per spec (FR-009)
- Read-only shares grant `view()` but not `update()`; read-write shares grant both

**Pattern**:
```php
public function view(User $user, Recipe $recipe): bool
{
    if ($recipe->user_id === null) return true;  // system recipes
    if ($recipe->user_id === $user->id) return true;  // owner
    return $this->hasShareAccess($user, $recipe);  // shared
}

public function update(User $user, Recipe $recipe): bool
{
    if ($recipe->user_id === null) return false;
    if ($recipe->user_id === $user->id) return true;
    return $this->hasWriteShareAccess($user, $recipe);
}
```

**Alternatives considered**:
- **Gate-based approach**: A global `before` callback on the Gate that checks shares. Rejected because it's too broad and would bypass model-specific logic (e.g., system recipes).
- **Trait on policies**: A `HasSharePermissions` trait. Viable, but with only three policies the duplication is minimal and explicit code in each policy is clearer. Could be refactored later if more shareable types are added.
- **Middleware approach**: Check sharing at the route level. Rejected because authorization should be at the policy level for consistency with existing patterns.

---

## Research Task 4: Invitation Mechanism for Non-Registered Users

**Context**: When sharing with an email that doesn't belong to a registered user, the system needs to send an invitation and activate the share upon registration.

**Decision**: Use Laravel Mail (Mailable class) to send the invitation. Store the `recipient_email` on the ContentShare record (in addition to nullable `recipient_id`). On user registration, a listener resolves pending shares by matching email.

**Rationale**:
- The project has no existing Notification or Mail classes, so this establishes the Mail pattern
- `recipient_id` is nullable; `recipient_email` is always set. When a user registers, a listener fires on the `Registered` event that queries `ContentShare::whereNull('recipient_id')->where('recipient_email', $user->email)` and sets the `recipient_id`
- Laravel's `Registered` event is already dispatched by Fortify during registration
- The Mailable provides a clean, testable email template

**Alternatives considered**:
- **Laravel Notifications**: More complex for this use case since the "notifiable" doesn't exist yet (no User model to notify). On-demand notifications are possible but less intuitive than a direct Mailable. Rejected for simplicity.
- **Queued invitation**: Could queue the email, but for a small-scale app synchronous is fine. Can be upgraded to `ShouldQueue` later if needed.
- **Magic link in invitation**: The invitation email could contain a pre-filled registration link. Simpler to just link to the registration page. The shares activate automatically upon registration with the matching email.

---

## Research Task 5: List Query Pattern for Shared Content

**Context**: Dashboard and index pages (recipes, meal plans, grocery lists) currently query `where('user_id', auth()->id())`. How should these queries be modified to include shared content?

**Decision**: Add a query scope to each shareable model (e.g., `scopeAccessibleBy(User $user)`) that returns items owned by the user OR shared with them (via specific share or share-all).

**Rationale**:
- Encapsulates the sharing logic in the model, keeping Livewire components clean
- Replaces `where('user_id', auth()->id())` with `accessibleBy(auth()->user())` in index queries
- The scope handles both specific-item shares and share-all shares in a single query
- Eager-loads the owner relationship for shared items to display the owner's name

**Query pattern**:
```php
public function scopeAccessibleBy(Builder $query, User $user): Builder
{
    return $query->where(function ($q) use ($user) {
        // Items the user owns
        $q->where('user_id', $user->id)
          // Items specifically shared with the user
          ->orWhereHas('contentShares', function ($sq) use ($user) {
              $sq->where('recipient_id', $user->id);
          })
          // Items covered by "share all" for this content type
          ->orWhereIn('user_id', function ($sq) use ($user) {
              $sq->select('owner_id')
                 ->from('content_shares')
                 ->where('recipient_id', $user->id)
                 ->where('share_all', true)
                 ->where('shareable_type', static::class);
          });
    });
}
```

**Alternatives considered**:
- **Separate "Shared with me" tab/page**: Simpler query but fragments the user experience. Rejected per spec (shared items appear alongside owned items).
- **Database view**: A SQL view combining owned and shared items. Over-engineered for this scale. Rejected.

---

## Research Task 6: Share Dialog UX Pattern

**Context**: Users need to share from the Recipe Show, MealPlan Show, and GroceryList Show pages. How should the share dialog be implemented?

**Decision**: A reusable Blade component (`<x-share-modal>`) that receives the shareable model and dispatches a Livewire event. Each Show component adds a `share()` method and includes the modal.

**Rationale**:
- Follows Flux modal pattern already used in the codebase (e.g., `showMealPlanModal` in `Recipes/Show.php`)
- The modal contains: email input, permission select (read-only/read-write), submit button
- Each Show component handles the actual share creation logic via a `shareWith()` Livewire action
- Keeps the modal presentation separate from business logic

**Alternatives considered**:
- **Standalone Livewire component for sharing**: A `ShareDialog` Livewire component mounted on each page. More complex and requires passing context. Rejected for simplicity.
- **Alpine.js-only modal**: Would not have server-side validation. Rejected per constitution (Livewire-first).
