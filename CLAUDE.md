# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application built with Livewire 3, Volt, and Flux components. It uses the Laravel Fortify starter kit for authentication and provides a full-featured authentication system with two-factor authentication support.

## Development Environment

The project uses DDEV for local development:
- **Start development environment**: `ddev start`
- **Access application**: https://project-tabletop.ddev.site
- **SSH into container**: `ddev ssh`
- **Stop environment**: `ddev stop`

DDEV Configuration:
- PHP 8.3
- MariaDB 10.11
- nginx-fpm webserver
- SQLite for local/testing (MariaDB available via DDEV)

## Common Commands

### Backend (PHP/Laravel)

**Development Server (with all services)**:
```bash
composer dev
```
This runs concurrently:
- `php artisan serve` - Laravel development server
- `php artisan queue:listen --tries=1` - Queue worker
- `php artisan pail --timeout=0` - Log monitoring
- `npm run dev` - Vite dev server

**Testing**:
```bash
# Run all tests
composer test
# Or directly with artisan
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run specific test method
php artisan test --filter test_method_name
```

**Code Quality**:
```bash
# Run Laravel Pint (code formatter)
vendor/bin/pint
```

**Database**:
```bash
# Run migrations
php artisan migrate

# Fresh migrate with seeders
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name
```

**Artisan Utilities**:
```bash
# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Generate application key
php artisan key:generate
```

### Frontend (JavaScript/Vite)

```bash
# Start Vite dev server
npm run dev

# Build for production
npm run build
```

### End-to-End Testing

```bash
# Run Playwright tests
npx playwright test

# Run tests in UI mode
npx playwright test --ui

# Run tests in specific browser
npx playwright test --project=chromium
```

Test directory: `e2e/`

## Architecture Overview

### Backend Structure

**Authentication Flow**:
- Uses Laravel Fortify for authentication backend
- Livewire components handle all auth UI (`app/Livewire/Auth/`)
- Authentication routes defined in `routes/auth.php`
- Supports: login, registration, password reset, email verification, 2FA
- Two-factor authentication via `app/Livewire/Settings/TwoFactor.php`

**Livewire Component Organization**:
- `app/Livewire/Auth/` - Authentication components (Login, Register, ForgotPassword, etc.)
- `app/Livewire/Settings/` - User settings components (Profile, Password, Appearance, TwoFactor)
- `app/Livewire/Actions/` - Reusable actions (Logout)
- All components follow full-page component pattern with corresponding Blade views

**Routing**:
- `routes/web.php` - Main application routes
- `routes/auth.php` - Authentication routes
- `routes/console.php` - Artisan console commands
- Uses Livewire component classes directly as route handlers (e.g., `Route::get('login', Login::class)`)

**Views Structure**:
- `resources/views/livewire/` - Livewire component views (matches Livewire class structure)
- `resources/views/components/` - Reusable Blade components
- `resources/views/components/layouts/` - Layout components (app, auth)
- `resources/views/flux/` - Flux UI component customizations
- Uses Livewire Flux for UI components

### Frontend Structure

**Styling**:
- Tailwind CSS 4.x via Vite plugin
- Configuration in Vite (no separate tailwind.config.js)
- Main CSS entry: `resources/css/app.css`

**JavaScript**:
- Minimal custom JavaScript (Livewire handles most interactivity)
- Vite for asset bundling
- Entry point: `resources/js/app.js`
- Livewire provides reactive SPA-like experience without heavy JavaScript

### Testing Structure

**PHP Tests** (Pest):
- `tests/Unit/` - Unit tests
- `tests/Feature/` - Feature/integration tests
- Uses in-memory SQLite for testing (configured in `phpunit.xml`)

**E2E Tests** (Playwright):
- `e2e/` - Browser-based end-to-end tests
- Configured in `playwright.config.ts`
- Tests run against Chromium, Firefox, and WebKit

## Key Technologies

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Livewire 3, Livewire Volt, Livewire Flux
- **Authentication**: Laravel Fortify
- **Styling**: Tailwind CSS 4.x
- **Build Tool**: Vite
- **Testing**: Pest (PHP), Playwright (E2E)
- **Database**: SQLite (dev/test), MariaDB (DDEV)

## Development Workflow

1. **Creating new features**:
   - Create Livewire component: `php artisan make:livewire ComponentName`
   - Add route in `routes/web.php` pointing to component class
   - Use Flux components in views for UI elements
   - Follow existing authentication/settings patterns for consistency

2. **Database changes**:
   - Create migration: `php artisan make:migration description`
   - Run migration: `php artisan migrate`
   - For models: `php artisan make:model ModelName -m` (includes migration)

3. **Testing new features**:
   - Write Pest tests in `tests/Feature/`
   - Run tests before committing: `composer test`
   - Add E2E tests in `e2e/` for critical user flows

## Important Notes

- This is a Livewire-first application - prefer Livewire components over traditional controllers
- Authentication UI is fully customized via Livewire components (not using Fortify views)
- The application uses full-page Livewire components with layouts rather than traditional Blade views
- Flux provides the UI component library - use existing Flux components before creating custom ones
- Queue and cache drivers use database (see `.env.example`)
