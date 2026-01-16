# YesChef

> A feature-rich recipe management and meal planning application built with Laravel, Livewire, and modern web technologies.

[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-3-FB70A9?logo=livewire)](https://livewire.laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4-38B2AC?logo=tailwind-css)](https://tailwindcss.com)
[![Pest](https://img.shields.io/badge/Pest-4-6E4C3A)](https://pestphp.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## Table of Contents

- [About](#about)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Getting Started](#getting-started)
- [Development](#development)
- [Testing](#testing)
- [Speckit Development Workflow](#speckit-development-workflow)
- [Project Architecture](#project-architecture)
- [Contributing](#contributing)
- [License](#license)

## About

**YesChef** is a comprehensive meal planning and recipe management application designed to streamline your cooking workflow. From recipe discovery to grocery shopping, YesChef handles the entire meal planning lifecycle with intelligent features like recipe imports, serving size adjustments, smart grocery lists, and shareable meal plans.

### Built with Claude Code & Speckit

This project is developed using **Claude Code** and follows a strict **Test-Driven Development (TDD)** methodology enforced through **Speckit** — a structured feature development framework. Every feature is:

- **Specified** with clear requirements and acceptance criteria
- **Planned** with detailed implementation strategies
- **Tested** BEFORE implementation (TDD is constitutional, not optional)
- **Verified** through comprehensive unit, feature, and E2E tests

Our [project constitution](>.specify/memory/constitution.md) ensures consistent quality, maintainability, and adherence to best practices across all features.

## Features

### Recipe Management

- **Create Recipes Manually**: Add recipes with complete metadata including prep/cook times, servings, meal type, cuisine, difficulty, dietary tags, and step-by-step instructions
- **Import from URLs**: Automatically fetch and parse recipes from external websites using schema.org Recipe microdata
- **Import Preview**: Review imported recipes before saving with the ability to edit any field
- **Edit & Delete**: Full CRUD operations for all recipes
- **Ingredient Management**: Associate ingredients with precise quantities (decimal precision to 3 places) and standardized measurement units
- **Source Tracking**: Distinguish between manually created and imported recipes
- **Serving Size Adjustments**: Dynamically scale ingredient quantities based on desired servings

### Meal Planning

- **Create Meal Plans**: Define meal plans with start dates, end dates, names, and descriptions
- **Recipe Assignments**: Assign recipes to specific dates and meal types (breakfast, lunch, dinner, snack)
- **Serving Multipliers**: Adjust serving sizes per meal assignment with decimal precision
- **Meal Notes**: Add notes or variations to individual meal assignments
- **Standalone Notes**: Create meal plan notes for specific dates and meal types without recipe assignments
- **Status Tracking**: Automatic computation of active, past, or future status
- **Duration Calculation**: View total days in any meal plan
- **Generate Grocery Lists**: One-click generation of shopping lists from meal plan assignments

### Grocery List Management

- **Create Lists**: Standalone or linked to meal plans
- **Smart Autocomplete**: Intelligent item suggestions with two-tier template system
  - **Common Templates**: System-wide items with consistent metadata
  - **User Templates**: Personalized items based on your usage history
- **Purchase Tracking**: Mark items as purchased with automatic timestamps
- **Category Organization**: Organize items across 10 categories (produce, dairy, meat, seafood, pantry, frozen, bakery, deli, beverages, other)
- **Quantity Formatting**: Display quantities as fractions (¼, ½, ¾) or decimals
- **Export Options**: Export lists as PDF or plain text
- **Share Lists**: Generate shareable links with optional expiration dates
- **Progress Tracking**: Visual completion percentage and completed items count
- **Soft Deletes**: Preserve deleted items for audit trails

### Item Templates

- **Template Management**: View and edit user-specific item templates in settings
- **Usage Tracking**: Track frequency and last used timestamps
- **Smart Search**: Keyword-based matching for quick item entry
- **Consistent Metadata**: Pre-fill category, unit, and default quantity

### Authentication & User Management

- **User Registration & Login**: Built with Laravel Fortify
- **Email Verification**: Secure account verification
- **Two-Factor Authentication (2FA)**: Enhanced security with TOTP support
- **Password Reset**: Secure password recovery flow
- **Profile Management**: Update user information
- **Appearance Settings**: Customize theme preferences (dark mode support)
- **Account Deletion**: Full account removal capability

## Technology Stack

### Backend

- **PHP**: 8.3
- **Laravel**: 12
- **Livewire**: 3 (full-page components)
- **Livewire Volt**: 1 (single-file components)
- **Laravel Fortify**: 1 (authentication)

### Frontend

- **Livewire Flux**: 2 (UI component library)
- **Tailwind CSS**: 4.x
- **Alpine.js**: Included with Livewire
- **Vite**: Asset bundling

### Database

- **MariaDB**: 10.11 (production via DDEV)
- **SQLite**: In-memory (development & testing)

### Development Tools

- **DDEV**: Local development environment
- **Composer**: PHP dependency management
- **NPM**: JavaScript package management

### Testing

- **Pest**: 4 (PHP testing framework)
- **Playwright**: End-to-end browser testing
- **PHPUnit**: 12 (underlying test runner)

### Code Quality

- **Laravel Pint**: PSR-12 code formatting

## Getting Started

### Prerequisites

- **Docker Desktop**: For DDEV containers
- **PHP**: 8.3 or higher
- **Composer**: Latest version
- **Node.js & NPM**: LTS version

### Installation

1. **Clone the repository**

   ```bash
   git clone <repository-url>
   cd project-tabletop
   ```

2. **Install PHP dependencies**

   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**

   ```bash
   npm install
   ```

4. **Configure environment**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Start DDEV environment**

   ```bash
   ddev start
   ```

6. **Run database migrations**

   ```bash
   ddev ssh
   php artisan migrate
   ```

7. **Seed the database (optional)**

   ```bash
   php artisan db:seed
   ```

8. **Build frontend assets**

   ```bash
   npm run dev
   ```

9. **Access the application**

   Visit: [https://yeschef.ddev.site](https://yeschef.ddev.site)

## Development

### Development Server

Start all development services concurrently:

```bash
composer dev
```

This runs:
- `php artisan serve` - Laravel development server
- `php artisan queue:listen --tries=1` - Queue worker
- `php artisan pail --timeout=0` - Real-time log monitoring
- `npm run dev` - Vite development server with hot reload

### DDEV Commands

```bash
# Start DDEV environment
ddev start

# SSH into DDEV container
ddev ssh

# Stop DDEV environment
ddev stop

# View DDEV status
ddev describe
```

### Common Artisan Commands

```bash
# Run migrations
php artisan migrate

# Fresh migration with seeders
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name

# Create Livewire component
php artisan make:livewire ComponentName

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Code Formatting

Run Laravel Pint before committing:

```bash
vendor/bin/pint --dirty
```

This ensures PSR-12 code style compliance.

## Testing

### Testing Philosophy

YesChef follows a **constitutional TDD approach** — tests are written BEFORE implementation. This is a non-negotiable principle enforced by our project governance.

### Test Coverage

- **80+ test files** across multiple layers
- **Unit Tests** (8 files): Business logic, calculations, services
- **Feature Tests** (52 files): Livewire components, integration tests
- **E2E Tests** (18 files): Full user journeys with Playwright

### Running Tests

**All PHP tests:**
```bash
composer test
# or
php artisan test
```

**Specific test file:**
```bash
php artisan test tests/Feature/RecipeTest.php
```

**Filtered tests by name:**
```bash
php artisan test --filter test_user_can_create_recipe
```

**Playwright E2E tests:**
```bash
# Run all E2E tests
npx playwright test

# Run with UI mode
npx playwright test --ui

# Run specific browser
npx playwright test --project=chromium
npx playwright test --project=firefox
npx playwright test --project=webkit
```

### Testing Stack

- **Pest 4**: Modern PHP testing framework with expressive syntax
- **Playwright**: Multi-browser E2E testing (Chromium, Firefox, WebKit)
- **In-memory SQLite**: Fast test execution with database isolation
- **RefreshDatabase**: Automatic database reset between tests
- **Factories & Seeders**: Comprehensive test data generation

## Speckit Development Workflow

### What is Speckit?

**Speckit** is a structured feature development framework integrated with Claude Code that enforces documentation, planning, and test-driven development. Every feature follows a consistent lifecycle from specification to verification.

### Feature Development Process

1. **Specify** (`/specify`)
   - Create detailed feature specification in `/specs/{feature}/spec.md`
   - Define user stories, acceptance criteria, and requirements
   - Establish success metrics

2. **Plan** (`/plan`)
   - Generate implementation plan in `plan.md`
   - Identify technical approach and architectural decisions
   - Map dependencies and phases
   - Verify constitutional compliance

3. **Tasks** (`/tasks`)
   - Break down into actionable tasks in `tasks.md`
   - Mark parallelizable tasks
   - Define task dependencies
   - Emphasize TDD requirements

4. **Implement** (`/implement`)
   - Write tests FIRST (unit, feature, E2E)
   - Verify tests FAIL
   - Implement feature code
   - Verify tests PASS

5. **Verify**
   - Run full test suite
   - Check constitutional compliance
   - Code formatting with Pint
   - Cross-artifact consistency analysis (`/analyze`)

### Project Constitution

Our [constitution](>.specify/memory/constitution.md) defines 5 core principles:

1. **Livewire-First Architecture**: All features use Livewire components
2. **Component-Driven Development**: Prefer Flux components over custom implementations
3. **Test-First Development**: Tests written before implementation (NON-NEGOTIABLE)
4. **Full-Stack Integration Testing**: E2E tests for critical user journeys
5. **Developer Experience & Observability**: DDEV, logging, code quality tools

### Available Speckit Commands

Execute via Claude Code:

- `/specify` - Create or update feature specifications
- `/plan` - Generate implementation plans
- `/tasks` - Create actionable task lists
- `/implement` - Execute implementation workflow
- `/analyze` - Cross-artifact consistency analysis
- `/clarify` - Identify underspecified areas
- `/constitution` - Update project governance

### Example Features

See `/specs/` directory for complete feature documentation:

- `001-grocery-item-lookup` - Smart autocomplete system
- `005-delete-grocery-list` - Soft deletes with audit trails
- `006-import-recipe` - URL-based recipe importing
- `007-format-ingredient-quantities` - Fraction display formatting
- `009-recipe-servings-multiplier` - Dynamic serving adjustments
- `010-meal-plan-notes` - Standalone meal notes

Each feature includes `spec.md`, `plan.md`, and `tasks.md` with comprehensive documentation.

## Project Architecture

### Livewire-First Design

YesChef uses **full-page Livewire components** instead of traditional controllers. Routes map directly to Livewire component classes:

```php
Route::get('/recipes', \App\Livewire\Recipes\Index::class);
Route::get('/meal-plans', \App\Livewire\MealPlans\Index::class);
```

### Component Structure

- **Livewire Volt**: Single-file components combining PHP logic and Blade templates
- **Flux UI**: Pre-built UI component library (buttons, forms, modals, etc.)
- **Tailwind CSS 4**: Utility-first styling with dark mode support
- **Alpine.js**: Minimal JavaScript for interactive behaviors

### Data Models

**Core Models:**
- `User` - Authentication and user data
- `Recipe` - Recipe definitions with source tracking
- `Ingredient` - Ingredient library with categorization
- `RecipeIngredient` - Junction table with quantity precision
- `MealPlan` - Meal planning container
- `MealAssignment` - Recipe-to-date/meal-type assignments
- `MealPlanNote` - Standalone notes for meal plans
- `GroceryList` - Grocery list container with sharing
- `GroceryItem` - Individual grocery items with purchase tracking
- `CommonItemTemplate` - System-wide item templates
- `UserItemTemplate` - User-customized templates

### Services

- **RecipeImporter**: Multi-component system (fetcher, parser, sanitizer)
- **GroceryListGenerator**: Generate lists from meal assignments
- **ItemAutoCompleteService**: Smart search and suggestions
- **UnitConverter**: Measurement unit conversions
- **ServingSizeScaler**: Recipe quantity adjustments
- **IngredientAggregator**: Combine ingredients from multiple recipes

### Enums

- **MealType**: breakfast, lunch, dinner, snack
- **MeasurementUnit**: 27 standardized units
- **IngredientCategory**: 10 food categories
- **SourceType**: Generated vs manually added tracking

## Contributing

Contributions are welcome! Please follow these guidelines:

1. **Review the Constitution**: Read [>.specify/memory/constitution.md](>.specify/memory/constitution.md)
2. **Use Speckit Workflow**: All features must follow specify → plan → tasks → implement
3. **Write Tests First**: TDD is mandatory, not optional
4. **Run Pint**: Format code before committing (`vendor/bin/pint --dirty`)
5. **Comprehensive Tests**: Include unit, feature, and E2E tests where applicable
6. **Documentation**: Update specs and README as needed

### Submitting Changes

1. Create a feature branch from `main`
2. Use Speckit commands to create spec/plan/tasks
3. Write failing tests
4. Implement feature
5. Verify all tests pass
6. Run Pint for code formatting
7. Submit pull request with reference to feature spec

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

**Built with [Claude Code](https://claude.ai/code) using [Speckit](https://github.com/anthropics/claude-code/tree/main/skills/speckit) and Test-Driven Development**
