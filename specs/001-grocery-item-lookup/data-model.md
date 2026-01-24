# Data Model: Grocery Item Autocomplete Lookup

**Feature**: 001-grocery-item-lookup
**Created**: 2025-12-27
**Status**: Design Phase

## Overview

This data model supports autocomplete suggestions for grocery items by maintaining two sets of templates:
1. **Common Item Templates**: Global defaults available to all users
2. **User Item Templates**: Personalized item history learned from user behavior

## Entity Definitions

### CommonItemTemplate

Represents a pre-defined grocery item available to all users as autocomplete suggestions.

**Purpose**: Provide immediate value to new users by suggesting common grocery items with sensible category defaults.

**Attributes**:
- `id` (Primary Key): Unique identifier
- `name` (String, required, indexed): Item name (e.g., "milk", "bread", "eggs")
- `category` (Enum, required): Default category (produce, dairy, meat, seafood, pantry, frozen, bakery, deli, beverages, other)
- `unit` (Enum, nullable): Default measurement unit (tsp, tbsp, cup, gallon, lb, etc.)
- `default_quantity` (Decimal, nullable): Suggested default quantity (e.g., 1 gallon of milk)
- `search_keywords` (Text, nullable): Additional keywords for fuzzy matching (e.g., "whole milk" → "milk dairy")
- `usage_count` (Integer, default: 0): Track global popularity for ranking
- `created_at` (Timestamp): Record creation time
- `updated_at` (Timestamp): Last modification time

**Indexes**:
- Primary key on `id`
- Unique index on `name` (prevent duplicate common items)
- Index on `name` for LIKE queries (prefix matching for autocomplete)
- Full-text index on `name, search_keywords` for fuzzy search (optional, performance-dependent)

**Validation Rules**:
- `name`: Required, max 255 characters, unique
- `category`: Required, must be valid IngredientCategory enum value
- `unit`: Optional, must be valid MeasurementUnit enum value
- `default_quantity`: Optional, positive decimal (8,3)

**Business Rules**:
- Common templates are read-only for users (managed by system admins/seeders)
- New common items can be added via database seeders or admin interface (future)
- Usage count increments when users select this template (optional tracking)

---

### UserItemTemplate

Represents a user's personalized grocery item based on their usage history.

**Purpose**: Learn user preferences over time and provide personalized autocomplete suggestions that prioritize their actual shopping patterns.

**Attributes**:
- `id` (Primary Key): Unique identifier
- `user_id` (Foreign Key, required, indexed): Reference to users table
- `name` (String, required, indexed): Item name as entered by user (normalized to lowercase)
- `category` (Enum, required): User's preferred category for this item
- `unit` (Enum, nullable): User's preferred measurement unit
- `default_quantity` (Decimal, nullable): User's typical quantity for this item
- `usage_count` (Integer, default: 1): Number of times user added this item
- `last_used_at` (Timestamp, required): Most recent usage timestamp
- `created_at` (Timestamp): First usage timestamp
- `updated_at` (Timestamp): Last modification time

**Indexes**:
- Primary key on `id`
- Composite index on `(user_id, name)` for fast user-specific lookups (unique)
- Index on `user_id` for user's templates listing
- Index on `(user_id, usage_count DESC, last_used_at DESC)` for ranking suggestions

**Validation Rules**:
- `user_id`: Required, must reference existing user
- `name`: Required, max 255 characters
- `category`: Required, must be valid IngredientCategory enum value
- `unit`: Optional, must be valid MeasurementUnit enum value
- `default_quantity`: Optional, positive decimal (8,3)
- `usage_count`: Required, positive integer, minimum 1
- `last_used_at`: Required timestamp

**Business Rules**:
- User templates are automatically created/updated when user adds grocery items
- Templates are user-specific (isolated per user_id)
- When user adds an item:
  - If template exists for (user_id, name): increment usage_count, update last_used_at and other fields
  - If template doesn't exist: create new template with usage_count = 1
- User templates take precedence over common templates in autocomplete suggestions
- Users can manually edit/delete their templates via management UI (P3 feature)
- Soft delete support optional (could use deleted_at for "hidden" templates)

---

## Relationships

### User → UserItemTemplates (One-to-Many)

**From**: User model (existing)
**To**: UserItemTemplate model (new)
**Type**: One-to-Many (User hasMany UserItemTemplates)
**Foreign Key**: `user_item_templates.user_id` → `users.id`
**Cascade**: ON DELETE CASCADE (if user deleted, remove their templates)

**Eloquent Definition** (User model):
```php
public function itemTemplates(): HasMany
{
    return $this->hasMany(UserItemTemplate::class);
}
```

**Eloquent Definition** (UserItemTemplate model):
```php
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}
```

---

## Autocomplete Query Strategy

### Suggestion Ranking Algorithm

When user types a query (e.g., "mil"):

1. **Query user templates first**:
   ```sql
   SELECT * FROM user_item_templates
   WHERE user_id = ? AND name LIKE ?
   ORDER BY usage_count DESC, last_used_at DESC
   LIMIT 5
   ```

2. **Query common templates second** (if user templates < 5 results):
   ```sql
   SELECT * FROM common_item_templates
   WHERE name LIKE ?
   ORDER BY usage_count DESC
   LIMIT (5 - user_results_count)
   ```

3. **Merge results**:
   - User templates appear first (ranked by usage_count, then recency)
   - Common templates fill remaining slots
   - De-duplicate by name (user template wins if both exist)

4. **Fuzzy matching fallback** (if exact prefix matches < 5):
   - Apply fuzzy matching (Levenshtein distance, trigrams) to find close matches
   - Rank by similarity score

### Performance Considerations

- **Expected Query Time**: <200ms for 10,000 user templates
- **Index Usage**: Composite index on (user_id, name) enables fast LIKE prefix queries
- **Caching**: Consider caching user's top 20 most-used templates in session/Redis
- **Pagination**: Limit autocomplete results to 5-10 suggestions to reduce payload size
- **Debouncing**: Frontend debounces input (300ms) to reduce query frequency

---

## Migration Strategy

### Migration 1: Create common_item_templates

```php
Schema::create('common_item_templates', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->enum('category', ['produce', 'dairy', 'meat', 'seafood', 'pantry', 'frozen', 'bakery', 'deli', 'beverages', 'other']);
    $table->enum('unit', ['tsp', 'tbsp', 'fl_oz', 'cup', 'pint', 'quart', 'gallon', 'ml', 'liter', 'oz', 'lb', 'gram', 'kg', 'whole', 'clove', 'slice', 'piece', 'pinch', 'dash', 'to_taste'])->nullable();
    $table->decimal('default_quantity', 8, 3)->nullable();
    $table->text('search_keywords')->nullable();
    $table->unsignedInteger('usage_count')->default(0);
    $table->timestamps();

    // Indexes
    $table->index('name'); // For LIKE queries
});
```

### Migration 2: Create user_item_templates

```php
Schema::create('user_item_templates', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->enum('category', ['produce', 'dairy', 'meat', 'seafood', 'pantry', 'frozen', 'bakery', 'deli', 'beverages', 'other']);
    $table->enum('unit', ['tsp', 'tbsp', 'fl_oz', 'cup', 'pint', 'quart', 'gallon', 'ml', 'liter', 'oz', 'lb', 'gram', 'kg', 'whole', 'clove', 'slice', 'piece', 'pinch', 'dash', 'to_taste'])->nullable();
    $table->decimal('default_quantity', 8, 3)->nullable();
    $table->unsignedInteger('usage_count')->default(1);
    $table->timestamp('last_used_at');
    $table->timestamps();

    // Indexes
    $table->unique(['user_id', 'name']); // Composite unique constraint
    $table->index(['user_id', 'usage_count', 'last_used_at']); // For ranking queries
});
```

---

## State Transitions

### UserItemTemplate Lifecycle

1. **Created**: When user adds a new item for the first time
   - `usage_count = 1`
   - `last_used_at = now()`
   - `category, unit, default_quantity` copied from user's input

2. **Updated**: When user adds the same item again (matched by name)
   - `usage_count += 1`
   - `last_used_at = now()`
   - `category, unit, default_quantity` updated to latest values

3. **Manually Edited** (P3): User edits template via management UI
   - Fields updated as specified
   - `usage_count` preserved
   - `updated_at` timestamp refreshed

4. **Deleted** (P3): User deletes template via management UI
   - Hard delete (or soft delete if implemented)
   - Future autocomplete falls back to common templates

---

## Example Data

### Common Item Templates (Seed Data)

| name | category | unit | default_quantity |
|------|----------|------|------------------|
| milk | dairy | gallon | 1 |
| bread | bakery | whole | 1 |
| eggs | dairy | whole | 12 |
| banana | produce | whole | 6 |
| chicken breast | meat | lb | 2 |
| ground beef | meat | lb | 1 |
| tomato | produce | whole | 4 |
| lettuce | produce | whole | 1 |
| cheese | dairy | lb | 1 |
| pasta | pantry | lb | 1 |

### User Item Templates (Example User)

| user_id | name | category | unit | usage_count | last_used_at |
|---------|------|----------|------|-------------|--------------|
| 1 | almond milk | beverages | gallon | 15 | 2025-12-26 |
| 1 | sourdough bread | bakery | whole | 8 | 2025-12-20 |
| 1 | organic eggs | dairy | whole | 12 | 2025-12-25 |
| 1 | avocado | produce | whole | 20 | 2025-12-27 |

**Note**: User's "almond milk" categorized as "beverages" (not "dairy" like common template) shows personalization.

---

## Open Questions / Future Enhancements

1. **Fuzzy Matching Implementation**: Exact algorithm TBD in research.md (Levenshtein vs. trigrams vs. full-text search)
2. **Common Template Management**: Future admin UI to add/edit common templates
3. **Template Import/Export**: Allow users to share templates or import from external sources
4. **Multi-language Support**: Store item names in multiple languages (future internationalization)
5. **Synonyms**: Handle "soda" vs. "pop" vs. "soft drink" (search_keywords field supports this)
