# Task 003: Update All Livewire Routes to Route::livewire()

**Status**: completed
**Depends on**: [001]
**Retry count**: 0

## Description
Livewire 4 requires that all full-page Livewire component routes use `Route::livewire()` instead of `Route::get()` with a component class. This is a high-impact breaking change that affects every Livewire page route in the application.

## Context
- Files to modify: `routes/web.php`, `routes/auth.php`
- Controller-handled routes (POST, PUT, DELETE) are NOT affected
- `Route::redirect()` is NOT affected

### Current Pattern (Livewire 3)
```php
Route::get('recipes', RecipesIndex::class)->name('recipes.index');
```

### New Pattern (Livewire 4)
```php
Route::livewire('recipes', RecipesIndex::class)->name('recipes.index');
```

### Routes in `routes/web.php` to Update (20 routes)
- `Route::get('dashboard', Dashboard::class)`
- `Route::get('recipes', RecipesIndex::class)`
- `Route::get('recipes/create', RecipesCreate::class)`
- `Route::get('recipes/import', RecipesImport::class)`
- `Route::get('recipes/import/preview', RecipesImportPreview::class)`
- `Route::get('recipes/{recipe}', RecipesShow::class)`
- `Route::get('recipes/{recipe}/edit', RecipesEdit::class)`
- `Route::get('meal-plans', MealPlansIndex::class)`
- `Route::get('meal-plans/create', MealPlansCreate::class)`
- `Route::get('meal-plans/{mealPlan}', MealPlansShow::class)`
- `Route::get('meal-plans/{mealPlan}/edit', MealPlansEdit::class)`
- `Route::get('grocery-lists', GroceryListsIndex::class)`
- `Route::get('grocery-lists/create', GroceryListsCreate::class)`
- `Route::get('grocery-lists/{groceryList}', GroceryListsShow::class)`
- `Route::get('grocery-lists/generate/{mealPlan}', GroceryListsGenerate::class)`
- `Route::get('grocery-lists/shared/{token}', GroceryListsShared::class)`
- `Route::get('settings/profile', Profile::class)`
- `Route::get('settings/password', Password::class)`
- `Route::get('settings/appearance', Appearance::class)`
- `Route::get('settings/item-templates', ItemTemplates::class)`
- `Route::get('settings/item-templates/create', ItemTemplatesEdit::class)`
- `Route::get('settings/item-templates/{template}/edit', ItemTemplatesEdit::class)`
- `Route::get('settings/sharing', Sharing::class)`
- `Route::get('settings/two-factor', TwoFactor::class)`

### Routes in `routes/auth.php` to Update (5 routes)
- `Route::get('login', Login::class)`
- `Route::get('register', Register::class)`
- `Route::get('forgot-password', ForgotPassword::class)`
- `Route::get('reset-password/{token}', ResetPassword::class)`
- `Route::get('verify-email', VerifyEmail::class)`

### Routes NOT to Change (controller or closure routes)
- `Route::delete('recipes/{recipe}', ...)` — controller
- `Route::post('meal-plans', ...)` — controller
- `Route::put('meal-plans/{mealPlan}', ...)` — controller
- `Route::delete('meal-plans/{mealPlan}', ...)` — controller
- All meal plan assignment routes (POST/PUT/DELETE)
- All grocery list controller routes (export PDF/text, items CRUD)
- `Route::redirect('settings', ...)` — redirect
- `Route::post('logout', Logout::class)` — action class (not a page component)
- `Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)` — controller
- Test-only closure routes

## Requirements (Test Descriptions)
- [x] `it can access the dashboard route without errors`
- [x] `it can access the recipes index route without errors`
- [x] `it can access the recipe show route with a valid recipe`
- [x] `it can access the meal plans index route without errors`
- [x] `it can access the grocery lists index route without errors`
- [x] `it can access the settings profile route without errors`
- [x] `it can access the login route as a guest`
- [x] `it can access the register route as a guest`
- [x] `it lists all named routes correctly via php artisan route:list`

## Acceptance Criteria
- All requirements have passing tests
- All Livewire component page routes use `Route::livewire()`
- Named routes remain unchanged (`.name()` chaining still works)
- Middleware chaining still works on converted routes
- Existing tests that hit these routes pass
