import { test, expect } from '@playwright/test';

test.describe('Header Branding', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to login page and authenticate
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  test('displays Project Table Top branding', async ({ page }) => {
    // Check for branding in header (first occurrence)
    await expect(page.locator('text=Project Table Top').first()).toBeVisible();
    await expect(page.locator('text=Laravel Starter Kit')).not.toBeVisible();
  });

  test('page title includes Project Table Top', async ({ page }) => {
    // Page title should include both page name and app name (e.g., "Dashboard - Project Table Top")
    await expect(page).toHaveTitle(/Project Table Top/);
    const title = await page.title();
    expect(title).not.toContain('Laravel Starter Kit');
    // Verify it follows the pattern "Page Name - App Name"
    expect(title).toContain(' - ');
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
    // Find the logo within the app-logo component (not just any SVG)
    // The logo should be in the header link that goes to dashboard
    const logoLink = page.locator('a[href*="dashboard"]').first();
    const logo = logoLink.locator('svg');
    await expect(logo).toBeVisible();

    // Dark mode: The logo uses currentColor on child elements and has dark mode classes
    // Verify the logo has appropriate styling classes for light/dark mode
    await expect(logo).toHaveClass(/text-white|dark:text-black/);
  });

  test('logo scales correctly on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 375, height: 667 });
    // On mobile, logo is in the sidebar
    const logoLink = page.locator('a[href*="dashboard"]').first();
    const logo = logoLink.locator('svg');
    await expect(logo).toBeVisible();
  });

  test('branding is consistent across pages', async ({ page }) => {
    const pages = ['/dashboard', '/recipes', '/meal-plans', '/grocery-lists'];

    for (const url of pages) {
      await page.goto(url);
      // Use .first() to handle multiple instances (header + sidebar)
      await expect(page.locator('text=Project Table Top').first()).toBeVisible();
    }
  });
});
