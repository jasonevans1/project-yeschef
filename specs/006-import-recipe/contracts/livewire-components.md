# Livewire Component Contracts

**Feature**: Import Recipe from URL
**Date**: 2025-11-30

## Overview

This document defines the Livewire component interface contracts for the recipe import feature. Since this is a Livewire-first application, there are no traditional REST API endpoints. Instead, user interactions are handled through Livewire component methods and properties.

---

## Component 1: Import (URL Input Page)

**Class**: `App\Livewire\Recipe\Import`
**Route**: `/recipes/import` (GET)
**View**: `resources/views/livewire/recipe/import.blade.php`
**Middleware**: `auth` (authenticated users only)

### Purpose

Provides an interface for users to input a URL and initiate the recipe import process.

### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `url` | string | '' | The URL input by the user |

### Public Methods

#### `import()`

Validates the URL, fetches the page, parses recipe data, and redirects to preview.

**Livewire Action**: `wire:click="import"`

**Validation Rules**:
```php
[
    'url' => ['required', 'url', 'max:2048']
]
```

**Process Flow**:
1. Validate URL format
2. Check for duplicate imports (optional - P3)
3. Fetch HTML from URL using `RecipeFetcher`
4. Parse schema.org data using `MicrodataParser`
5. Validate that recipe data was found
6. Store parsed data in session
7. Redirect to `ImportPreview` component with session data

**Success Response**:
- Redirect to `/recipes/import/preview` route
- Session contains parsed recipe data

**Error Responses**:

| Error | Message | User Action |
|-------|---------|-------------|
| Invalid URL format | "Please enter a valid URL." | Re-enter URL |
| URL fetch failed | "Could not access the page. Please check the URL and try again." | Try different URL |
| No recipe data found | "No recipe data found on this page. Please make sure the page contains a recipe with schema.org markup." | Try different URL |
| Connection timeout | "The request timed out. Please try again or use a different URL." | Retry or try different URL |
| Duplicate import (P3) | "You have already imported a recipe from this URL. View existing recipe or import a new copy?" | View existing or proceed |

### Component State

**Loading states**:
- `wire:loading` active during fetch/parse operation
- Display loading spinner and "Fetching recipe..." message

**Wire directives**:
- `wire:model="url"` on input field
- `wire:submit="import"` on form
- `wire:loading` on button and loading indicator

### Example Component Structure

```php
<?php

namespace App\Livewire\Recipe;

use App\Services\RecipeImporter\RecipeImportService;
use Livewire\Component;
use Illuminate\Support\Facades\Session;

class Import extends Component
{
    public string $url = '';

    protected array $rules = [
        'url' => ['required', 'url', 'max:2048'],
    ];

    protected array $messages = [
        'url.required' => 'Please enter a URL.',
        'url.url' => 'Please enter a valid URL.',
        'url.max' => 'URL is too long.',
    ];

    public function import(): void
    {
        $this->validate();

        try {
            $importer = app(RecipeImportService::class);
            $recipeData = $importer->fetchAndParse($this->url);

            if (!$recipeData) {
                $this->addError('url', 'No recipe data found on this page. Please make sure the page contains a recipe with schema.org markup.');
                return;
            }

            // Store in session for preview
            Session::put('import_preview', $recipeData);

            $this->redirect(route('recipes.import.preview'));

        } catch (\Exception $e) {
            $this->addError('url', 'Could not access the page. Please check the URL and try again.');
        }
    }

    public function render()
    {
        return view('livewire.recipe.import');
    }
}
```

---

## Component 2: ImportPreview (Preview & Confirmation)

**Class**: `App\Livewire\Recipe\ImportPreview`
**Route**: `/recipes/import/preview` (GET)
**View**: `resources/views/livewire/recipe/import-preview.blade.php`
**Middleware**: `auth` (authenticated users only)

### Purpose

Displays extracted recipe data for user review and provides confirmation/cancellation actions.

### Public Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `recipeData` | array | [] | The parsed recipe data from session |

### Lifecycle Hooks

#### `mount()`

Loads recipe data from session. Redirects to import page if no data found.

```php
public function mount(): void
{
    $this->recipeData = Session::get('import_preview');

    if (!$this->recipeData) {
        $this->redirect(route('recipes.import'));
    }
}
```

### Public Methods

#### `confirmImport()`

Creates recipe record and associated ingredient records in database.

**Livewire Action**: `wire:click="confirmImport"`

**Process Flow**:
1. Sanitize all text fields (XSS prevention)
2. Create Recipe model with user_id = auth()->id()
3. Parse and create RecipeIngredient records
4. Clear session data
5. Redirect to recipe show page

**Success Response**:
- Recipe created in database
- Redirect to `/recipes/{id}` route
- Flash message: "Recipe imported successfully!"

**Error Responses**:

| Error | Message | User Action |
|-------|---------|-------------|
| Validation failed | "Could not import recipe. Please try again." | Return to import |
| Database error | "An error occurred while saving the recipe. Please try again." | Retry |
| Missing required data | "Required recipe data is missing. Please try importing again." | Start over |

#### `cancel()`

Abandons the import and returns to import page.

**Livewire Action**: `wire:click="cancel"`

**Process Flow**:
1. Clear session data
2. Redirect to import page or recipe index

**Response**:
- Redirect to `/recipes/import` or `/recipes`
- No database changes
- No flash message

### Component State

**Loading states**:
- `wire:loading` active during database operations
- Display loading spinner and "Importing recipe..." message

**Wire directives**:
- `wire:click="confirmImport"` on confirm button
- `wire:click="cancel"` on cancel button
- `wire:loading` on buttons and loading indicator

### Recipe Data Structure

The `recipeData` array contains the following structure (matches schema.org Recipe):

```php
[
    'name' => string,              // Required
    'description' => ?string,
    'prepTime' => ?int,            // Minutes
    'cookTime' => ?int,            // Minutes
    'totalTime' => ?int,           // Minutes
    'servings' => int,
    'cuisine' => ?string,
    'mealType' => ?string,         // Enum: breakfast, lunch, dinner, snack
    'ingredients' => [             // Array of strings
        '2 cups flour',
        '1 tablespoon salt',
        // ...
    ],
    'instructions' => string,      // Required, already flattened
    'imageUrl' => ?string,
    'sourceUrl' => string,         // Original import URL
]
```

### Example Component Structure

```php
<?php

namespace App\Livewire\Recipe;

use App\Models\Recipe;
use App\Services\RecipeImporter\RecipeSanitizer;
use App\Services\RecipeImporter\IngredientParser;
use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class ImportPreview extends Component
{
    public array $recipeData = [];

    public function mount(): void
    {
        $this->recipeData = Session::get('import_preview');

        if (!$this->recipeData) {
            $this->redirect(route('recipes.import'));
        }
    }

    public function confirmImport(): void
    {
        try {
            DB::transaction(function () {
                // Sanitize data
                $sanitizer = app(RecipeSanitizer::class);
                $cleanData = $sanitizer->sanitize($this->recipeData);

                // Create recipe
                $recipe = Recipe::create([
                    'user_id' => auth()->id(),
                    'name' => $cleanData['name'],
                    'description' => $cleanData['description'],
                    'prep_time' => $cleanData['prepTime'],
                    'cook_time' => $cleanData['cookTime'],
                    'servings' => $cleanData['servings'],
                    'cuisine' => $cleanData['cuisine'],
                    'meal_type' => $cleanData['mealType'],
                    'instructions' => $cleanData['instructions'],
                    'image_url' => $cleanData['imageUrl'],
                    'source_url' => $cleanData['sourceUrl'],
                ]);

                // Parse and create ingredients
                $parser = app(IngredientParser::class);
                foreach ($cleanData['ingredients'] as $index => $ingredientText) {
                    $parsed = $parser->parse($ingredientText);
                    $recipe->recipeIngredients()->create([
                        'ingredient_id' => $parsed['ingredient_id'],
                        'quantity' => $parsed['quantity'],
                        'unit' => $parsed['unit'],
                        'notes' => $parsed['notes'],
                        'sort_order' => $index,
                    ]);
                }

                // Clear session
                Session::forget('import_preview');

                // Redirect to recipe
                session()->flash('message', 'Recipe imported successfully!');
                $this->redirect(route('recipes.show', $recipe));
            });
        } catch (\Exception $e) {
            $this->addError('import', 'An error occurred while saving the recipe. Please try again.');
        }
    }

    public function cancel(): void
    {
        Session::forget('import_preview');
        $this->redirect(route('recipes.import'));
    }

    public function render()
    {
        return view('livewire.recipe.import-preview');
    }
}
```

---

## Service Layer Contracts

While not Livewire components, these service classes are critical to the import workflow:

### RecipeImportService

**Namespace**: `App\Services\RecipeImporter`

**Purpose**: Orchestrates the fetch, parse, transform, and validate workflow.

**Public Methods**:

#### `fetchAndParse(string $url): ?array`

Fetches URL, parses recipe data, and transforms to array format.

**Parameters**:
- `$url` - The URL to fetch

**Returns**:
- Array of recipe data (if found)
- null if no recipe data found

**Throws**:
- `ConnectionException` - If timeout or network error
- `InvalidArgumentException` - If URL is invalid

**Example**:
```php
$service = new RecipeImportService(
    new RecipeFetcher(),
    new MicrodataParser()
);

$recipeData = $service->fetchAndParse('https://example.com/recipe');
```

### RecipeFetcher

**Namespace**: `App\Services\RecipeImporter`

**Purpose**: HTTP client wrapper for fetching external URLs.

**Public Methods**:

#### `fetch(string $url): string`

Fetches HTML content from URL.

**Parameters**:
- `$url` - The URL to fetch

**Returns**:
- HTML content as string

**Throws**:
- `ConnectionException` - If timeout or connection error
- `RequestException` - If HTTP error (4xx, 5xx)

### MicrodataParser

**Namespace**: `App\Services\RecipeImporter`

**Purpose**: Extracts schema.org Recipe data from HTML.

**Public Methods**:

#### `parse(string $html): ?array`

Parses HTML and extracts recipe data.

**Parameters**:
- `$html` - HTML content to parse

**Returns**:
- Array of recipe data (if found)
- null if no recipe data found

**Example**:
```php
$parser = new MicrodataParser();
$recipeData = $parser->parse($htmlContent);

if ($recipeData) {
    $name = $recipeData['name'];
    $ingredients = $recipeData['ingredients'];
}
```

### RecipeSanitizer

**Namespace**: `App\Services\RecipeImporter`

**Purpose**: Sanitizes extracted recipe data to prevent XSS and injection attacks.

**Public Methods**:

#### `sanitize(array $recipeData): array`

Sanitizes all text fields in recipe data.

**Parameters**:
- `$recipeData` - Raw recipe data array

**Returns**:
- Sanitized recipe data array

**Sanitization Rules**:
- Strip all HTML tags from text fields
- Validate URL format for image_url and source_url
- Truncate strings to max lengths
- Escape special characters

---

## Routes

**File**: `routes/web.php`

```php
use App\Livewire\Recipe\Import;
use App\Livewire\Recipe\ImportPreview;

Route::middleware(['auth'])->group(function () {
    Route::get('/recipes/import', Import::class)->name('recipes.import');
    Route::get('/recipes/import/preview', ImportPreview::class)->name('recipes.import.preview');
});
```

---

## Testing Contracts

### Feature Tests

**File**: `tests/Feature/Recipe/ImportRecipeTest.php`

Test scenarios:
1. Authenticated user can access import page
2. Guest user redirected to login
3. Valid URL with recipe data shows preview
4. Invalid URL shows error message
5. URL without recipe data shows error
6. Confirming import creates recipe record
7. Canceling import does not create record
8. Duplicate URL shows notification (P3)

### E2E Tests

**File**: `e2e/recipe-import.spec.ts`

Test complete user journey:
1. Navigate to import page
2. Enter valid recipe URL
3. See preview with extracted data
4. Confirm import
5. Redirected to recipe show page
6. Recipe appears in database

---

## Error Handling Matrix

| Scenario | Component | Error Type | User Message | Recovery Action |
|----------|-----------|-----------|--------------|-----------------|
| Invalid URL format | Import | Validation | "Please enter a valid URL." | Re-enter |
| Network timeout | Import | Connection | "The request timed out. Please try again." | Retry |
| 404 Not Found | Import | HTTP | "Could not access the page. Please check the URL." | Try different URL |
| No recipe data | Import | Business Logic | "No recipe data found on this page." | Try different URL |
| Parse error | Import | System | "Could not parse recipe data. Please try again." | Try different URL |
| Missing required fields | ImportPreview | Validation | "Required recipe data is missing." | Start over |
| Database error | ImportPreview | System | "An error occurred while saving. Please try again." | Retry |
| Session expired | ImportPreview | Session | Redirect to import page | Start over |

---

## Summary

**Components**: 2 full-page Livewire components (Import, ImportPreview)

**Routes**: 2 authenticated routes

**Service Classes**: 4 (RecipeImportService, RecipeFetcher, MicrodataParser, RecipeSanitizer)

**User Flow**: URL input → Fetch/Parse → Preview → Confirm → Recipe created

**Next Steps**: Generate quickstart.md for developer onboarding
