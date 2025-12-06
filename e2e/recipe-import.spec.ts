import { test, expect } from '@playwright/test';

// Helper function to login as a test user
async function loginAsUser(page: any) {
  await page.goto('/login');
  await page.fill('input[name="email"]', 'test@example.com');
  await page.fill('input[name="password"]', 'password');
  await page.click('button[type="submit"]');
  await page.waitForLoadState('networkidle');
}

// Helper function to get the test recipe URL (valid recipe data)
function getTestRecipeURL(page: any): string {
  const baseURL = page.context()._options.baseURL || 'https://project-tabletop.ddev.site';
  return `${baseURL}/test/recipe-valid`;
}

// Helper function to get the invalid test recipe URL (no recipe data)
function getInvalidRecipeURL(page: any): string {
  const baseURL = page.context()._options.baseURL || 'https://project-tabletop.ddev.site';
  return `${baseURL}/test/recipe-invalid`;
}

// T046: Phase 3 - E2E test for complete happy path
test('successfully imports recipe from URL', async ({ page }) => {
  // Login as user
  await loginAsUser(page);

  // Navigate to import page
  await page.goto('/recipes/import');
  await page.waitForLoadState('networkidle');

  // Verify we're on the import page
  await expect(page).toHaveURL(/\/recipes\/import$/);
  await expect(page.locator('h1, h2').filter({ hasText: /import recipe/i })).toBeVisible();

  // Enter a valid recipe URL (using test route)
  await page.fill('input[name="url"]', getTestRecipeURL(page));

  // Click import button
  await page.click('button:has-text("Import Recipe")');

  // Wait for redirect to preview page
  await page.waitForURL(/\/recipes\/import\/preview$/, { timeout: 10000 });

  // Verify preview page elements
  await expect(page.locator('h1').filter({ hasText: /preview recipe import/i })).toBeVisible();

  // Verify recipe data is displayed from mocked data
  await expect(page.locator('text=Test Chocolate Chip Cookies')).toBeVisible();
  await expect(page.locator('text=Delicious test cookies for E2E testing')).toBeVisible();
  await expect(page.locator('text=2 cups all-purpose flour')).toBeVisible();
  await expect(page.locator('text=/Confirm & Save Recipe/i')).toBeVisible();
  await expect(page.locator('button:has-text("Cancel")')).toBeVisible();

  // Click confirm button
  await page.click('button:has-text("Confirm & Save Recipe")');

  // Wait for redirect to recipe show page
  await page.waitForURL(/\/recipes\/\d+$/, { timeout: 10000 });

  // Verify success message
  await expect(page.locator('text=/recipe imported successfully/i')).toBeVisible();

  // Verify we're on the recipe page
  await expect(page).toHaveURL(/\/recipes\/\d+$/);

  // Verify recipe name appears on the page
  await expect(page.locator('text=Test Chocolate Chip Cookies')).toBeVisible();
});

// T047: Phase 3 - E2E test for cancel flow
test('allows user to cancel recipe import', async ({ page }) => {
  // Login as user
  await loginAsUser(page);

  // Navigate to import page
  await page.goto('/recipes/import');
  await page.waitForLoadState('networkidle');

  // Enter a valid recipe URL (using test route)
  await page.fill('input[name="url"]', getTestRecipeURL(page));

  // Click import button
  await page.click('button:has-text("Import Recipe")');

  // Wait for redirect to preview page
  await page.waitForURL(/\/recipes\/import\/preview$/, { timeout: 10000 });

  // Verify we're on the preview page
  await expect(page.locator('h1').filter({ hasText: /preview recipe import/i })).toBeVisible();
  await expect(page.locator('text=Test Chocolate Chip Cookies')).toBeVisible();

  // Click cancel button
  await page.click('button:has-text("Cancel")');

  // Wait for redirect back to import page
  await page.waitForURL(/\/recipes\/import$/, { timeout: 5000 });

  // Verify we're back on the import page
  await expect(page).toHaveURL(/\/recipes\/import$/);

  // Verify the URL input is empty (session was cleared)
  const urlInput = page.locator('input[name="url"]');
  await expect(urlInput).toHaveValue('');
});

// T057: Phase 4 - E2E test for error flow
test('displays error for invalid URL', async ({ page }) => {
  // Login as user (required for authenticated routes)
  await loginAsUser(page);

  // Navigate to import page
  await page.goto('/recipes/import');

  // Wait for page to load
  await page.waitForLoadState('networkidle');

  // Enter invalid URL (no recipe data) using test route
  await page.fill('input[name="url"]', getInvalidRecipeURL(page));

  // Click import button
  await page.click('button:has-text("Import Recipe")');

  // Wait for error message to appear
  await page.waitForSelector('.flux-error, [role="alert"]', { timeout: 5000 });

  // Verify error message is displayed
  const errorElement = page.locator('.flux-error, [role="alert"]');
  await expect(errorElement).toBeVisible();

  // Verify the specific error message for no recipe data
  await expect(errorElement).toContainText('No recipe data found');

  // Verify still on import page (no redirect)
  await expect(page).toHaveURL(/\/recipes\/import$/);
});
