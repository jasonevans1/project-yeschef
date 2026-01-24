# Research: Family Meal Planning Application

**Date**: 2025-10-10
**Phase**: 0 - Research & Design Decisions

## Overview

This document captures research findings and design decisions for implementing a meal planning application with Laravel 12, Livewire 3, and Flux components. The research focuses on Livewire patterns, data modeling, ingredient aggregation algorithms, unit conversion strategies, and testing approaches.

---

## 1. Livewire Component Architecture Patterns

### Decision: Full-Page Components with Domain-Based Organization

**Rationale**:
- Livewire 3's full-page component pattern eliminates the need for traditional controllers
- Domain-based organization (Recipes, MealPlans, GroceryLists) improves code discoverability
- Each component represents a complete user interaction flow
- Aligns with constitutional requirement for Livewire-first architecture

**Implementation Pattern**:
```php
// Route definition
Route::get('/recipes', \App\Livewire\Recipes\Index::class)->name('recipes.index');

// Component structure
class Index extends Component
{
    public function render()
    {
        return view('livewire.recipes.index');
    }
}
```

**Alternatives Considered**:
- **Traditional MVC controllers**: Rejected - violates Livewire-first principle
- **Nested components with parent-child communication**: Rejected - increases complexity, harder to test
- **Single monolithic component per domain**: Rejected - poor separation of concerns

**Best Practices**:
- Use component properties for state management (`#[Url]` for shareable state)
- Leverage Livewire's lifecycle hooks (`mount()`, `updated()`, `hydrate()`)
- Keep component methods focused on single responsibilities
- Use Livewire's `#[Validate]` attribute for inline validation rules
- Dispatch browser events for cross-component communication when needed

**References**:
- Livewire 3 documentation: Full-page components
- Laravel 12 routing with Livewire components
- Project constitution: Principle I (Livewire-First Architecture)

---

## 2. Data Modeling Strategy

### Decision: Normalized Relational Model with Pivot Tables

**Rationale**:
- Recipes and ingredients have many-to-many relationship (recipes contain many ingredients, ingredients appear in many recipes)
- Meal assignments require junction table between meal plans and recipes with additional metadata (date, meal type, serving multiplier)
- Grocery items track both generated and manual items with soft delete for regeneration tracking
- Normalization prevents data duplication and ensures consistency

**Core Relationships**:

```
User ──< Recipe (user_id for personal recipes, null for system recipes)
Recipe ──< RecipeIngredient >── Ingredient
MealPlan ──< MealAssignment >── Recipe
MealPlan ──< GroceryList ──< GroceryItem
GroceryItem references aggregated ingredients (not direct FK to maintain flexibility)
```

**Key Design Decisions**:

**2.1 Recipe-Ingredient Relationship**:
- Use pivot table `recipe_ingredients` with columns: `recipe_id`, `ingredient_id`, `quantity`, `unit`, `sort_order`
- Store quantity and unit in pivot (not on ingredient) - same ingredient can have different quantities in different recipes
- `sort_order` ensures ingredients display in recipe creation order

**2.2 Ingredient Normalization**:
- Shared `ingredients` table for ingredient names and categories
- Prevents duplicate entries ("Milk" vs "milk" vs "whole milk")
- Enables efficient searching and filtering
- Category stored at ingredient level for consistent grocery list grouping

**2.3 Meal Plan Flexibility**:
- `MealAssignment` junction table with: `meal_plan_id`, `recipe_id`, `date`, `meal_type` (enum), `serving_multiplier`
- Allows same recipe multiple times in one plan (different dates/meals)
- `serving_multiplier` stores user's portion adjustment (e.g., 1.5 for 6 servings when recipe serves 4)
- Partial plans supported (no requirement to fill all slots)

**2.4 Grocery List State Tracking**:
- `GroceryItem` includes `source_type` enum: 'generated' | 'manual'
- `original_values` JSON column tracks pre-edit values for generated items
- `deleted_at` timestamp enables soft deletes for regeneration conflict detection
- Nullable `meal_plan_id` on `GroceryList` supports standalone lists

**Alternatives Considered**:
- **Embedded JSON for ingredients**: Rejected - loses querying capability, normalization benefits
- **NoSQL document store**: Rejected - relational queries needed for aggregation, complex joins
- **Single grocery item table without source tracking**: Rejected - can't preserve manual edits during regeneration

**Migration Strategy**:
1. Create base tables: `recipes`, `ingredients`
2. Create pivot: `recipe_ingredients` (foreign keys to both)
3. Create meal planning: `meal_plans`, `meal_assignments`
4. Create grocery: `grocery_lists`, `grocery_items`
5. Seed system recipes with ingredients

**References**:
- Laravel Eloquent relationships: Many-to-Many with pivot data
- Database normalization principles (3NF)
- Soft deletes for audit trails

---

## 3. Ingredient Aggregation Algorithm

### Decision: Two-Pass Aggregation with Unit Normalization

**Rationale**:
- Grocery lists must combine duplicate ingredients across multiple recipes
- Different recipes may use different units for same ingredient (e.g., "2 cups milk" + "1 pint milk")
- Unit conversion required before aggregation
- Algorithm must be performant for meal plans with 20+ recipes

**Algorithm Design**:

**Pass 1: Collection & Normalization**
```
For each recipe in meal plan:
  For each ingredient in recipe:
    - Apply serving multiplier to quantity
    - Convert quantity to base unit (e.g., cups → fluid ounces)
    - Store: {ingredient_name, base_quantity, base_unit, category}
```

**Pass 2: Aggregation & Formatting**
```
Group by ingredient_name:
  - Sum all base_quantity values
  - Convert back to user-friendly unit (prefer original unit if reasonable)
  - Create GroceryItem with aggregated quantity
  - Assign to ingredient category for grouping
```

**Unit Conversion Strategy**:
- Define base units per measurement type:
  - Volume: fluid ounces (fl oz)
  - Weight: ounces (oz)
  - Count: each/whole
- Conversion table for common units:
  - 1 cup = 8 fl oz
  - 1 pint = 16 fl oz
  - 1 quart = 32 fl oz
  - 1 tbsp = 0.5 fl oz
  - 1 tsp = 0.167 fl oz
  - 1 lb = 16 oz
- After aggregation, convert to largest whole unit (e.g., 16 fl oz → 1 pint or 2 cups)

**Edge Case Handling**:
- **Incompatible units** (e.g., "1 cup flour" + "2 oz flour"): Keep separate, cannot safely convert volume ↔ weight
- **Non-standard measurements** ("pinch", "to taste", "dash"): Aggregate by count (e.g., "2 pinches salt")
- **Fractional results**: Round to common fractions (0.33 → ⅓, 0.5 → ½, 0.67 → ⅔)
- **Very small quantities**: Keep precision (0.25 tsp valid, don't round to 0)

**Performance Optimization**:
- Eager load relationships: `MealPlan::with('mealAssignments.recipe.recipeIngredients.ingredient')`
- Process in memory (avoid N+1 queries)
- Cache unit conversion table
- Expected complexity: O(n*m) where n=recipes, m=avg ingredients per recipe
- Target: <5 seconds for 20 recipes with 10 ingredients each = 200 items

**Alternatives Considered**:
- **Database-level aggregation (SQL GROUP BY)**: Rejected - unit conversion requires application logic
- **Single-pass streaming aggregation**: Rejected - requires complex state management, harder to test
- **Third-party unit conversion library**: Accepted for production if needed, but custom solution sufficient for culinary measurements

**Implementation**:
- Service class: `GroceryListGenerator::generate(MealPlan $mealPlan): GroceryList`
- Helper service: `IngredientAggregator::aggregate(Collection $items): Collection`
- Helper service: `UnitConverter::convert(float $quantity, string $fromUnit, string $toUnit): float`

**Testing Strategy**:
- Unit tests for `UnitConverter` (all conversion pairs)
- Unit tests for `IngredientAggregator` (duplicate detection, quantity summing)
- Feature test for `GroceryListGenerator` (end-to-end with sample meal plan)
- Test cases: same unit, different compatible units, incompatible units, non-standard measurements

**References**:
- USDA culinary conversion standards
- PHP unit conversion libraries (for reference)
- Algorithm complexity analysis

---

## 4. Serving Size Scaling

### Decision: Linear Scaling with Fractional Multiplication

**Rationale**:
- Users need to adjust recipes for household size
- Most ingredients scale linearly (2x servings = 2x ingredients)
- Store multiplier at meal assignment level (user may scale each meal differently)
- Apply scaling before aggregation to ensure grocery list reflects adjusted amounts

**Scaling Formula**:
```
scaled_quantity = original_quantity * (target_servings / recipe_servings)
```

**Example**:
```
Recipe serves 4, user needs 6 servings
Multiplier: 6 / 4 = 1.5
Original: 2 cups flour → Scaled: 3 cups flour
Original: 1 tsp salt → Scaled: 1.5 tsp salt
```

**Non-Linear Ingredients** (Known Limitations):
- **Yeast**: Doesn't scale linearly in baking (2x recipe ≠ 2x yeast)
- **Seasonings**: Often scale sub-linearly (2x recipe might need 1.5x salt)
- **Baking times**: Not ingredient quantities, but related
- **Initial version**: Apply linear scaling to all ingredients
- **Future enhancement**: Flag certain ingredients as "review scaling" in UI

**Display Strategy**:
- Show both original and adjusted servings in meal plan view
- Grocery list shows final scaled quantities only
- Recipe detail page shows original quantities (reference)

**Storage**:
- `meal_assignments.serving_multiplier` (decimal, default 1.0)
- Calculate on read, not stored in grocery items (grocery list regeneration recalculates)

**Validation**:
- Multiplier range: 0.25 to 10.0 (¼ to 10x original servings)
- Prevent extreme scaling that might produce unreasonable quantities

**Alternatives Considered**:
- **Non-linear scaling models**: Rejected for MVP - too complex, benefit unclear
- **Per-ingredient scaling factors**: Rejected - requires expert culinary knowledge
- **No scaling feature**: Rejected - user requirement (success criteria SC-007)

**References**:
- Culinary scaling best practices
- Laravel model accessors for calculated fields

---

## 5. Grocery List Regeneration & Conflict Resolution

### Decision: Preserve Manual Edits with Soft Delete Tracking

**Rationale**:
- Users manually edit generated grocery lists (add items, edit quantities, delete items)
- When user regenerates list from updated meal plan, must preserve manual changes
- Challenge: distinguish user intent (deleted item) from recipe change (ingredient removed)

**Conflict Resolution Strategy**:

**Manual Items** (source_type = 'manual'):
- Always preserved during regeneration
- Not affected by meal plan changes
- User can delete manually, permanently removed

**Generated Items - User Edited** (source_type = 'generated', original_values not null):
- Preserve edited values during regeneration
- If ingredient no longer in meal plan, keep edited item (user may have added quantity for other purposes)
- UI indicator: "Manually adjusted" badge

**Generated Items - User Deleted** (source_type = 'generated', deleted_at not null):
- Use soft deletes to track deletion
- During regeneration, check if ingredient reappears in updated meal plan
- If reappears: Don't re-add (respect user's deletion)
- If still deleted after 30 days: Permanent cleanup (configurable)

**Generated Items - Unmodified** (source_type = 'generated', original_values null, deleted_at null):
- Recalculate from current meal plan
- Update quantity if meal plan changed
- Remove if no longer in meal plan

**Regeneration Process**:
```
1. Collect all current GroceryItems (including soft-deleted)
2. Generate new aggregated list from meal plan
3. For each new item:
   a. Check for existing item with same ingredient
   b. If exists and manually edited: keep edited version
   c. If exists and user deleted: skip (don't re-add)
   d. If exists and unmodified: update quantity
   e. If new: create as generated item
4. For each existing item not in new list:
   a. If manual: keep
   b. If generated and edited: keep (with indicator)
   c. If generated and unmodified: soft delete
```

**UI Considerations**:
- Regenerate button with confirmation dialog
- Show diff preview: "3 items added, 2 items updated, 1 item removed"
- Allow user to review changes before confirming
- "Undo" option immediately after regeneration

**Alternatives Considered**:
- **Always discard manual changes**: Rejected - poor UX, user loses work
- **Merge conflicts with manual resolution**: Rejected - too complex for MVP
- **Never regenerate, always manual edits**: Rejected - defeats purpose of meal plan integration

**Schema Support**:
```sql
grocery_items:
  - source_type ENUM('generated', 'manual')
  - original_values JSON (stores {quantity, unit} before user edit)
  - deleted_at TIMESTAMP (soft delete for conflict detection)
  - updated_by ENUM('system', 'user') (tracks last modification source)
```

**References**:
- Laravel soft deletes
- Conflict resolution patterns in CRUD applications
- UX patterns for destructive actions

---

## 6. Search & Filtering Implementation

### Decision: Database-Level Full-Text Search with Eager Loading

**Rationale**:
- Users search recipes by name, ingredients, meal type, dietary tags
- Performance requirement: <1 second for 10,000 recipes
- MariaDB supports full-text indexing
- Livewire's reactive properties enable real-time filtering

**Search Implementation**:

**Full-Text Search** (for recipe names and descriptions):
```sql
CREATE FULLTEXT INDEX idx_recipe_search ON recipes(name, description);

-- Query
SELECT * FROM recipes
WHERE MATCH(name, description) AGAINST('chicken pasta' IN NATURAL LANGUAGE MODE)
LIMIT 50;
```

**Ingredient Search**:
```sql
-- Find recipes containing specific ingredient
SELECT DISTINCT recipes.*
FROM recipes
JOIN recipe_ingredients ON recipes.id = recipe_ingredients.recipe_id
JOIN ingredients ON recipe_ingredients.ingredient_id = ingredients.id
WHERE ingredients.name LIKE '%tomato%'
LIMIT 50;
```

**Combined Filters**:
```php
// Livewire component property
#[Url]
public string $search = '';

#[Url]
public array $mealTypes = [];

#[Url]
public array $dietaryTags = [];

// Query builder
Recipe::query()
    ->when($this->search, fn($q) =>
        $q->whereFullText(['name', 'description'], $this->search)
          ->orWhereHas('ingredients', fn($q) =>
              $q->where('name', 'like', "%{$this->search}%")
          )
    )
    ->when($this->mealTypes, fn($q) =>
        $q->whereIn('meal_type', $this->mealTypes)
    )
    ->when($this->dietaryTags, fn($q) =>
        $q->whereJsonContains('dietary_tags', $this->dietaryTags)
    )
    ->with('recipeIngredients.ingredient') // Eager load
    ->paginate(24);
```

**Pagination**:
- Use Laravel's `paginate(24)` for infinite scroll or traditional pagination
- Livewire's `#[Url]` attribute maintains filter state in URL
- Load 24 recipes per page (4x6 grid on desktop)

**Performance Optimization**:
- Full-text index on `recipes.name` and `recipes.description`
- Index on `recipes.meal_type`
- JSON index on `recipes.dietary_tags` (MariaDB 10.11+)
- Eager load relationships to avoid N+1
- Cache popular searches (optional, post-MVP)

**Alternatives Considered**:
- **Elasticsearch / Algolia**: Rejected for MVP - adds infrastructure complexity
- **Client-side filtering**: Rejected - doesn't scale, loads all data
- **LIKE queries without full-text**: Accepted as fallback for SQLite tests

**Livewire Reactive Filtering**:
```php
// Component automatically re-renders when properties change
public function updatedSearch()
{
    $this->resetPage(); // Reset to page 1 when search changes
}
```

**References**:
- MariaDB full-text search documentation
- Laravel query builder whereFullText
- Livewire URL query parameters

---

## 7. PDF/Export Generation

### Decision: Laravel-DomPDF for PDF Generation

**Rationale**:
- Users export grocery lists as PDF for shopping
- Laravel ecosystem package: well-maintained, no external dependencies
- Renders Blade views to PDF (reuse templates)
- Adequate performance for grocery lists (<500 items)

**Implementation**:
```php
// Service method
use Barryvdh\DomPDF\Facade\Pdf;

public function exportPdf(GroceryList $groceryList): \Illuminate\Http\Response
{
    $pdf = Pdf::loadView('grocery-lists.pdf', [
        'groceryList' => $groceryList,
        'items' => $groceryList->items()->orderBy('category')->get(),
    ]);

    return $pdf->download("grocery-list-{$groceryList->id}.pdf");
}
```

**Template Design**:
- Group items by category
- Checkboxes for manual marking
- Recipe names as reference (which recipes contributed)
- Print-friendly styling (black/white, clear fonts)

**Plain Text Export**:
```php
public function exportText(GroceryList $groceryList): \Illuminate\Http\Response
{
    $content = $groceryList->items()
        ->orderBy('category')
        ->get()
        ->groupBy('category')
        ->map(function($items, $category) {
            return "## {$category}\n" .
                   $items->map(fn($item) =>
                       "[ ] {$item->name} - {$item->quantity} {$item->unit}"
                   )->join("\n");
        })
        ->join("\n\n");

    return response($content)
        ->header('Content-Type', 'text/plain')
        ->header('Content-Disposition', 'attachment; filename="grocery-list.txt"');
}
```

**Sharing Links**:
- Generate UUID-based shareable URL: `/grocery-lists/{uuid}/shared`
- Middleware: `auth` (recipients must be logged in)
- Read-only view (no edit/delete buttons)
- Optional: Expiration time (7 days default)

**Alternatives Considered**:
- **WeasyPrint (Python)**: Rejected - requires Python runtime
- **Puppeteer/Chrome headless**: Rejected - resource intensive
- **Email delivery**: Future enhancement, not MVP

**Dependencies**:
```bash
composer require barryvdh/laravel-dompdf
```

**References**:
- Laravel-DomPDF documentation
- Blade templating for PDF layouts

---

## 8. Testing Strategy

### Decision: Test Pyramid with Pest + Playwright

**Rationale**:
- Constitution requires test-first development
- Test pyramid: Many unit tests, some feature tests, few E2E tests
- Pest for PHP (Laravel standard)
- Playwright for E2E (cross-browser, fast, reliable)

**Test Distribution**:

**Unit Tests** (70% of tests):
- Service classes: `UnitConverter`, `IngredientAggregator`, `ServingSizeScaler`
- Model methods: calculated attributes, relationships
- Helper functions: unit conversions, date calculations
- No database, fast execution (<1ms per test)

**Feature Tests** (25% of tests):
- Livewire component interactions (Pest + Livewire testing helpers)
- Database operations (migrations, model persistence)
- Authorization checks (user can only edit own recipes)
- API-like operations (generate grocery list, create meal plan)
- Use SQLite in-memory database

**E2E Tests** (5% of tests):
- Complete user journeys (browse recipes → create meal plan → generate grocery list)
- Cross-component interactions (recipe selection modal in meal plan editor)
- JavaScript-dependent features (if any AlpineJS)
- Visual regression (optional, post-MVP)

**Pest Testing Patterns**:

```php
// Feature test example
test('user can create personal recipe', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(Recipes\Create::class)
        ->set('name', 'My Recipe')
        ->set('description', 'Family favorite')
        ->call('addIngredient', 'Flour', '2', 'cups')
        ->call('save')
        ->assertRedirect(route('recipes.show', Recipe::latest()->first()));

    expect(Recipe::where('user_id', $user->id)->count())->toBe(1);
});

// Unit test example
test('unit converter converts cups to fluid ounces', function () {
    $converter = new UnitConverter();

    expect($converter->convert(2, 'cups', 'fl oz'))->toBe(16.0);
});
```

**Playwright Testing Patterns**:

```typescript
// E2E test example
test('user can create meal plan and generate grocery list', async ({ page }) => {
  await page.goto('/login');
  await page.fill('[name="email"]', 'user@example.com');
  await page.fill('[name="password"]', 'password');
  await page.click('button[type="submit"]');

  // Create meal plan
  await page.goto('/meal-plans/create');
  await page.fill('[name="name"]', 'Weekly Plan');
  await page.fill('[name="start_date"]', '2025-10-14');
  await page.fill('[name="end_date"]', '2025-10-20');
  await page.click('button:has-text("Create")');

  // Assign recipe to Monday dinner
  await page.click('[data-date="2025-10-14"][data-meal="dinner"]');
  await page.fill('[name="search"]', 'Chicken Pasta');
  await page.click('text=Chicken Pasta');
  await page.click('button:has-text("Assign")');

  // Generate grocery list
  await page.click('button:has-text("Generate Grocery List")');
  await expect(page.locator('h1')).toContainText('Grocery List');
  await expect(page.locator('[data-category="produce"]')).toBeVisible();
});
```

**Test Data Management**:
- Factories for all models (`RecipeFactory`, `MealPlanFactory`)
- Seeders for system recipes (separate seeder for tests vs. production)
- Database transactions in feature tests (auto-rollback)

**CI/CD Integration**:
```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  pest:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.3
      - name: Install Dependencies
        run: composer install
      - name: Run Pest Tests
        run: php artisan test

  playwright:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: 18
      - name: Install Dependencies
        run: npm ci
      - name: Install Playwright
        run: npx playwright install --with-deps
      - name: Run Playwright Tests
        run: npx playwright test
```

**References**:
- Pest documentation
- Livewire testing documentation
- Playwright documentation
- Test pyramid pattern

---

## 9. Performance Considerations

### Decision: Eager Loading, Query Optimization, and Redis Caching

**Rationale**:
- Performance goal: <200ms p95 page loads
- Grocery list generation: <10 seconds for 4-week meal plans
- Avoid N+1 queries (Livewire components can easily trigger them)

**Optimization Strategies**:

**1. Eager Loading**:
```php
// BAD - N+1 queries
$recipes = Recipe::all();
foreach ($recipes as $recipe) {
    $recipe->recipeIngredients; // Query per recipe
}

// GOOD - Single query
$recipes = Recipe::with('recipeIngredients.ingredient')->get();
```

**2. Query Optimization**:
- Index foreign keys: `recipe_ingredients.recipe_id`, `recipe_ingredients.ingredient_id`
- Composite index: `meal_assignments(meal_plan_id, date)` for calendar queries
- Full-text index: `recipes(name, description)`
- Limit result sets: Paginate recipes (24 per page), limit grocery items

**3. Caching Strategy**:
```php
// Cache system recipes (rarely change)
Cache::remember('system_recipes', now()->addDay(), fn() =>
    Recipe::whereNull('user_id')->with('recipeIngredients.ingredient')->get()
);

// Cache user's favorite recipes (optional)
Cache::remember("user_{$userId}_favorites", now()->addHour(), fn() =>
    Recipe::where('user_id', $userId)->latest()->limit(10)->get()
);
```

**4. Database Query Monitoring**:
- Laravel Debugbar in development (query count, execution time)
- `php artisan telescope:install` (optional, for production monitoring)
- Log slow queries (>100ms) for optimization

**5. Livewire Performance**:
- Use `wire:key` on lists to prevent re-rendering entire list
- Debounce search input: `wire:model.debounce.300ms="search"`
- Lazy load components: `<livewire:component lazy />`
- Disable Livewire auto-injection on static pages

**Alternatives Considered**:
- **Materialized views**: Rejected - adds complexity, MariaDB support limited
- **Redis queue for grocery list generation**: Accepted for future if >10s generation time
- **CDN for static assets**: Future enhancement

**Monitoring**:
- Laravel Telescope (query log, requests, jobs)
- New Relic or Blackfire (profiling)
- Custom metrics: `Log::info('Grocery list generated', ['duration' => $duration, 'item_count' => $count]);`

**References**:
- Laravel performance best practices
- MariaDB query optimization
- Redis caching patterns

---

## 10. Security Considerations

### Decision: Authorization Policies with Laravel Gates

**Rationale**:
- Users can only edit/delete their own personal recipes
- Users can only access their own meal plans and grocery lists
- System recipes are read-only for all users
- Laravel's authorization system (policies) provides declarative access control

**Policy Implementation**:

```php
// app/Policies/RecipePolicy.php
class RecipePolicy
{
    public function view(User $user, Recipe $recipe): bool
    {
        // Anyone can view system recipes or their own recipes
        return $recipe->user_id === null || $recipe->user_id === $user->id;
    }

    public function update(User $user, Recipe $recipe): bool
    {
        // Can only update own recipes (not system recipes)
        return $recipe->user_id === $user->id;
    }

    public function delete(User $user, Recipe $recipe): bool
    {
        // Can only delete own recipes (not system recipes)
        return $recipe->user_id === $user->id;
    }
}

// Livewire component usage
public function delete(Recipe $recipe)
{
    $this->authorize('delete', $recipe);

    $recipe->delete();
    // ...
}
```

**Middleware**:
- All routes require authentication: `Route::middleware(['auth'])`
- Laravel Fortify handles authentication (existing setup)

**Data Isolation**:
```php
// Always scope queries to current user
public function render()
{
    return view('livewire.meal-plans.index', [
        'mealPlans' => auth()->user()->mealPlans()->latest()->paginate(10)
    ]);
}
```

**Mass Assignment Protection**:
```php
// Model fillable/guarded
class Recipe extends Model
{
    protected $fillable = ['name', 'description', 'prep_time', 'cook_time', 'servings'];

    protected $guarded = ['user_id']; // Set by application, not user input
}
```

**Input Validation**:
```php
// Livewire validation
#[Validate('required|min:3|max:255')]
public string $name = '';

#[Validate('required|integer|min:1|max:24')]
public int $prepTime;

#[Validate('required|array')]
public array $ingredients = [];
```

**CSRF Protection**:
- Automatic with Livewire (built into Laravel)
- All forms protected by default

**SQL Injection Prevention**:
- Use Eloquent query builder (parameterized queries)
- Never use raw SQL with user input
- If raw queries needed: `DB::select('...', [$binding])`

**XSS Prevention**:
- Blade templates auto-escape: `{{ $variable }}`
- Use `{!! !!}` only for trusted HTML (e.g., rendered markdown)
- Purify user HTML input if rich text editor added

**References**:
- Laravel authorization documentation
- OWASP Top 10
- Laravel security best practices

---

## Summary of Key Decisions

| Area | Decision | Rationale |
|------|----------|-----------|
| **Architecture** | Livewire full-page components | Constitutional requirement, SPA-like UX |
| **Data Model** | Normalized relational with pivots | Flexibility, normalization, query efficiency |
| **Aggregation** | Two-pass with unit conversion | Handles duplicate ingredients with different units |
| **Scaling** | Linear multiplier | Simple, covers 90% of use cases |
| **Regeneration** | Preserve edits with soft deletes | Best UX, respects user modifications |
| **Search** | Full-text DB index | Fast, scales to 10k+ recipes |
| **Export** | Laravel-DomPDF | Simple, no external dependencies |
| **Testing** | Pest + Playwright pyramid | Constitutional requirement, comprehensive coverage |
| **Performance** | Eager loading + Redis cache | Meets <200ms p95 target |
| **Security** | Policies + validation | Defense in depth, least privilege |

---

## Next Steps

With research complete, proceed to **Phase 1: Design & Contracts**:
1. Generate detailed data model (data-model.md)
2. Create API contracts for Livewire component interactions (contracts/)
3. Write developer quickstart guide (quickstart.md)
4. Update agent context with technology decisions

All design decisions documented here will inform implementation tasks in Phase 2.
