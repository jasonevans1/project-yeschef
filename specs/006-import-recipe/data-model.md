# Data Model: Import Recipe from URL

**Feature**: Import Recipe from URL
**Branch**: 006-import-recipe
**Date**: 2025-11-30

## Overview

This document defines the data model changes required to support importing recipes from external URLs with schema.org microdata. The Recipe model already exists in the application - this feature adds source URL tracking and utilizes existing fields.

## Entities

### Recipe (Existing Model - Modification Required)

**Location**: `app/Models/Recipe.php`

**Current Schema** (from database/migrations/2025_10_12_190255_create_recipes_table.php):
- `id` - bigint unsigned, primary key
- `user_id` - bigint unsigned, nullable, foreign key to users table
- `name` - string
- `description` - text, nullable
- `prep_time` - unsigned integer, nullable (minutes)
- `cook_time` - unsigned integer, nullable (minutes)
- `servings` - unsigned integer, default 4
- `meal_type` - enum (breakfast, lunch, dinner, snack), nullable
- `cuisine` - string(100), nullable
- `difficulty` - enum (easy, medium, hard), nullable
- `dietary_tags` - json, nullable
- `instructions` - text
- `image_url` - string, nullable
- `timestamps` - created_at, updated_at

**Required Addition**:
- `source_url` - string, nullable - The original URL from which the recipe was imported

**Field Mapping from schema.org Recipe to Existing Schema**:

| schema.org Property | Recipe Model Field | Transformation Notes |
|---------------------|-------------------|---------------------|
| `name` | `name` | Direct mapping, string |
| `description` | `description` | Direct mapping, nullable text |
| `prepTime` | `prep_time` | Parse ISO 8601 duration to minutes (e.g., "PT30M" → 30) |
| `cookTime` | `cook_time` | Parse ISO 8601 duration to minutes |
| `totalTime` | N/A | Computed attribute exists: `getTotalTimeAttribute()` |
| `recipeYield` | `servings` | Extract number from string (e.g., "4 servings" → 4) |
| `recipeCuisine` | `cuisine` | Direct mapping, max 100 chars |
| `recipeCategory` | `meal_type` | Map category string to enum (breakfast/lunch/dinner/snack) |
| `recipeIngredient` | Via `RecipeIngredient` relationship | See RecipeIngredient entity below |
| `recipeInstructions` | `instructions` | Flatten array of HowToStep or strings to text |
| `image` | `image_url` | Take first image URL if array |
| `nutrition` | N/A | Not stored (out of scope for Phase 1) |
| N/A | `source_url` | New field - store original import URL |
| N/A | `user_id` | Set to authenticated user's ID |
| N/A | `difficulty` | Not extracted from schema.org (manual field) |
| N/A | `dietary_tags` | Not extracted from schema.org (manual field) |

**Validation Rules** (for import):

Required fields:
- `name` - string, max 255 characters
- `instructions` - string, not empty
- `user_id` - exists in users table
- `source_url` - string, valid URL format, max 2048 characters

Optional fields:
- `description` - string, max 5000 characters
- `prep_time` - integer, min 0, max 1440 (24 hours in minutes)
- `cook_time` - integer, min 0, max 1440
- `servings` - integer, min 1, max 100
- `cuisine` - string, max 100 characters
- `meal_type` - enum (breakfast, lunch, dinner, snack)
- `image_url` - string, valid URL format, max 2048 characters

**Relationships** (existing):
- `user()` - BelongsTo User
- `ingredients()` - BelongsToMany Ingredient (via recipe_ingredients pivot)
- `recipeIngredients()` - HasMany RecipeIngredient

**Computed Attributes** (existing):
- `total_time` - Sum of prep_time and cook_time
- `is_system_recipe` - True if user_id is null
- `ingredient_count` - Count of recipe ingredients

### RecipeIngredient (Existing Model - No Changes Required)

**Location**: `app/Models/RecipeIngredient.php` (assumed)

This pivot model handles the many-to-many relationship between recipes and ingredients.

**Schema** (assumed from Recipe model relationship):
- `id` - bigint unsigned, primary key
- `recipe_id` - bigint unsigned, foreign key to recipes table
- `ingredient_id` - bigint unsigned, foreign key to ingredients table
- `quantity` - decimal or string (e.g., "1.5", "2-3")
- `unit` - string (e.g., "cup", "tablespoon", "gram")
- `sort_order` - integer (for maintaining ingredient order)
- `notes` - text, nullable (e.g., "chopped", "diced")
- `timestamps` - created_at, updated_at

**Import Strategy for Ingredients**:

Since schema.org provides `recipeIngredient` as an array of strings (e.g., "2 cups flour", "1 tablespoon salt"), we need to:

1. Parse each ingredient string to extract quantity, unit, and name
2. Look up or create `Ingredient` record by name
3. Create `RecipeIngredient` record linking recipe to ingredient with quantity/unit/notes

**Parsing Strategy**:
- Input: "2 cups all-purpose flour, sifted"
- Extract: quantity="2", unit="cups", name="all-purpose flour", notes="sifted"
- This parsing logic belongs in `RecipeImportService` or dedicated `IngredientParser`

**Edge Cases**:
- Ingredient strings without quantities (e.g., "Salt and pepper to taste") → quantity=null, notes="to taste"
- Fractional quantities (e.g., "1/2 cup") → Handle common fractions or store as string
- Multiple units (e.g., "1 lb (450g)") → Use primary unit, store secondary in notes

### Ingredient (Existing Model - No Changes Required)

**Location**: `app/Models/Ingredient.php` (assumed)

**Schema** (assumed):
- `id` - bigint unsigned, primary key
- `name` - string, unique
- `category` - string, nullable (e.g., "dairy", "produce", "spices")
- `timestamps` - created_at, updated_at

No changes required for import feature. Ingredients will be looked up or created during import.

### User (Existing Model - No Changes Required)

**Location**: `app/Models/User.php`

The authenticated user who performs the import becomes the owner of the recipe via `user_id` foreign key.

No schema changes required.

---

## Database Migration Required

### Migration: Add source_url to recipes table

**File**: `database/migrations/YYYY_MM_DD_HHMMSS_add_source_url_to_recipes_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->string('source_url', 2048)->nullable()->after('image_url');
            $table->index('source_url'); // For duplicate detection (User Story 4)
        });
    }

    public function down(): void
    {
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropIndex(['source_url']);
            $table->dropColumn('source_url');
        });
    }
};
```

**Rationale**:
- `source_url` is nullable to support manually created recipes (no import source)
- Max length 2048 characters to support very long URLs
- Indexed for efficient duplicate detection queries
- Positioned after `image_url` to group URL fields together

---

## Model Updates Required

### Recipe Model Changes

**File**: `app/Models/Recipe.php`

**Add to $fillable array**:
```php
protected $fillable = [
    'user_id',
    'name',
    'description',
    'prep_time',
    'cook_time',
    'servings',
    'meal_type',
    'cuisine',
    'difficulty',
    'dietary_tags',
    'instructions',
    'image_url',
    'source_url', // NEW
];
```

**No additional casts or relationships required** - source_url is a simple string field.

**Optional: Add scope for imported recipes**:
```php
public function scopeImported($query)
{
    return $query->whereNotNull('source_url');
}

public function scopeManual($query)
{
    return $query->whereNull('source_url');
}
```

---

## Data Transformation Logic

### ISO 8601 Duration to Minutes

Schema.org uses ISO 8601 duration format for time fields (e.g., "PT1H30M" = 1 hour 30 minutes).

**Parsing Function** (belongs in `RecipeImportService` or helper):
```php
function parseIsoDuration(?string $duration): ?int
{
    if (!$duration) {
        return null;
    }

    // Match patterns like PT1H30M, PT45M, PT2H
    if (!preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?$/i', $duration, $matches)) {
        return null;
    }

    $hours = isset($matches[1]) ? (int) $matches[1] : 0;
    $minutes = isset($matches[2]) ? (int) $matches[2] : 0;

    return ($hours * 60) + $minutes;
}
```

**Test Cases**:
- "PT30M" → 30
- "PT1H" → 60
- "PT1H30M" → 90
- "PT2H15M" → 135
- null → null
- "invalid" → null

### Recipe Yield to Servings

Schema.org `recipeYield` can be:
- String: "4 servings", "Makes 12", "8 portions"
- Number: 4
- Array: ["4 servings", "2 cups"]

**Parsing Function**:
```php
function parseServings(mixed $yield): int
{
    if (is_int($yield)) {
        return $yield;
    }

    if (is_string($yield)) {
        // Extract first number found
        if (preg_match('/(\d+)/', $yield, $matches)) {
            return (int) $matches[1];
        }
    }

    if (is_array($yield) && !empty($yield)) {
        // Try first element
        return parseServings($yield[0]);
    }

    return 4; // Default fallback
}
```

### Recipe Instructions Flattening

Schema.org `recipeInstructions` can be:
- String: "Preheat oven. Mix ingredients. Bake for 30 minutes."
- Array of strings: ["Preheat oven to 350°F", "Mix dry ingredients", "Bake 30 min"]
- Array of HowToStep objects: [{"@type": "HowToStep", "text": "Preheat oven"}, ...]

**Flattening Function**:
```php
function flattenInstructions(mixed $instructions): string
{
    if (is_string($instructions)) {
        return $instructions;
    }

    if (is_array($instructions)) {
        $steps = [];
        foreach ($instructions as $index => $step) {
            if (is_string($step)) {
                $steps[] = ($index + 1) . ". " . $step;
            } elseif (is_array($step) && isset($step['text'])) {
                $steps[] = ($index + 1) . ". " . $step['text'];
            }
        }
        return implode("\n", $steps);
    }

    return '';
}
```

### Category to Meal Type Mapping

Schema.org `recipeCategory` is free-form text. Map to enum values where possible.

**Mapping Function**:
```php
function mapCategory(?string $category): ?string
{
    if (!$category) {
        return null;
    }

    $category = strtolower($category);

    $mapping = [
        'breakfast' => 'breakfast',
        'brunch' => 'breakfast',
        'lunch' => 'lunch',
        'dinner' => 'dinner',
        'supper' => 'dinner',
        'snack' => 'snack',
        'appetizer' => 'snack',
        'dessert' => 'snack',
    ];

    return $mapping[$category] ?? null;
}
```

---

## State Transitions

The recipe import process has three states:

1. **URL Submitted** → System fetches and parses HTML
2. **Preview** → User reviews extracted data, can cancel or confirm
3. **Imported** → Recipe record created in database

**State is NOT persisted** - the preview state exists only in the Livewire component session. If the user navigates away from the preview page without confirming, no database records are created.

**Data Flow**:
```
User Input (URL)
    ↓
Fetch HTML (RecipeFetcher)
    ↓
Parse Microdata (MicrodataParser)
    ↓
Transform to Recipe array
    ↓
Validate & Sanitize (RecipeSanitizer)
    ↓
Preview in Livewire component (ImportPreview)
    ↓
User confirms
    ↓
Create Recipe model + RecipeIngredient records
    ↓
Redirect to recipe show page
```

---

## Constraints and Business Rules

1. **Uniqueness**: `source_url` should be unique per user to prevent duplicate imports (User Story 4 - Priority P3)
   - Validation rule: `unique:recipes,source_url,NULL,id,user_id,{auth_user_id}`
   - Allow same URL for different users (they may have different private collections)

2. **Required Fields**: Recipe MUST have `name` and `instructions` to be valid
   - If schema.org data missing these, show error message (cannot import)

3. **Authentication**: Only authenticated users can import recipes
   - `user_id` set to `auth()->id()` at time of import
   - Route middleware: `auth`

4. **Data Sanitization**: All text fields MUST be sanitized before storage
   - Remove HTML tags from name, description, ingredients, instructions
   - Validate URL format for `source_url` and `image_url`
   - Prevent XSS, SQL injection, script injection

5. **Time Limits**: External URL fetch timeout is 30 seconds
   - Connection timeout: 10 seconds
   - Total request timeout: 30 seconds
   - Error handling if timeout exceeded

6. **Image Handling**: If multiple images in schema.org data, take the first one
   - Validate image URL format
   - Do NOT download/store image locally (just store URL)
   - Broken image URLs are user's responsibility

---

## Summary

**New Database Field**: `source_url` (string, nullable, indexed)

**Model Changes**: Add `source_url` to Recipe model $fillable array

**No New Models Required**: Recipe, RecipeIngredient, Ingredient, User models all exist

**Data Transformations Required**:
- ISO 8601 duration → minutes
- Recipe yield → servings integer
- Recipe instructions array → flat text
- Recipe category → meal_type enum
- Ingredient strings → parsed quantity/unit/name

**Next Steps** (contracts phase):
- Define HTTP endpoints for import workflow
- Specify request/response formats
- Document error responses
