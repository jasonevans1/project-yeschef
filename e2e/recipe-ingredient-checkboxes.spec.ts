import { test, expect } from '@playwright/test';

const BASE_URL = process.env.BASE_URL || 'https://project-tabletop.ddev.site';

test.describe('Recipe Ingredient Checkboxes', () => {
  test.beforeEach(async ({ page }) => {
    // Login
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard|recipes/);
  });

  // T016: Ingredient checkboxes are visible and functional
  test('ingredient checkboxes are visible and functional', async ({ page }) => {
    // Navigate to recipes
    await page.goto(`${BASE_URL}/recipes`);

    // Click first recipe
    const firstRecipe = page.locator('a[href*="/recipes/"]:not([href*="/create"]):not([href*="/import"]):not([href*="/edit"])').first();
    await firstRecipe.click();

    // Wait for recipe page to load
    await expect(page).toHaveURL(/\/recipes\/\d+/);
    await expect(page.getByText('Ingredients')).toBeVisible();

    // Verify checkboxes exist
    const checkboxes = page.locator('input[type="checkbox"]');
    const checkboxCount = await checkboxes.count();
    expect(checkboxCount).toBeGreaterThan(0);

    // Verify all checkboxes are initially unchecked
    for (let i = 0; i < checkboxCount; i++) {
      await expect(checkboxes.nth(i)).not.toBeChecked();
    }
  });

  // T017: Checking ingredient applies strikethrough and opacity
  test('checking ingredient applies strikethrough and opacity', async ({ page }) => {
    await page.goto(`${BASE_URL}/recipes`);
    const firstRecipe = page.locator('a[href*="/recipes/"]:not([href*="/create"]):not([href*="/import"]):not([href*="/edit"])').first();
    await firstRecipe.click();

    await expect(page).toHaveURL(/\/recipes\/\d+/);

    // Get first ingredient list item
    const firstIngredient = page.locator('ul.space-y-3 > li').first();
    const firstCheckbox = firstIngredient.locator('input[type="checkbox"]');
    const textContainer = firstIngredient.locator('.flex-1');

    // Verify initial state
    await expect(textContainer).not.toHaveClass(/line-through/);
    await expect(textContainer).not.toHaveClass(/opacity-50/);

    // Check the checkbox
    await firstCheckbox.check();

    // Verify visual feedback is applied
    await expect(textContainer).toHaveClass(/line-through/);
    await expect(textContainer).toHaveClass(/opacity-50/);

    // Uncheck the checkbox
    await firstCheckbox.uncheck();

    // Verify visual feedback is removed
    await expect(textContainer).not.toHaveClass(/line-through/);
  });

  // T018: Multiple ingredients can be checked independently
  test('multiple ingredients can be checked independently', async ({ page }) => {
    await page.goto(`${BASE_URL}/recipes`);
    const firstRecipe = page.locator('a[href*="/recipes/"]:not([href*="/create"]):not([href*="/import"]):not([href*="/edit"])').first();
    await firstRecipe.click();

    await expect(page).toHaveURL(/\/recipes\/\d+/);

    const checkboxes = page.locator('input[type="checkbox"]');
    const checkboxCount = await checkboxes.count();

    // Skip if less than 3 ingredients
    if (checkboxCount < 3) {
      test.skip();
    }

    // Check first and third checkboxes
    await checkboxes.nth(0).check();
    await checkboxes.nth(2).check();

    // Verify state
    await expect(checkboxes.nth(0)).toBeChecked();
    await expect(checkboxes.nth(1)).not.toBeChecked();
    await expect(checkboxes.nth(2)).toBeChecked();
  });

  // T019: Checkbox state resets after page refresh
  test('checkbox state resets after page refresh', async ({ page }) => {
    await page.goto(`${BASE_URL}/recipes`);
    const firstRecipe = page.locator('a[href*="/recipes/"]:not([href*="/create"]):not([href*="/import"]):not([href*="/edit"])').first();
    await firstRecipe.click();

    await expect(page).toHaveURL(/\/recipes\/\d+/);

    // Check all checkboxes
    const checkboxes = page.locator('input[type="checkbox"]');
    const checkboxCount = await checkboxes.count();

    for (let i = 0; i < checkboxCount; i++) {
      await checkboxes.nth(i).check();
    }

    // Verify all are checked
    for (let i = 0; i < checkboxCount; i++) {
      await expect(checkboxes.nth(i)).toBeChecked();
    }

    // Refresh the page
    await page.reload();

    // Verify all checkboxes are unchecked after refresh
    const checkboxesAfterRefresh = page.locator('input[type="checkbox"]');
    for (let i = 0; i < checkboxCount; i++) {
      await expect(checkboxesAfterRefresh.nth(i)).not.toBeChecked();
    }
  });

  // T020: Checkbox state resets when navigating to different recipe and back
  test('checkbox state resets when navigating to different recipe and back', async ({ page }) => {
    await page.goto(`${BASE_URL}/recipes`);

    // Get first two recipe links
    const recipes = page.locator('a[href*="/recipes/"]:not([href*="/create"]):not([href*="/import"]):not([href*="/edit"])');

    // Navigate to first recipe
    await recipes.nth(0).click();
    await expect(page).toHaveURL(/\/recipes\/\d+/);
    const firstRecipeUrl = page.url();

    // Check first ingredient
    const firstCheckbox = page.locator('input[type="checkbox"]').first();
    await firstCheckbox.check();
    await expect(firstCheckbox).toBeChecked();

    // Navigate to recipes index
    await page.click('text=Back to Recipes');
    await expect(page).toHaveURL(/\/recipes/);

    // Navigate to second recipe
    const recipesAfterBack = page.locator('a[href*="/recipes/"]:not([href*="/create"]):not([href*="/import"]):not([href*="/edit"])');
    await recipesAfterBack.nth(1).click();
    await expect(page).toHaveURL(/\/recipes\/\d+/);

    // Navigate back to first recipe
    await page.goto(firstRecipeUrl);

    // Verify checkbox is unchecked (state was reset)
    const checkboxAfterReturn = page.locator('input[type="checkbox"]').first();
    await expect(checkboxAfterReturn).not.toBeChecked();
  });

  // T021: Checkboxes work on mobile viewport
  test('checkboxes work on mobile viewport', async ({ page }) => {
    // Set mobile viewport
    await page.setViewportSize({ width: 375, height: 667 });

    await page.goto(`${BASE_URL}/recipes`);
    const firstRecipe = page.locator('a[href*="/recipes/"]:not([href*="/create"]):not([href*="/import"]):not([href*="/edit"])').first();
    await firstRecipe.click();

    await expect(page).toHaveURL(/\/recipes\/\d+/);

    // Get first ingredient list item
    const firstIngredient = page.locator('ul.space-y-3 > li').first();
    const firstCheckbox = firstIngredient.locator('input[type="checkbox"]');
    const textContainer = firstIngredient.locator('.flex-1');

    // Verify checkboxes are still clickable on mobile
    await firstCheckbox.check();
    await expect(firstCheckbox).toBeChecked();
    await expect(textContainer).toHaveClass(/line-through/);
  });

  // T022: Checkboxes are keyboard accessible (Tab + Space)
  test('checkboxes are keyboard accessible', async ({ page }) => {
    await page.goto(`${BASE_URL}/recipes`);
    const firstRecipe = page.locator('a[href*="/recipes/"]:not([href*="/create"]):not([href*="/import"]):not([href*="/edit"])').first();
    await firstRecipe.click();

    await expect(page).toHaveURL(/\/recipes\/\d+/);

    const firstCheckbox = page.locator('input[type="checkbox"]').first();

    // Focus the checkbox explicitly for testing
    await firstCheckbox.focus();

    // Toggle with Space key
    await page.keyboard.press('Space');
    await expect(firstCheckbox).toBeChecked();

    // Toggle again
    await page.keyboard.press('Space');
    await expect(firstCheckbox).not.toBeChecked();
  });
});
