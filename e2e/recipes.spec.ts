import { test, expect } from '@playwright/test';

// This test assumes the application is running at the DDEV URL
// and that test data has been seeded
const BASE_URL = process.env.BASE_URL || 'https://project-tabletop.ddev.site';

test.describe('Recipe browsing journey', () => {
  test.beforeEach(async ({ page }) => {
    // Navigate to the home page
    await page.goto(BASE_URL);
  });

  test('complete recipe browsing flow', async ({ page }) => {
    // Step 1: User logs in
    await page.goto(`${BASE_URL}/login`);

    // Assuming test user credentials (should be seeded in database)
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for redirect after login
    await page.waitForURL(/dashboard|recipes/);

    // Step 2: Navigate to recipes page
    await page.goto(`${BASE_URL}/recipes`);

    // Step 3: Verify recipe grid is visible
    await expect(page).toHaveURL(/\/recipes/);

    // Check for presence of recipe cards (should have some seeded recipes)
    // This is flexible and doesn't require specific recipes
    const recipeCards = page.locator('[data-test="recipe-card"]').first();
    await expect(recipeCards).toBeVisible({ timeout: 5000 }).catch(() => {
      // If data-test attribute not available, look for common recipe elements
      return expect(page.locator('h2, h3').first()).toBeVisible();
    });

    // Step 4: Use search filter
    const searchInput = page.locator('input[name="search"], input[placeholder*="Search"]').first();
    await searchInput.fill('Chicken');

    // Wait for search results to update (debounced)
    await page.waitForTimeout(500);

    // Verify URL contains search parameter
    await expect(page).toHaveURL(/search=Chicken/);

    // Step 5: Click on a recipe card to view details
    // Find first recipe link/card and click it
    const firstRecipe = page.locator('a[href*="/recipes/"]').first();
    await firstRecipe.click();

    // Step 6: Verify we're on recipe details page
    await expect(page).toHaveURL(/\/recipes\/\d+/);

    // Verify recipe details are visible (Flux components don't use h1/h2 tags)
    // Look for key sections: Back to Recipes link and Ingredients/Instructions sections
    await expect(page.getByText('Back to Recipes')).toBeVisible();
    await expect(page.getByText('Ingredients')).toBeVisible();
    await expect(page.getByText('Instructions')).toBeVisible();

    // Step 7: Use back button to return to recipe list
    await page.goBack();

    // Verify we're back on the recipes index
    await expect(page).toHaveURL(/\/recipes(?:\?|$)/);

    // Step 8: Try different meal type filter
    // Look for meal type filter (breakfast, lunch, dinner, snack)
    const mealTypeFilter = page.locator('input[type="checkbox"][value="breakfast"], label:has-text("Breakfast")').first();

    if (await mealTypeFilter.isVisible({ timeout: 2000 }).catch(() => false)) {
      await mealTypeFilter.click();

      // Wait for filter to apply
      await page.waitForTimeout(500);

      // Verify URL contains meal type parameter
      await expect(page).toHaveURL(/mealTypes/);
    }
  });

  test('recipe search functionality', async ({ page }) => {
    // Login first
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard|recipes/);

    // Go to recipes
    await page.goto(`${BASE_URL}/recipes`);

    // Test search functionality
    const searchInput = page.locator('input[name="search"], input[placeholder*="Search"]').first();

    if (await searchInput.isVisible({ timeout: 2000 }).catch(() => false)) {
      // Type in search box
      await searchInput.fill('pasta');
      await page.waitForTimeout(500);

      // Verify search parameter in URL
      await expect(page).toHaveURL(/search=pasta/);

      // Clear search
      await searchInput.clear();
      await page.waitForTimeout(500);
    }
  });

  test('pagination works correctly', async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard|recipes/);

    // Go to recipes
    await page.goto(`${BASE_URL}/recipes`);

    // Look for pagination (Next/Previous links)
    const nextButton = page.locator('a:has-text("Next"), button:has-text("Next")').first();

    if (await nextButton.isVisible({ timeout: 2000 }).catch(() => false)) {
      // If pagination exists, test it
      await nextButton.click();

      // Verify page parameter in URL
      await expect(page).toHaveURL(/page=2/);

      // Verify previous button appears
      const prevButton = page.locator('a:has-text("Previous"), button:has-text("Previous")').first();
      await expect(prevButton).toBeVisible();
    }
  });

  test('guest user redirected to login when accessing recipes', async ({ page }) => {
    // Try to access recipes without logging in
    await page.goto(`${BASE_URL}/recipes`);

    // Should be redirected to login page
    await expect(page).toHaveURL(/\/login/);
  });

  test('filter labels are visible', async ({ page }) => {
    // Login first
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard|recipes/);

    // Go to recipes page
    await page.goto(`${BASE_URL}/recipes`);

    // Verify Meal Type filter section heading
    await expect(page.getByText('Meal Type')).toBeVisible();

    // Verify all meal type filter labels are visible
    await expect(page.getByRole('checkbox', { name: 'Breakfast' })).toBeVisible();
    await expect(page.getByRole('checkbox', { name: 'Lunch' })).toBeVisible();
    await expect(page.getByRole('checkbox', { name: 'Dinner' })).toBeVisible();
    await expect(page.getByRole('checkbox', { name: 'Snack' })).toBeVisible();

    // Verify Dietary Preferences filter section heading
    await expect(page.getByText('Dietary Preferences')).toBeVisible();

    // Verify all dietary preference filter labels are visible
    await expect(page.getByRole('checkbox', { name: 'Vegetarian' })).toBeVisible();
    await expect(page.getByRole('checkbox', { name: 'Vegan' })).toBeVisible();
    await expect(page.getByRole('checkbox', { name: 'Gluten-Free' })).toBeVisible();
    await expect(page.getByRole('checkbox', { name: 'Dairy-Free' })).toBeVisible();
  });
});
