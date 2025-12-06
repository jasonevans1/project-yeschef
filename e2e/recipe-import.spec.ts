import { test, expect } from '@playwright/test';

// T057: Phase 4 - E2E test for error flow
test('displays error for invalid URL', async ({ page }) => {
  // Navigate to import page (adjust URL based on your dev environment)
  await page.goto('/recipes/import');

  // Wait for page to load
  await page.waitForLoadState('networkidle');

  // Enter invalid URL (no recipe data)
  await page.fill('input[name="url"]', 'https://example.com/no-recipe');

  // Click import button
  await page.click('button:has-text("Import Recipe")');

  // Wait for error message to appear
  await page.waitForSelector('.flux-error, [role="alert"]', { timeout: 5000 });

  // Verify error message is displayed
  const errorElement = page.locator('.flux-error, [role="alert"]');
  await expect(errorElement).toBeVisible();

  // Verify still on import page (no redirect)
  await expect(page).toHaveURL(/\/recipes\/import$/);
});
