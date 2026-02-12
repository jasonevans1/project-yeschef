<?php

use App\Livewire\Dashboard;
use App\Livewire\GroceryLists\Create as GroceryListsCreate;
use App\Livewire\GroceryLists\Generate as GroceryListsGenerate;
use App\Livewire\GroceryLists\Index as GroceryListsIndex;
use App\Livewire\GroceryLists\Shared as GroceryListsShared;
use App\Livewire\GroceryLists\Show as GroceryListsShow;
use App\Livewire\MealPlans\Create as MealPlansCreate;
use App\Livewire\MealPlans\Edit as MealPlansEdit;
use App\Livewire\MealPlans\Index as MealPlansIndex;
use App\Livewire\MealPlans\Show as MealPlansShow;
use App\Livewire\Recipes\Create as RecipesCreate;
use App\Livewire\Recipes\Edit as RecipesEdit;
use App\Livewire\Recipes\Import as RecipesImport;
use App\Livewire\Recipes\ImportPreview as RecipesImportPreview;
use App\Livewire\Recipes\Index as RecipesIndex;
use App\Livewire\Recipes\Show as RecipesShow;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\ItemTemplates;
use App\Livewire\Settings\ItemTemplatesEdit;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\Sharing;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Recipe Routes
    Route::get('recipes', RecipesIndex::class)->name('recipes.index');
    Route::get('recipes/create', RecipesCreate::class)->name('recipes.create'); // US5 - T099, T100
    Route::get('recipes/import', RecipesImport::class)->name('recipes.import'); // US1 - T036
    Route::get('recipes/import/preview', RecipesImportPreview::class)->name('recipes.import.preview'); // US3 - T036
    Route::get('recipes/{recipe}', RecipesShow::class)->name('recipes.show');
    Route::get('recipes/{recipe}/edit', RecipesEdit::class)->name('recipes.edit'); // US5 - T101, T102
    Route::delete('recipes/{recipe}', [\App\Http\Controllers\RecipeController::class, 'destroy'])->name('recipes.destroy'); // US5 - T103

    // Meal Plan Routes
    Route::get('meal-plans', MealPlansIndex::class)->name('meal-plans.index');
    Route::get('meal-plans/create', MealPlansCreate::class)->name('meal-plans.create');
    Route::get('meal-plans/{mealPlan}', MealPlansShow::class)->name('meal-plans.show');
    Route::get('meal-plans/{mealPlan}/edit', MealPlansEdit::class)->name('meal-plans.edit');

    // Meal Plan Actions (POST/PUT/DELETE)
    Route::post('meal-plans', [\App\Http\Controllers\MealPlanController::class, 'store'])->name('meal-plans.store');
    Route::put('meal-plans/{mealPlan}', [\App\Http\Controllers\MealPlanController::class, 'update'])->name('meal-plans.update');
    Route::delete('meal-plans/{mealPlan}', [\App\Http\Controllers\MealPlanController::class, 'destroy'])->name('meal-plans.destroy');

    // Meal Plan Assignment Routes (nested resource)
    Route::post('meal-plans/{mealPlan}/assignments', [\App\Http\Controllers\MealAssignmentController::class, 'store'])->name('meal-plans.assignments.store');
    Route::put('meal-plans/{mealPlan}/assignments/{assignment}', [\App\Http\Controllers\MealAssignmentController::class, 'update'])->name('meal-plans.assignments.update');
    Route::delete('meal-plans/{mealPlan}/assignments/{assignment}', [\App\Http\Controllers\MealAssignmentController::class, 'destroy'])->name('meal-plans.assignments.destroy');

    // Grocery List Routes (authenticated)
    Route::get('grocery-lists', GroceryListsIndex::class)->name('grocery-lists.index'); // US3 - T074
    Route::get('grocery-lists/create', GroceryListsCreate::class)->name('grocery-lists.create'); // US6 - T110
    Route::get('grocery-lists/{groceryList}', GroceryListsShow::class)->name('grocery-lists.show'); // US3 - T078
    Route::get('grocery-lists/generate/{mealPlan}', GroceryListsGenerate::class)->name('grocery-lists.generate'); // US3 - T076, T077

    // Grocery List Export Routes (US8 - T126)
    Route::get('grocery-lists/{groceryList}/export/pdf', [\App\Http\Controllers\GroceryListController::class, 'exportPdf'])->name('grocery-lists.export.pdf');
    Route::get('grocery-lists/{groceryList}/export/text', [\App\Http\Controllers\GroceryListController::class, 'exportText'])->name('grocery-lists.export.text');

    // Shared Grocery List Route (US8 - T129)
    Route::get('grocery-lists/shared/{token}', GroceryListsShared::class)->name('grocery-lists.shared');

    // Grocery List Item Actions (POST/PUT/DELETE)
    Route::post('grocery-lists/{groceryList}/items', [\App\Http\Controllers\GroceryItemController::class, 'store'])->name('grocery-lists.items.store'); // US4 - T087
    Route::put('grocery-lists/{groceryList}/items/{item}', [\App\Http\Controllers\GroceryItemController::class, 'update'])->name('grocery-lists.items.update'); // US4 - T088
    Route::delete('grocery-lists/{groceryList}/items/{item}', [\App\Http\Controllers\GroceryItemController::class, 'destroy'])->name('grocery-lists.items.destroy'); // US4 - T089

    // Settings Routes
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

    // Item Templates Routes (moved to settings)
    Route::get('settings/item-templates', ItemTemplates::class)->name('settings.item-templates');
    Route::get('settings/item-templates/create', ItemTemplatesEdit::class)->name('settings.item-templates.create');
    Route::get('settings/item-templates/{template}/edit', ItemTemplatesEdit::class)->name('settings.item-templates.edit');

    Route::get('settings/sharing', Sharing::class)->name('settings.sharing');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});

require __DIR__.'/auth.php';

// Test-only routes for E2E testing (only available when APP_ENV=local or testing)
if (app()->environment(['local', 'testing'])) {
    Route::get('/test/recipe-valid', function () {
        return response(<<<'HTML'
<html>
<head>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Recipe",
    "name": "Test Chocolate Chip Cookies",
    "description": "Delicious test cookies for E2E testing",
    "prepTime": "PT15M",
    "cookTime": "PT12M",
    "recipeYield": "24 cookies",
    "recipeIngredient": [
      "2 cups all-purpose flour",
      "1 cup white sugar",
      "1/2 cup butter, softened",
      "2 eggs",
      "1 tsp vanilla extract",
      "1 tsp baking soda",
      "1/2 tsp salt",
      "2 cups chocolate chips"
    ],
    "recipeInstructions": "Preheat oven to 350°F. Mix butter and sugar. Add eggs and vanilla. Combine dry ingredients. Fold in chocolate chips. Drop by spoonfuls onto baking sheet. Bake 10-12 minutes.",
    "image": "https://example.com/cookies.jpg",
    "recipeCuisine": "American",
    "recipeCategory": "Dessert"
  }
  </script>
</head>
<body>Test Recipe Content</body>
</html>
HTML, 200, ['Content-Type' => 'text/html']);
    })->name('test.recipe.valid');

    Route::get('/test/recipe-invalid', function () {
        return response('<html><body>Just a regular page with no recipe data</body></html>', 200, ['Content-Type' => 'text/html']);
    })->name('test.recipe.invalid');
}
