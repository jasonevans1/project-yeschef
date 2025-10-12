# Implementation Plan: Family Meal Planning Application with Grocery List Management

**Branch**: `003-merge-the-2` | **Date**: 2025-10-10 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/specs/003-merge-the-2/spec.md`

**Note**: This template is filled in by the `/speckit.plan` command. See `.specify/templates/commands/plan.md` for the execution workflow.

## Summary

This implementation plan covers a comprehensive family meal planning application that enables users to browse recipes, create meal plans for flexible time periods (single days to multiple weeks), generate aggregated grocery lists from meal plans, and manually manage grocery list items. The application supports personal recipe creation, serving size adjustments, and export/sharing of grocery lists. The technical approach follows Livewire-first architecture with full-page components, Flux UI components, and test-first development using Pest for PHP tests and Playwright for E2E validation.

## Technical Context

**Language/Version**: PHP 8.3
**Primary Dependencies**: Laravel 12, Livewire 3 (with Volt and Flux), Laravel Fortify, Tailwind CSS 4.x, AlpineJS
**Storage**: MariaDB 10.11 (production/DDEV), SQLite (development/testing), Redis (cache/queue)
**Testing**: Pest (unit/feature tests), Playwright (E2E tests)
**Target Platform**: Web application (nginx server, accessed via browsers)
**Project Type**: Web application with Livewire full-page components
**Performance Goals**:
- Recipe search results in <1 second for databases up to 10,000 recipes
- Grocery list generation in <10 seconds for meal plans up to 4 weeks
- Support 100+ concurrent users without degradation
- Meal plan creation workflow completes in <5 minutes for 1 week

**Constraints**:
- <200ms p95 for page loads
- <100MB memory per request
- Mobile-responsive design (Tailwind breakpoints)
- Browser compatibility: Modern evergreen browsers (Chrome, Firefox, Safari, Edge)

**Scale/Scope**:
- Initial MVP: 500-1000 users
- Recipe database: 1000+ system recipes + unlimited user recipes
- Support meal plans up to 28 days (4 weeks)
- Grocery lists up to 500 items

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

✅ **Principle I - Livewire-First Architecture**: COMPLIANT
- All features use Livewire components as primary pattern
- Full-page components with layouts (no traditional Blade views)
- Routes point directly to component classes
- No traditional controllers for UI rendering

✅ **Principle II - Component-Driven Development**: COMPLIANT
- Will use Flux components for all UI elements
- Component organization follows established structure:
  - `app/Livewire/Recipes/` for recipe management
  - `app/Livewire/MealPlans/` for meal planning
  - `app/Livewire/GroceryLists/` for grocery list management
- Corresponding views in `resources/views/livewire/` matching class structure

✅ **Principle III - Test-First Development**: COMPLIANT
- Feature tests will be written before implementation (Pest)
- E2E tests for critical flows (Playwright)
- TDD workflow: write failing tests → implement → pass tests → refactor

✅ **Principle IV - Full-Stack Integration Testing**: COMPLIANT
- E2E tests for all critical user journeys
- Full Livewire component lifecycle validation
- SQLite for test execution, migrations verified in test environment

✅ **Principle V - Developer Experience & Observability**: COMPLIANT
- DDEV for local development
- `composer dev` runs all services
- Laravel Pint for code formatting
- Log monitoring via `php artisan pail`

**Result**: All constitutional principles satisfied. No violations require justification.

**Phase 1 Re-Check**: ✅ PASSED
- Data model follows normalized relational design
- Service classes separate business logic from components
- Component contracts define clear interaction patterns
- All design decisions documented in research.md
- Authorization policies implement least-privilege access
- No architectural patterns violate constitutional principles

## Project Structure

### Documentation (this feature)

```
specs/003-merge-the-2/
├── spec.md              # Feature specification (completed)
├── plan.md              # This file (/speckit.plan command output)
├── research.md          # Phase 0 output (generated below)
├── data-model.md        # Phase 1 output (generated below)
├── quickstart.md        # Phase 1 output (generated below)
├── contracts/           # Phase 1 output (generated below)
│   ├── recipes.yaml     # Recipe management API contracts
│   ├── meal-plans.yaml  # Meal planning API contracts
│   └── grocery-lists.yaml # Grocery list API contracts
├── checklists/
│   └── requirements.md  # Specification validation (completed)
└── tasks.md             # Phase 2 output (/speckit.tasks command - NOT created by /speckit.plan)
```

### Source Code (repository root)

```
# Laravel 12 application structure
app/
├── Livewire/
│   ├── Recipes/
│   │   ├── Index.php          # Browse/search recipes
│   │   ├── Show.php           # View recipe details
│   │   ├── Create.php         # Create personal recipe
│   │   ├── Edit.php           # Edit personal recipe
│   │   └── Delete.php         # Delete personal recipe (action component)
│   ├── MealPlans/
│   │   ├── Index.php          # List meal plans
│   │   ├── Create.php         # Create meal plan
│   │   ├── Show.php           # View meal plan calendar
│   │   ├── Edit.php           # Edit meal plan (assign/remove recipes)
│   │   └── Delete.php         # Delete meal plan (action component)
│   └── GroceryLists/
│       ├── Index.php          # List grocery lists
│       ├── Show.php           # View/manage grocery list
│       ├── Create.php         # Create standalone grocery list
│       ├── Generate.php       # Generate from meal plan
│       └── Export.php         # Export grocery list
├── Models/
│   ├── Recipe.php
│   ├── RecipeIngredient.php
│   ├── Ingredient.php
│   ├── MealPlan.php
│   ├── MealAssignment.php
│   ├── GroceryList.php
│   └── GroceryItem.php
├── Services/
│   ├── GroceryListGenerator.php    # Aggregates ingredients from meal plans
│   ├── IngredientAggregator.php    # Combines duplicate ingredients
│   ├── UnitConverter.php           # Converts between measurement units
│   └── ServingSizeScaler.php       # Scales recipe quantities
└── Enums/
    ├── MealType.php               # breakfast, lunch, dinner, snack
    ├── IngredientCategory.php     # produce, dairy, meat, etc.
    └── MeasurementUnit.php        # cups, tbsp, oz, lbs, etc.

database/
├── migrations/
│   ├── 2025_10_10_create_recipes_table.php
│   ├── 2025_10_10_create_ingredients_table.php
│   ├── 2025_10_10_create_recipe_ingredients_table.php
│   ├── 2025_10_10_create_meal_plans_table.php
│   ├── 2025_10_10_create_meal_assignments_table.php
│   ├── 2025_10_10_create_grocery_lists_table.php
│   └── 2025_10_10_create_grocery_items_table.php
├── factories/
│   ├── RecipeFactory.php
│   ├── MealPlanFactory.php
│   └── GroceryListFactory.php
└── seeders/
    ├── RecipeSeeder.php           # System recipe database
    └── DatabaseSeeder.php

resources/
├── views/
│   ├── livewire/
│   │   ├── recipes/
│   │   │   ├── index.blade.php
│   │   │   ├── show.blade.php
│   │   │   ├── create.blade.php
│   │   │   └── edit.blade.php
│   │   ├── meal-plans/
│   │   │   ├── index.blade.php
│   │   │   ├── create.blade.php
│   │   │   ├── show.blade.php    # Calendar view
│   │   │   └── edit.blade.php
│   │   └── grocery-lists/
│   │       ├── index.blade.php
│   │       ├── show.blade.php
│   │       ├── create.blade.php
│   │       └── export.blade.php
│   └── components/
│       ├── recipe-card.blade.php
│       ├── meal-calendar.blade.php
│       └── grocery-category.blade.php
├── css/
│   └── app.css
└── js/
    └── app.js

routes/
├── web.php                 # Livewire component routes
├── auth.php                # Authentication routes (existing)
└── console.php             # Artisan commands

tests/
├── Feature/
│   ├── Recipes/
│   │   ├── BrowseRecipesTest.php
│   │   ├── CreateRecipeTest.php
│   │   ├── EditRecipeTest.php
│   │   └── DeleteRecipeTest.php
│   ├── MealPlans/
│   │   ├── CreateMealPlanTest.php
│   │   ├── AssignRecipesTest.php
│   │   ├── ServingSizeAdjustmentTest.php
│   │   └── DeleteMealPlanTest.php
│   └── GroceryLists/
│       ├── GenerateGroceryListTest.php
│       ├── ManualItemManagementTest.php
│       ├── StandaloneGroceryListTest.php
│       └── ExportGroceryListTest.php
└── Unit/
    ├── IngredientAggregatorTest.php
    ├── UnitConverterTest.php
    └── ServingSizeScalerTest.php

e2e/
├── recipes.spec.ts         # Recipe browsing, search, create, edit
├── meal-plans.spec.ts      # Meal plan creation, recipe assignment
└── grocery-lists.spec.ts   # Grocery list generation, manual management
```

**Structure Decision**: This follows Laravel 12 conventions with Livewire-first architecture. All user-facing features are implemented as full-page Livewire components organized by domain (Recipes, MealPlans, GroceryLists). Service classes handle complex business logic (aggregation, unit conversion, scaling). Tests mirror the source structure for discoverability. E2E tests validate complete user journeys across components.

## Complexity Tracking

*No constitutional violations - this section is not needed.*
