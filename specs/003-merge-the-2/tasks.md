# Tasks: Family Meal Planning Application with Grocery List Management

**Input**: Design documents from `/specs/003-merge-the-2/`
**Prerequisites**: plan.md, spec.md, research.md, data-model.md, contracts/

**Organization**: Tasks are grouped by user story to enable independent implementation and testing of each story.

## Format: `[ID] [P?] [Story] Description`
- **[P]**: Can run in parallel (different files, no dependencies)
- **[Story]**: Which user story this task belongs to (US1, US2, US3, etc.)
- Include exact file paths in descriptions

---

## Phase 1: Setup (Minimal Infrastructure)

**Purpose**: Basic project infrastructure not already in place

**Note**: Most infrastructure already exists (Laravel 12, Livewire 3, Fortify auth, DDEV). Only minimal setup needed.

- [X] T001 [P] [SETUP] Run Laravel Pint to verify code formatting setup: `vendor/bin/pint --test`
- [X] T002 [P] [SETUP] Verify DDEV environment running: `ddev start && ddev describe`
- [X] T003 [P] [SETUP] Install PDF export dependency: `composer require barryvdh/laravel-dompdf`

**Checkpoint**: Development environment ready, all dependencies installed

---

## Phase 2: Foundational (Blocking Prerequisites)

**Purpose**: Core infrastructure that MUST be complete before ANY user story can be implemented

**⚠️ CRITICAL**: No user story work can begin until this phase is complete

### Database Schema (Migrations)

- [X] T004 [FOUNDATION] Create recipes table migration at `/Users/jasonevans/projects/project-tabletop/database/migrations/2025_10_12_000001_create_recipes_table.php` with columns: id, user_id (nullable FK to users), name, description, prep_time, cook_time, servings (default 4), meal_type (enum nullable), cuisine, difficulty (enum nullable), dietary_tags (JSON), instructions, image_url, timestamps. Include FULLTEXT index on (name, description).

- [X] T005 [P] [FOUNDATION] Create ingredients table migration at `/Users/jasonevans/projects/project-tabletop/database/migrations/2025_10_12_000002_create_ingredients_table.php` with columns: id, name (unique), category (enum: produce, dairy, meat, seafood, pantry, frozen, bakery, deli, beverages, other), timestamps.

- [X] T006 [FOUNDATION] Create recipe_ingredients pivot table migration at `/Users/jasonevans/projects/project-tabletop/database/migrations/2025_10_12_000003_create_recipe_ingredients_table.php` with columns: id, recipe_id (FK to recipes CASCADE), ingredient_id (FK to ingredients RESTRICT), quantity (decimal 10,3), unit (enum matching MeasurementUnit), sort_order (int default 0), notes, timestamps. Include unique constraint on (recipe_id, ingredient_id).

- [X] T007 [P] [FOUNDATION] Create meal_plans table migration at `/Users/jasonevans/projects/project-tabletop/database/migrations/2025_10_12_000004_create_meal_plans_table.php` with columns: id, user_id (FK to users CASCADE), name, start_date, end_date, description, timestamps. Include composite index on (user_id, start_date).

- [X] T008 [FOUNDATION] Create meal_assignments table migration at `/Users/jasonevans/projects/project-tabletop/database/migrations/2025_10_12_000005_create_meal_assignments_table.php` with columns: id, meal_plan_id (FK CASCADE), recipe_id (FK RESTRICT), date, meal_type (enum: breakfast, lunch, dinner, snack), serving_multiplier (decimal 5,2 default 1.00), notes, timestamps. Include unique constraint on (meal_plan_id, date, meal_type).

- [X] T009 [P] [FOUNDATION] Create grocery_lists table migration at `/Users/jasonevans/projects/project-tabletop/database/migrations/2025_10_12_000006_create_grocery_lists_table.php` with columns: id, user_id (FK CASCADE), meal_plan_id (nullable FK SET NULL), name, generated_at, regenerated_at (nullable), share_token (char 36 nullable), share_expires_at (nullable), timestamps. Include index on share_token.

- [X] T010 [FOUNDATION] Create grocery_items table migration at `/Users/jasonevans/projects/project-tabletop/database/migrations/2025_10_12_000007_create_grocery_items_table.php` with columns: id, grocery_list_id (FK CASCADE), name, quantity (nullable decimal 10,3), unit (nullable enum), category (enum same as ingredients), source_type (enum: generated, manual default manual), original_values (JSON nullable), purchased (boolean default false), purchased_at (nullable), notes, sort_order (int default 0), deleted_at (nullable for soft delete), timestamps. Include composite index on (grocery_list_id, category, sort_order).

- [X] T011 [FOUNDATION] Run all migrations and verify schema: `php artisan migrate` (ensure no errors and all tables created correctly)

### Enums

- [X] T012 [P] [FOUNDATION] Create MealType enum at `/Users/jasonevans/projects/project-tabletop/app/Enums/MealType.php` with cases: BREAKFAST='breakfast', LUNCH='lunch', DINNER='dinner', SNACK='snack'

- [X] T013 [P] [FOUNDATION] Create IngredientCategory enum at `/Users/jasonevans/projects/project-tabletop/app/Enums/IngredientCategory.php` with cases: PRODUCE='produce', DAIRY='dairy', MEAT='meat', SEAFOOD='seafood', PANTRY='pantry', FROZEN='frozen', BAKERY='bakery', DELI='deli', BEVERAGES='beverages', OTHER='other'

- [X] T014 [P] [FOUNDATION] Create MeasurementUnit enum at `/Users/jasonevans/projects/project-tabletop/app/Enums/MeasurementUnit.php` with cases for volume (TSP, TBSP, FL_OZ, CUP, PINT, QUART, GALLON, ML, LITER), weight (OZ, LB, GRAM, KG), count (WHOLE, CLOVE, SLICE, PIECE), non-standard (PINCH, DASH, TO_TASTE)

- [X] T015 [P] [FOUNDATION] Create SourceType enum at `/Users/jasonevans/projects/project-tabletop/app/Enums/SourceType.php` with cases: GENERATED='generated', MANUAL='manual'

### Base Models

- [X] T016 [P] [FOUNDATION] Create Recipe model at `/Users/jasonevans/projects/project-tabletop/app/Models/Recipe.php` with fillable fields, casts (dietary_tags to array, meal_type/difficulty to enums), relationships (belongsTo User, belongsToMany Ingredient via recipe_ingredients, hasMany MealAssignment), computed attributes (total_time, is_system_recipe, ingredient_count)

- [X] T017 [P] [FOUNDATION] Create Ingredient model at `/Users/jasonevans/projects/project-tabletop/app/Models/Ingredient.php` with fillable fields, casts (category to enum), relationships (belongsToMany Recipe via recipe_ingredients, hasMany RecipeIngredient), mutator for lowercase name storage

- [X] T018 [P] [FOUNDATION] Create RecipeIngredient model at `/Users/jasonevans/projects/project-tabletop/app/Models/RecipeIngredient.php` with fillable fields, casts (unit to enum), relationships (belongsTo Recipe, belongsTo Ingredient)

- [X] T019 [P] [FOUNDATION] Create MealPlan model at `/Users/jasonevans/projects/project-tabletop/app/Models/MealPlan.php` with fillable fields, casts (start_date/end_date to date), relationships (belongsTo User, hasMany MealAssignment, hasOne GroceryList, belongsToMany Recipe via meal_assignments), computed attributes (duration_days, is_active, is_past, is_future, assignment_count)

- [X] T020 [P] [FOUNDATION] Create MealAssignment model at `/Users/jasonevans/projects/project-tabletop/app/Models/MealAssignment.php` with fillable fields, casts (date to date, meal_type to enum), relationships (belongsTo MealPlan, belongsTo Recipe)

- [X] T021 [P] [FOUNDATION] Create GroceryList model at `/Users/jasonevans/projects/project-tabletop/app/Models/GroceryList.php` with fillable fields, casts (generated_at/regenerated_at/share_expires_at to datetime), relationships (belongsTo User, belongsTo MealPlan nullable, hasMany GroceryItem), computed attributes (is_standalone, is_meal_plan_linked, is_shared, share_url, total_items, completed_items, completion_percentage)

- [X] T022 [P] [FOUNDATION] Create GroceryItem model at `/Users/jasonevans/projects/project-tabletop/app/Models/GroceryItem.php` with fillable fields, casts (category/source_type/unit to enums, original_values to array, purchased to boolean, purchased_at to datetime), relationships (belongsTo GroceryList), SoftDeletes trait, computed attributes (is_generated, is_manual, is_edited, display_quantity)

### Service Classes (with Unit Tests FIRST)

- [X] T023 [FOUNDATION] Write unit tests for UnitConverter service at `/Users/jasonevans/projects/project-tabletop/tests/Unit/UnitConverterTest.php` covering: convert cups to fl_oz, convert pints to cups, convert lbs to oz, convert metric units, handle same-unit conversion, throw exception for incompatible types (volume to weight), handle edge cases (zero, negative, very large numbers)

- [X] T024 [FOUNDATION] Implement UnitConverter service at `/Users/jasonevans/projects/project-tabletop/app/Services/UnitConverter.php` with convert(float $quantity, MeasurementUnit $from, MeasurementUnit $to): float method, conversion tables for volume/weight units, exception handling for incompatible conversions (ensure T023 tests pass)

- [X] T025 [FOUNDATION] Write unit tests for IngredientAggregator service at `/Users/jasonevans/projects/project-tabletop/tests/Unit/IngredientAggregatorTest.php` covering: aggregate identical ingredients with same unit, aggregate identical ingredients with different compatible units (using UnitConverter), keep separate ingredients with incompatible units, handle non-standard measurements (pinch, dash), preserve category information, handle empty input

- [X] T026 [FOUNDATION] Implement IngredientAggregator service at `/Users/jasonevans/projects/project-tabletop/app/Services/IngredientAggregator.php` with aggregate(Collection $items): Collection method, utilizes UnitConverter for compatible units, groups by ingredient name (case-insensitive), sums quantities, formats output (ensure T025 tests pass)

- [X] T027 [FOUNDATION] Write unit tests for ServingSizeScaler service at `/Users/jasonevans/projects/project-tabletop/tests/Unit/ServingSizeScalerTest.php` covering: scale quantities by multiplier (1.5x, 2x, 0.5x), handle fractional results, preserve unit, handle zero/negative quantities, scale collections of ingredients

- [X] T028 [FOUNDATION] Implement ServingSizeScaler service at `/Users/jasonevans/projects/project-tabletop/app/Services/ServingSizeScaler.php` with scale(float $quantity, float $multiplier): float method and scaleIngredients(Collection $ingredients, float $multiplier): Collection method (ensure T027 tests pass)

- [X] T029 [FOUNDATION] Write unit tests for GroceryListGenerator service at `/Users/jasonevans/projects/project-tabletop/tests/Unit/GroceryListGeneratorTest.php` covering: generate list from meal plan with single recipe, aggregate duplicates across multiple recipes, apply serving multipliers, organize by category, handle empty meal plan, preserve manual items during regeneration, handle edited generated items, respect soft-deleted items

- [X] T030 [FOUNDATION] Implement GroceryListGenerator service at `/Users/jasonevans/projects/project-tabletop/app/Services/GroceryListGenerator.php` with generate(MealPlan $mealPlan): GroceryList method and regenerate(GroceryList $groceryList): GroceryList method, uses ServingSizeScaler, IngredientAggregator, UnitConverter, implements two-pass algorithm from research.md (ensure T029 tests pass)

### Authorization Policies

- [X] T031 [P] [FOUNDATION] Create RecipePolicy at `/Users/jasonevans/projects/project-tabletop/app/Policies/RecipePolicy.php` with methods: view (allow all for system recipes, owner only for personal), create (authenticated users), update (owner only, not system recipes), delete (owner only, not system recipes)

- [X] T032 [P] [FOUNDATION] Create MealPlanPolicy at `/Users/jasonevans/projects/project-tabletop/app/Policies/MealPlanPolicy.php` with methods: viewAny, view, create, update, delete (all owner-only operations)

- [X] T033 [P] [FOUNDATION] Create GroceryListPolicy at `/Users/jasonevans/projects/project-tabletop/app/Policies/GroceryListPolicy.php` with methods: viewAny, view, create, update, delete (all owner-only), viewShared (requires authentication, checks share_token and expiration)

### Factories & Seeders

- [X] T034 [P] [FOUNDATION] Create RecipeFactory at `/Users/jasonevans/projects/project-tabletop/database/factories/RecipeFactory.php` generating realistic recipe data with ingredients (via RecipeIngredient), supports user_id nullable for system recipes

- [X] T035 [P] [FOUNDATION] Create IngredientFactory at `/Users/jasonevans/projects/project-tabletop/database/factories/IngredientFactory.php` generating ingredient names with appropriate categories

- [X] T036 [P] [FOUNDATION] Create MealPlanFactory at `/Users/jasonevans/projects/project-tabletop/database/factories/MealPlanFactory.php` generating meal plans with date ranges (1-28 days)

- [X] T037 [P] [FOUNDATION] Create GroceryListFactory at `/Users/jasonevans/projects/project-tabletop/database/factories/GroceryListFactory.php` generating lists with items (both generated and manual)

- [X] T038 [FOUNDATION] Create RecipeSeeder at `/Users/jasonevans/projects/project-tabletop/database/seeders/RecipeSeeder.php` seeding 50-100 system recipes (user_id = null) with diverse cuisines, meal types, difficulties, dietary tags, and realistic ingredients (can run: php artisan db:seed --class=RecipeSeeder)

- [X] T039 [FOUNDATION] Update DatabaseSeeder at `/Users/jasonevans/projects/project-tabletop/database/seeders/DatabaseSeeder.php` to call RecipeSeeder in appropriate order

### Routes Structure

- [X] T040 [FOUNDATION] Add recipe routes to `/Users/jasonevans/projects/project-tabletop/routes/web.php` for: recipes.index, recipes.show, recipes.create, recipes.edit (all using Livewire component classes, all within auth middleware group)

- [X] T041 [FOUNDATION] Add meal plan routes to `/Users/jasonevans/projects/project-tabletop/routes/web.php` for: meal-plans.index, meal-plans.create, meal-plans.show, meal-plans.edit (all using Livewire component classes, all within auth middleware group)

- [X] T042 [FOUNDATION] Add grocery list routes to `/Users/jasonevans/projects/project-tabletop/routes/web.php` for: grocery-lists.index, grocery-lists.show, grocery-lists.create, grocery-lists.generate, grocery-lists.export, grocery-lists.shared (all using Livewire component classes, auth required except shared uses separate middleware)

**Checkpoint**: Foundation ready - user story implementation can now begin in parallel. All migrations run successfully, all models have relationships, all services have passing unit tests, all policies defined, factories work, routes registered.

---

## Phase 3: User Story 1 - Browse and View Recipes (Priority: P1) 🎯 MVP

**Goal**: Users can browse system and personal recipes, view full recipe details including ingredients and instructions, search and filter by name/meal type/dietary tags

**Independent Test**: Seed database with 10 recipes, load recipes page, verify all 10 display, click one recipe, verify full details shown, use search filter, verify results update

### Tests for User Story 1 (TDD - Write tests FIRST, ensure they FAIL)

- [X] T043 [P] [US1] Feature test for browsing recipes at `/Users/jasonevans/projects/project-tabletop/tests/Feature/Recipes/BrowseRecipesTest.php` covering: authenticated user can view recipes index, recipes display with name/image/description, pagination works (24 per page), system recipes visible to all users, user's personal recipes visible in list

- [X] T044 [P] [US1] Feature test for viewing recipe details at `/Users/jasonevans/projects/project-tabletop/tests/Feature/Recipes/ViewRecipeTest.php` covering: user can view system recipe details, user can view own recipe details, recipe shows all fields (name, description, times, servings, instructions), ingredients list displays with quantities/units, unauthorized user cannot view another user's personal recipe

- [X] T045 [P] [US1] Feature test for searching recipes at `/Users/jasonevans/projects/project-tabletop/tests/Feature/Recipes/SearchRecipesTest.php` covering: search by recipe name (full-text), search by ingredient name, filter by meal type, filter by dietary tags, combined filters (search + meal type), URL contains search parameters (shareable)

- [X] T046 [P] [US1] E2E test for recipe browsing journey at `/Users/jasonevans/projects/project-tabletop/e2e/recipes.spec.ts` covering: user logs in, navigates to recipes page, sees recipe grid, uses search filter, clicks recipe card, views full recipe details, uses back button, tries different meal type filter

### Implementation for User Story 1

- [X] T047 [P] [US1] Create Recipes\Index Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/Recipes/Index.php` with properties: #[Url] search (string), #[Url] mealTypes (array), #[Url] dietaryTags (array), render method returning paginated recipes (24 per page) with eager loading (recipeIngredients.ingredient), query builder with whereFullText, whereIn, whereJsonContains, updatedSearch() method to reset pagination

- [X] T048 [P] [US1] Create index view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/recipes/index.blade.php` using Flux components for: search input (wire:model.live.debounce.300ms="search"), meal type filter checkboxes, dietary tag filter checkboxes, recipe grid (4 columns desktop, 2 tablet, 1 mobile), recipe cards with image/name/brief description, pagination controls

- [X] T049 [P] [US1] Create Recipes\Show Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/Recipes/Show.php` with mount(Recipe $recipe) method checking authorization (view policy), computed properties for total_time and is_system_recipe, render method with eager loaded recipe data

- [X] T050 [P] [US1] Create show view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/recipes/show.blade.php` using Flux components displaying: recipe header (name, image, cuisine, difficulty), time information (prep, cook, total), servings, dietary tags as badges, ingredients list grouped with quantities/units/notes, step-by-step instructions, "Edit" button (if user owns recipe), "Back to Recipes" button

- [X] T051 [US1] Add authorization checks to routes for recipes in `/Users/jasonevans/projects/project-tabletop/routes/web.php` ensuring RecipePolicy is applied

- [X] T052 [US1] Create reusable recipe-card Blade component at `/Users/jasonevans/projects/project-tabletop/resources/views/components/recipe-card.blade.php` accepting recipe prop, displaying image/name/description/meal-type-badge/dietary-tags, clickable link to recipe.show route

**Checkpoint**: User Story 1 fully functional - users can browse recipes, search/filter, view details. Run tests (php artisan test tests/Feature/Recipes/ && npx playwright test e2e/recipes.spec.ts) - all should pass. Test manually by creating/seeding recipes and navigating through UI.

---

## Phase 4: User Story 2 - Create a Basic Meal Plan (Priority: P1)

**Goal**: Users can create meal plans with date ranges (1 day to 4 weeks), assign recipes to specific meal slots (breakfast/lunch/dinner/snack) on specific days, view meal plan in calendar format, edit assignments, delete meal plans

**Independent Test**: Create a meal plan for "Oct 14-20", assign "Chicken Pasta" to Monday dinner and "Pancakes" to Tuesday breakfast, view meal plan calendar showing both assignments, remove one assignment, verify it's gone, delete entire meal plan, verify it no longer exists

### Tests for User Story 2 (TDD - Write tests FIRST)

- [X] T053 [P] [US2] Feature test for creating meal plans at `/Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/CreateMealPlanTest.php` covering: user can create meal plan with name/start_date/end_date, validation requires all fields, validation enforces end_date >= start_date, validation enforces max 28 days duration, meal plan saved with correct user_id, redirects to meal plan show page

- [X] T054 [P] [US2] Feature test for assigning recipes at `/Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/AssignRecipesTest.php` covering: user can assign recipe to meal slot (date + meal_type), assignment saved with meal_plan_id/recipe_id/date/meal_type, unique constraint prevents duplicate assignments to same slot, user can reassign different recipe to same slot (replaces), user cannot assign to date outside meal plan range

- [X] T055 [P] [US2] Feature test for viewing meal plans at `/Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ViewMealPlanTest.php` covering: user can view own meal plan, meal plan shows all days in range, assigned recipes display in correct slots, empty slots show as available, user cannot view another user's meal plan

- [X] T056 [P] [US2] Feature test for editing meal plans at `/Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/EditMealPlanTest.php` covering: user can remove recipe from slot, user can add note to meal assignment, user can update meal plan name/dates, cannot edit another user's meal plan

- [X] T057 [P] [US2] Feature test for deleting meal plans at `/Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/DeleteMealPlanTest.php` covering: user can delete own meal plan, meal assignments cascade delete, cannot delete another user's meal plan

- [X] T058 [P] [US2] E2E test for meal planning journey at `/Users/jasonevans/projects/project-tabletop/e2e/meal-plans.spec.ts` covering: user logs in, creates new meal plan with date range, clicks on Monday dinner slot, searches for recipe, assigns recipe to slot, sees recipe appear in calendar, assigns recipe to Tuesday breakfast, views full meal plan, removes one assignment, deletes entire meal plan

### Implementation for User Story 2

- [X] T059 [P] [US2] Create MealPlans\Index Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/MealPlans/Index.php` with render method returning auth()->user()->mealPlans()->latest()->paginate(10), separate lists for active, future, and past meal plans using computed attributes

- [X] T060 [P] [US2] Create index view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/meal-plans/index.blade.php` using Flux components displaying: "Create New Meal Plan" button, list of meal plans grouped by status (Active, Upcoming, Past) showing name/date-range/assignment-count, click to view/edit

- [X] T061 [P] [US2] Create MealPlans\Create Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/MealPlans/Create.php` with #[Validate] properties: name (required|min:3|max:255), start_date (required|date|after_or_equal:today), end_date (required|date|after_or_equal:start_date, custom rule: max 28 days), save() method creating MealPlan with auth()->user()->mealPlans()->create()

- [X] T062 [P] [US2] Create create view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/meal-plans/create.blade.php` using Flux form components: text input for name, date inputs for start_date/end_date, description textarea (optional), submit button, validation error display

- [X] T063 [P] [US2] Create MealPlans\Show Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/MealPlans/Show.php` with mount(MealPlan $mealPlan) checking authorization, properties for selectedDate/selectedMealType, methods: assignRecipe(Recipe $recipe), removeAssignment(MealAssignment $assignment), render method eager loading mealAssignments.recipe

- [X] T064 [P] [US2] Create show view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/meal-plans/show.blade.php` using Flux components displaying: meal plan header (name, date range, actions), calendar grid (rows=days, columns=meal types), each cell showing assigned recipe or "Add Recipe" button, click cell opens recipe selector modal, "Generate Grocery List" button

- [X] T065 [P] [US2] Create MealPlans\Edit Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/MealPlans/Edit.php` with mount(MealPlan $mealPlan) checking authorization, #[Validate] properties for name/dates, update() method saving changes, similar to Create but pre-populated

- [X] T066 [P] [US2] Create edit view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/meal-plans/edit.blade.php` similar to create view but with existing values, "Update" vs "Create" button, "Cancel" returns to show page

- [X] T067 [P] [US2] Create MealPlans\Delete Livewire component (action) at `/Users/jasonevans/projects/project-tabletop/app/Livewire/MealPlans/Delete.php` with delete(MealPlan method checking authorization, deleting model, redirecting to index with success message

- [X] T068 [US2] Create reusable meal-calendar Blade component at `/Users/jasonevans/projects/project-tabletop/resources/views/components/meal-calendar.blade.php` accepting mealPlan and assignments props, rendering responsive grid with dates and meal slots, wire:click handlers for cell selection

- [X] T069 [US2] Create recipe selector modal partial at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/meal-plans/partials/recipe-selector.blade.php` with search input, filtered recipe list, click to assign, uses existing Recipes\Index component logic or inlined search

**Checkpoint**: User Story 2 fully functional - users can create meal plans, assign recipes to meal slots, view in calendar, edit, delete. Run tests (php artisan test tests/Feature/MealPlans/ && npx playwright test e2e/meal-plans.spec.ts) - all should pass. Test manually by creating a meal plan for the current week and assigning multiple recipes.

---

## Phase 5: User Story 3 - Generate Grocery List from Meal Plan (Priority: P1)

**Goal**: Users can generate aggregated grocery lists from meal plans with combined ingredient quantities, organized by category, mark items as purchased

**Independent Test**: Create meal plan with 3 recipes (some with overlapping ingredients like "milk" or "chicken"), generate grocery list, verify duplicate ingredients are combined with summed quantities, verify items grouped by category, mark 2 items as purchased, verify checkboxes update, verify purchased_at timestamp set

### Tests for User Story 3 (TDD - Write tests FIRST)

- [X] T070 [P] [US3] Feature test for generating grocery list at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/GenerateGroceryListTest.php` covering: user can generate list from meal plan, list contains all ingredients from assigned recipes, duplicate ingredients aggregated (same unit), duplicate ingredients aggregated (different compatible units using UnitConverter), items organized by category, grocery list linked to source meal plan, serving multipliers applied correctly, empty meal plan generates empty list with helpful message

- [X] T071 [P] [US3] Feature test for marking items purchased at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/MarkItemsTest.php` covering: user can mark item as purchased (purchased = true, purchased_at = now), user can unmark item (purchased = false, purchased_at = null), completion percentage updates correctly, only list owner can mark items

- [X] T072 [P] [US3] Feature test for viewing grocery list at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/ViewGroceryListTest.php` covering: user can view own grocery list, items display with name/quantity/unit, items grouped by category, purchased items visually distinguished, user cannot view another user's grocery list

- [X] T073 [P] [US3] E2E test for grocery list generation at `/Users/jasonevans/projects/project-tabletop/e2e/grocery-lists.spec.ts` covering: user creates meal plan, assigns 3 recipes with overlapping ingredients, clicks "Generate Grocery List", sees grocery list with aggregated items, items grouped by category (Produce, Dairy, etc.), marks 2 items as purchased, sees checkmarks, views completion progress

### Implementation for User Story 3

- [X] T074 [P] [US3] Create GroceryLists\Index Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/GroceryLists/Index.php` with render method returning auth()->user()->groceryLists()->with('mealPlan')->latest()->paginate(15), separate sections for meal-plan-linked and standalone lists

- [X] T075 [P] [US3] Create index view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/grocery-lists/index.blade.php` using Flux components displaying: list of grocery lists with name/generated-date/source-meal-plan-name/completion-percentage, "Create Standalone List" button, click to view list

- [X] T076 [P] [US3] Create GroceryLists\Generate Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/GroceryLists/Generate.php` with mount(MealPlan $mealPlan) checking authorization, generate() method using GroceryListGenerator service, handles existing list (prompt to regenerate or create new), redirects to grocery-lists.show with generated list

- [X] T077 [P] [US3] Create generate view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/grocery-lists/generate.blade.php` showing: confirmation dialog "Generate grocery list for [Meal Plan Name]?", preview of recipe count, estimated item count, "Generate" button, "Cancel" returns to meal plan

- [X] T078 [P] [US3] Create GroceryLists\Show Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/GroceryLists/Show.php` with mount(GroceryList $groceryList) checking authorization, methods: togglePurchased(GroceryItem $item), addManualItem() (for US4), editItem() (for US4), deleteItem() (for US4), regenerate() (if meal-plan-linked), render method eager loading items grouped by category

- [X] T079 [P] [US3] Create show view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/grocery-lists/show.blade.php` using Flux components displaying: list header (name, source meal plan link if applicable, completion progress bar), action buttons (Add Item, Export, Share, Regenerate if applicable), items grouped by category with category headers, each item shows checkbox (wire:click="togglePurchased"), name, quantity/unit, edit/delete icons (for US4), purchased items styled differently (strikethrough, gray)

- [X] T080 [US3] Create reusable grocery-category Blade component at `/Users/jasonevans/projects/project-tabletop/resources/views/components/grocery-category.blade.php` accepting category and items props, rendering category header with item count, list of items with checkboxes

- [X] T081 [US3] Add "Generate Grocery List" button to MealPlans\Show view (update `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/meal-plans/show.blade.php`) linking to grocery-lists.generate route with meal plan parameter

**Checkpoint**: User Story 3 fully functional - users can generate grocery lists from meal plans, view aggregated items grouped by category, mark items as purchased. Run tests (php artisan test tests/Feature/GroceryLists/Generate* && npx playwright test e2e/grocery-lists.spec.ts) - all should pass. Test manually by generating list from meal plan with duplicate ingredients, verify aggregation works, test marking items.

---

## Phase 6: User Story 4 - Manually Manage Grocery List Items (Priority: P1)

**Goal**: Users can manually add, edit, and delete grocery list items, manual items preserved during regeneration, edited generated items tracked

**Independent Test**: Open existing grocery list, click "Add Item", enter "Paper Towels" with category "other", save, verify it appears in list, edit quantity to "2" and unit to "whole", save, verify update, delete item, verify removal, regenerate list from meal plan, verify manual items still present

### Tests for User Story 4 (TDD - Write tests FIRST)

- [X] T082 [P] [US4] Feature test for adding manual items at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/AddManualItemTest.php` covering: user can add item with name only, user can add item with name/quantity/unit/category, validation requires name, validation allows optional quantity/unit, item saved with source_type='manual', item appears in correct category, user cannot add to another user's list

- [X] T083 [P] [US4] Feature test for editing items at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/EditItemTest.php` covering: user can edit manual item (all fields), user can edit generated item (tracks original_values in JSON), edited generated item marked as edited (original_values not null), user cannot edit item in another user's list, validation on edit (name required, quantity positive)

- [X] T084 [P] [US4] Feature test for deleting items at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/DeleteItemTest.php` covering: user can delete manual item (hard delete), user can delete generated item (soft delete - deleted_at set), deleted generated item not shown in list view, user cannot delete item from another user's list

- [X] T085 [P] [US4] Feature test for regeneration preserving manual edits at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/RegenerateWithManualChangesTest.php` covering: manual items preserved after regeneration, edited generated items preserved with user's values, soft-deleted generated items not re-added, unmodified generated items updated to reflect meal plan changes, new ingredients from meal plan added as generated items

- [X] T086 [P] [US4] E2E test for manual item management at `/Users/jasonevans/projects/project-tabletop/e2e/grocery-lists-manual.spec.ts` covering: user opens grocery list, clicks "Add Item", fills form (name, quantity, unit, category), saves, sees item appear, clicks edit on item, changes quantity, saves, sees update, clicks delete, confirms, item removed, user regenerates list, manually added item still present

### Implementation for User Story 4

- [X] T087 [P] [US4] Add addManualItem() method to GroceryLists\Show component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/GroceryLists/Show.php` with #[Validate] properties: itemName, itemQuantity (nullable), itemUnit (nullable), itemCategory, createItem() method saving GroceryItem with source_type='manual', resetting form, refreshing list

- [X] T088 [P] [US4] Add editItem() method to GroceryLists\Show component with properties: editingItemId, editItemName, editItemQuantity, editItemUnit, editItemCategory, startEditing(GroceryItem $item) populating edit form, saveEdit() method: if generated item, store original_values JSON before update, save changes, reset editing state

- [X] T089 [P] [US4] Add deleteItem() method to GroceryLists\Show component with delete(GroceryItem $item) method: if manual item, hard delete ($item->forceDelete()), if generated item, soft delete ($item->delete()), refresh list

- [X] T090 [P] [US4] Add regenerate() method to GroceryLists\Show component using GroceryListGenerator->regenerate($groceryList), showing confirmation dialog with diff preview (items added/updated/removed counts), success message after regeneration

- [X] T091 [P] [US4] Update show view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/grocery-lists/show.blade.php` adding: "Add Item" button opening inline form or modal, form fields for name (required), quantity (optional), unit (optional dropdown), category (dropdown with enum values), "Save Item" button, edit icon on each item opening edit form (similar to add), delete icon with confirmation, "Regenerate" button (if is_meal_plan_linked) with confirmation dialog

- [X] T092 [US4] Create item edit form partial at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/grocery-lists/partials/item-form.blade.php` reusable for add and edit, includes all fields, validation error display, category dropdown with friendly labels

- [X] T093 [US4] Update GroceryListGenerator service regenerate() method at `/Users/jasonevans/projects/project-tabletop/app/Services/GroceryListGenerator.php` implementing conflict resolution: preserve manual items (source_type='manual'), preserve edited generated items (original_values not null), skip soft-deleted generated items (deleted_at not null), update unmodified generated items, add new generated items, return updated GroceryList

**Checkpoint**: User Story 4 fully functional - users can add/edit/delete grocery items, manual items preserved during regeneration, edited items tracked. Run tests (php artisan test tests/Feature/GroceryLists/ && npx playwright test e2e/grocery-lists-manual.spec.ts) - all should pass. Test manually by adding items, editing quantities, deleting, then regenerating and verifying manual changes persist.

---

## Phase 7: User Story 5 - Create and Manage Personal Recipes (Priority: P2)

**Goal**: Users can create their own recipes with ingredients and instructions, edit and delete personal recipes, personal recipes available in meal planning

**Independent Test**: Click "Create Recipe", enter recipe name "Mom's Lasagna", add 5 ingredients with quantities, enter cooking instructions, save, verify recipe appears in user's recipe list with "My Recipe" badge, edit recipe to add ingredient, save, verify update, delete recipe, verify removal, verify recipe not available to other users

### Tests for User Story 5 (TDD - Write tests FIRST)

- [X] T094 [P] [US5] Feature test for creating recipes at `/Users/jasonevans/projects/project-tabletop/tests/Feature/Recipes/CreateRecipeTest.php` covering: user can create recipe with all fields, recipe saved with user_id, validation requires name/instructions/at-least-one-ingredient, ingredients saved to recipe_ingredients pivot with quantities/units, redirects to recipe show page, recipe appears in user's list, other users cannot see private recipe

- [X] T095 [P] [US5] Feature test for editing recipes at `/Users/jasonevans/projects/project-tabletop/tests/Feature/Recipes/EditRecipeTest.php` covering: user can edit own recipe (all fields), user can add ingredients to recipe, user can remove ingredients from recipe, user can update ingredient quantities, user cannot edit system recipe (user_id=null), user cannot edit another user's recipe, changes persist correctly

- [X] T096 [P] [US5] Feature test for deleting recipes at `/Users/jasonevans/projects/project-tabletop/tests/Feature/Recipes/DeleteRecipeTest.php` covering: user can delete own recipe, recipe and recipe_ingredients cascade delete, user cannot delete system recipe, user cannot delete another user's recipe, recipe preserved in existing meal plans (ON DELETE RESTRICT on meal_assignments)

- [X] T097 [P] [US5] Feature test for recipe authorization at `/Users/jasonevans/projects/project-tabletop/tests/Feature/Recipes/RecipeAuthorizationTest.php` covering: RecipePolicy view method allows system recipes, RecipePolicy view method allows owner to view personal recipe, RecipePolicy view method denies non-owner viewing personal recipe, update/delete policies enforce ownership

- [X] T098 [P] [US5] E2E test for recipe creation and management at `/Users/jasonevans/projects/project-tabletop/e2e/recipes-create.spec.ts` covering: user logs in, clicks "Create Recipe", fills form (name, description, prep/cook times, servings, meal type, cuisine, difficulty, dietary tags), adds ingredients (search or create new ingredient, set quantity/unit), adds instruction steps, clicks "Save Recipe", sees success message, recipe appears in list with "My Recipe" badge, user edits recipe, adds ingredient, saves, sees update, user assigns personal recipe to meal plan, user deletes recipe

### Implementation for User Story 5

- [X] T099 [P] [US5] Create Recipes\Create Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/Recipes/Create.php` with #[Validate] properties for all recipe fields, ingredients array with sub-properties (ingredient_name, quantity, unit, notes), addIngredient() method adding to array, removeIngredient($index) method, save() method: create Recipe with auth()->user()->recipes()->create(), handle ingredients (find or create Ingredient by name, create RecipeIngredient pivots), redirect to recipe show

- [X] T100 [P] [US5] Create create view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/recipes/create.blade.php` using Flux form components: text input name, textarea description, number inputs prep_time/cook_time/servings, select meal_type (enum values), text input cuisine, select difficulty (enum values), multiselect dietary_tags (array input), textarea instructions, ingredients section with "Add Ingredient" button opening ingredient form, ingredient forms showing name/quantity/unit/notes with remove button, "Save Recipe" button

- [X] T101 [P] [US5] Create Recipes\Edit Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/Recipes/Edit.php` with mount(Recipe $recipe) checking authorization (update policy), similar properties/methods to Create but pre-populated, update() method saving changes to recipe and syncing ingredients (detach removed, attach new, update existing via RecipeIngredient), redirect to recipe show

- [X] T102 [P] [US5] Create edit view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/recipes/edit.blade.php` similar to create view but with existing values, "Update Recipe" button, "Cancel" returns to show page, existing ingredients loaded in form

- [X] T103 [P] [US5] Create Recipes\Delete Livewire component (action) at `/Users/jasonevans/projects/project-tabletop/app/Livewire/Recipes/Delete.php` with delete(Recipe $recipe) method checking authorization (delete policy), try-catch for foreign key constraint (recipe in meal plans), display error if cannot delete, otherwise delete and redirect to recipes index

- [X] T104 [US5] Update Recipes\Index view to show "My Recipe" badge on personal recipes (check if recipe->user_id === auth()->id()), add "Create New Recipe" button at top of page

- [X] T105 [US5] Update Recipes\Show view to show "Edit" and "Delete" buttons only if user owns recipe (use @can blade directive with RecipePolicy), show "System Recipe" badge if user_id is null

- [X] T106 [US5] Create ingredient input partial at `/Users/jasonevans/projects/project-tabletop/resources/views/components/ingredient-input.blade.php` reusable for create and edit, includes autocomplete for ingredient names (optional enhancement - can be simple text input for MVP), quantity/unit/notes fields, remove button

**Checkpoint**: User Story 5 fully functional - users can create/edit/delete personal recipes, recipes available in meal planning, authorization enforced. Run tests (php artisan test tests/Feature/Recipes/Create* tests/Feature/Recipes/Edit* tests/Feature/Recipes/Delete* && npx playwright test e2e/recipes-create.spec.ts) - all should pass. Test manually by creating recipe, editing, using in meal plan, deleting.

---

## Phase 8: User Story 6 - Create Standalone Grocery Lists (Priority: P2)

**Goal**: Users can create grocery lists without linking to meal plans, manually add all items, manage like generated lists

**Independent Test**: Click "Create Standalone List", enter name "Party Shopping", save, verify empty list created, add 10 items manually, verify all appear, mark some as purchased, export list, verify export works without meal plan link, delete list

### Tests for User Story 6 (TDD - Write tests FIRST)

- [X] T107 [P] [US6] Feature test for creating standalone lists at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/CreateStandaloneListTest.php` covering: user can create grocery list without meal_plan_id, validation requires name, list saved with meal_plan_id=null, list marked as standalone (is_standalone computed attribute true), user cannot create list for another user

- [X] T108 [P] [US6] Feature test for standalone list operations at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/StandaloneListOperationsTest.php` covering: user can add manual items to standalone list, user can edit items, user can delete items, user can mark items purchased, standalone list has no "Regenerate" option (meal_plan_id null), user can delete standalone list

- [X] T109 [P] [US6] E2E test for standalone list creation at `/Users/jasonevans/projects/project-tabletop/e2e/grocery-lists-standalone.spec.ts` covering: user logs in, navigates to grocery lists, clicks "Create Standalone List", enters name, saves, sees empty list, adds 5 items manually with different categories, marks 2 as purchased, views completion progress, verifies no "Regenerate" button, exports list, deletes list

### Implementation for User Story 6

- [X] T110 [P] [US6] Create GroceryLists\Create Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/GroceryLists/Create.php` with #[Validate] properties: name (required|min:3|max:255), save() method creating GroceryList with auth()->user()->groceryLists()->create(['meal_plan_id' => null]), redirect to grocery-lists.show

- [X] T111 [P] [US6] Create create view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/grocery-lists/create.blade.php` using Flux form components: text input for name, description explaining "Create a shopping list not linked to any meal plan", "Create List" button, "Cancel" returns to index

- [X] T112 [US6] Update GroceryLists\Index view to clearly show "Standalone Lists" section separate from "Meal Plan Lists", add "Create Standalone List" button prominently

- [X] T113 [US6] Update GroceryLists\Show view to hide "Regenerate" button when is_standalone is true (meal_plan_id null), show different header text for standalone vs meal-plan-linked lists, show "Source: Standalone" vs "Source: [Meal Plan Name]"

**Checkpoint**: User Story 6 fully functional - users can create standalone grocery lists, add items manually, manage like any list. Run tests (php artisan test tests/Feature/GroceryLists/Standalone* && npx playwright test e2e/grocery-lists-standalone.spec.ts) - all should pass. Test manually by creating standalone list, adding items, verifying no regenerate option.

---

## Phase 9: User Story 7 - Adjust Meal Plan for Household Size (Priority: P3)

**Goal**: Users can specify serving adjustments when assigning recipes to meal plans, ingredient quantities scale proportionally, grocery lists reflect scaled amounts

**Independent Test**: Create meal plan, assign recipe that serves 4, set serving multiplier to 1.5 (for 6 servings), verify recipe shows "6 servings" in meal plan view, generate grocery list, verify all quantities scaled by 1.5x (e.g., 2 cups becomes 3 cups)

### Tests for User Story 7 (TDD - Write tests FIRST)

- [X] T114 [P] [US7] Feature test for serving adjustment at `/Users/jasonevans/projects/project-tabletop/tests/Feature/MealPlans/ServingAdjustmentTest.php` covering: user can set serving_multiplier when assigning recipe, default multiplier is 1.0, validation enforces range 0.25 to 10.0, meal assignment saves with multiplier, meal plan view shows adjusted serving count (original * multiplier), ServingSizeScaler service used in grocery list generation

- [X] T115 [P] [US7] Feature test for scaled grocery list at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/ScaledQuantitiesTest.php` covering: grocery list items reflect serving multipliers (2 cups * 1.5 = 3 cups), multiple recipes with different multipliers aggregate correctly (2 cups * 1.5 + 1 cup * 2.0 = 5 cups total), fractional results handled correctly (display as fractions or decimals)

- [X] T116 [P] [US7] E2E test for serving adjustment at `/Users/jasonevans/projects/project-tabletop/e2e/meal-plans-serving-adjustment.spec.ts` covering: user assigns recipe to meal slot, sees "Servings" input field, changes from default 4 to 6, sees multiplier calculated (1.5x), saves assignment, views meal plan showing "6 servings", generates grocery list, verifies quantities scaled correctly in grocery list

### Implementation for User Story 7

- [X] T117 [P] [US7] Update MealPlans\Show component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/MealPlans/Show.php` adding servingMultiplier property to assignRecipe() method parameters (default 1.0), save multiplier to meal_assignment.serving_multiplier column, display adjusted servings in calendar view (recipe->servings * assignment->serving_multiplier)

- [ ] T118 [P] [US7] Update show view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/meal-plans/show.blade.php` adding "Servings" input to recipe assignment flow, show original servings, input for desired servings, calculate and display multiplier, show both original and adjusted servings in calendar cells

- [ ] T119 [P] [US7] Update recipe selector modal partial at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/meal-plans/partials/recipe-selector.blade.php` adding serving adjustment UI: show recipe default servings, number input for desired servings with validation (1-100), calculated multiplier display (read-only), "Assign with [X] servings" button

- [ ] T120 [US7] Verify GroceryListGenerator service at `/Users/jasonevans/projects/project-tabletop/app/Services/GroceryListGenerator.php` correctly applies serving_multiplier from meal_assignments when generating list (should already use ServingSizeScaler from T030, verify integration)

- [ ] T121 [US7] Update meal-calendar component at `/Users/jasonevans/projects/project-tabletop/resources/views/components/meal-calendar.blade.php` to show adjusted servings badge on assigned recipes (e.g., "6 servings" or "1.5x" indicator)

**Checkpoint**: User Story 7 fully functional - users can adjust serving sizes, quantities scale correctly, grocery lists reflect adjustments. Run tests (php artisan test tests/Feature/MealPlans/ServingAdjustment* && npx playwright test e2e/meal-plans-serving-adjustment.spec.ts) - all should pass. Test manually by creating meal plan with scaled servings, generating grocery list, verifying math.

---

## Phase 10: User Story 8 - Export and Share Grocery Lists (Priority: P3)

**Goal**: Users can export grocery lists as PDF or plain text, share lists via authenticated link with expiration

**Independent Test**: Open grocery list, click "Export as PDF", verify PDF downloads with items grouped by category and checkboxes, click "Export as Text", verify text file downloads with markdown format, click "Share", generate shareable link, copy link, log out, log in as different user, paste link, verify read-only access to shared list until expiration

### Tests for User Story 8 (TDD - Write tests FIRST)

- [ ] T122 [P] [US8] Feature test for PDF export at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/ExportPdfTest.php` covering: user can export own grocery list as PDF, PDF response has correct headers (Content-Type: application/pdf, Content-Disposition: attachment), PDF contains grocery list name, PDF contains all items grouped by category, user cannot export another user's list

- [ ] T123 [P] [US8] Feature test for text export at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/ExportTextTest.php` covering: user can export own grocery list as plain text, text response has correct headers (Content-Type: text/plain), text format includes category headers and item checkboxes (markdown style), user cannot export another user's list

- [ ] T124 [P] [US8] Feature test for sharing at `/Users/jasonevans/projects/project-tabletop/tests/Feature/GroceryLists/ShareGroceryListTest.php` covering: user can generate shareable link (creates share_token UUID, sets share_expires_at), authenticated user can view shared list via token, shared view is read-only (no edit/delete buttons), expired share links denied (share_expires_at < now), unauthenticated user redirected to login, user cannot view invalid share token (404)

- [ ] T125 [P] [US8] E2E test for export and sharing at `/Users/jasonevans/projects/project-tabletop/e2e/grocery-lists-export.spec.ts` covering: user opens grocery list, clicks "Export PDF", sees download dialog, clicks "Export Text", sees download dialog, clicks "Share", sees share dialog with generated link and expiration date, copies link, logs out, logs in as different user, pastes share link, sees grocery list in read-only mode (no edit buttons), verifies items visible

### Implementation for User Story 8

- [ ] T126 [P] [US8] Create GroceryLists\Export Livewire component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/GroceryLists/Export.php` with mount(GroceryList $groceryList) checking authorization, exportPdf() method using Barryvdh\DomPDF\Facade\Pdf::loadView('grocery-lists.pdf', [...]), return $pdf->download(), exportText() method generating plain text with category headers and item checkboxes, return response()->download()

- [ ] T127 [P] [US8] Create PDF template view at `/Users/jasonevans/projects/project-tabletop/resources/views/grocery-lists/pdf.blade.php` with print-friendly styling (black/white, clear fonts), grocery list header (name, date), items grouped by category with category headers, checkbox squares (☐) for each item, item name, quantity, unit on each line

- [ ] T128 [P] [US8] Add share() method to GroceryLists\Show component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/GroceryLists/Show.php` generating UUID for share_token (Str::uuid()), setting share_expires_at to now()->addDays(7), saving grocery list, returning shareable URL (route('grocery-lists.shared', $share_token))

- [ ] T129 [P] [US8] Create shared view route and component at `/Users/jasonevans/projects/project-tabletop/app/Livewire/GroceryLists/Shared.php` with mount($token) finding grocery list by share_token, checking expiration (abort 403 if expired), middleware auth required, render read-only view (no edit/delete buttons, no mark purchased)

- [ ] T130 [P] [US8] Create shared view at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/grocery-lists/shared.blade.php` similar to show view but read-only, show "Shared by [Owner Name]" header, show expiration date, no action buttons (Add Item, Edit, Delete, Regenerate), display-only items

- [ ] T131 [US8] Update GroceryLists\Show view to add export and share buttons: "Export" dropdown menu with "Download PDF" and "Download Text" options, "Share" button opening share dialog modal showing generated link with copy button and expiration date

- [ ] T132 [US8] Create share dialog modal partial at `/Users/jasonevans/projects/project-tabletop/resources/views/livewire/grocery-lists/partials/share-dialog.blade.php` with shareable URL display, copy-to-clipboard button (JavaScript), expiration date display, "Close" button

- [ ] T133 [US8] Update GroceryListPolicy at `/Users/jasonevans/projects/project-tabletop/app/Policies/GroceryListPolicy.php` adding viewShared(User $user, GroceryList $groceryList) method checking share_token is not null and share_expires_at > now()

**Checkpoint**: User Story 8 fully functional - users can export lists to PDF/text, share via authenticated links. Run tests (php artisan test tests/Feature/GroceryLists/Export* tests/Feature/GroceryLists/Share* && npx playwright test e2e/grocery-lists-export.spec.ts) - all should pass. Test manually by exporting PDF, exporting text, generating share link, accessing as another user.

---

## Phase 11: Polish & Cross-Cutting Concerns

**Purpose**: Improvements that affect multiple user stories, final validation

- [ ] T134 [P] [POLISH] Run Laravel Pint on all PHP files: `vendor/bin/pint` to ensure consistent code formatting

- [ ] T135 [P] [POLISH] Run full test suite and verify 100% pass rate: `php artisan test && npx playwright test`

- [ ] T136 [P] [POLISH] Create development data seeder at `/Users/jasonevans/projects/project-tabletop/database/seeders/DevelopmentSeeder.php` creating: 5 test users, 100 system recipes, 10 personal recipes per user, 3 meal plans per user with assigned recipes, 5 grocery lists (3 from meal plans, 2 standalone) with items

- [ ] T137 [P] [POLISH] Update navigation menu in main layout at `/Users/jasonevans/projects/project-tabletop/resources/views/components/layouts/app.blade.php` adding links to: Recipes, Meal Plans, Grocery Lists (all within auth check)

- [ ] T138 [P] [POLISH] Create dashboard/home page at `/Users/jasonevans/projects/project-tabletop/app/Livewire/Dashboard.php` showing: upcoming meal plans (next 7 days), recent grocery lists, quick actions (Create Meal Plan, Browse Recipes, Create Shopping List)

- [ ] T139 [P] [POLISH] Add helpful empty states to all index views: when no recipes/meal-plans/grocery-lists exist, show friendly message with call-to-action button

- [ ] T140 [P] [POLISH] Add loading states to all Livewire components using wire:loading directive for better UX during operations

- [ ] T141 [P] [POLISH] Add success/error toast notifications to all form submissions using Livewire flash messages and Flux notification components

- [ ] T142 [P] [POLISH] Verify mobile responsiveness of all views using Tailwind breakpoints (test on mobile viewport in browser dev tools)

- [ ] T143 [P] [POLISH] Add database indexes review: verify all foreign keys indexed, all commonly queried columns indexed (user_id, dates, meal_type, category)

- [ ] T144 [P] [POLISH] Verify N+1 query prevention: review all Livewire components for proper eager loading (with() clauses on all relationship queries)

- [ ] T145 [POLISH] Create quickstart documentation at `/Users/jasonevans/projects/project-tabletop/specs/003-merge-the-2/quickstart.md` with: local setup instructions (ddev start, composer install, npm install, migrate, seed), common workflows (create recipe, create meal plan, generate grocery list), troubleshooting tips

- [ ] T146 [POLISH] Run manual acceptance testing for all 8 user stories following test scenarios from spec.md acceptance scenarios

- [ ] T147 [POLISH] Run performance testing: generate meal plan with 20 recipes (80+ ingredients), generate grocery list, verify <10 seconds generation time, verify page loads <200ms

- [ ] T148 [POLISH] Review security: verify all routes require authentication, verify policies applied to all resource operations, verify CSRF protection active, verify mass assignment protection on all models

**Checkpoint**: Application polished, fully tested, documented, ready for demo or deployment. All user stories independently functional and integrated correctly.

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies - can start immediately
- **Foundational (Phase 2)**: Depends on Setup completion - BLOCKS all user stories
- **User Stories (Phases 3-10)**: All depend on Foundational phase completion
  - Phase 3 (US1 - Browse Recipes): Can start after Phase 2 - No dependencies on other stories
  - Phase 4 (US2 - Meal Plans): Can start after Phase 2 - No dependencies on other stories (but typically follows US1 for UX flow)
  - Phase 5 (US3 - Generate Lists): Depends on Phase 4 (needs meal plans) - Can run parallel to US1
  - Phase 6 (US4 - Manual Items): Depends on Phase 5 (needs grocery lists) - Extends US3
  - Phase 7 (US5 - Personal Recipes): Can start after Phase 2 - Can run parallel to US2/US3/US4
  - Phase 8 (US6 - Standalone Lists): Depends on Phase 5 or 6 (needs grocery list components) - Can run parallel to US5/US7
  - Phase 9 (US7 - Serving Adjustment): Depends on Phase 4 and 5 (needs meal plans and grocery generation) - Can run parallel to US5/US6/US8
  - Phase 10 (US8 - Export/Share): Depends on Phase 5 or 6 (needs grocery lists) - Can run parallel to US5/US7
- **Polish (Phase 11)**: Depends on all desired user stories being complete

### Critical Path (Sequential Execution)

If implementing alone in priority order:
```
Phase 1 (Setup) → Phase 2 (Foundation) → Phase 3 (US1) → Phase 4 (US2) →
Phase 5 (US3) → Phase 6 (US4) → Phase 7 (US5) → Phase 8 (US6) →
Phase 9 (US7) → Phase 10 (US8) → Phase 11 (Polish)
```

### Parallel Opportunities (Team Execution)

After Phase 2 completes, these can run in parallel:

**Wave 1** (P1 Features - MVP Core):
- Developer A: Phase 3 (US1 - Browse Recipes)
- Developer B: Phase 4 (US2 - Meal Plans)
- Wait for Phase 4 to complete, then Developer B starts Phase 5

**Wave 2** (P1 Completion):
- Developer A: Can continue to Phase 7 (US5 - Personal Recipes)
- Developer B: Phase 5 (US3 - Generate Lists) → Phase 6 (US4 - Manual Items)

**Wave 3** (P2/P3 Features):
- Developer A: Phase 8 (US6 - Standalone Lists) or Phase 10 (US8 - Export)
- Developer B: Phase 9 (US7 - Serving Adjustment)

**Wave 4** (Polish):
- All developers: Phase 11 tasks (can be distributed)

### Within Each User Story Phase

- Tests MUST be written and FAIL before implementation (TDD)
- Models/Services before Livewire components
- Livewire components before views
- Views before routes/integration
- Core implementation before polish/UX enhancements
- Feature tests before E2E tests
- Story complete and tested before moving to next

### Parallel Tasks Within Phases

**Phase 2 Foundational** (after migrations complete):
- Enums (T012-T015): All 4 can run in parallel [P]
- Models (T016-T022): All 7 can run in parallel [P]
- Service unit tests (T023, T025, T027, T029): Can run in parallel [P]
- Policies (T031-T033): All 3 can run in parallel [P]
- Factories (T034-T037): All 4 can run in parallel [P]

**Phase 3 US1**:
- Tests (T043-T046): All 4 test files can be written in parallel [P]
- Components (T047, T049): Index and Show can run in parallel [P]
- Views (T048, T050, T052): Can run in parallel [P]

**Phase 4 US2**:
- Tests (T053-T058): All 6 test files can be written in parallel [P]
- Components (T059, T061, T063, T065, T067): All can run in parallel [P]
- Views (T060, T062, T064, T066, T068, T069): Can run in parallel [P]

Similar parallelization available in all user story phases for tests, components, and views that don't conflict on file paths.

---

## Implementation Strategy

### MVP First (US1-US4 Only - P1 Features)

1. Complete Phase 1: Setup (T001-T003)
2. Complete Phase 2: Foundational (T004-T042) - CRITICAL BLOCKER
3. Complete Phase 3: User Story 1 - Browse Recipes (T043-T052)
4. **STOP and VALIDATE**: Test US1 independently with seeded data
5. Complete Phase 4: User Story 2 - Meal Plans (T053-T069)
6. **STOP and VALIDATE**: Test US2 independently
7. Complete Phase 5: User Story 3 - Generate Lists (T070-T081)
8. **STOP and VALIDATE**: Test US3 independently
9. Complete Phase 6: User Story 4 - Manual Items (T082-T093)
10. **STOP and VALIDATE**: Test US4 independently
11. Complete Phase 11: Polish (T134-T148) for P1 features
12. **MVP READY**: Deploy/demo basic meal planning with grocery lists

### Incremental Delivery (Adding P2/P3 Features)

After MVP deployed:

**Increment 2** (P2 Features):
1. Complete Phase 7: User Story 5 - Personal Recipes (T094-T106)
2. Test independently → Deploy/Demo
3. Complete Phase 8: User Story 6 - Standalone Lists (T107-T113)
4. Test independently → Deploy/Demo

**Increment 3** (P3 Features):
1. Complete Phase 9: User Story 7 - Serving Adjustment (T114-T121)
2. Test independently → Deploy/Demo
3. Complete Phase 10: User Story 8 - Export/Share (T122-T133)
4. Test independently → Deploy/Demo
5. Final Polish (Phase 11) for all features

### Parallel Team Strategy (3 Developers)

**Week 1**:
- All: Phase 1 Setup (quick)
- All: Phase 2 Foundational together (pair on complex services)

**Week 2-3** (MVP Sprint):
- Developer A: Phase 3 (US1 - Recipes)
- Developer B: Phase 4 (US2 - Meal Plans)
- Developer C: Phase 2 polish (factories, seeders, tests)

**Week 4** (MVP Completion):
- Developer A: Phase 7 (US5 - Personal Recipes)
- Developer B: Phase 5 (US3 - Generate Lists)
- Developer C: Assists with integration testing

**Week 5**:
- Developer A: Phase 8 (US6 - Standalone Lists)
- Developer B: Phase 6 (US4 - Manual Items)
- Developer C: Phase 9 (US7 - Serving Adjustment)

**Week 6**:
- Developer A: Phase 10 (US8 - Export/Share)
- Developer B & C: Phase 11 (Polish)

**Week 7**:
- All: Final testing, bug fixes, documentation

---

## Notes

- **[P]** tasks = different files, no dependencies, can run in parallel
- **[Story]** label (US1, US2, etc.) maps task to specific user story for traceability
- Each user story should be independently completable and testable
- **TDD**: Tests MUST be written first and FAIL before implementation begins
- Commit after each completed task or logical task group
- Stop at each checkpoint to validate story independently
- Absolute file paths provided for all tasks (repository root: /Users/jasonevans/projects/project-tabletop)
- Run tests frequently: `php artisan test` for Pest, `npx playwright test` for E2E
- Use `ddev start` for local environment, `composer dev` to run all services concurrently
- Phase 2 Foundational is the CRITICAL PATH - nothing else can proceed until complete
- Avoid cross-story dependencies that break independence
- Each user story adds value without breaking previous stories
- Laravel 12 + Livewire 3 + Flux component architecture throughout
- All routes require authentication (existing Fortify setup)
- Authorization policies enforce ownership rules
- Mobile-responsive design required (Tailwind breakpoints)
- Performance targets: <200ms page loads, <10s grocery list generation
- Security: CSRF protection, mass assignment protection, policy authorization on all operations

---

## Task Count Summary

- **Phase 1 (Setup)**: 3 tasks
- **Phase 2 (Foundational)**: 39 tasks (CRITICAL - blocks all user stories)
- **Phase 3 (US1 - Browse Recipes)**: 10 tasks
- **Phase 4 (US2 - Meal Plans)**: 17 tasks
- **Phase 5 (US3 - Generate Lists)**: 12 tasks
- **Phase 6 (US4 - Manual Items)**: 12 tasks
- **Phase 7 (US5 - Personal Recipes)**: 13 tasks
- **Phase 8 (US6 - Standalone Lists)**: 7 tasks
- **Phase 9 (US7 - Serving Adjustment)**: 8 tasks
- **Phase 10 (US8 - Export/Share)**: 12 tasks
- **Phase 11 (Polish)**: 15 tasks

**Total**: 148 tasks

**MVP (P1 - US1-US4)**: ~81 tasks (Setup + Foundation + US1-4)
**Full Feature Set**: All 148 tasks
