# Architecture

## Directory Structure

```
app/
├── Actions/              # Single-purpose action classes (e.g., Logout)
├── Console/Commands/     # Artisan commands (auto-registered)
├── Enums/                # PHP enums (e.g., MeasurementUnit)
├── Exceptions/           # Custom exception classes
├── Http/
│   └── Requests/         # Form Request validation classes
├── Jobs/                 # Queued jobs (ShouldQueue)
├── Listeners/            # Event listeners
├── Livewire/
│   ├── Auth/             # Full-page auth components (Login, Register, etc.)
│   ├── Dashboard.php     # Dashboard component
│   ├── GroceryLists/     # Grocery list components
│   ├── MealPlans/        # Meal planning components
│   ├── Recipes/          # Recipe management components
│   └── Settings/         # User settings (Profile, Password, TwoFactor, etc.)
├── Mail/                 # Mailable classes
├── Models/               # Eloquent models
├── Observers/            # Model observers
├── Policies/             # Authorization policies
├── Providers/            # Service providers
├── Rules/                # Custom validation rules
└── Services/             # Business logic services

resources/
├── css/app.css           # Main CSS (Tailwind @import)
├── js/app.js             # Main JS entry (Livewire bootstrap)
└── views/
    ├── components/
    │   └── layouts/      # App and auth layouts
    ├── flux/             # Flux UI customizations
    └── livewire/         # Volt single-file components (mirrors Livewire class structure)

routes/
├── web.php               # Main app routes
├── auth.php              # Auth routes
└── console.php           # Artisan console commands

tests/
├── Browser/              # Pest 4 browser tests
├── Feature/              # Feature/integration tests (organized by domain)
│   ├── Auth/
│   ├── GroceryList/
│   ├── MealPlans/
│   ├── Recipe/
│   ├── RecipeImporter/
│   ├── Settings/
│   └── Sharing/
└── Unit/                 # Unit tests

e2e/                      # Playwright E2E tests
database/
├── factories/            # Model factories
├── migrations/           # Database migrations
└── seeders/              # Database seeders
```

## Patterns Used

### Livewire-First
All pages are full-page Livewire/Volt components registered directly as route handlers:
```php
Route::get('/recipes', RecipeIndex::class)->middleware('auth');
```
No traditional controllers for page rendering.

### Volt Single-File Components
Interactive pages use Livewire Volt — PHP logic and Blade template co-located in one file under `resources/views/livewire/`.

### Form Requests
All input validation uses dedicated Form Request classes in `app/Http/Requests/`. Never validate inline in Livewire components or controllers.

### Service Classes
Business logic that spans multiple models lives in `app/Services/`.

### Policies
Authorization is handled via Laravel Policies in `app/Policies/`.

### Observers
Model lifecycle hooks use Observers in `app/Observers/`.

## Routing
- `routes/web.php` — main app (authenticated routes)
- `routes/auth.php` — login, register, password reset, 2FA
- Auth guard: Laravel Fortify handles backend, Livewire handles UI
- Fortify views are replaced by custom Livewire components

## Authentication
- Laravel Fortify for backend auth logic
- Custom Livewire components for all auth UI (no Fortify views)
- Two-factor authentication via `app/Livewire/Settings/TwoFactor.php`
- Email verification supported

## Database
- SQLite for development/testing (fast, zero config)
- MariaDB 10.11 in production (via DDEV)
- All schema changes via migrations
- Use factories + seeders for test and development data

## Key Conventions
- One class per file
- Tests mirror source structure (e.g., `app/Services/Foo.php` → `tests/Feature/FooTest.php`)
- No new base directories without approval
- No new dependencies without approval
