# Quickstart: Import Recipe from URL

**Feature**: Import Recipe from URL
**Branch**: 006-import-recipe
**Date**: 2025-11-30

## Overview

This guide helps developers implement the recipe import feature. Follow the test-first workflow to build each component systematically.

---

## Prerequisites

Before starting:
- [ ] DDEV environment running (`ddev start`)
- [ ] Branch checked out: `006-import-recipe`
- [ ] Read `spec.md`, `plan.md`, `research.md`, `data-model.md`, and `contracts/`
- [ ] Understand existing Recipe model (`app/Models/Recipe.php`)

---

## Implementation Checklist

### Phase 1: Database Setup

- [ ] **1.1** Create migration for `source_url` field
  ```bash
  php artisan make:migration add_source_url_to_recipes_table
  ```
  - Add `source_url` string field (nullable, max 2048, indexed)
  - Position after `image_url`

- [ ] **1.2** Update Recipe model
  - Add `source_url` to `$fillable` array
  - Optional: Add `scopeImported()` and `scopeManual()` methods

- [ ] **1.3** Run migration
  ```bash
  php artisan migrate
  ```

- [ ] **1.4** Verify in database
  ```bash
  ddev describe  # Check database connection
  ```

**Verification**: Recipe model can save `source_url` field

---

### Phase 2: Service Layer (Test-First)

#### 2.1 RecipeFetcher Service

- [ ] **Write test first**: `tests/Unit/RecipeImporter/RecipeFetcherTest.php`
  ```bash
  php artisan make:test RecipeImporter/RecipeFetcherTest --unit --pest
  ```
  - Test successful fetch
  - Test timeout handling
  - Test invalid URL
  - Test network errors
  - Use `Http::fake()` for mocking

- [ ] **Implement**: `app/Services/RecipeImporter/RecipeFetcher.php`
  ```bash
  php artisan make:class Services/RecipeImporter/RecipeFetcher
  ```
  - Use Laravel HTTP facade (`Illuminate\Support\Facades\Http`)
  - Set 30-second timeout, 10-second connection timeout
  - Handle redirects automatically
  - Return HTML as string

- [ ] **Run tests**: `php artisan test --filter=RecipeFetcherTest`

**Verification**: All RecipeFetcher tests pass ✅

#### 2.2 MicrodataParser Service

- [ ] **Write test first**: `tests/Unit/RecipeImporter/MicrodataParserTest.php`
  ```bash
  php artisan make:test RecipeImporter/MicrodataParserTest --unit --pest
  ```
  - Test JSON-LD parsing (primary format)
  - Test missing recipe data returns null
  - Test malformed JSON handling
  - Test @graph array handling
  - Test single Recipe object handling
  - Use real recipe HTML samples as fixtures

- [ ] **Implement**: `app/Services/RecipeImporter/MicrodataParser.php`
  ```bash
  php artisan make:class Services/RecipeImporter/MicrodataParser
  ```
  - Use native PHP DOMDocument for JSON-LD parsing
  - Extract `<script type="application/ld+json">` tags
  - Parse JSON, find Recipe objects
  - Transform to normalized array format
  - Handle edge cases (comments in JSON, @graph arrays)

- [ ] **Run tests**: `php artisan test --filter=MicrodataParserTest`

**Verification**: All MicrodataParser tests pass ✅

#### 2.3 RecipeSanitizer Service

- [ ] **Write test first**: `tests/Unit/RecipeImporter/RecipeSanitizerTest.php`
  ```bash
  php artisan make:test RecipeImporter/RecipeSanitizerTest --unit --pest
  ```
  - Test HTML tag stripping from text fields
  - Test URL validation
  - Test XSS prevention
  - Test field truncation to max lengths

- [ ] **Implement**: `app/Services/RecipeImporter/RecipeSanitizer.php`
  ```bash
  php artisan make:class Services/RecipeImporter/RecipeSanitizer
  ```
  - Use `strip_tags()` for text fields
  - Use `filter_var(FILTER_VALIDATE_URL)` for URLs
  - Truncate strings to database field limits
  - Return sanitized array

- [ ] **Run tests**: `php artisan test --filter=RecipeSanitizerTest`

**Verification**: All RecipeSanitizer tests pass ✅

#### 2.4 RecipeImportService (Orchestrator)

- [ ] **Write test first**: `tests/Feature/Recipe/RecipeImportServiceTest.php`
  ```bash
  php artisan make:test Recipe/RecipeImportServiceTest --pest
  ```
  - Test complete fetch → parse → transform flow
  - Test error handling at each stage
  - Mock HTTP responses with `Http::fake()`
  - Use real recipe HTML samples

- [ ] **Implement**: `app/Services/RecipeImporter/RecipeImportService.php`
  ```bash
  php artisan make:class Services/RecipeImporter/RecipeImportService
  ```
  - Constructor inject: RecipeFetcher, MicrodataParser
  - Method: `fetchAndParse(string $url): ?array`
  - Orchestrate: fetch → parse → transform
  - Transform ISO 8601 durations to minutes
  - Transform recipe yield to servings integer
  - Flatten recipe instructions to text
  - Map category to meal_type enum
  - Return normalized array or null

- [ ] **Run tests**: `php artisan test --filter=RecipeImportServiceTest`

**Verification**: All RecipeImportService tests pass ✅

---

### Phase 3: Livewire Components (Test-First)

#### 3.1 Import Component (URL Input)

- [ ] **Write test first**: `tests/Feature/Recipe/ImportRecipeTest.php`
  ```bash
  php artisan make:test Recipe/ImportRecipeTest --pest
  ```
  - Test authenticated user can access
  - Test guest redirected to login
  - Test URL validation errors
  - Test successful fetch redirects to preview
  - Test "no recipe data" error
  - Test network error handling
  - Use `Livewire::test()` and `Http::fake()`

- [ ] **Create component**:
  ```bash
  php artisan make:livewire Recipe/Import
  ```
  - Property: `public string $url = ''`
  - Validation: required, url, max:2048
  - Method: `import()` - validate, fetch, parse, redirect
  - Use RecipeImportService
  - Store parsed data in session
  - Redirect to preview route

- [ ] **Create view**: `resources/views/livewire/recipe/import.blade.php`
  - Use Flux components: `<flux:input>`, `<flux:button>`
  - Wire directives: `wire:model="url"`, `wire:submit="import"`
  - Loading state: `wire:loading`
  - Error display: `@error('url')`

- [ ] **Add route**: `routes/web.php`
  ```php
  Route::middleware(['auth'])->group(function () {
      Route::get('/recipes/import', Import::class)->name('recipes.import');
  });
  ```

- [ ] **Run tests**: `php artisan test --filter=ImportRecipeTest`

**Verification**: All Import component tests pass ✅

#### 3.2 ImportPreview Component (Confirmation)

- [ ] **Continue test file**: `tests/Feature/Recipe/ImportRecipeTest.php`
  - Test preview page loads from session
  - Test redirect if no session data
  - Test confirm creates recipe record
  - Test confirm creates ingredient records
  - Test cancel clears session, no records created
  - Use database transactions in tests

- [ ] **Create component**:
  ```bash
  php artisan make:livewire Recipe/ImportPreview
  ```
  - Property: `public array $recipeData = []`
  - Hook: `mount()` - load from session, redirect if missing
  - Method: `confirmImport()` - sanitize, create records, redirect
  - Method: `cancel()` - clear session, redirect
  - Use RecipeSanitizer
  - Use database transaction for atomicity

- [ ] **Create view**: `resources/views/livewire/recipe/import-preview.blade.php`
  - Display recipe data in preview format
  - Use Flux components: `<flux:card>`, `<flux:button>`
  - Wire directives: `wire:click="confirmImport"`, `wire:click="cancel"`
  - Loading state: `wire:loading`

- [ ] **Add route**: `routes/web.php`
  ```php
  Route::get('/recipes/import/preview', ImportPreview::class)->name('recipes.import.preview');
  ```

- [ ] **Run tests**: `php artisan test --filter=ImportRecipeTest`

**Verification**: All ImportPreview tests pass ✅

---

### Phase 4: End-to-End Testing

- [ ] **Create E2E test**: `e2e/recipe-import.spec.ts`
  ```bash
  npx playwright codegen https://project-tabletop.ddev.site/recipes/import
  ```
  - Test complete flow: login → import page → enter URL → preview → confirm → recipe page
  - Test cancel flow: preview → cancel → back to import page
  - Test error flow: invalid URL → error message displayed
  - Use Playwright fixtures for test data

- [ ] **Run E2E tests**:
  ```bash
  npx playwright test recipe-import
  ```

**Verification**: E2E tests pass ✅

---

### Phase 5: Quality Gates

- [ ] **Run all tests**:
  ```bash
  composer test
  ```

- [ ] **Run code formatter**:
  ```bash
  vendor/bin/pint
  ```

- [ ] **Verify in browser**:
  - Visit https://project-tabletop.ddev.site/recipes/import
  - Import a real recipe from a major recipe site (AllRecipes, Food Network, NYT Cooking)
  - Verify preview displays correctly
  - Confirm import creates recipe
  - Verify recipe appears in database

- [ ] **Check for console errors**:
  - Open browser DevTools
  - No JavaScript errors during import flow

- [ ] **Verify authentication**:
  - Log out, try to access `/recipes/import`
  - Should redirect to login page

**Verification**: All quality gates pass ✅

---

## Testing Strategy

### Unit Tests

**Location**: `tests/Unit/RecipeImporter/`

Test individual service classes in isolation:
- RecipeFetcher: HTTP requests and error handling
- MicrodataParser: JSON-LD parsing logic
- RecipeSanitizer: Text sanitization and validation

**Mock external dependencies** using `Http::fake()` and fixtures.

### Feature Tests

**Location**: `tests/Feature/Recipe/`

Test Livewire components and service integration:
- Import component: URL submission and validation
- ImportPreview component: Preview and confirmation
- RecipeImportService: End-to-end service flow

**Use database transactions** with `RefreshDatabase` trait.

### E2E Tests

**Location**: `e2e/`

Test complete user journey in browser:
- Authentication flow
- Import workflow (happy path)
- Error handling (sad paths)
- UI feedback and loading states

**Use Playwright** to simulate real user interactions.

---

## Common Pitfalls

### 1. JSON-LD Parsing Issues

**Problem**: `json_decode()` fails on valid-looking JSON

**Solution**: Remove comments and newlines before decoding
```php
$json_txt = preg_replace('@/\*.*?\*/@', '', $script->textContent);
$json_txt = preg_replace("/\r|\n/", " ", trim($json_txt));
```

### 2. Session Data Persistence

**Problem**: Session data lost between components

**Solution**: Ensure session is committed after storing data
```php
Session::put('import_preview', $recipeData);
Session::save(); // Force commit
```

### 3. ISO 8601 Duration Parsing

**Problem**: Times like "PT1H30M" not converted correctly

**Solution**: Use regex to extract hours and minutes separately
```php
preg_match('/^PT(?:(\d+)H)?(?:(\d+)M)?$/i', $duration, $matches);
```

### 4. Ingredient Parsing

**Problem**: Complex ingredient strings (e.g., "1 1/2 cups flour, sifted")

**Solution**: Start simple, iterate based on real-world data
- Phase 1: Store as plain text in `notes` field
- Phase 2: Add regex parsing for quantity/unit
- Phase 3: Handle fractions and ranges

### 5. Testing with Http::fake()

**Problem**: Tests don't match real HTTP behavior

**Solution**: Use real recipe HTML in test fixtures
```php
Http::fake([
    'example.com/*' => Http::response(
        file_get_contents(__DIR__ . '/fixtures/recipe.html'),
        200
    ),
]);
```

---

## Development Workflow

### Daily Workflow

1. **Start DDEV**: `ddev start`
2. **Start dev server**: `composer dev` (runs Laravel, queue, pail, Vite)
3. **Write failing test** for next feature
4. **Implement feature** to make test pass
5. **Run tests**: `php artisan test --filter=<TestName>`
6. **Refactor** while keeping tests green
7. **Format code**: `vendor/bin/pint`
8. **Commit** with descriptive message

### Git Workflow

**Branch**: `006-import-recipe` (already checked out)

**Commit messages**:
- "Add source_url migration and model update"
- "Implement RecipeFetcher service with tests"
- "Implement MicrodataParser for JSON-LD"
- "Add Import Livewire component with tests"
- "Add ImportPreview component and E2E tests"
- "Add documentation and cleanup"

**Do NOT** push to main until all tests pass and feature is complete.

---

## Useful Commands

### Artisan Commands

```bash
# Create migration
php artisan make:migration add_source_url_to_recipes_table

# Run migrations
php artisan migrate

# Create Livewire component
php artisan make:livewire Recipe/Import

# Create class
php artisan make:class Services/RecipeImporter/RecipeFetcher

# Create test
php artisan make:test Recipe/ImportRecipeTest --pest

# Run tests
php artisan test
php artisan test --filter=ImportRecipeTest
php artisan test tests/Feature/Recipe/ImportRecipeTest.php

# Format code
vendor/bin/pint

# Tinker (for debugging)
php artisan tinker
>>> Recipe::where('source_url', '!=', null)->count();
```

### DDEV Commands

```bash
# Start environment
ddev start

# SSH into container
ddev ssh

# Describe environment
ddev describe

# View logs
ddev logs

# Stop environment
ddev stop
```

### Playwright Commands

```bash
# Run E2E tests
npx playwright test

# Run specific test file
npx playwright test recipe-import

# Run in UI mode (interactive)
npx playwright test --ui

# Generate test code (codegen)
npx playwright codegen https://project-tabletop.ddev.site/recipes/import
```

---

## Debugging Tips

### 1. Recipe Not Parsing

**Check**:
- View page source in browser, look for `<script type="application/ld+json">`
- Copy JSON-LD content, validate at https://validator.schema.org
- Check if `@type` is "Recipe" (not "Article" or other)

### 2. Tests Failing

**Check**:
- Run single test: `php artisan test --filter=test_method_name`
- Add `dd()` or `dump()` in test to inspect data
- Check test database state with `DB::table('recipes')->get()`

### 3. Livewire Not Updating

**Check**:
- `wire:model` vs `wire:model.live` (live for real-time)
- `wire:loading` targets correct action
- Session data persists between components
- Validation errors displayed with `@error`

### 4. HTTP Requests Timing Out

**Check**:
- Increase timeout: `Http::timeout(60)->get($url)`
- Check network connectivity: `curl -v $url`
- Verify DDEV has internet access

---

## Reference Documentation

### Laravel

- [Livewire Documentation](https://livewire.laravel.com/docs)
- [Laravel HTTP Client](https://laravel.com/docs/12.x/http-client)
- [Laravel Validation](https://laravel.com/docs/12.x/validation)
- [Eloquent Models](https://laravel.com/docs/12.x/eloquent)

### Testing

- [Pest Documentation](https://pestphp.com/docs)
- [Livewire Testing](https://livewire.laravel.com/docs/testing)
- [Playwright Documentation](https://playwright.dev/docs/intro)

### Schema.org

- [Recipe Schema](https://schema.org/Recipe)
- [JSON-LD Specification](https://json-ld.org/)
- [Google Recipe Validator](https://validator.schema.org/)

---

## Next Steps

After completing this checklist:

1. **Run full test suite**: `composer test` - all tests must pass
2. **Run E2E tests**: `npx playwright test` - all tests must pass
3. **Format code**: `vendor/bin/pint` - no violations
4. **Manual testing**: Import recipes from 5+ different recipe sites
5. **Code review**: Submit PR for review
6. **Documentation**: Update user-facing documentation if needed

---

## Success Criteria

✅ All Pest tests pass (`composer test`)
✅ All Playwright E2E tests pass
✅ Code formatted with Pint
✅ Can import recipe from major recipe sites (AllRecipes, Food Network, NYT Cooking)
✅ Preview displays all extracted data correctly
✅ Recipe saved to database with correct data
✅ Authentication enforced (guests redirected)
✅ Error messages clear and actionable
✅ No console errors in browser
✅ DDEV environment works without issues

**Ready to implement!** 🚀
