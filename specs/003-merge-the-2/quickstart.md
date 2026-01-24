# Developer Quickstart: Meal Planning Application

**Feature**: Family Meal Planning Application with Grocery List Management
**Branch**: `003-merge-the-2`
**Updated**: 2025-10-10

## Prerequisites

Before starting implementation, ensure you have:

- ✅ DDEV installed and running (`ddev start`)
- ✅ PHP 8.3+
- ✅ Composer dependencies installed (`composer install`)
- ✅ Node.js 18+ and npm installed
- ✅ Frontend dependencies installed (`npm install`)
- ✅ Database configured (MariaDB via DDEV, SQLite for tests)
- ✅ Laravel Fortify authentication already set up

## Architecture Overview

This application follows a **Livewire-first architecture** (see Project Constitution):

- **Frontend**: Full-page Livewire 3 components with Flux UI components
- **Backend**: Laravel 12 with Eloquent models and service classes
- **Database**: MariaDB (production), SQLite (tests)
- **Testing**: Pest (PHP tests), Playwright (E2E tests)
- **Styling**: Tailwind CSS 4.x (via Vite)

## Project Structure

```
app/
├── Livewire/          # Full-page Livewire components
│   ├── Recipes/       # Recipe management
│   ├── MealPlans/     # Meal planning
│   └── GroceryLists/  # Grocery list management
├── Models/            # Eloquent models
├── Services/          # Business logic (aggregation, conversion, scaling)
└── Enums/             # Enum classes (MealType, IngredientCategory, etc.)

database/migrations/   # Database schema
resources/views/       # Blade templates
tests/                 # Pest tests
e2e/                   # Playwright tests
```

## Getting Started

### Step 1: Review Documentation

Read these documents in order:

1. **spec.md** - Feature requirements and user scenarios
2. **research.md** - Design decisions and algorithms
3. **data-model.md** - Database schema and entity relationships
4. **contracts/** - Livewire component contracts
5. **This file (quickstart.md)** - Implementation guide

### Step 2: Database Setup

Create migrations in this order (maintains referential integrity):

```bash
# Core recipe management
php artisan make:migration create_recipes_table
php artisan make:migration create_ingredients_table
php artisan make:migration create_recipe_ingredients_table

# Meal planning
php artisan make:migration create_meal_plans_table
php artisan make:migration create_meal_assignments_table

# Grocery lists
php artisan make:migration create_grocery_lists_table
php artisan make:migration create_grocery_items_table
```

**Important**: Follow the schema in `data-model.md` exactly. Pay special attention to:
- Foreign key constraints (CASCADE vs RESTRICT vs SET NULL)
- Indexes (full-text, composite, unique)
- Default values
- Nullable fields

Run migrations:
```bash
php artisan migrate
```

### Step 3: Create Eloquent Models

Generate models with relationships:

```bash
php artisan make:model Recipe
php artisan make:model Ingredient
php artisan make:model RecipeIngredient
php artisan make:model MealPlan
php artisan make:model MealAssignment
php artisan make:model GroceryList
php artisan make:model GroceryItem
```

Define relationships in each model (see `data-model.md` for complete relationships).

Example (`Recipe.php`):
```php
class Recipe extends Model
{
    protected $fillable = ['name', 'description', 'prep_time', 'cook_time', 'servings', /* ... */];

    protected $casts = [
        'dietary_tags' => 'array',
        'prep_time' => 'integer',
        'cook_time' => 'integer',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
                    ->withPivot('quantity', 'unit', 'sort_order', 'notes')
                    ->orderByPivot('sort_order');
    }

    public function recipeIngredients()
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('sort_order');
    }

    // Computed attributes
    public function getTotalTimeAttribute()
    {
        return $this->prep_time + $this->cook_time;
    }

    public function getIsSystemRecipeAttribute()
    {
        return $this->user_id === null;
    }
}
```

### Step 4: Create Enums

Generate enum classes for type safety:

```bash
php artisan make:enum MealType
php artisan make:enum IngredientCategory
php artisan make:enum MeasurementUnit
php artisan make:enum SourceType
```

Example (`MealType.php`):
```php
namespace App\Enums;

enum MealType: string
{
    case BREAKFAST = 'breakfast';
    case LUNCH = 'lunch';
    case DINNER = 'dinner';
    case SNACK = 'snack';
}
```

### Step 5: Create Authorization Policies

Generate policies for access control:

```bash
php artisan make:policy RecipePolicy --model=Recipe
php artisan make:policy MealPlanPolicy --model=MealPlan
php artisan make:policy GroceryListPolicy --model=GroceryList
```

Implement policy methods (see `contracts/*.yaml` for authorization rules).

Example (`RecipePolicy.php`):
```php
class RecipePolicy
{
    public function view(User $user, Recipe $recipe): bool
    {
        // System recipes or own recipes
        return $recipe->user_id === null || $recipe->user_id === $user->id;
    }

    public function update(User $user, Recipe $recipe): bool
    {
        // Can only update own recipes
        return $recipe->user_id === $user->id;
    }

    public function delete(User $user, Recipe $recipe): bool
    {
        // Can only delete own recipes
        return $recipe->user_id === $user->id;
    }
}
```

Register policies in `AuthServiceProvider`:
```php
protected $policies = [
    Recipe::class => RecipePolicy::class,
    MealPlan::class => MealPlanPolicy::class,
    GroceryList::class => GroceryListPolicy::class,
];
```

### Step 6: Create Service Classes

Generate service classes for business logic:

```bash
php artisan make:class Services/GroceryListGenerator
php artisan make:class Services/IngredientAggregator
php artisan make:class Services/UnitConverter
php artisan make:class Services/ServingSizeScaler
```

Implement algorithms from `research.md`:

- **UnitConverter**: Convert between measurement units (see research.md section 3)
- **ServingSizeScaler**: Apply serving multipliers (see research.md section 4)
- **IngredientAggregator**: Combine duplicate ingredients (see research.md section 3)
- **GroceryListGenerator**: Orchestrate generation and regeneration (see contracts/grocery-lists.yaml)

### Step 7: Create Livewire Components

Generate Livewire components following the structure in `plan.md`:

```bash
# Recipes
php artisan make:livewire Recipes/Index
php artisan make:livewire Recipes/Show
php artisan make:livewire Recipes/Create
php artisan make:livewire Recipes/Edit

# Meal Plans
php artisan make:livewire MealPlans/Index
php artisan make:livewire MealPlans/Create
php artisan make:livewire MealPlans/Show
php artisan make:livewire MealPlans/Edit

# Grocery Lists
php artisan make:livewire GroceryLists/Index
php artisan make:livewire GroceryLists/Show
php artisan make:livewire GroceryLists/Create
php artisan make:livewire GroceryLists/Generate
php artisan make:livewire GroceryLists/Export
```

Implement component logic following contracts in `contracts/*.yaml`.

Example component structure (`Recipes/Index.php`):
```php
namespace App\Livewire\Recipes;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public array $mealTypes = [];

    #[Url]
    public array $dietaryTags = [];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $recipes = Recipe::query()
            ->when($this->search, fn($q) =>
                $q->whereFullText(['name', 'description'], $this->search)
            )
            ->when($this->mealTypes, fn($q) =>
                $q->whereIn('meal_type', $this->mealTypes)
            )
            ->when($this->dietaryTags, fn($q) =>
                $q->where(function($q) {
                    foreach ($this->dietaryTags as $tag) {
                        $q->orWhereJsonContains('dietary_tags', $tag);
                    }
                })
            )
            ->with('recipeIngredients.ingredient')
            ->paginate(24);

        return view('livewire.recipes.index', compact('recipes'));
    }
}
```

### Step 8: Create Blade Views

Create corresponding Blade views for each Livewire component using Flux components:

```
resources/views/livewire/
├── recipes/
│   ├── index.blade.php
│   ├── show.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── meal-plans/
│   ├── index.blade.php
│   ├── show.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
└── grocery-lists/
    ├── index.blade.php
    ├── show.blade.php
    ├── create.blade.php
    └── generate.blade.php
```

Use Flux components for consistent UI (see Constitution Principle II).

Example view (`recipes/index.blade.php`):
```blade
<div>
    <flux:heading size="xl">Recipes</flux:heading>

    <div class="mt-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search recipes..." />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-6">
        @foreach($recipes as $recipe)
            <flux:card wire:click="$navigate('{{ route('recipes.show', $recipe) }}')" class="cursor-pointer">
                @if($recipe->image_url)
                    <img src="{{ $recipe->image_url }}" alt="{{ $recipe->name }}" class="w-full h-48 object-cover">
                @endif
                <flux:card.title>{{ $recipe->name }}</flux:card.title>
                <flux:card.description>{{ Str::limit($recipe->description, 100) }}</flux:card.description>
            </flux:card>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $recipes->links() }}
    </div>
</div>
```

### Step 9: Define Routes

Add routes in `routes/web.php` pointing to Livewire components:

```php
use App\Livewire\Recipes;
use App\Livewire\MealPlans;
use App\Livewire\GroceryLists;

Route::middleware(['auth'])->group(function () {
    // Recipes
    Route::get('/recipes', Recipes\Index::class)->name('recipes.index');
    Route::get('/recipes/create', Recipes\Create::class)->name('recipes.create');
    Route::get('/recipes/{recipe}', Recipes\Show::class)->name('recipes.show');
    Route::get('/recipes/{recipe}/edit', Recipes\Edit::class)->name('recipes.edit');

    // Meal Plans
    Route::get('/meal-plans', MealPlans\Index::class)->name('meal-plans.index');
    Route::get('/meal-plans/create', MealPlans\Create::class)->name('meal-plans.create');
    Route::get('/meal-plans/{mealPlan}', MealPlans\Show::class)->name('meal-plans.show');
    Route::get('/meal-plans/{mealPlan}/edit', MealPlans\Edit::class)->name('meal-plans.edit');

    // Grocery Lists
    Route::get('/grocery-lists', GroceryLists\Index::class)->name('grocery-lists.index');
    Route::get('/grocery-lists/create', GroceryLists\Create::class)->name('grocery-lists.create');
    Route::get('/grocery-lists/generate/{mealPlan}', GroceryLists\Generate::class)->name('grocery-lists.generate');
    Route::get('/grocery-lists/{groceryList}', GroceryLists\Show::class)->name('grocery-lists.show');
});
```

### Step 10: Create Factories and Seeders

Generate factories for testing:

```bash
php artisan make:factory RecipeFactory
php artisan make:factory MealPlanFactory
php artisan make:factory GroceryListFactory
```

Generate seeder for system recipes:

```bash
php artisan make:seeder RecipeSeeder
```

Seed the database:
```bash
php artisan db:seed --class=RecipeSeeder
```

## Testing Strategy

### Test-First Development (TDD)

**This is non-negotiable per the Constitution!**

1. Write failing Pest test first
2. Implement feature to make test pass
3. Refactor while keeping tests green
4. Run `composer test` before committing

### Writing Pest Tests

Example feature test:

```php
use App\Models\Recipe;
use App\Models\User;
use Livewire\Livewire;

test('user can create personal recipe', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(\App\Livewire\Recipes\Create::class)
        ->set('name', 'My Recipe')
        ->set('description', 'Test recipe')
        ->set('servings', 4)
        ->set('instructions', 'Cook the thing')
        ->set('ingredients', [
            ['ingredient_name' => 'Flour', 'quantity' => 2, 'unit' => 'cups', 'category' => 'pantry']
        ])
        ->call('save')
        ->assertRedirect();

    expect(Recipe::where('user_id', $user->id)->count())->toBe(1);
});
```

Example unit test:

```php
use App\Services\UnitConverter;

test('converts cups to fluid ounces', function () {
    $converter = new UnitConverter();

    [$quantity, $unit] = $converter->toBaseUnit(2, 'cup');

    expect($quantity)->toBe(16.0);
    expect($unit)->toBe('fl_oz');
});
```

### Writing Playwright Tests

Example E2E test (`e2e/recipes.spec.ts`):

```typescript
import { test, expect } from '@playwright/test';

test('user can browse and view recipe', async ({ page }) => {
  // Login
  await page.goto('/login');
  await page.fill('[name="email"]', 'user@example.com');
  await page.fill('[name="password"]', 'password');
  await page.click('button[type="submit"]');

  // Browse recipes
  await page.goto('/recipes');
  await expect(page.locator('h1')).toContainText('Recipes');

  // Search for recipe
  await page.fill('[placeholder="Search recipes..."]', 'Chicken');
  await expect(page.locator('text=Chicken Parmesan')).toBeVisible();

  // View recipe details
  await page.click('text=Chicken Parmesan');
  await expect(page.locator('h1')).toContainText('Chicken Parmesan');
  await expect(page.locator('text=Ingredients')).toBeVisible();
});
```

Run Playwright tests:
```bash
npx playwright test
npx playwright test --ui  # UI mode
```

## Development Workflow

### Daily Development

1. Start DDEV environment:
   ```bash
   ddev start
   ```

2. Run all services concurrently:
   ```bash
   composer dev
   ```
   This runs: Laravel server, queue worker, log monitoring (pail), Vite dev server

3. Access application:
   ```
   https://project-tabletop.ddev.site
   ```

### Before Committing

Run the quality gates:

```bash
# Run Pest tests (must pass)
composer test

# Run Playwright tests (for affected features)
npx playwright test

# Run code formatter (fixes issues automatically)
vendor/bin/pint

# Verify no console errors in browser
```

### Code Quality Standards

- **Laravel Pint**: Enforces PSR-12 coding standards
- **Pest tests**: All tests must pass
- **Playwright tests**: Critical flows must pass
- **Authorization**: All components must check user permissions
- **Validation**: All user inputs must be validated

## Common Tasks

### Adding a New Recipe Field

1. Create migration: `php artisan make:migration add_field_to_recipes_table`
2. Update `Recipe` model `$fillable` and `$casts`
3. Update `RecipePolicy` if field affects authorization
4. Update Livewire components (`Create`, `Edit`, `Show`)
5. Update Blade views
6. Update validation rules
7. Write tests for new field
8. Update `data-model.md` and `contracts/recipes.yaml`

### Debugging Grocery List Generation

- Enable query logging: `DB::enableQueryLog();`
- Use `php artisan pail` to monitor logs
- Add debug statements: `Log::info('Aggregating', ['count' => $items->count()]);`
- Test aggregation service in isolation with unit tests
- Check for N+1 queries with Laravel Debugbar

### Troubleshooting Common Issues

**Problem**: N+1 queries slowing down page
**Solution**: Use `with()` to eager load relationships

**Problem**: Full-text search not working
**Solution**: Ensure full-text index exists (`SHOW CREATE TABLE recipes`)

**Problem**: Livewire component not updating
**Solution**: Check wire:model bindings, use `$this->dispatch('$refresh')`

**Problem**: Authorization failing
**Solution**: Verify policy registered in `AuthServiceProvider`, check `Gate::allows()`

## Next Steps

After completing implementation:

1. Review with stakeholders (demo the working application)
2. Conduct code review focusing on constitutional compliance
3. Run full test suite (Pest + Playwright)
4. Deploy to staging environment
5. User acceptance testing
6. Deploy to production

## Additional Resources

- **Laravel 12 Docs**: https://laravel.com/docs/12.x
- **Livewire 3 Docs**: https://livewire.laravel.com/docs
- **Flux Components**: https://fluxui.dev
- **Pest Docs**: https://pestphp.com/docs
- **Playwright Docs**: https://playwright.dev
- **Project Constitution**: `/.specify/memory/constitution.md`
- **CLAUDE.md**: `/CLAUDE.md` (runtime development guidance)

## Questions?

Refer to:
- `spec.md` for "What are we building?" (requirements)
- `research.md` for "How should we build it?" (design decisions)
- `data-model.md` for "How is data structured?" (schema)
- `contracts/*.yaml` for "How do components interact?" (API contracts)
- This file (`quickstart.md`) for "How do I start?" (implementation guide)

Happy coding! 🚀
