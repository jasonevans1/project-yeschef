# Quickstart Guide: Grocery Item Autocomplete Lookup

**Feature**: 001-grocery-item-lookup
**Branch**: `001-grocery-item-lookup`
**Prerequisites**: DDEV running, Laravel 12, PHP 8.3, Livewire 3

## Overview

This guide walks you through setting up the grocery item autocomplete feature from scratch. Follow these steps to get the autocomplete system running in your local DDEV environment.

---

## 1. Prerequisites Check

Ensure your development environment is ready:

```bash
# Verify you're on the feature branch
git branch --show-current
# Should output: 001-grocery-item-lookup

# Verify DDEV is running
ddev describe
# Should show project-tabletop running

# Verify database connectivity
ddev exec php artisan migrate:status
# Should show migration table exists
```

---

## 2. Database Setup (Migrations)

Create the two new tables required for autocomplete:

### Step 2.1: Create CommonItemTemplate Migration

```bash
ddev exec php artisan make:migration create_common_item_templates_table --no-interaction
```

**Edit the migration file** (database/migrations/YYYY_MM_DD_HHMMSS_create_common_item_templates_table.php):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('common_item_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('category', [
                'produce',
                'dairy',
                'meat',
                'seafood',
                'pantry',
                'frozen',
                'bakery',
                'deli',
                'beverages',
                'other',
            ]);
            $table->enum('unit', [
                'tsp', 'tbsp', 'fl_oz', 'cup', 'pint', 'quart', 'gallon',
                'ml', 'liter', 'oz', 'lb', 'gram', 'kg', 'whole', 'clove',
                'slice', 'piece', 'pinch', 'dash', 'to_taste',
            ])->nullable();
            $table->decimal('default_quantity', 8, 3)->nullable();
            $table->text('search_keywords')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();

            // Indexes for autocomplete performance
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('common_item_templates');
    }
};
```

### Step 2.2: Create UserItemTemplate Migration

```bash
ddev exec php artisan make:migration create_user_item_templates_table --no-interaction
```

**Edit the migration file** (database/migrations/YYYY_MM_DD_HHMMSS_create_user_item_templates_table.php):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_item_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('category', [
                'produce',
                'dairy',
                'meat',
                'seafood',
                'pantry',
                'frozen',
                'bakery',
                'deli',
                'beverages',
                'other',
            ]);
            $table->enum('unit', [
                'tsp', 'tbsp', 'fl_oz', 'cup', 'pint', 'quart', 'gallon',
                'ml', 'liter', 'oz', 'lb', 'gram', 'kg', 'whole', 'clove',
                'slice', 'piece', 'pinch', 'dash', 'to_taste',
            ])->nullable();
            $table->decimal('default_quantity', 8, 3)->nullable();
            $table->unsignedInteger('usage_count')->default(1);
            $table->timestamp('last_used_at');
            $table->timestamps();

            // Prevent duplicate templates per user
            $table->unique(['user_id', 'name']);

            // Query performance for autocomplete
            $table->index(['user_id', 'usage_count', 'last_used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_item_templates');
    }
};
```

### Step 2.3: Run Migrations

```bash
ddev exec php artisan migrate
```

**Expected output:**
```
Migration table created successfully.
Migrating: YYYY_MM_DD_HHMMSS_create_common_item_templates_table
Migrated:  YYYY_MM_DD_HHMMSS_create_common_item_templates_table (XX.XXms)
Migrating: YYYY_MM_DD_HHMMSS_create_user_item_templates_table
Migrated:  YYYY_MM_DD_HHMMSS_create_user_item_templates_table (XX.XXms)
```

---

## 3. Create Models

### Step 3.1: Create CommonItemTemplate Model

```bash
ddev exec php artisan make:model CommonItemTemplate --no-interaction
```

**Edit** `app/Models/CommonItemTemplate.php`:

```php
<?php

namespace App\Models;

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use Illuminate\Database\Eloquent\Model;

class CommonItemTemplate extends Model
{
    protected $fillable = [
        'name',
        'category',
        'unit',
        'default_quantity',
        'search_keywords',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'category' => IngredientCategory::class,
            'unit' => MeasurementUnit::class,
            'default_quantity' => 'decimal:3',
            'usage_count' => 'integer',
        ];
    }
}
```

### Step 3.2: Create UserItemTemplate Model

```bash
ddev exec php artisan make:model UserItemTemplate --no-interaction
```

**Edit** `app/Models/UserItemTemplate.php`:

```php
<?php

namespace App\Models;

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserItemTemplate extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'category',
        'unit',
        'default_quantity',
        'usage_count',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => IngredientCategory::class,
            'unit' => MeasurementUnit::class,
            'default_quantity' => 'decimal:3',
            'usage_count' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### Step 3.3: Add Relationship to User Model

**Edit** `app/Models/User.php`, add this method:

```php
public function itemTemplates(): HasMany
{
    return $this->hasMany(UserItemTemplate::class);
}
```

---

## 4. Seed Common Item Templates

### Step 4.1: Create Seeder

```bash
ddev exec php artisan make:seeder CommonItemTemplateSeeder --no-interaction
```

**Edit** `database/seeders/CommonItemTemplateSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\CommonItemTemplate;
use Illuminate\Database\Seeder;

class CommonItemTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'milk', 'category' => 'dairy', 'unit' => 'gallon', 'default_quantity' => 1],
            ['name' => 'bread', 'category' => 'bakery', 'unit' => 'whole', 'default_quantity' => 1],
            ['name' => 'eggs', 'category' => 'dairy', 'unit' => 'whole', 'default_quantity' => 12],
            ['name' => 'banana', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 6],
            ['name' => 'chicken breast', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 2],
            ['name' => 'ground beef', 'category' => 'meat', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'tomato', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 4],
            ['name' => 'lettuce', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 1],
            ['name' => 'cheddar cheese', 'category' => 'dairy', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'pasta', 'category' => 'pantry', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'rice', 'category' => 'pantry', 'unit' => 'lb', 'default_quantity' => 2],
            ['name' => 'onion', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 3],
            ['name' => 'garlic', 'category' => 'produce', 'unit' => 'clove', 'default_quantity' => 6],
            ['name' => 'carrot', 'category' => 'produce', 'unit' => 'whole', 'default_quantity' => 5],
            ['name' => 'potato', 'category' => 'produce', 'unit' => 'lb', 'default_quantity' => 3],
            ['name' => 'orange juice', 'category' => 'beverages', 'unit' => 'gallon', 'default_quantity' => 1],
            ['name' => 'butter', 'category' => 'dairy', 'unit' => 'lb', 'default_quantity' => 1],
            ['name' => 'flour', 'category' => 'pantry', 'unit' => 'lb', 'default_quantity' => 5],
            ['name' => 'sugar', 'category' => 'pantry', 'unit' => 'lb', 'default_quantity' => 2],
            ['name' => 'olive oil', 'category' => 'pantry', 'unit' => 'liter', 'default_quantity' => 1],
        ];

        foreach ($items as $item) {
            CommonItemTemplate::create($item);
        }

        $this->command->info('Seeded ' . count($items) . ' common item templates');
    }
}
```

### Step 4.2: Run Seeder

```bash
ddev exec php artisan db:seed --class=CommonItemTemplateSeeder
```

**Expected output:**
```
Seeded 20 common item templates
```

### Step 4.3: Verify Seed Data

```bash
ddev exec php artisan tinker
```

```php
CommonItemTemplate::count(); // Should return 20
CommonItemTemplate::where('name', 'milk')->first(); // Should show milk template
exit
```

---

## 5. Install Frontend Dependencies

**No action required** - Alpine.js is bundled with Livewire 3.

Verify Alpine is available:

```bash
# Check that Alpine is loaded in your assets
grep -r "Alpine" resources/js/
```

---

## 6. Run Tests

### Step 6.1: Run Existing Tests (Baseline)

```bash
ddev exec php artisan test
```

All existing tests should pass (no regressions).

### Step 6.2: Run Specific Feature Tests (After Implementation)

```bash
# After implementing autocomplete, run these:
ddev exec php artisan test --filter=AutocompleteItemTest
ddev exec php artisan test --filter=CreateUserTemplateTest
ddev exec php artisan test --filter=PrioritizePersonalHistoryTest
```

---

## 7. Start Development Environment

### Step 7.1: Start All Services

```bash
ddev exec composer dev
```

This runs concurrently:
- Laravel development server (`php artisan serve`)
- Queue worker (`php artisan queue:listen --tries=1`)
- Log monitoring (`php artisan pail --timeout=0`)
- Vite dev server (`npm run dev`)

### Step 7.2: Access Application

Open your browser to: **https://project-tabletop.ddev.site**

### Step 7.3: Test in Browser

1. **Create a user account** (if not already done)
2. **Navigate to a grocery list** (create one if needed)
3. **Add a new item** using the autocomplete:
   - Type "mil" → should suggest "milk" (from common templates)
   - Select suggestion → category should auto-populate to "Dairy"
   - Save item
4. **Repeat** - Type "mil" again → should now show your personal template

---

## 8. Development Workflow

### Daily Development Loop

```bash
# 1. Start DDEV
ddev start

# 2. Pull latest changes
git pull origin 001-grocery-item-lookup

# 3. Run migrations (if any new ones)
ddev exec php artisan migrate

# 4. Start dev services
ddev exec composer dev

# 5. Open browser to https://project-tabletop.ddev.site

# 6. Make code changes...

# 7. Run tests before committing
ddev exec php artisan test

# 8. Format code
ddev exec vendor/bin/pint

# 9. Commit changes
git add .
git commit -m "Description of changes"
```

### Monitoring Logs

```bash
# Watch Laravel logs (already running in composer dev)
ddev exec php artisan pail --timeout=0

# Watch queue jobs
ddev exec php artisan queue:listen --tries=1

# Check database queries in development
# Queries are automatically logged when APP_DEBUG=true
```

---

## 9. Testing Autocomplete Functionality

### Manual Testing Checklist

- [ ] **Basic autocomplete**: Type "mil" → see "milk" suggestion
- [ ] **Partial match**: Type "banan" → see "banana" suggestion
- [ ] **Category population**: Select suggestion → category auto-fills
- [ ] **Personal history**: Add "almond milk" as "beverages" → type "almond" → see personal template first
- [ ] **Keyboard navigation**:
  - Arrow down/up to navigate suggestions
  - Enter to select active suggestion
  - Escape to close dropdown
- [ ] **Mobile responsive**: Test on mobile viewport (responsive design)
- [ ] **Accessibility**: Use screen reader (VoiceOver/NVDA) to verify ARIA labels

### Automated Testing

```bash
# Run Pest feature tests
ddev exec php artisan test tests/Feature/GroceryLists/AutocompleteItemTest.php

# Run Playwright E2E tests
npx playwright test e2e/grocery-lists/autocomplete-item.spec.ts
```

---

## 10. Debugging Common Issues

### Issue: Autocomplete Dropdown Not Showing

**Check:**
1. Verify JavaScript is enabled
2. Check browser console for errors: `F12 → Console`
3. Verify Alpine.js is loaded: `window.Alpine` in console should return object
4. Check Livewire is connected: `Livewire` in console should return object

### Issue: Suggestions Not Appearing

**Check:**
1. Verify database has common item templates: `CommonItemTemplate::count()` in tinker
2. Check Livewire component is querying correctly: Add `dd($this->suggestions)` in component
3. Verify debounce delay: Wait 300ms after typing

### Issue: Personal History Not Updating

**Check:**
1. Verify observer is registered: Check `AppServiceProvider` boot method
2. Check queue is running: `ddev exec php artisan queue:listen`
3. Verify job is dispatched: Check `jobs` table in database
4. Check logs: `ddev exec php artisan pail`

### Issue: Performance Too Slow (>200ms)

**Check:**
1. Verify database indexes exist: Run `SHOW INDEXES FROM user_item_templates;` in database
2. Check query execution time: Enable query logging in `config/database.php`
3. Verify debounce is working: Should not query on every keystroke

---

## 11. Useful Commands Reference

```bash
# Database
ddev exec php artisan migrate           # Run migrations
ddev exec php artisan migrate:rollback  # Rollback last migration
ddev exec php artisan db:seed           # Run all seeders
ddev exec php artisan tinker            # Interactive REPL

# Code Generation
ddev exec php artisan make:model ModelName
ddev exec php artisan make:migration migration_name
ddev exec php artisan make:livewire ComponentName
ddev exec php artisan make:test TestName

# Testing
ddev exec php artisan test                    # Run all tests
ddev exec php artisan test --filter=TestName  # Run specific test
npx playwright test                           # Run E2E tests
npx playwright test --ui                      # Run E2E tests in UI mode

# Code Quality
ddev exec vendor/bin/pint                # Format code
ddev exec php artisan pail               # Monitor logs

# Queue
ddev exec php artisan queue:listen       # Start queue worker
ddev exec php artisan queue:work --once # Process one job

# Cache
ddev exec php artisan config:clear       # Clear config cache
ddev exec php artisan cache:clear        # Clear application cache
ddev exec php artisan view:clear         # Clear view cache
```

---

## 12. Next Steps

After completing this quickstart:

1. **Implement autocomplete UI** - Follow `data-model.md` and `contracts/autocomplete-api.json`
2. **Write tests** - See `spec.md` for acceptance scenarios
3. **Add observer + job** - Implement user template tracking
4. **Test performance** - Verify <200ms response time
5. **Run E2E tests** - Playwright tests for full workflow

Refer to:
- `spec.md` - Feature requirements and user stories
- `plan.md` - Implementation plan and architecture
- `research.md` - Technical decisions and patterns
- `data-model.md` - Database schema and relationships
- `contracts/` - API endpoint specifications

---

**Quickstart Complete!** 🎉

You should now have:
- ✅ Database tables created and seeded
- ✅ Models defined with relationships
- ✅ Development environment running
- ✅ Common item templates available for autocomplete

Ready to start implementing the autocomplete UI!
