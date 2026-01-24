# Data Model: Delete Grocery List

**Feature**: Delete Grocery List with Confirmation
**Date**: 2025-11-30
**Status**: Complete

## Overview

This feature modifies existing data models to support soft deletion of grocery lists and their related items. No new entities are created; we're adding soft delete capabilities to existing models.

## Entity Changes

### GroceryList (Existing Entity - Modified)

**Purpose**: Represents a grocery shopping list owned by a user. Modified to support soft deletion.

**Changes Required**:
- Add `deleted_at` column (timestamp, nullable)
- Add `SoftDeletes` trait to model
- Update casts to include `deleted_at` as datetime

**Schema Changes**:
```sql
-- Migration: YYYY_MM_DD_add_soft_deletes_to_grocery_lists_table.php
ALTER TABLE grocery_lists
ADD COLUMN deleted_at TIMESTAMP NULL;

-- Index for soft delete queries (optional but recommended)
CREATE INDEX idx_grocery_lists_deleted_at ON grocery_lists(deleted_at);
```

**Model Changes**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes; // Add this import

class GroceryList extends Model
{
    use HasFactory, SoftDeletes; // Add SoftDeletes trait

    protected $fillable = [
        'user_id',
        'meal_plan_id',
        'name',
        'generated_at',
        'regenerated_at',
        'share_token',
        'share_expires_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'regenerated_at' => 'datetime',
        'share_expires_at' => 'datetime',
        'deleted_at' => 'datetime', // Add this cast
    ];

    // All existing relationships and methods remain unchanged
}
```

**Existing Attributes** (no changes):
- `id`: Primary key
- `user_id`: Foreign key to users table (owner)
- `meal_plan_id`: Foreign key to meal_plans table (nullable)
- `name`: String, grocery list name
- `generated_at`: Timestamp when list was generated
- `regenerated_at`: Timestamp when list was last regenerated
- `share_token`: Unique token for public sharing (nullable)
- `share_expires_at`: Expiration for share link (nullable)
- `created_at`: Laravel timestamp
- `updated_at`: Laravel timestamp

**New Attributes**:
- `deleted_at`: Timestamp when soft deleted (nullable)

**Existing Relationships** (no changes):
- `belongsTo(User)`: User who owns the list
- `belongsTo(MealPlan)`: Optional linked meal plan
- `hasMany(GroceryItem)`: Items in the list

**Cascade Behavior**:
When a GroceryList is soft deleted:
- All related GroceryItems are automatically soft deleted via database constraint
- User relationship remains intact (for audit purposes)
- MealPlan relationship remains intact (list can be restored)

---

### GroceryItem (Existing Entity - Modified)

**Purpose**: Represents individual items in a grocery list. Modified to support cascade soft deletion.

**Changes Required**:
- Add `deleted_at` column (timestamp, nullable)
- Add `SoftDeletes` trait to model
- Verify foreign key constraint supports cascade delete

**Schema Changes**:
```sql
-- Migration: YYYY_MM_DD_add_soft_deletes_to_grocery_items_table.php
ALTER TABLE grocery_items
ADD COLUMN deleted_at TIMESTAMP NULL;

-- Verify foreign key constraint (should already exist)
-- If not, add constraint:
ALTER TABLE grocery_items
ADD CONSTRAINT fk_grocery_items_grocery_list_id
FOREIGN KEY (grocery_list_id)
REFERENCES grocery_lists(id)
ON DELETE CASCADE;

-- Index for soft delete queries
CREATE INDEX idx_grocery_items_deleted_at ON grocery_items(deleted_at);
```

**Model Changes**:
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes; // Add this import

class GroceryItem extends Model
{
    use HasFactory, SoftDeletes; // Add SoftDeletes trait

    protected $casts = [
        // ... existing casts
        'deleted_at' => 'datetime', // Add this cast
    ];

    // All existing relationships and methods remain unchanged
}
```

**Cascade Behavior**:
- When parent GroceryList is soft deleted, all items automatically soft delete
- Database foreign key constraint handles cascade
- No application-level code needed for cascade

---

### User (No Changes)

**Existing Relationship**:
- `hasMany(GroceryList)`: User can have multiple grocery lists

**Behavior with Soft Deletes**:
- When querying user's grocery lists, soft deleted lists are automatically excluded
- To include deleted lists: `$user->groceryLists()->withTrashed()->get()`
- User model itself is NOT affected by this feature

---

## Data Flow: Delete Operation

### 1. User Clicks Delete Button
```
User clicks "Delete" button on show page
  ↓
Livewire fires wire:click="confirmDelete"
  ↓
Component sets $showDeleteConfirm = true
  ↓
Flux modal displays confirmation dialog
```

### 2. User Confirms Deletion
```
User clicks "Confirm Delete" button in modal
  ↓
Livewire fires wire:click="delete"
  ↓
Component method: Show::delete()
  ↓
Authorization check: GroceryListPolicy::delete()
  ↓
If authorized: $groceryList->delete() [soft delete]
  ↓
Database sets deleted_at = now()
  ↓
Cascade: All grocery_items.deleted_at = now()
  ↓
Flash success message to session
  ↓
Redirect to grocery-lists.index
```

### 3. User Cancels Deletion
```
User clicks "Cancel" button in modal
  ↓
Livewire fires wire:click="cancelDelete"
  ↓
Component sets $showDeleteConfirm = false
  ↓
Modal closes, no database changes
```

---

## Queries Affected by Soft Deletes

### Before (No Soft Deletes)
```php
// Get all grocery lists
GroceryList::all(); // Returns all records

// Get user's lists
$user->groceryLists; // Returns all user's lists

// Find by ID
GroceryList::find($id); // Returns record if exists
```

### After (With Soft Deletes)
```php
// Get all active grocery lists (default behavior)
GroceryList::all(); // Returns only non-deleted records

// Get user's active lists
$user->groceryLists; // Returns only non-deleted lists

// Find by ID (excludes soft deleted)
GroceryList::find($id); // Returns null if soft deleted

// Include soft deleted
GroceryList::withTrashed()->get(); // Returns all including deleted
GroceryList::onlyTrashed()->get(); // Returns only deleted records

// Restore soft deleted
$groceryList->restore(); // Un-deletes the record
```

### Route Model Binding
```php
// Automatically excludes soft deleted models
Route::get('/grocery-lists/{groceryList}', Show::class);

// If user accesses URL of deleted list:
// Laravel returns 404 ModelNotFoundException
```

---

## Database Indexes

### Recommended Indexes
```sql
-- For soft delete queries (performance)
CREATE INDEX idx_grocery_lists_deleted_at ON grocery_lists(deleted_at);
CREATE INDEX idx_grocery_items_deleted_at ON grocery_items(deleted_at);

-- Existing indexes remain unchanged
-- These support queries like: WHERE deleted_at IS NULL
```

---

## State Transitions

### GroceryList States

```
[Active List]
   |
   | User clicks delete & confirms
   |
   v
[Soft Deleted]
   |
   | deleted_at = timestamp
   | Related items also soft deleted
   |
   | (Future: restore functionality)
   v
[Restored] (not in current scope)
```

**State Rules**:
- Active → Soft Deleted: Requires authorization (owner only)
- Soft Deleted → Active: Not implemented in this feature (future scope)
- Soft Deleted → Hard Deleted: Not implemented (manual DB cleanup only)

---

## Validation Rules

### Delete Operation

**Authorization**:
- User must be authenticated
- User must own the grocery list (`user_id` matches `auth()->id()`)
- Enforced via `GroceryListPolicy::delete()` method

**Business Rules**:
- No validation needed for deletion (any list can be deleted)
- No cascade restrictions (items are automatically deleted)
- No confirmation required at model level (handled in UI)

**Edge Cases Handled**:
- Already deleted list: Route model binding returns 404
- Concurrent deletion: Last delete wins (idempotent operation)
- Session expired during modal: Livewire handles session refresh
- Unauthorized access: Policy returns 403 Forbidden

---

## Rollback / Recovery

### Data Recovery (Future Scope)

While not in current feature scope, soft deletes enable:

```php
// Restore a soft deleted list
$groceryList = GroceryList::withTrashed()->find($id);
$groceryList->restore();

// Related items are also restored
$groceryList->groceryItems()->restore();
```

### Migration Rollback

```php
// Down method for grocery_lists migration
public function down()
{
    Schema::table('grocery_lists', function (Blueprint $table) {
        $table->dropColumn('deleted_at');
    });
}

// Down method for grocery_items migration
public function down()
{
    Schema::table('grocery_items', function (Blueprint $table) {
        $table->dropColumn('deleted_at');
    });
}
```

---

## Summary

**Entities Modified**: 2 (GroceryList, GroceryItem)
**New Columns**: 2 (`deleted_at` on each table)
**New Relationships**: 0 (using existing relationships)
**Breaking Changes**: 0 (soft deletes are backwards compatible)
**Migration Files**: 2 (one per table)

This data model supports the delete grocery list feature while maintaining data integrity, providing audit trail, and enabling future restoration functionality.
