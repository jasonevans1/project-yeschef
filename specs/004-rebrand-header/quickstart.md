# Quickstart Guide: Header Rebranding

**Date**: 2025-11-25
**Feature**: Rebrand Application Header
**Estimated Time**: 30-45 minutes (including testing)

## Overview

This guide walks you through rebranding the application header from "Laravel Starter Kit" to "Project Table Top", including logo replacement, navigation cleanup, and comprehensive testing.

## Prerequisites

- DDEV environment running (`ddev start`)
- Composer and npm dependencies installed
- Git branch `004-rebrand-header` checked out
- Basic familiarity with Blade templates and Tailwind CSS

## Step-by-Step Implementation

### Step 1: Design the New Logo (5-10 minutes)

Create a simple SVG logo that represents "Project Table Top" (tabletop gaming theme).

**Design Requirements**:
- Single-color design using `currentColor` for automatic dark mode support
- Square aspect ratio (e.g., 40x40 viewBox)
- Geometric shapes suggesting tabletop gaming (dice, grid, table)
- Simple enough to scale from 16px (mobile) to 48px+ (desktop)

**Example Logo Concept** (dice on a table):
```svg
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" {{ $attributes }}>
    <!-- Table surface (rectangle) -->
    <rect x="2" y="20" width="36" height="18" fill="currentColor" />

    <!-- Die face showing 5 dots -->
    <rect x="10" y="6" width="14" height="14" rx="2" fill="currentColor" />
    <circle cx="13" cy="9" r="1.5" fill="white" />
    <circle cx="21" cy="9" r="1.5" fill="white" />
    <circle cx="17" cy="13" r="1.5" fill="white" />
    <circle cx="13" cy="17" r="1.5" fill="white" />
    <circle cx="21" cy="17" r="1.5" fill="white" />
</svg>
```

You can refine this concept or create your own design. Save your design concept - you'll implement it in Step 3.

### Step 2: Write Tests First (TDD) (10-15 minutes)

Following the Test-First Development principle, write tests BEFORE making any changes.

#### 2a. Create Pest Feature Test

Create `tests/Feature/BrandingTest.php`:

```php
<?php

declare(strict_types=1);

test('header displays project table top branding', function () {
    $response = $this->get('/dashboard');

    $response->assertSee('Project Table Top');
    $response->assertDontSee('Laravel Starter Kit');
});

test('page title includes project table top', function () {
    $response = $this->get('/dashboard');

    $response->assertSee('<title>Project Table Top</title>', false);
});

test('search link is not present in header', function () {
    $response = $this->get('/dashboard');

    $response->assertDontSee('Search');
    $response->assertDontSee('magnifying-glass');
});

test('repository link is not present in header', function () {
    $response = $this->get('/dashboard');

    $response->assertDontSee('https://github.com/laravel/livewire-starter-kit');
    $response->assertDontSee('Repository');
});

test('documentation link is not present in header', function () {
    $response = $this->get('/dashboard');

    $response->assertDontSee('https://laravel.com/docs/starter-kits#livewire');
    $response->assertDontSee('Documentation');
});
```

#### 2b. Create Playwright E2E Test

Create `e2e/header-branding.spec.ts`:

```typescript
import { test, expect } from '@playwright/test';

test.describe('Header Branding', () => {
  test.beforeEach(async ({ page }) => {
    // Assumes you have a test user - adjust as needed
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('displays Project Table Top branding', async ({ page }) => {
    await expect(page.locator('text=Project Table Top')).toBeVisible();
    await expect(page.locator('text=Laravel Starter Kit')).not.toBeVisible();
  });

  test('page title includes Project Table Top', async ({ page }) => {
    await expect(page).toHaveTitle(/Project Table Top/);
  });

  test('removed links are not present', async ({ page }) => {
    // Search link
    await expect(page.locator('[aria-label="Search"]')).not.toBeVisible();

    // Repository link
    await expect(page.locator('a[href*="github.com/laravel/livewire-starter-kit"]')).not.toBeVisible();

    // Documentation link
    await expect(page.locator('a[href*="laravel.com/docs/starter-kits"]')).not.toBeVisible();
  });

  test('logo is visible in light and dark mode', async ({ page }) => {
    // Light mode (default)
    const logo = page.locator('svg').first();
    await expect(logo).toBeVisible();

    // Switch to dark mode (adjust selector based on your dark mode toggle)
    await page.click('[data-theme-toggle]'); // Adjust selector as needed
    await expect(logo).toBeVisible();
  });

  test('logo scales correctly on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    const logo = page.locator('svg').first();
    await expect(logo).toBeVisible();
  });

  test('branding is consistent across pages', async ({ page }) => {
    const pages = ['/dashboard', '/recipes', '/meal-plans', '/grocery-lists'];

    for (const url of pages) {
      await page.goto(url);
      await expect(page.locator('text=Project Table Top')).toBeVisible();
    }
  });
});
```

#### 2c. Run Tests (Should Fail)

```bash
# Run Pest tests - should fail because changes not made yet
php artisan test --filter=BrandingTest

# Run Playwright tests - should fail
npx playwright test e2e/header-branding.spec.ts
```

**Expected**: All tests should FAIL at this point. This confirms TDD workflow.

### Step 3: Update Logo Components (5 minutes)

#### 3a. Update Logo Icon

Edit `resources/views/components/app-logo-icon.blade.php`:

Replace the entire SVG with your new logo design from Step 1. Make sure to:
- Keep `{{ $attributes }}` on the `<svg>` tag
- Use `fill="currentColor"` for all paths/shapes
- Maintain a square viewBox (e.g., `viewBox="0 0 40 40"`)

```blade
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" {{ $attributes }}>
    <!-- Your logo design here -->
    <!-- Example: Table and dice from Step 1 -->
    <rect x="2" y="20" width="36" height="18" fill="currentColor" />
    <rect x="10" y="6" width="14" height="14" rx="2" fill="currentColor" />
    <circle cx="13" cy="9" r="1.5" fill="white" />
    <circle cx="21" cy="9" r="1.5" fill="white" />
    <circle cx="17" cy="13" r="1.5" fill="white" />
    <circle cx="13" cy="17" r="1.5" fill="white" />
    <circle cx="21" cy="17" r="1.5" fill="white" />
</svg>
```

#### 3b. Update Logo Text

Edit `resources/views/components/app-logo.blade.php`:

Change line 5 from:
```blade
<span class="mb-0.5 truncate leading-tight font-semibold">Laravel Starter Kit</span>
```

To:
```blade
<span class="mb-0.5 truncate leading-tight font-semibold">Project Table Top</span>
```

### Step 4: Remove Navigation Links (5 minutes)

Edit `resources/views/components/layouts/app/header.blade.php`:

#### 4a. Remove Desktop Navigation Links

Delete lines 31-53 (the navbar section with search, repository, documentation):

```blade
<flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
    <flux:tooltip :content="__('Search')" position="bottom">
        <flux:navbar.item class="!h-10 [&>div>svg]:size-5" icon="magnifying-glass" href="#" :label="__('Search')" />
    </flux:tooltip>
    <flux:tooltip :content="__('Repository')" position="bottom">
        <flux:navbar.item
            class="h-10 max-lg:hidden [&>div>svg]:size-5"
            icon="folder-git-2"
            href="https://github.com/laravel/livewire-starter-kit"
            target="_blank"
            :label="__('Repository')"
        />
    </flux:tooltip>
    <flux:tooltip :content="__('Documentation')" position="bottom">
        <flux:navbar.item
            class="h-10 max-lg:hidden [&>div>svg]:size-5"
            icon="book-open-text"
            href="https://laravel.com/docs/starter-kits#livewire"
            target="_blank"
            label="Documentation"
        />
    </flux:tooltip>
</flux:navbar>
```

#### 4b. Remove Mobile Sidebar Links

Delete lines 127-135 (the repository and documentation links in mobile sidebar):

```blade
<flux:navlist variant="outline">
    <flux:navlist.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
        {{ __('Repository') }}
    </flux:navlist.item>

    <flux:navlist.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
        {{ __('Documentation') }}
    </flux:navlist.item>
</flux:navlist>
```

### Step 5: Update Application Name (2 minutes)

Edit `config/app.php`:

Change line 17-19 (approximately) from:
```php
'name' => env('APP_NAME', 'Laravel'),
```

To:
```php
'name' => env('APP_NAME', 'Project Table Top'),
```

**Optional**: Update `.env` file:
```env
APP_NAME="Project Table Top"
```

### Step 6: Run Tests Again (Should Pass) (5 minutes)

```bash
# Run Pest tests - should pass now
php artisan test --filter=BrandingTest

# Run Playwright tests - should pass
npx playwright test e2e/header-branding.spec.ts

# Run all tests to ensure nothing broke
php artisan test
npx playwright test
```

**Expected**: All tests should PASS now.

### Step 7: Visual QA in Browser (5-10 minutes)

Start the development server if not already running:

```bash
composer dev
```

Visit https://project-tabletop.ddev.site and verify:

#### Light Mode
- [ ] Header shows "Project Table Top" text
- [ ] Logo displays correctly next to brand text
- [ ] No search icon in header
- [ ] No repository link in header
- [ ] No documentation link in header
- [ ] Page title shows "Project Table Top"

#### Dark Mode
- [ ] Toggle dark mode (if you have a theme switcher)
- [ ] Logo remains visible with good contrast
- [ ] All branding elements are readable

#### Responsive Design
- [ ] Resize browser to mobile width (< 1024px)
- [ ] Mobile sidebar opens (hamburger menu)
- [ ] "Project Table Top" branding visible in sidebar
- [ ] No repository/documentation links in mobile sidebar
- [ ] Logo scales appropriately

#### Multiple Pages
- [ ] Navigate to Dashboard - verify branding
- [ ] Navigate to Recipes - verify branding
- [ ] Navigate to Meal Plans - verify branding
- [ ] Navigate to Grocery Lists - verify branding

### Step 8: Code Formatting (2 minutes)

Run Laravel Pint to ensure code style compliance:

```bash
vendor/bin/pint
```

Fix any issues reported.

### Step 9: Commit Changes

```bash
git add -A
git commit -m "Rebrand application header to Project Table Top

- Replace logo SVG with tabletop gaming themed design
- Update brand text from 'Laravel Starter Kit' to 'Project Table Top'
- Remove search, repository, and documentation links from header
- Update application name in config
- Add comprehensive Pest and Playwright tests
- Maintain dark mode support and responsive design

Closes #004"
```

## Verification Checklist

Before considering this feature complete, verify:

- [ ] All Pest tests pass (`php artisan test`)
- [ ] All Playwright tests pass (`npx playwright test`)
- [ ] Laravel Pint passes (`vendor/bin/pint`)
- [ ] "Project Table Top" appears in header on all pages
- [ ] Logo is visible in light and dark mode
- [ ] Logo scales correctly on mobile and desktop
- [ ] Page titles show "Project Table Top"
- [ ] Search, repository, documentation links are removed
- [ ] No console errors in browser
- [ ] DDEV environment starts successfully (`ddev start`)

## Troubleshooting

### Tests fail with "text not found"
- Clear Laravel cache: `php artisan config:clear && php artisan view:clear`
- Rebuild assets: `npm run build`
- Refresh browser with hard reload (Cmd+Shift+R or Ctrl+Shift+R)

### Logo not visible in dark mode
- Verify SVG uses `fill="currentColor"` not hardcoded colors
- Check parent container has `text-white dark:text-black` or similar
- Inspect element in browser devtools to see computed styles

### Mobile sidebar doesn't show logo
- Verify the logo component is rendered in mobile sidebar section (lines 100-106 in header.blade.php)
- Check responsive classes are not hiding the logo on small screens

### Page title not updating
- Verify `config/app.php` has been updated
- Clear config cache: `php artisan config:clear`
- Check `.env` file doesn't override with old value

### Vite not reflecting changes
- Stop and restart `composer dev`
- Clear browser cache
- Check that Vite dev server is running (should see messages in terminal)

## Next Steps

After completing this feature:

1. Create a pull request from `004-rebrand-header` to `main`
2. Request code review
3. Ensure CI/CD pipeline passes
4. Merge to main after approval
5. Deploy to staging/production environments

## Related Documentation

- [Feature Specification](./spec.md) - Original requirements
- [Research Document](./research.md) - Technical decisions and rationale
- [Implementation Plan](./plan.md) - Overall feature planning
