# Research: Import Recipe from URL

**Feature**: Import Recipe from URL
**Branch**: 006-import-recipe
**Date**: 2025-11-30

## Overview

This document captures research findings that resolve NEEDS CLARIFICATION items from the Technical Context section of the implementation plan. All decisions are based on Laravel 12 best practices, existing project dependencies, and the specific requirements of the recipe import feature.

## Research Tasks Completed

1. HTTP client selection for fetching external URLs
2. HTML parser library for schema.org microdata extraction

---

## Decision 1: HTTP Client for URL Fetching

### Decision

**Laravel HTTP Client (Http Facade)** - Use `Illuminate\Support\Facades\Http`

### Rationale

- **Built-in and Already Available**: Laravel 12 includes a built-in HTTP client that wraps Guzzle HTTP client (v7.10.0 already installed as transitive dependency via `laravel/framework`)
- **Perfectly Matches Requirements**:
  - Native timeout support via `timeout()` method (30-second default matches requirement)
  - Connection timeout via `connectTimeout()` method (10-second default)
  - Automatic redirect handling (up to 5 redirects by default)
  - Clean error handling without exceptions for 4xx/5xx responses
- **Laravel 12 Best Practices**: Documentation explicitly recommends HTTP facade as the Laravel-native way to make HTTP requests
- **No Additional Dependencies**: Guzzle already included, no need to modify composer.json
- **Superior Developer Experience**:
  - Expressive, minimal API for common use cases
  - Built-in testing support with `Http::fake()` for mocking
  - Seamless integration with Laravel service container
  - Consistent with project's existing facade usage patterns

### Alternatives Considered

| Alternative | Pros | Cons | Verdict |
|-------------|------|------|---------|
| Direct Guzzle | More low-level control | Less Laravel-native, throws exceptions by default, more verbose, violates Laravel Boost guidelines | ❌ Not recommended |
| cURL (native PHP) | No dependencies | Very low-level, requires boilerplate, difficult to test, poor error handling | ❌ Not recommended |
| file_get_contents() | Simple for basic GET | Limited features, poor error handling, no timeout control | ❌ Not recommended |

### Implementation Notes

**Basic Usage:**
```php
use Illuminate\Support\Facades\Http;

$response = Http::timeout(30)
    ->connectTimeout(10)
    ->get('https://example.com/recipe');

if ($response->successful()) {
    $html = $response->body();
    // Process recipe...
} else if ($response->failed()) {
    Log::error('Failed to fetch recipe', [
        'url' => $url,
        'status' => $response->status()
    ]);
}
```

**Error Handling:**
- `ConnectionException` thrown if timeout exceeded or connection fails
- Use try-catch for connection errors
- Use `$response->successful()`, `$response->failed()` for HTTP status checking
- `$response->throw()` or `$response->throwIf()` for specific condition exceptions

**Testing Support:**
```php
Http::fake([
    'example.com/*' => Http::response($mockHtml, 200),
    'failing-site.com/*' => Http::response('Not Found', 404),
]);
```

**Redirect Handling:**
- Guzzle automatically follows redirects (up to 5 by default)
- Customizable: `withOptions(['allow_redirects' => ['max' => 10]])`

---

## Decision 2: HTML Parser for schema.org Microdata Extraction

### Decision

**Hybrid Approach:**
1. **Primary**: Native PHP with DOMDocument for JSON-LD parsing (no additional library)
2. **Fallback**: brick/structured-data (+ optional brick/schema) for Microdata/RDFa

### Rationale

**Why Hybrid?**
- JSON-LD is the most common format for Recipe schema (used by 90%+ of major recipe sites)
- JSON-LD is significantly easier to parse (it's just JSON in a `<script>` tag)
- Adding a library for JSON-LD parsing is overkill when native PHP handles it perfectly
- Native PHP parsing is faster than DOM traversal libraries
- Microdata/RDFa support available if needed via brick/structured-data

**Why brick/structured-data for fallback?**
- Modern PHP 8.1+ codebase (matches project's PHP 8.3)
- Actively maintained (latest release June 2025)
- Supports all three formats (JSON-LD, Microdata, RDFa Lite 1.1)
- IDE-friendly type-safe objects with optional brick/schema package
- Similar design philosophy to search engine parsers (Google, Yandex)

### Alternatives Considered

| Alternative | Pros | Cons | Verdict |
|-------------|------|------|---------|
| spatie/schema-org | Popular package | For *generating* markup, not parsing | ❌ Rejected - wrong purpose |
| crwlr/schema-org | JSON-LD specific | Adds 3+ dependencies, only JSON-LD, overkill | ❌ Rejected - unnecessary complexity |
| onetsp/RecipeParser | Recipe-specific | Last update unclear, site-specific parsers, high maintenance | ❌ Rejected - outdated |
| jkphl/micrometa | Comprehensive | Last release Feb 2023, supports PHP 5.6+ (older) | ⚠️ Viable but less modern |
| yusufkandemir/microdata-parser | W3C compliant, recent (May 2024) | Microdata-only, doesn't handle JSON-LD | ⚠️ Too narrow |
| brick/structured-data | Modern, actively maintained, all formats | Two packages, 0.x version (breaking changes possible) | ✅ Best modern option |

### Implementation Notes

#### Approach 1: Native PHP for JSON-LD (Primary - Implement First)

```php
function extractRecipeJsonLd(string $html): ?array
{
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();

    foreach ($dom->getElementsByTagName('script') as $script) {
        if ($script->getAttribute('type') === 'application/ld+json') {
            // Remove comments and newlines that break JSON parsing
            $json_txt = preg_replace('@/\*.*?\*/@', '', $script->textContent);
            $json_txt = preg_replace("/\r|\n/", " ", trim($json_txt));

            $data = json_decode($json_txt, true);

            // Handle single objects
            if (isset($data['@type']) && $data['@type'] === 'Recipe') {
                return $data;
            }

            // Handle @graph arrays
            if (isset($data['@graph']) && is_array($data['@graph'])) {
                foreach ($data['@graph'] as $item) {
                    if (isset($item['@type']) && $item['@type'] === 'Recipe') {
                        return $item;
                    }
                }
            }
        }
    }

    return null;
}
```

**Key Gotchas:**
- Remove comments (`/* */`) from JSON - breaks `json_decode()`
- Strip newlines embedded in text fields
- Handle both single Recipe objects and `@graph` arrays
- Some sites have multiple `<script type="application/ld+json">` tags - filter by `@type`

#### Approach 2: brick/structured-data (Fallback - Add Only If Needed)

**Installation (only if Microdata/RDFa support needed):**
```bash
composer require brick/structured-data:0.2.*
composer require brick/schema:0.2.*  # Optional: type-safe objects
```

**Basic Usage:**
```php
use Brick\StructuredData\Reader\MicrodataReader;
use Brick\StructuredData\Reader\RdfaLiteReader;

$readers = [
    new MicrodataReader(),
    new RdfaLiteReader(),
];

foreach ($readers as $reader) {
    $items = $reader->read($html);

    foreach ($items as $item) {
        if (in_array('https://schema.org/Recipe', $item->getTypes())) {
            $name = $item->getProperty('name')[0] ?? null;
            $ingredients = $item->getProperty('recipeIngredient') ?? [];
            break 2;
        }
    }
}
```

### Recommended Implementation Strategy

1. **Start with JSON-LD parsing** using native PHP (covers ~90% of recipe sites)
2. **Monitor for failures** during development and testing
3. **Only add brick/structured-data** if you encounter Microdata/RDFa formats
4. **Consider brick/schema** if type safety and IDE autocomplete desired

### Laravel Service Integration Pattern

```php
namespace App\Services\RecipeImporter;

class MicrodataParser
{
    public function parse(string $html): ?array
    {
        // Try JSON-LD first (most common)
        $recipe = $this->parseJsonLd($html);

        if (!$recipe) {
            // Fallback to Microdata/RDFa if brick/structured-data installed
            if (class_exists(\Brick\StructuredData\Reader\MicrodataReader::class)) {
                $recipe = $this->parseStructuredData($html);
            }
        }

        return $recipe;
    }

    private function parseJsonLd(string $html): ?array
    {
        // Native PHP implementation from above
    }

    private function parseStructuredData(string $html): ?array
    {
        // brick/structured-data implementation (if needed)
    }
}
```

### Testing Recommendations

1. Create Pest feature tests with real recipe HTML samples
2. Test against multiple recipe sites (AllRecipes, Food Network, NYT Cooking, etc.)
3. Verify handling of edge cases:
   - Missing optional fields
   - Array vs string values for same property
   - Multiple recipe objects on one page
   - Malformed JSON-LD
4. Mock HTTP responses using `Http::fake()` for reliable, fast tests

---

## Additional Findings

### Schema.org Recipe Properties

**Required fields** (from FR-006):
- `name` - Recipe title
- `recipeIngredient` - Array of ingredient strings
- `recipeInstructions` - Step-by-step instructions (can be array or string)
- `recipeYield` - Servings (e.g., "4 servings", "8")

**Optional fields** (from FR-007):
- `description` - Recipe summary
- `image` - Image URL(s) (can be string or array)
- `prepTime` - Preparation time (ISO 8601 duration, e.g., "PT30M")
- `cookTime` - Cooking time (ISO 8601 duration)
- `totalTime` - Total time (ISO 8601 duration)
- `recipeCategory` - Category (e.g., "Dinner", "Dessert")
- `recipeCuisine` - Cuisine type (e.g., "Italian", "Mexican")
- `nutrition` - Nutrition information object

**Data type handling notes:**
- Times are in ISO 8601 duration format (e.g., "PT1H30M" = 1 hour 30 minutes)
- `recipeInstructions` can be:
  - Array of strings
  - Array of HowToStep objects
  - Single string with full instructions
- `image` can be string (URL) or array of URLs or ImageObject

### Security Considerations

**Content sanitization requirements** (from FR-016):
- Strip/escape HTML from extracted text fields before storage
- Validate URLs for images
- Prevent XSS in recipe names, descriptions, ingredients, instructions
- Consider using Laravel's `Purifier` or `strip_tags()` with allowed tags

**Recommended sanitization approach:**
```php
// For text fields (name, description, ingredients, instructions)
$cleanText = strip_tags($rawText);

// For URLs (images)
$cleanUrl = filter_var($rawUrl, FILTER_VALIDATE_URL) ? $rawUrl : null;

// For HTML content that may need limited formatting
// Consider: mews/purifier package or custom allowed tags list
```

---

## Summary

All NEEDS CLARIFICATION items resolved:

1. **HTTP Client**: Laravel HTTP Client (Http facade) - already available, no new dependencies
2. **HTML Parser**: Hybrid approach - Native PHP for JSON-LD + optional brick/structured-data for Microdata/RDFa

**Next Steps** (Phase 1):
- Generate data-model.md with Recipe entity schema
- Generate API contracts for import workflow
- Generate quickstart.md for developer onboarding
- Update agent context with new technology decisions

**Dependencies to add** (if needed):
- None required initially
- Add `brick/structured-data:0.2.*` only if Microdata/RDFa support becomes necessary
