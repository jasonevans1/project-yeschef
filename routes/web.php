<?php

use App\Livewire\GroceryLists\Create as GroceryListsCreate;
use App\Livewire\GroceryLists\Export as GroceryListsExport;
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
use App\Livewire\Recipes\Index as RecipesIndex;
use App\Livewire\Recipes\Show as RecipesShow;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // Recipe Routes
    Route::get('recipes', RecipesIndex::class)->name('recipes.index');
    // Route::get('recipes/create', RecipesCreate::class)->name('recipes.create'); // TODO: US5 - T099
    Route::get('recipes/{recipe}', RecipesShow::class)->name('recipes.show');
    // Route::get('recipes/{recipe}/edit', RecipesEdit::class)->name('recipes.edit'); // TODO: US5 - T101

    // Meal Plan Routes
    // Route::get('meal-plans', MealPlansIndex::class)->name('meal-plans.index'); // TODO: US2 - T059
    // Route::get('meal-plans/create', MealPlansCreate::class)->name('meal-plans.create'); // TODO: US2 - T061
    // Route::get('meal-plans/{mealPlan}', MealPlansShow::class)->name('meal-plans.show'); // TODO: US2 - T063
    // Route::get('meal-plans/{mealPlan}/edit', MealPlansEdit::class)->name('meal-plans.edit'); // TODO: US2 - T065

    // Grocery List Routes (authenticated)
    // Route::get('grocery-lists', GroceryListsIndex::class)->name('grocery-lists.index'); // TODO: US3 - T074
    // Route::get('grocery-lists/create', GroceryListsCreate::class)->name('grocery-lists.create'); // TODO: US6 - T110
    // Route::get('grocery-lists/{groceryList}', GroceryListsShow::class)->name('grocery-lists.show'); // TODO: US3 - T078
    // Route::get('grocery-lists/{groceryList}/generate', GroceryListsGenerate::class)->name('grocery-lists.generate'); // TODO: US3 - T076
    // Route::get('grocery-lists/{groceryList}/export', GroceryListsExport::class)->name('grocery-lists.export'); // TODO: US8 - T126

    // Settings Routes
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');

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

// Shared Grocery List Route (requires authentication)
// Route::get('grocery-lists/shared/{token}', GroceryListsShared::class)
//     ->middleware(['auth'])
//     ->name('grocery-lists.shared'); // TODO: US8 - T129

require __DIR__.'/auth.php';
