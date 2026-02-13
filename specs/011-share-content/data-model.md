# Data Model: Content Sharing

**Feature**: 011-share-content
**Date**: 2026-02-10

## Entities

### ContentShare

Represents a sharing relationship between an owner and a recipient for a specific content type or item.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| id | bigint (PK) | auto-increment | Primary key |
| owner_id | bigint (FK) | required, references users.id, cascadeOnDelete | The user who shared the content |
| recipient_id | bigint (FK) | nullable, references users.id, nullOnDelete | The user receiving access (null if not yet registered) |
| recipient_email | string(255) | required | Email of the recipient (used for pending invitations and deduplication) |
| shareable_type | string | required | Polymorphic type: `App\Models\Recipe`, `App\Models\MealPlan`, or `App\Models\GroceryList` |
| shareable_id | bigint | nullable | Polymorphic ID: specific item ID, or null when `share_all = true` |
| permission | enum | required, values: `read`, `write` | Permission level granted |
| share_all | boolean | required, default: false | When true, share applies to all items of the shareable_type owned by the owner |
| created_at | timestamp | auto | When the share was created |
| updated_at | timestamp | auto | When the share was last modified |

**Indexes**:
- `content_shares_owner_id_index` on `owner_id`
- `content_shares_recipient_id_index` on `recipient_id`
- `content_shares_recipient_email_index` on `recipient_email`
- `content_shares_shareable_type_shareable_id_index` on `(shareable_type, shareable_id)` — polymorphic index
- `content_shares_unique_share` unique on `(owner_id, recipient_email, shareable_type, shareable_id)` — prevents duplicate shares

**Constraints**:
- When `share_all = true`, `shareable_id` MUST be null
- When `share_all = false`, `shareable_id` MUST NOT be null
- `owner_id` and `recipient_email` MUST NOT refer to the same user (no self-sharing)

---

### Enums

#### SharePermission

```
Read    => 'read'
Write   => 'write'
```

Used in the `permission` column of `content_shares`. `Read` grants view-only access. `Write` grants view and edit access.

#### ShareableType

```
Recipe      => 'App\Models\Recipe'
MealPlan    => 'App\Models\MealPlan'
GroceryList => 'App\Models\GroceryList'
```

Used for `shareable_type` polymorphic column and for "share all" filtering. Matches Laravel's morph map convention.

---

## Relationships

### ContentShare

| Relationship | Type | Target | Description |
|-------------|------|--------|-------------|
| owner | BelongsTo | User | The user who created the share |
| recipient | BelongsTo | User | The user receiving access (nullable) |
| shareable | MorphTo | Recipe/MealPlan/GroceryList | The specific shared item (nullable for share_all) |

### User (additions)

| Relationship | Type | Target | Description |
|-------------|------|--------|-------------|
| outgoingShares | HasMany | ContentShare | Shares this user has created (owner_id) |
| incomingShares | HasMany | ContentShare | Shares this user has received (recipient_id) |

### Recipe, MealPlan, GroceryList (additions)

| Relationship | Type | Target | Description |
|-------------|------|--------|-------------|
| contentShares | MorphMany | ContentShare | Shares for this specific item |

---

## Validation Rules

### Creating a share

| Field | Rules |
|-------|-------|
| recipient_email | required, email, max:255, not equal to owner's email |
| shareable_type | required, in: recipe, meal_plan, grocery_list |
| shareable_id | required_if:share_all,false, exists on the relevant table, owned by current user |
| permission | required, in: read, write |
| share_all | required, boolean |

### Updating a share

| Field | Rules |
|-------|-------|
| permission | required, in: read, write |

---

## State Transitions

### Share Lifecycle

```
[Created] → Active (recipient_id set if user exists)
         → Pending (recipient_id null if user not registered)

[Pending] → Active (on recipient registration, recipient_id set)

[Active] → Permission Changed (owner updates read↔write)
        → Revoked (owner deletes the share record)
```

### Pending Share Resolution

When a new user registers with an email that has pending shares:
1. `Registered` event fires (from Fortify)
2. Listener queries `ContentShare::whereNull('recipient_id')->where('recipient_email', $newUser->email)`
3. Updates matching records: sets `recipient_id = $newUser->id`
4. All pending shares become immediately active

---

## Migration

```sql
CREATE TABLE content_shares (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id BIGINT UNSIGNED NOT NULL,
    recipient_id BIGINT UNSIGNED NULL,
    recipient_email VARCHAR(255) NOT NULL,
    shareable_type VARCHAR(255) NOT NULL,
    shareable_id BIGINT UNSIGNED NULL,
    permission VARCHAR(10) NOT NULL DEFAULT 'read',
    share_all BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX content_shares_owner_id_index (owner_id),
    INDEX content_shares_recipient_id_index (recipient_id),
    INDEX content_shares_recipient_email_index (recipient_email),
    INDEX content_shares_shareable_index (shareable_type, shareable_id),
    UNIQUE content_shares_unique_share (owner_id, recipient_email, shareable_type, shareable_id)
);
```

Note: The unique constraint on `(owner_id, recipient_email, shareable_type, shareable_id)` prevents duplicate shares. Since `shareable_id` can be NULL for share_all records, and NULL != NULL in SQL, an additional application-level check is needed for share_all uniqueness.
