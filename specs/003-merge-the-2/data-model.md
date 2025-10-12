# Data Model: Family Meal Planning Application

**Date**: 2025-10-10
**Phase**: 1 - Design & Contracts

## Overview

This document defines the complete data model for the meal planning application, including entities, relationships, validation rules, and state transitions. The model supports recipe management, meal planning, grocery list generation with manual item management, serving size adjustments, and export/sharing features.

---

## Entity Relationship Diagram

```
┌─────────┐
│  User   │
└────┬────┘
     │
     │ 1:N (owns personal recipes)
     ▼
┌──────────┐        N:M via recipe_ingredients         ┌──────────────┐
│  Recipe  │◄──────────────────────────────────────────►│  Ingredient  │
└────┬─────┘                                            └──────────────┘
     │
     │ N:M via meal_assignments
     ▼
┌───────────────┐
│  MealPlan     │
└───────┬───────┘
        │
        │ 1:1 or 1:0 (optional)
        ▼
┌───────────────┐
│ GroceryList   │
└───────┬───────┘
        │
        │ 1:N
        ▼
┌───────────────┐
│ GroceryItem   │
└───────────────┘
```

---

## Core Entities

### 1. Recipe

**Purpose**: Represents a dish with ingredients and cooking instructions. Can be system-provided (seeded) or user-created (personal).

**Table**: `recipes`

**Columns**:
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO | Primary key |
| `user_id` | BIGINT UNSIGNED | YES | NULL | Foreign key to users (NULL = system recipe) |
| `name` | VARCHAR(255) | NO | - | Recipe name (e.g., "Chicken Parmesan") |
| `description` | TEXT | YES | NULL | Brief recipe description |
| `prep_time` | INT UNSIGNED | YES | NULL | Preparation time in minutes |
| `cook_time` | INT UNSIGNED | YES | NULL | Cooking time in minutes |
| `servings` | INT UNSIGNED | NO | 4 | Default number of servings |
| `meal_type` | ENUM | YES | NULL | Options: 'breakfast', 'lunch', 'dinner', 'snack', NULL (any) |
| `cuisine` | VARCHAR(100) | YES | NULL | Cuisine type (e.g., "Italian", "Mexican") |
| `difficulty` | ENUM | YES | NULL | Options: 'easy', 'medium', 'hard', NULL |
| `dietary_tags` | JSON | YES | NULL | Array of tags (e.g., ["vegetarian", "gluten-free"]) |
| `instructions` | TEXT | NO | - | Step-by-step cooking instructions |
| `image_url` | VARCHAR(255) | YES | NULL | Optional recipe image path/URL |
| `created_at` | TIMESTAMP | NO | CURRENT | Record creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT | Record update timestamp |

**Indexes**:
- Primary: `id`
- Foreign key: `user_id` → `users.id` (ON DELETE CASCADE)
- Full-text: `(name, description)` for search
- Standard: `meal_type` for filtering
- JSON: `dietary_tags` for tag filtering (MariaDB 10.11+)

**Validation Rules** (Laravel):
```php
'name' => 'required|string|min:3|max:255',
'description' => 'nullable|string|max:1000',
'prep_time' => 'nullable|integer|min:0|max:1440', // max 24 hours
'cook_time' => 'nullable|integer|min:0|max:1440',
'servings' => 'required|integer|min:1|max:100',
'meal_type' => 'nullable|in:breakfast,lunch,dinner,snack',
'cuisine' => 'nullable|string|max:100',
'difficulty' => 'nullable|in:easy,medium,hard',
'dietary_tags' => 'nullable|array',
'dietary_tags.*' => 'string|max:50',
'instructions' => 'required|string|min:10',
'image_url' => 'nullable|url|max:255',
```

**Relationships**:
- `belongsTo(User)` via `user_id` (nullable for system recipes)
- `belongsToMany(Ingredient)` via `recipe_ingredients` pivot
- `hasMany(MealAssignment)` - recipes assigned to meal plans
- `hasManyThrough(MealPlan, MealAssignment)` - meal plans using this recipe

**Computed Attributes**:
- `total_time`: `prep_time + cook_time`
- `is_system_recipe`: `user_id === null`
- `ingredient_count`: Count of associated ingredients

**Authorization**:
- View: Anyone (system recipes), owner only (personal recipes)
- Create: Authenticated users
- Update: Owner only (not system recipes)
- Delete: Owner only (not system recipes)

---

### 2. Ingredient

**Purpose**: Represents a food item used in recipes. Normalized to prevent duplicates and enable efficient searching.

**Table**: `ingredients`

**Columns**:
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO | Primary key |
| `name` | VARCHAR(255) | NO | - | Ingredient name (e.g., "Chicken Breast", "Olive Oil") |
| `category` | ENUM | NO | 'other' | Options: see IngredientCategory enum |
| `created_at` | TIMESTAMP | NO | CURRENT | Record creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT | Record update timestamp |

**IngredientCategory Enum Values**:
- `produce` - Fruits, vegetables, herbs
- `dairy` - Milk, cheese, yogurt, butter
- `meat` - Beef, pork, lamb, poultry (raw)
- `seafood` - Fish, shellfish
- `pantry` - Dry goods, canned goods, spices, oils
- `frozen` - Frozen vegetables, frozen meals
- `bakery` - Bread, tortillas, baked goods
- `deli` - Prepared meats, cheeses
- `beverages` - Drinks, juices
- `other` - Miscellaneous items

**Indexes**:
- Primary: `id`
- Unique: `name` (case-insensitive, enforce uniqueness)
- Standard: `category` for grouping

**Validation Rules**:
```php
'name' => 'required|string|min:2|max:255|unique:ingredients,name',
'category' => 'required|in:produce,dairy,meat,seafood,pantry,frozen,bakery,deli,beverages,other',
```

**Relationships**:
- `belongsToMany(Recipe)` via `recipe_ingredients` pivot
- `hasMany(RecipeIngredient)` - pivot records with quantities

**Notes**:
- Ingredient names should be stored in lowercase for consistency (accessor/mutator)
- Category helps organize grocery lists by store section
- Consider auto-categorization based on ingredient name patterns (future enhancement)

---

### 3. RecipeIngredient (Pivot Table)

**Purpose**: Junction table connecting recipes to ingredients with quantity and unit information.

**Table**: `recipe_ingredients`

**Columns**:
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO | Primary key |
| `recipe_id` | BIGINT UNSIGNED | NO | - | Foreign key to recipes |
| `ingredient_id` | BIGINT UNSIGNED | NO | - | Foreign key to ingredients |
| `quantity` | DECIMAL(10,3) | NO | - | Amount needed (e.g., 2.5) |
| `unit` | ENUM | NO | - | Measurement unit (see MeasurementUnit enum) |
| `sort_order` | INT UNSIGNED | NO | 0 | Display order in recipe (0-indexed) |
| `notes` | VARCHAR(255) | YES | NULL | Optional notes (e.g., "finely chopped") |
| `created_at` | TIMESTAMP | NO | CURRENT | Record creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT | Record update timestamp |

**MeasurementUnit Enum Values**:

*Volume*:
- `tsp` - Teaspoon
- `tbsp` - Tablespoon
- `fl_oz` - Fluid ounce
- `cup` - Cup
- `pint` - Pint
- `quart` - Quart
- `gallon` - Gallon
- `ml` - Milliliter
- `liter` - Liter

*Weight*:
- `oz` - Ounce
- `lb` - Pound
- `gram` - Gram
- `kg` - Kilogram

*Count*:
- `whole` - Whole item (e.g., "2 whole eggs")
- `clove` - Clove (garlic)
- `slice` - Slice
- `piece` - Piece

*Non-standard*:
- `pinch` - Pinch
- `dash` - Dash
- `to_taste` - To taste

**Indexes**:
- Primary: `id`
- Foreign keys:
  - `recipe_id` → `recipes.id` (ON DELETE CASCADE)
  - `ingredient_id` → `ingredients.id` (ON DELETE RESTRICT)
- Composite: `(recipe_id, sort_order)` for ordered retrieval
- Composite unique: `(recipe_id, ingredient_id)` to prevent duplicate ingredients in same recipe

**Validation Rules**:
```php
'recipe_id' => 'required|exists:recipes,id',
'ingredient_id' => 'required|exists:ingredients,id',
'quantity' => 'required|numeric|min:0.001|max:9999.999',
'unit' => 'required|in:tsp,tbsp,fl_oz,cup,pint,quart,gallon,ml,liter,oz,lb,gram,kg,whole,clove,slice,piece,pinch,dash,to_taste',
'sort_order' => 'required|integer|min:0',
'notes' => 'nullable|string|max:255',
```

**Relationships**:
- `belongsTo(Recipe)`
- `belongsTo(Ingredient)`

---

### 4. MealPlan

**Purpose**: Represents a user's planned meals for a date range (single day to multiple weeks).

**Table**: `meal_plans`

**Columns**:
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO | Primary key |
| `user_id` | BIGINT UNSIGNED | NO | - | Foreign key to users (owner) |
| `name` | VARCHAR(255) | NO | - | Meal plan name (e.g., "Week of Oct 14") |
| `start_date` | DATE | NO | - | Plan start date |
| `end_date` | DATE | NO | - | Plan end date (inclusive) |
| `description` | TEXT | YES | NULL | Optional notes about the plan |
| `created_at` | TIMESTAMP | NO | CURRENT | Record creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT | Record update timestamp |

**Indexes**:
- Primary: `id`
- Foreign key: `user_id` → `users.id` (ON DELETE CASCADE)
- Composite: `(user_id, start_date)` for user's plans by date

**Validation Rules**:
```php
'user_id' => 'required|exists:users,id',
'name' => 'required|string|min:3|max:255',
'start_date' => 'required|date|after_or_equal:today',
'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:+28 days', // max 4 weeks
'description' => 'nullable|string|max:1000',
```

**Relationships**:
- `belongsTo(User)` - owner
- `hasMany(MealAssignment)` - assigned recipes
- `hasOne(GroceryList)` - generated grocery list (optional)
- `belongsToMany(Recipe)` via `meal_assignments`

**Computed Attributes**:
- `duration_days`: `start_date->diffInDays(end_date) + 1`
- `is_active`: `start_date <= today <= end_date`
- `is_past`: `end_date < today`
- `is_future`: `start_date > today`
- `assignment_count`: Count of meal assignments

**State Transitions**:
- **Draft** → **Active** (when `start_date` arrives)
- **Active** → **Past** (when `end_date` passes)
- No explicit state field, computed from dates

**Business Rules**:
- Date range validation: `end_date >= start_date`
- Maximum duration: 28 days (4 weeks)
- Can have overlapping meal plans (not restricted)
- Partial plans allowed (not all days/meals assigned)

---

### 5. MealAssignment

**Purpose**: Junction table connecting meal plans to recipes with date, meal type, and serving adjustment.

**Table**: `meal_assignments`

**Columns**:
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO | Primary key |
| `meal_plan_id` | BIGINT UNSIGNED | NO | - | Foreign key to meal_plans |
| `recipe_id` | BIGINT UNSIGNED | NO | - | Foreign key to recipes |
| `date` | DATE | NO | - | Assignment date (within meal plan date range) |
| `meal_type` | ENUM | NO | - | Options: 'breakfast', 'lunch', 'dinner', 'snack' |
| `serving_multiplier` | DECIMAL(5,2) | NO | 1.00 | Serving size adjustment (1.0 = original) |
| `notes` | TEXT | YES | NULL | Optional notes for this meal |
| `created_at` | TIMESTAMP | NO | CURRENT | Record creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT | Record update timestamp |

**MealType Enum Values**:
- `breakfast` - Morning meal
- `lunch` - Midday meal
- `dinner` - Evening meal
- `snack` - Snack/dessert

**Indexes**:
- Primary: `id`
- Foreign keys:
  - `meal_plan_id` → `meal_plans.id` (ON DELETE CASCADE)
  - `recipe_id` → `recipes.id` (ON DELETE RESTRICT - preserve in meal plans)
- Composite: `(meal_plan_id, date, meal_type)` for calendar queries
- Composite unique: `(meal_plan_id, date, meal_type)` - one recipe per slot

**Validation Rules**:
```php
'meal_plan_id' => 'required|exists:meal_plans,id',
'recipe_id' => 'required|exists:recipes,id',
'date' => 'required|date|between:meal_plan.start_date,meal_plan.end_date',
'meal_type' => 'required|in:breakfast,lunch,dinner,snack',
'serving_multiplier' => 'required|numeric|min:0.25|max:10.00',
'notes' => 'nullable|string|max:500',
```

**Relationships**:
- `belongsTo(MealPlan)`
- `belongsTo(Recipe)`

**Business Rules**:
- One recipe per meal slot (meal_plan_id + date + meal_type must be unique)
- Date must be within meal plan's start_date and end_date
- Serving multiplier range: 0.25x to 10x (¼ to 10 times original servings)
- Same recipe can be assigned to multiple slots (different dates/meals)

---

### 6. GroceryList

**Purpose**: Represents a shopping list, either generated from a meal plan or created standalone.

**Table**: `grocery_lists`

**Columns**:
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO | Primary key |
| `user_id` | BIGINT UNSIGNED | NO | - | Foreign key to users (owner) |
| `meal_plan_id` | BIGINT UNSIGNED | YES | NULL | Foreign key to meal_plans (NULL = standalone) |
| `name` | VARCHAR(255) | NO | - | List name (e.g., "Weekly Groceries", "Party Shopping") |
| `generated_at` | TIMESTAMP | NO | CURRENT | Initial generation/creation timestamp |
| `regenerated_at` | TIMESTAMP | YES | NULL | Last regeneration timestamp (if from meal plan) |
| `share_token` | CHAR(36) | YES | NULL | UUID for shareable link (NULL = not shared) |
| `share_expires_at` | TIMESTAMP | YES | NULL | Expiration for share link |
| `created_at` | TIMESTAMP | NO | CURRENT | Record creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT | Record update timestamp |

**Indexes**:
- Primary: `id`
- Foreign keys:
  - `user_id` → `users.id` (ON DELETE CASCADE)
  - `meal_plan_id` → `meal_plans.id` (ON DELETE SET NULL)
- Standard: `share_token` for sharing feature
- Composite: `(user_id, created_at)` for user's lists chronologically

**Validation Rules**:
```php
'user_id' => 'required|exists:users,id',
'meal_plan_id' => 'nullable|exists:meal_plans,id',
'name' => 'required|string|min:3|max:255',
'share_token' => 'nullable|uuid',
'share_expires_at' => 'nullable|date|after:now',
```

**Relationships**:
- `belongsTo(User)` - owner
- `belongsTo(MealPlan)` - source meal plan (nullable)
- `hasMany(GroceryItem)` - list items

**Computed Attributes**:
- `is_standalone`: `meal_plan_id === null`
- `is_meal_plan_linked`: `meal_plan_id !== null`
- `is_shared`: `share_token !== null`
- `share_url`: `route('grocery-lists.shared', $share_token)` (if shared)
- `total_items`: Count of grocery items
- `completed_items`: Count where `purchased = true`
- `completion_percentage`: `(completed_items / total_items) * 100`

**State Transitions**:
- **Empty** → **In Progress** (when items added)
- **In Progress** → **Completed** (when all items purchased)
- Can regenerate from meal plan (resets to In Progress)

**Business Rules**:
- Meal plan link is optional (supports standalone lists)
- Can only regenerate if `meal_plan_id` is not null
- Share token generated when user creates shareable link
- Share expiration default: 7 days (configurable)

---

### 7. GroceryItem

**Purpose**: Represents an item on a grocery list, either generated from recipes or manually added.

**Table**: `grocery_items`

**Columns**:
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT UNSIGNED | NO | AUTO | Primary key |
| `grocery_list_id` | BIGINT UNSIGNED | NO | - | Foreign key to grocery_lists |
| `name` | VARCHAR(255) | NO | - | Item name (e.g., "Chicken Breast", "Milk") |
| `quantity` | DECIMAL(10,3) | YES | NULL | Amount needed (NULL = unspecified) |
| `unit` | ENUM | YES | NULL | Measurement unit (same as RecipeIngredient) |
| `category` | ENUM | NO | 'other' | Same as IngredientCategory enum |
| `source_type` | ENUM | NO | 'manual' | Options: 'generated', 'manual' |
| `original_values` | JSON | YES | NULL | Stores {quantity, unit} before user edit (for generated items) |
| `purchased` | BOOLEAN | NO | FALSE | Whether item has been purchased/checked off |
| `purchased_at` | TIMESTAMP | YES | NULL | When item was marked purchased |
| `notes` | TEXT | YES | NULL | Optional user notes |
| `sort_order` | INT UNSIGNED | NO | 0 | Display order within category |
| `deleted_at` | TIMESTAMP | YES | NULL | Soft delete for regeneration tracking |
| `created_at` | TIMESTAMP | NO | CURRENT | Record creation timestamp |
| `updated_at` | TIMESTAMP | NO | CURRENT | Record update timestamp |

**SourceType Enum Values**:
- `generated` - Item created by aggregating recipe ingredients
- `manual` - Item manually added by user

**Indexes**:
- Primary: `id`
- Foreign key: `grocery_list_id` → `grocery_lists.id` (ON DELETE CASCADE)
- Composite: `(grocery_list_id, category, sort_order)` for organized display
- Standard: `deleted_at` for soft delete queries

**Validation Rules**:
```php
'grocery_list_id' => 'required|exists:grocery_lists,id',
'name' => 'required|string|min:1|max:255',
'quantity' => 'nullable|numeric|min:0.001|max:9999.999',
'unit' => 'nullable|in:tsp,tbsp,fl_oz,cup,pint,quart,gallon,ml,liter,oz,lb,gram,kg,whole,clove,slice,piece,pinch,dash,to_taste',
'category' => 'required|in:produce,dairy,meat,seafood,pantry,frozen,bakery,deli,beverages,other',
'source_type' => 'required|in:generated,manual',
'original_values' => 'nullable|json',
'purchased' => 'boolean',
'notes' => 'nullable|string|max:500',
'sort_order' => 'required|integer|min:0',
```

**Relationships**:
- `belongsTo(GroceryList)`

**Soft Deletes**:
- Uses Laravel's `SoftDeletes` trait
- `deleted_at` timestamp tracks user deletions
- Soft-deleted generated items persist to prevent re-adding during regeneration
- Manual items hard-delete (permanent removal)

**Computed Attributes**:
- `is_generated`: `source_type === 'generated'`
- `is_manual`: `source_type === 'manual'`
- `is_edited`: `original_values !== null`
- `display_quantity`: Formatted quantity with unit (e.g., "2½ cups")

**Business Rules**:
- Quantity and unit are optional (e.g., "Milk" without amount)
- Generated items track original values when user edits them
- Soft delete only applies to generated items (for regeneration logic)
- Manual items delete permanently when user removes them
- Category determines grouping in grocery list view

---

## Enums Summary

### MealType
```php
enum MealType: string
{
    case BREAKFAST = 'breakfast';
    case LUNCH = 'lunch';
    case DINNER = 'dinner';
    case SNACK = 'snack';
}
```

### IngredientCategory
```php
enum IngredientCategory: string
{
    case PRODUCE = 'produce';
    case DAIRY = 'dairy';
    case MEAT = 'meat';
    case SEAFOOD = 'seafood';
    case PANTRY = 'pantry';
    case FROZEN = 'frozen';
    case BAKERY = 'bakery';
    case DELI = 'deli';
    case BEVERAGES = 'beverages';
    case OTHER = 'other';
}
```

### MeasurementUnit
```php
enum MeasurementUnit: string
{
    // Volume
    case TSP = 'tsp';
    case TBSP = 'tbsp';
    case FL_OZ = 'fl_oz';
    case CUP = 'cup';
    case PINT = 'pint';
    case QUART = 'quart';
    case GALLON = 'gallon';
    case ML = 'ml';
    case LITER = 'liter';

    // Weight
    case OZ = 'oz';
    case LB = 'lb';
    case GRAM = 'gram';
    case KG = 'kg';

    // Count
    case WHOLE = 'whole';
    case CLOVE = 'clove';
    case SLICE = 'slice';
    case PIECE = 'piece';

    // Non-standard
    case PINCH = 'pinch';
    case DASH = 'dash';
    case TO_TASTE = 'to_taste';
}
```

### SourceType
```php
enum SourceType: string
{
    case GENERATED = 'generated';
    case MANUAL = 'manual';
}
```

---

## Database Schema Diagram

```sql
-- Core recipe management
CREATE TABLE recipes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  prep_time INT UNSIGNED,
  cook_time INT UNSIGNED,
  servings INT UNSIGNED NOT NULL DEFAULT 4,
  meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack'),
  cuisine VARCHAR(100),
  difficulty ENUM('easy', 'medium', 'hard'),
  dietary_tags JSON,
  instructions TEXT NOT NULL,
  image_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FULLTEXT(name, description)
);

CREATE TABLE ingredients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL UNIQUE,
  category ENUM('produce', 'dairy', 'meat', 'seafood', 'pantry', 'frozen', 'bakery', 'deli', 'beverages', 'other') NOT NULL DEFAULT 'other',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE recipe_ingredients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipe_id BIGINT UNSIGNED NOT NULL,
  ingredient_id BIGINT UNSIGNED NOT NULL,
  quantity DECIMAL(10,3) NOT NULL,
  unit ENUM('tsp', 'tbsp', 'fl_oz', 'cup', 'pint', 'quart', 'gallon', 'ml', 'liter', 'oz', 'lb', 'gram', 'kg', 'whole', 'clove', 'slice', 'piece', 'pinch', 'dash', 'to_taste') NOT NULL,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  notes VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE CASCADE,
  FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT,
  UNIQUE KEY unique_recipe_ingredient (recipe_id, ingredient_id)
);

-- Meal planning
CREATE TABLE meal_plans (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  start_date DATE NOT NULL,
  end_date DATE NOT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_date (user_id, start_date)
);

CREATE TABLE meal_assignments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  meal_plan_id BIGINT UNSIGNED NOT NULL,
  recipe_id BIGINT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
  serving_multiplier DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  notes TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (meal_plan_id) REFERENCES meal_plans(id) ON DELETE CASCADE,
  FOREIGN KEY (recipe_id) REFERENCES recipes(id) ON DELETE RESTRICT,
  UNIQUE KEY unique_meal_slot (meal_plan_id, date, meal_type),
  INDEX idx_meal_plan_date (meal_plan_id, date)
);

-- Grocery lists
CREATE TABLE grocery_lists (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  meal_plan_id BIGINT UNSIGNED NULL,
  name VARCHAR(255) NOT NULL,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  regenerated_at TIMESTAMP NULL,
  share_token CHAR(36) NULL,
  share_expires_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (meal_plan_id) REFERENCES meal_plans(id) ON DELETE SET NULL,
  INDEX idx_share_token (share_token)
);

CREATE TABLE grocery_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  grocery_list_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(255) NOT NULL,
  quantity DECIMAL(10,3),
  unit ENUM('tsp', 'tbsp', 'fl_oz', 'cup', 'pint', 'quart', 'gallon', 'ml', 'liter', 'oz', 'lb', 'gram', 'kg', 'whole', 'clove', 'slice', 'piece', 'pinch', 'dash', 'to_taste'),
  category ENUM('produce', 'dairy', 'meat', 'seafood', 'pantry', 'frozen', 'bakery', 'deli', 'beverages', 'other') NOT NULL DEFAULT 'other',
  source_type ENUM('generated', 'manual') NOT NULL DEFAULT 'manual',
  original_values JSON,
  purchased BOOLEAN NOT NULL DEFAULT FALSE,
  purchased_at TIMESTAMP NULL,
  notes TEXT,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  deleted_at TIMESTAMP NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (grocery_list_id) REFERENCES grocery_lists(id) ON DELETE CASCADE,
  INDEX idx_list_category_order (grocery_list_id, category, sort_order),
  INDEX idx_deleted_at (deleted_at)
);
```

---

## Migration Order

To maintain referential integrity, migrations must be created in this order:

1. `create_recipes_table` (depends on users)
2. `create_ingredients_table` (standalone)
3. `create_recipe_ingredients_table` (depends on recipes, ingredients)
4. `create_meal_plans_table` (depends on users)
5. `create_meal_assignments_table` (depends on meal_plans, recipes)
6. `create_grocery_lists_table` (depends on users, meal_plans)
7. `create_grocery_items_table` (depends on grocery_lists)

---

## Data Seeding Strategy

**System Recipes** (RecipeSeeder):
- Seed 100-1000 system recipes (user_id = NULL)
- Include variety of cuisines, meal types, difficulties
- Ingredients created via factory or from normalized list
- Diverse dietary tags (vegetarian, vegan, gluten-free, dairy-free, etc.)

**Test Data** (Factories):
- RecipeFactory: Generates user recipes with random ingredients
- MealPlanFactory: Creates meal plans with date ranges
- GroceryListFactory: Creates lists with items

**Development Seeding**:
```bash
php artisan db:seed --class=RecipeSeeder
```

---

## Next Steps

With the data model defined:
1. ✅ Generate migrations from this specification
2. ✅ Create Eloquent models with relationships
3. ✅ Implement validation rules in models/requests
4. ✅ Create factories for testing
5. ➡️ Proceed to API contracts (contracts/)
