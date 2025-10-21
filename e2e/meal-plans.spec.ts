import { test, expect } from '@playwright/test';

test.describe('Meal Planning Journey', () => {
  let testEmail: string;
  let testPassword: string;

  test.beforeEach(async ({ page }) => {
    // Create unique test credentials for each test
    testEmail = `test-${Date.now()}@example.com`;
    testPassword = 'password123';

    // Register a new user
    await page.goto('/register');
    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', testEmail);
    await page.fill('input[name="password"]', testPassword);
    await page.fill('input[name="password_confirmation"]', testPassword);
    await page.click('button[type="submit"]');

    // Wait for redirect after registration
    await page.waitForURL(/\/(?!register)/);
  });

  test('complete meal planning workflow', async ({ page }) => {
    // Navigate to meal plans page
    await page.goto('/meal-plans');
    await expect(page).toHaveURL('/meal-plans');

    // Create new meal plan
    await page.click('text=Create New Meal Plan');
    await expect(page).toHaveURL('/meal-plans/create');

    // Fill in meal plan details
    const startDate = '2025-10-14';
    const endDate = '2025-10-20';

    await page.fill('input[name="name"]', 'Week of Oct 14');
    await page.fill('input[name="start_date"]', startDate);
    await page.fill('input[name="end_date"]', endDate);
    await page.fill('textarea[name="description"]', 'My weekly meal plan');

    // Submit the form
    await page.click('button[type="submit"]');

    // Should redirect to meal plan show page
    await page.waitForURL(/\/meal-plans\/\d+/);

    // Verify meal plan details are displayed
    await expect(page.locator('h1, h2')).toContainText('Week of Oct 14');

    // Click on Monday dinner slot
    // Note: This selector will need to be adjusted based on actual implementation
    const mondayDinnerSlot = page.locator('[data-date="2025-10-15"][data-meal-type="dinner"]');
    await mondayDinnerSlot.click();

    // Search for a recipe
    const recipeSearchInput = page.locator('input[placeholder*="Search"], input[type="search"]').first();
    await recipeSearchInput.fill('Chicken');

    // Wait for search results to appear
    await page.waitForTimeout(500); // Debounce delay

    // Click on first recipe in results
    const firstRecipe = page.locator('[data-recipe-card]').first();
    const recipeName = await firstRecipe.textContent();
    await firstRecipe.click();

    // Confirm assignment (if there's a confirmation step)
    const assignButton = page.locator('button:has-text("Assign")');
    if (await assignButton.isVisible({ timeout: 1000 })) {
      await assignButton.click();
    }

    // Verify recipe appears in the calendar
    await expect(mondayDinnerSlot).toContainText(recipeName || '');

    // Assign recipe to Tuesday breakfast
    const tuesdayBreakfastSlot = page.locator('[data-date="2025-10-16"][data-meal-type="breakfast"]');
    await tuesdayBreakfastSlot.click();

    // Search for breakfast recipe
    await recipeSearchInput.fill('Pancake');
    await page.waitForTimeout(500);

    // Assign second recipe
    const secondRecipe = page.locator('[data-recipe-card]').first();
    const secondRecipeName = await secondRecipe.textContent();
    await secondRecipe.click();

    if (await assignButton.isVisible({ timeout: 1000 })) {
      await assignButton.click();
    }

    // Verify both recipes are assigned
    await expect(mondayDinnerSlot).toContainText(recipeName || '');
    await expect(tuesdayBreakfastSlot).toContainText(secondRecipeName || '');

    // Remove one assignment
    // Click on the Monday dinner slot to open options
    await mondayDinnerSlot.click();

    // Look for remove/delete button
    const removeButton = page.locator('button:has-text("Remove"), button:has-text("Delete")');
    if (await removeButton.isVisible({ timeout: 1000 })) {
      await removeButton.click();

      // Confirm deletion if there's a confirmation dialog
      const confirmButton = page.locator('button:has-text("Confirm"), button:has-text("Yes")');
      if (await confirmButton.isVisible({ timeout: 1000 })) {
        await confirmButton.click();
      }

      // Verify assignment is gone
      await expect(mondayDinnerSlot).not.toContainText(recipeName || '');
    }

    // Delete entire meal plan
    const deleteButton = page.locator('button:has-text("Delete Meal Plan"), button:has-text("Delete Plan")');
    await deleteButton.click();

    // Confirm deletion
    const confirmDeleteButton = page.locator('button:has-text("Confirm"), button:has-text("Yes"), button:has-text("Delete")');
    await confirmDeleteButton.click();

    // Should redirect to meal plans index
    await page.waitForURL('/meal-plans');

    // Verify meal plan no longer exists
    await expect(page).not.toHaveText('Week of Oct 14');
  });

  test('validates meal plan creation', async ({ page }) => {
    await page.goto('/meal-plans/create');

    // Try to submit empty form
    await page.click('button[type="submit"]');

    // Should show validation errors
    await expect(page.locator('text=/required/i')).toBeVisible();
  });

  test('validates date range constraints', async ({ page }) => {
    await page.goto('/meal-plans/create');

    // Try to create plan with end date before start date
    await page.fill('input[name="name"]', 'Invalid Plan');
    await page.fill('input[name="start_date"]', '2025-10-20');
    await page.fill('input[name="end_date"]', '2025-10-14');

    await page.click('button[type="submit"]');

    // Should show validation error
    await expect(page.locator('text=/end date/i, text=/after/i')).toBeVisible();

    // Try to create plan longer than 28 days
    await page.fill('input[name="start_date"]', '2025-10-01');
    await page.fill('input[name="end_date"]', '2025-11-05'); // 35 days

    await page.click('button[type="submit"]');

    // Should show validation error about maximum duration
    await expect(page.locator('text=/28 days/i, text=/maximum/i')).toBeVisible();
  });

  test('displays meal plan calendar correctly', async ({ page }) => {
    // Create a meal plan first (using the flow from the first test)
    await page.goto('/meal-plans/create');

    await page.fill('input[name="name"]', 'Calendar Test Plan');
    await page.fill('input[name="start_date"]', '2025-10-14');
    await page.fill('input[name="end_date"]', '2025-10-16'); // 3 days

    await page.click('button[type="submit"]');
    await page.waitForURL(/\/meal-plans\/\d+/);

    // Verify calendar structure
    // Should have 3 days × 4 meal types = 12 slots minimum
    const slots = page.locator('[data-date][data-meal-type]');
    await expect(slots).toHaveCount(12);

    // Verify all dates are present
    await expect(page.locator('[data-date="2025-10-14"]')).toBeVisible();
    await expect(page.locator('[data-date="2025-10-15"]')).toBeVisible();
    await expect(page.locator('[data-date="2025-10-16"]')).toBeVisible();

    // Verify all meal types are present for at least one day
    await expect(page.locator('[data-meal-type="breakfast"]').first()).toBeVisible();
    await expect(page.locator('[data-meal-type="lunch"]').first()).toBeVisible();
    await expect(page.locator('[data-meal-type="dinner"]').first()).toBeVisible();
    await expect(page.locator('[data-meal-type="snack"]').first()).toBeVisible();
  });
});
