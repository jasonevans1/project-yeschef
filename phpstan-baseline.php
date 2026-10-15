<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\GroceryItemController\\:\\:destroy\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/GroceryItemController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\GroceryItemController\\:\\:store\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/GroceryItemController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\GroceryItemController\\:\\:update\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/GroceryItemController.php',
];
$ignoreErrors[] = [
	'message' => '#^Using nullsafe property access on non\\-nullable type App\\\\Enums\\\\IngredientCategory\\. Use \\-\\> instead\\.$#',
	'identifier' => 'nullsafe.neverNull',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/GroceryItemController.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$category\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/app/Http/Controllers/GroceryListController.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/GroceryListController.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/GroceryListController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\GroceryListController\\:\\:exportPdf\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/GroceryListController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\GroceryListController\\:\\:exportText\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/GroceryListController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\GroceryListController\\:\\:generatePlainText\\(\\) has parameter \\$itemsByCategory with no type specified\\.$#',
	'identifier' => 'missingType.parameter',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/GroceryListController.php',
];
$ignoreErrors[] = [
	'message' => '#^Unable to resolve the template type TGroupKey in call to method Illuminate\\\\Support\\\\Collection\\<int,Illuminate\\\\Database\\\\Eloquent\\\\Model\\>\\:\\:groupBy\\(\\)$#',
	'identifier' => 'argument.templateType',
	'count' => 2,
	'path' => __DIR__ . '/app/Http/Controllers/GroceryListController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\MealAssignmentController\\:\\:destroy\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/MealAssignmentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\MealAssignmentController\\:\\:store\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/MealAssignmentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\MealAssignmentController\\:\\:update\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/MealAssignmentController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\MealPlanController\\:\\:destroy\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/MealPlanController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\MealPlanController\\:\\:store\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/MealPlanController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\MealPlanController\\:\\:update\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/MealPlanController.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Http\\\\Controllers\\\\RecipeController\\:\\:destroy\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Http/Controllers/RecipeController.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Contracts\\\\Auth\\\\Authenticatable\\:\\:\\$email\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Listeners/ResolvePendingShares.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Contracts\\\\Auth\\\\Authenticatable\\:\\:\\$id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Listeners/ResolvePendingShares.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\Actions\\\\Logout\\:\\:__invoke\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Actions/Logout.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\Auth\\\\Login\\:\\:validateCredentials\\(\\) should return App\\\\Models\\\\User but returns Illuminate\\\\Contracts\\\\Auth\\\\Authenticatable\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Auth/Login.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\Dashboard\\:\\:recentGroceryLists\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Dashboard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\Dashboard\\:\\:render\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Dashboard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\Dashboard\\:\\:upcomingMealPlans\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Dashboard.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Create\\:\\:render\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Create.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Create\\:\\:save\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Create.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$category\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Export.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Export.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Export.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Export\\:\\:exportPdf\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Export.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Export\\:\\:exportText\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Export.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Export\\:\\:generatePlainText\\(\\) has parameter \\$itemsByCategory with no type specified\\.$#',
	'identifier' => 'missingType.parameter',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Export.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Export\\:\\:mount\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Export.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Export\\:\\:render\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Export.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$view of function view expects view\\-string\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Export.php',
];
$ignoreErrors[] = [
	'message' => '#^Unable to resolve the template type TGroupKey in call to method Illuminate\\\\Support\\\\Collection\\<int,Illuminate\\\\Database\\\\Eloquent\\\\Model\\>\\:\\:groupBy\\(\\)$#',
	'identifier' => 'argument.templateType',
	'count' => 2,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Export.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$userId of method App\\\\Services\\\\GroceryListGenerator\\:\\:getCategoryItemCounts\\(\\) expects int, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Generate.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$userId of method App\\\\Services\\\\GroceryListGenerator\\:\\:getIngredientPreview\\(\\) expects int, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Generate.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\GroceryLists\\\\Generate\\:\\:\\$savedPantry \\(array\\<int, string\\>\\) does not accept array\\|string\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Generate.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\GroceryLists\\\\Generate\\:\\:\\$savedPreferences \\(array\\<int, string\\>\\) does not accept array\\|string\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Generate.php',
];
$ignoreErrors[] = [
	'message' => '#^Using nullsafe property access "\\?\\-\\>excluded_categories" on left side of \\?\\? is unnecessary\\. Use \\-\\> instead\\.$#',
	'identifier' => 'nullsafe.neverNull',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Generate.php',
];
$ignoreErrors[] = [
	'message' => '#^Using nullsafe property access "\\?\\-\\>excluded_ingredients" on left side of \\?\\? is unnecessary\\. Use \\-\\> instead\\.$#',
	'identifier' => 'nullsafe.neverNull',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Generate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Index\\:\\:render\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Index.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property App\\\\Livewire\\\\GroceryLists\\\\Shared\\:\\:\\$itemsByCategory\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Shared.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$category\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Shared.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Shared\\:\\:getItemsByCategoryProperty\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Shared.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Shared\\:\\:mount\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Shared.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Shared\\:\\:render\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Shared.php',
];
$ignoreErrors[] = [
	'message' => '#^Unable to resolve the template type TGroupKey in call to method Illuminate\\\\Support\\\\Collection\\<int,Illuminate\\\\Database\\\\Eloquent\\\\Model\\>\\:\\:groupBy\\(\\)$#',
	'identifier' => 'argument.templateType',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Shared.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property App\\\\Livewire\\\\GroceryLists\\\\Show\\:\\:\\$itemsByCategory\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$category\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$name\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$recipe\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Show\\:\\:calculateRegenerateDiff\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Show\\:\\:collectIngredientsFromMealPlan\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Show\\:\\:getItemsByCategoryProperty\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Show\\:\\:rules\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\GroceryLists\\\\Show\\:\\:selectGroceryItem\\(\\) has parameter \\$item with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$mealPlan of method App\\\\Livewire\\\\GroceryLists\\\\Show\\:\\:collectIngredientsFromMealPlan\\(\\) expects App\\\\Models\\\\MealPlan, Illuminate\\\\Database\\\\Eloquent\\\\Model\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$userId of method App\\\\Services\\\\ItemAutoCompleteService\\:\\:query\\(\\) expects int, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\GroceryLists\\\\Show\\:\\:\\$itemQuantity \\(string\\|null\\) does not accept float\\|null\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Unable to resolve the template type TGroupKey in call to method Illuminate\\\\Support\\\\Collection\\<int,Illuminate\\\\Database\\\\Eloquent\\\\Model\\>\\:\\:groupBy\\(\\)$#',
	'identifier' => 'argument.templateType',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Using nullsafe property access on non\\-nullable type App\\\\Enums\\\\IngredientCategory\\. Use \\-\\> instead\\.$#',
	'identifier' => 'nullsafe.neverNull',
	'count' => 2,
	'path' => __DIR__ . '/app/Livewire/GroceryLists/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Create\\:\\:mount\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Create.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Create\\:\\:render\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Create.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Create\\:\\:save\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Create.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Delete\\:\\:delete\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Delete.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Delete\\:\\:render\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Delete.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$view of function view expects view\\-string\\|null, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Delete.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Edit\\:\\:mount\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Edit\\:\\:render\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Edit\\:\\:update\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Index\\:\\:render\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Index.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property App\\\\Livewire\\\\MealPlans\\\\Show\\:\\:\\$selectedAssignment\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$date\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$meal_type\\.$#',
	'identifier' => 'property.notFound',
	'count' => 2,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$value on string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method format\\(\\) on string\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Show\\:\\:getRecipesProperty\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Collection does not specify its types\\: TKey, TModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Show\\:\\:openNoteForm\\(\\) has parameter \\$date with no type specified\\.$#',
	'identifier' => 'missingType.parameter',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Show\\:\\:openNoteForm\\(\\) has parameter \\$mealType with no type specified\\.$#',
	'identifier' => 'missingType.parameter',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Show\\:\\:openRecipeSelector\\(\\) has parameter \\$date with no type specified\\.$#',
	'identifier' => 'missingType.parameter',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\MealPlans\\\\Show\\:\\:openRecipeSelector\\(\\) has parameter \\$mealType with no type specified\\.$#',
	'identifier' => 'missingType.parameter',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Using nullsafe property access "\\?\\-\\>value" on left side of \\?\\? is unnecessary\\. Use \\-\\> instead\\.$#',
	'identifier' => 'nullsafe.neverNull',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/MealPlans/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:recipeIngredients\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Create.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$userId of method App\\\\Services\\\\IngredientCategorizationService\\:\\:resolve\\(\\) expects int, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Create.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\Recipes\\\\Create\\:\\:\\$dietary_tags type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Create.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\Recipes\\\\Create\\:\\:\\$ingredients type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Create.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between mixed and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Create.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between mixed and null will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Create.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$ingredient\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$notes\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$quantity\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$unit\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$callback of method Illuminate\\\\Database\\\\Eloquent\\\\Collection\\<int,Illuminate\\\\Database\\\\Eloquent\\\\Model\\>\\:\\:map\\(\\) contains unresolvable type\\.$#',
	'identifier' => 'argument.unresolvableType',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$userId of method App\\\\Services\\\\IngredientCategorizationService\\:\\:resolve\\(\\) expects int, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\Recipes\\\\Edit\\:\\:\\$dietary_tags type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\Recipes\\\\Edit\\:\\:\\$ingredients type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Return type of call to method Illuminate\\\\Database\\\\Eloquent\\\\Collection\\<int,Illuminate\\\\Database\\\\Eloquent\\\\Model\\>\\:\\:map\\(\\) contains unresolvable type\\.$#',
	'identifier' => 'method.unresolvableReturnType',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between mixed and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between mixed and null will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Edit.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$json of method App\\\\Services\\\\RecipeImporter\\\\RecipeImportService\\:\\:parseJson\\(\\) expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Import.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$string of function strlen expects string, string\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/app/Livewire/Recipes/Import.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:recipeIngredients\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/ImportPreview.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$userId of method App\\\\Services\\\\IngredientCategorizationService\\:\\:resolve\\(\\) expects int, int\\|string\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/ImportPreview.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\Recipes\\\\ImportPreview\\:\\:\\$dietary_tags type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/ImportPreview.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\Recipes\\\\ImportPreview\\:\\:\\$ingredients type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/ImportPreview.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\!\\=\\= between mixed and null will always evaluate to true\\.$#',
	'identifier' => 'notIdentical.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/ImportPreview.php',
];
$ignoreErrors[] = [
	'message' => '#^Strict comparison using \\=\\=\\= between mixed and null will always evaluate to false\\.$#',
	'identifier' => 'identical.alwaysFalse',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/ImportPreview.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\Recipes\\\\Index\\:\\:\\$dietaryTags type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Index.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\Recipes\\\\Index\\:\\:\\$mealTypes type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Index.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\Recipes\\\\Show\\:\\:getMealPlansProperty\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Recipes/Show.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\Settings\\\\ItemTemplates\\:\\:render\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Settings/ItemTemplates.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access property \\$value on string\\.$#',
	'identifier' => 'property.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/app/Livewire/Settings/ItemTemplatesEdit.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\Settings\\\\ItemTemplatesEdit\\:\\:render\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Settings/ItemTemplatesEdit.php',
];
$ignoreErrors[] = [
	'message' => '#^Match expression does not handle remaining value\\: string$#',
	'identifier' => 'match.unhandled',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Settings/Sharing.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Livewire\\\\Settings\\\\TwoFactor\\:\\:getModalConfigProperty\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Settings/TwoFactor.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Livewire\\\\Settings\\\\TwoFactor\\\\RecoveryCodes\\:\\:\\$recoveryCodes type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Livewire/Settings/TwoFactor/RecoveryCodes.php',
];
$ignoreErrors[] = [
	'message' => '#^Class App\\\\Models\\\\CommonItemTemplate uses generic trait Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\HasFactory but does not specify its types\\: TFactory$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/CommonItemTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\ContentShare\\:\\:owner\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/ContentShare.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\ContentShare\\:\\:recipient\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/ContentShare.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\ContentShare\\:\\:shareable\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\MorphTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/ContentShare.php',
];
$ignoreErrors[] = [
	'message' => '#^Array has 5 duplicate keys with value 0 \\(0\\.25, 0\\.33, 0\\.5, 0\\.66, 0\\.75\\)\\.$#',
	'identifier' => 'array.duplicateKey',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/GroceryItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Class App\\\\Models\\\\GroceryItem uses generic trait Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\HasFactory but does not specify its types\\: TFactory$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/GroceryItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Invalid array key type float\\.$#',
	'identifier' => 'array.invalidKey',
	'count' => 5,
	'path' => __DIR__ . '/app/Models/GroceryItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\GroceryItem\\:\\:groceryList\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/GroceryItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Using nullsafe property access "\\?\\-\\>value" on left side of \\?\\? is unnecessary\\. Use \\-\\> instead\\.$#',
	'identifier' => 'nullsafe.neverNull',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/GroceryItem.php',
];
$ignoreErrors[] = [
	'message' => '#^Class App\\\\Models\\\\GroceryList uses generic trait Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\HasFactory but does not specify its types\\: TFactory$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/GroceryList.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\GroceryList\\:\\:contentShares\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\MorphMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/GroceryList.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\GroceryList\\:\\:groceryItems\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/GroceryList.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\GroceryList\\:\\:mealPlan\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/GroceryList.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\GroceryList\\:\\:scopeAccessibleBy\\(\\) has parameter \\$query with generic class Illuminate\\\\Database\\\\Eloquent\\\\Builder but does not specify its types\\: TModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/GroceryList.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\GroceryList\\:\\:scopeAccessibleBy\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Builder does not specify its types\\: TModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/GroceryList.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\GroceryList\\:\\:user\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/GroceryList.php',
];
$ignoreErrors[] = [
	'message' => '#^Class App\\\\Models\\\\Ingredient uses generic trait Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\HasFactory but does not specify its types\\: TFactory$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Ingredient.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Ingredient\\:\\:recipeIngredients\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Ingredient.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Ingredient\\:\\:recipes\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsToMany does not specify its types\\: TRelatedModel, TDeclaringModel, TPivotModel, TAccessor \\(2\\-4 required\\)$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Ingredient.php',
];
$ignoreErrors[] = [
	'message' => '#^Class App\\\\Models\\\\MealAssignment uses generic trait Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\HasFactory but does not specify its types\\: TFactory$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealAssignment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealAssignment\\:\\:mealPlan\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealAssignment.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealAssignment\\:\\:recipe\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealAssignment.php',
];
$ignoreErrors[] = [
	'message' => '#^Class App\\\\Models\\\\MealPlan uses generic trait Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\HasFactory but does not specify its types\\: TFactory$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealPlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealPlan\\:\\:contentShares\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\MorphMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealPlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealPlan\\:\\:getDurationDaysAttribute\\(\\) should return int but returns float\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealPlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealPlan\\:\\:groceryList\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasOne does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealPlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealPlan\\:\\:mealAssignments\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealPlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealPlan\\:\\:mealPlanNotes\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealPlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealPlan\\:\\:recipes\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsToMany does not specify its types\\: TRelatedModel, TDeclaringModel, TPivotModel, TAccessor \\(2\\-4 required\\)$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealPlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealPlan\\:\\:scopeAccessibleBy\\(\\) has parameter \\$query with generic class Illuminate\\\\Database\\\\Eloquent\\\\Builder but does not specify its types\\: TModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealPlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealPlan\\:\\:scopeAccessibleBy\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Builder does not specify its types\\: TModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealPlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealPlan\\:\\:user\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealPlan.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\MealPlanNote\\:\\:mealPlan\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/MealPlanNote.php',
];
$ignoreErrors[] = [
	'message' => '#^Class App\\\\Models\\\\Recipe uses generic trait Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\HasFactory but does not specify its types\\: TFactory$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Recipe\\:\\:contentShares\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\MorphMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Recipe\\:\\:ingredients\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsToMany does not specify its types\\: TRelatedModel, TDeclaringModel, TPivotModel, TAccessor \\(2\\-4 required\\)$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Recipe\\:\\:mealAssignments\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Recipe\\:\\:recipeIngredients\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Recipe\\:\\:scopeAccessibleBy\\(\\) has parameter \\$query with generic class Illuminate\\\\Database\\\\Eloquent\\\\Builder but does not specify its types\\: TModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Recipe\\:\\:scopeAccessibleBy\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Builder does not specify its types\\: TModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Recipe\\:\\:scopeImported\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Recipe\\:\\:scopeImported\\(\\) has parameter \\$query with no type specified\\.$#',
	'identifier' => 'missingType.parameter',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Recipe\\:\\:scopeManual\\(\\) has no return type specified\\.$#',
	'identifier' => 'missingType.return',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Recipe\\:\\:scopeManual\\(\\) has parameter \\$query with no type specified\\.$#',
	'identifier' => 'missingType.parameter',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\Recipe\\:\\:user\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/Recipe.php',
];
$ignoreErrors[] = [
	'message' => '#^Class App\\\\Models\\\\RecipeIngredient uses generic trait Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\HasFactory but does not specify its types\\: TFactory$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/RecipeIngredient.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\RecipeIngredient\\:\\:ingredient\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/RecipeIngredient.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\RecipeIngredient\\:\\:recipe\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/RecipeIngredient.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\User\\:\\:groceryLists\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\User\\:\\:incomingShares\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\User\\:\\:itemTemplates\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\User\\:\\:mealPlans\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\User\\:\\:outgoingShares\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\User\\:\\:recipes\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/User.php',
];
$ignoreErrors[] = [
	'message' => '#^Class App\\\\Models\\\\UserItemTemplate uses generic trait Illuminate\\\\Database\\\\Eloquent\\\\Factories\\\\HasFactory but does not specify its types\\: TFactory$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/UserItemTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Models\\\\UserItemTemplate\\:\\:user\\(\\) return type with generic class Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\BelongsTo does not specify its types\\: TRelatedModel, TDeclaringModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Models/UserItemTemplate.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$user_id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Observers/GroceryItemObserver.php',
];
$ignoreErrors[] = [
	'message' => '#^Using nullsafe property access on non\\-nullable type App\\\\Enums\\\\IngredientCategory\\. Use \\-\\> instead\\.$#',
	'identifier' => 'nullsafe.neverNull',
	'count' => 1,
	'path' => __DIR__ . '/app/Observers/GroceryItemObserver.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$recipe\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\HasMany\\:\\:withTrashed\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\GroceryListGenerator\\:\\:aggregateIngredients\\(\\) has parameter \\$ingredients with generic class Illuminate\\\\Support\\\\Collection but does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\GroceryListGenerator\\:\\:aggregateIngredients\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\GroceryListGenerator\\:\\:collectIngredientsFromMealPlan\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\GroceryListGenerator\\:\\:getAggregatedIngredients\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\GroceryListGenerator\\:\\:getIngredientPreview\\(\\) should return Illuminate\\\\Support\\\\Collection\\<int, array\\{name\\: string, quantity\\: float\\|int\\|numeric\\-string\\|null, unit\\: string\\|null, category\\: string, category_label\\: string\\}\\> but returns Illuminate\\\\Support\\\\Collection\\<int, array\\{name\\: mixed, quantity\\: mixed, unit\\: string\\|null, category\\: string, category_label\\: string\\}\\>\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\GroceryListGenerator\\:\\:organizeByCategory\\(\\) has parameter \\$ingredients with generic class Illuminate\\\\Support\\\\Collection but does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\GroceryListGenerator\\:\\:organizeByCategory\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\GroceryListGenerator\\:\\:processIngredients\\(\\) has parameter \\$ingredients with generic class Illuminate\\\\Support\\\\Collection but does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\GroceryListGenerator\\:\\:processIngredients\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$mealPlan of method App\\\\Services\\\\GroceryListGenerator\\:\\:collectIngredientsFromMealPlan\\(\\) expects App\\\\Models\\\\MealPlan, Illuminate\\\\Database\\\\Eloquent\\\\Model\\|null given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Property App\\\\Services\\\\GroceryListGenerator\\:\\:\\$unitConverter is never read, only written\\.$#',
	'identifier' => 'property.onlyWritten',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Unable to resolve the template type TGroupKey in call to method Illuminate\\\\Support\\\\Collection\\<\\(int\\|string\\),mixed\\>\\:\\:groupBy\\(\\)$#',
	'identifier' => 'argument.templateType',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/GroceryListGenerator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\IngredientAggregator\\:\\:aggregate\\(\\) has parameter \\$items with generic class Illuminate\\\\Support\\\\Collection but does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/IngredientAggregator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\IngredientAggregator\\:\\:aggregate\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/IngredientAggregator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\IngredientAggregator\\:\\:aggregateCompatibleItems\\(\\) has parameter \\$items with generic class Illuminate\\\\Support\\\\Collection but does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/IngredientAggregator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\IngredientAggregator\\:\\:aggregateCompatibleItems\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/IngredientAggregator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\IngredientAggregator\\:\\:aggregateGroup\\(\\) has parameter \\$group with generic class Illuminate\\\\Support\\\\Collection but does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/IngredientAggregator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\IngredientAggregator\\:\\:aggregateGroup\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/IngredientAggregator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\IngredientAggregator\\:\\:mergeRecipeIds\\(\\) has parameter \\$items with generic class Illuminate\\\\Support\\\\Collection but does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/IngredientAggregator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\IngredientCategorizationService\\:\\:resolve\\(\\) should return App\\\\Enums\\\\IngredientCategory but returns string\\.$#',
	'identifier' => 'return.type',
	'count' => 2,
	'path' => __DIR__ . '/app/Services/IngredientCategorizationService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\ItemAutoCompleteService\\:\\:formatSuggestion\\(\\) has parameter \\$template with no type specified\\.$#',
	'identifier' => 'missingType.parameter',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/ItemAutoCompleteService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\ItemAutoCompleteService\\:\\:formatSuggestion\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/ItemAutoCompleteService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\ItemAutoCompleteService\\:\\:mergeAndDeduplicate\\(\\) has parameter \\$commonTemplates with generic class Illuminate\\\\Support\\\\Collection but does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/ItemAutoCompleteService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\ItemAutoCompleteService\\:\\:mergeAndDeduplicate\\(\\) has parameter \\$userTemplates with generic class Illuminate\\\\Support\\\\Collection but does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/ItemAutoCompleteService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\ItemAutoCompleteService\\:\\:mergeAndDeduplicate\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/ItemAutoCompleteService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\ItemAutoCompleteService\\:\\:query\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/ItemAutoCompleteService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\ItemAutoCompleteService\\:\\:queryCommonTemplates\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/ItemAutoCompleteService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\ItemAutoCompleteService\\:\\:queryUserTemplates\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/ItemAutoCompleteService.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 0 on list\\<string\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/QuantityFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset 1 on list\\<string\\>\\|false\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/QuantityFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$value of function count expects array\\|Countable, list\\<string\\>\\|false given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/QuantityFormatter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\RecipeImporter\\\\RecipeImportService\\:\\:extractStringValue\\(\\) has parameter \\$value with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/RecipeImporter/RecipeImportService.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\RecipeImporter\\\\RecipeImportService\\:\\:mapCategory\\(\\) has parameter \\$category with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/RecipeImporter/RecipeImportService.php',
];
$ignoreErrors[] = [
	'message' => '#^Variable \\$category in empty\\(\\) always exists and is not falsy\\.$#',
	'identifier' => 'empty.variable',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/RecipeImporter/RecipeImportService.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @return with type array\\<string\\> is incompatible with native type Closure\\.$#',
	'identifier' => 'return.phpDocType',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/RecipeImporter/UrlSafetyValidator.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\ServingSizeScaler\\:\\:scaleIngredients\\(\\) has parameter \\$ingredients with generic class Illuminate\\\\Support\\\\Collection but does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/ServingSizeScaler.php',
];
$ignoreErrors[] = [
	'message' => '#^Method App\\\\Services\\\\ServingSizeScaler\\:\\:scaleIngredients\\(\\) return type with generic class Illuminate\\\\Support\\\\Collection does not specify its types\\: TKey, TValue$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/app/Services/ServingSizeScaler.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$string of function explode expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/config/app.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$title of static method Illuminate\\\\Support\\\\Str\\:\\:slug\\(\\) expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/config/cache.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$title of static method Illuminate\\\\Support\\\\Str\\:\\:slug\\(\\) expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/config/database.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$string of function explode expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/config/logging.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$url of function parse_url expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/config/mail.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$title of static method Illuminate\\\\Support\\\\Str\\:\\:slug\\(\\) expects string, bool\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/config/session.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property App\\\\Models\\\\User\\|Illuminate\\\\Database\\\\Eloquent\\\\Collection\\<int, App\\\\Models\\\\User\\>\\:\\:\\$email\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/database/factories/ContentShareFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Using nullsafe property access "\\?\\-\\>email" on left side of \\?\\? is unnecessary\\. Use \\-\\> instead\\.$#',
	'identifier' => 'nullsafe.neverNull',
	'count' => 1,
	'path' => __DIR__ . '/database/factories/ContentShareFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Using nullsafe property access "\\?\\-\\>id" on left side of \\?\\? is unnecessary\\. Use \\-\\> instead\\.$#',
	'identifier' => 'nullsafe.neverNull',
	'count' => 3,
	'path' => __DIR__ . '/database/factories/ContentShareFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to an undefined method App\\\\Models\\\\GroceryList\\:\\:items\\(\\)\\.$#',
	'identifier' => 'method.notFound',
	'count' => 2,
	'path' => __DIR__ . '/database/factories/GroceryListFactory.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$id\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/database/seeders/DevelopmentSeeder.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Illuminate\\\\Database\\\\Eloquent\\\\Model\\:\\:\\$recipeIngredients\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/database/seeders/DevelopmentSeeder.php',
];
$ignoreErrors[] = [
	'message' => '#^Match expression does not handle remaining values\\: int\\<min, \\-1\\>\\|int\\<3, max\\>$#',
	'identifier' => 'match.unhandled',
	'count' => 2,
	'path' => __DIR__ . '/database/seeders/DevelopmentSeeder.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Database\\\\Seeders\\\\DevelopmentSeeder\\:\\:createMealPlanWithAssignments\\(\\) has parameter \\$recipes with generic class Illuminate\\\\Database\\\\Eloquent\\\\Collection but does not specify its types\\: TKey, TModel$#',
	'identifier' => 'missingType.generics',
	'count' => 1,
	'path' => __DIR__ . '/database/seeders/DevelopmentSeeder.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
