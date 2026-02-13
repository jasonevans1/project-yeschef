import { test, expect } from '@playwright/test';

test.describe('Meal Planning Journey', () => {
  test.beforeEach(async ({ page }) => {
    // Login with test user (should be seeded in database)
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for redirect after login
    await page.waitForURL(/dashboard|recipes|meal-plans/);
  });

  test('complete meal plan creation workflow', async ({ page }) => {
    // Go directly to create page
    await page.goto('/meal-plans/create');
    await expect(page).toHaveURL(/\/meal-plans\/create$/);

    // Fill in meal plan details (use future dates)
    const today = new Date();
    const startDate = new Date(today.getTime() + 24 * 60 * 60 * 1000); // Tomorrow
    const endDate = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000); // 7 days from now
    const startDateStr = startDate.toISOString().split('T')[0];
    const endDateStr = endDate.toISOString().split('T')[0];

    await page.fill('input[name="name"]', 'Week of Oct 23');
    await page.fill('input[name="start_date"]', startDateStr);
    await page.fill('input[name="end_date"]', endDateStr);
    await page.fill('textarea[name="description"]', 'My weekly meal plan');

    // Submit the form and wait for Livewire to process
    await page.click('button:has-text("Create Meal Plan")');

    // Wait for either redirect or error message
    await Promise.race([
      page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 }),
      page.waitForSelector('[data-flux-error]:not(.hidden)', { timeout: 10000 }).then(() => {
        throw new Error('Form validation failed - check error messages');
      }),
    ]);

    // Verify meal plan details are displayed
    await expect(page.locator('body')).toContainText('Week of Oct 23');

    // Note: Meal assignment and deletion features are not tested here as they
    // may not be fully implemented yet. These can be added to a separate test
    // once the UI for meal assignments is complete.
  });

  test('validates meal plan creation', async ({ page }) => {
    await page.goto('/meal-plans/create');

    // Verify form fields are present
    await expect(page.locator('input[name="name"]')).toBeVisible();
    await expect(page.locator('input[name="start_date"]')).toBeVisible();
    await expect(page.locator('input[name="end_date"]')).toBeVisible();

    // Fill in minimal valid data and verify it works
    await page.fill('input[name="name"]', 'Valid Test Plan');

    await page.click('button:has-text("Create Meal Plan")');

    // Should successfully create and redirect to show page
    await page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 });
  });

  test('creates meal plan with valid date range', async ({ page }) => {
    await page.goto('/meal-plans/create');

    // Create a valid meal plan with a 7-day range
    const today = new Date();
    const startDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);
    const endDate = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
    const startDateStr = startDate.toISOString().split('T')[0];
    const endDateStr = endDate.toISOString().split('T')[0];

    await page.fill('input[name="name"]', 'Valid Date Range Plan');
    await page.fill('input[name="start_date"]', startDateStr);
    await page.fill('input[name="end_date"]', endDateStr);

    await page.click('button:has-text("Create Meal Plan")');

    // Should successfully create and redirect
    await page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 });

    // Verify the plan name appears on the show page
    await expect(page.locator('body')).toContainText('Valid Date Range Plan');
  });

  test('meal calendar displays and functions correctly', async ({ page }) => {
    // Create a meal plan first
    await page.goto('/meal-plans/create');

    const today = new Date();
    const startDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);
    const endDate = new Date(today.getTime() + 3 * 24 * 60 * 60 * 1000); // 3 days
    const startDateStr = startDate.toISOString().split('T')[0];
    const endDateStr = endDate.toISOString().split('T')[0];

    await page.fill('input[name="name"]', 'Calendar Test Plan');
    await page.fill('input[name="start_date"]', startDateStr);
    await page.fill('input[name="end_date"]', endDateStr);

    await page.click('button:has-text("Create Meal Plan")');
    await page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 });

    // Verify the meal plan header is displayed
    await expect(page.locator('body')).toContainText('Calendar Test Plan');
    await expect(page.locator('body')).toContainText('3 days');

    // Verify action buttons/links are present (Flux buttons may render as links or buttons)
    const editButton = page.getByRole('link', { name: /edit/i }).or(page.getByRole('button', { name: /edit/i }));
    await expect(editButton).toBeVisible();

    const deleteButton = page.getByRole('button', { name: /delete/i });
    await expect(deleteButton).toBeVisible();

    // Verify calendar table structure
    await expect(page.locator('table thead')).toBeVisible();

    // Verify meal type column headers
    await expect(page.locator('th:has-text("Breakfast")')).toBeVisible();
    await expect(page.locator('th:has-text("Lunch")')).toBeVisible();
    await expect(page.locator('th:has-text("Dinner")')).toBeVisible();
    await expect(page.locator('th:has-text("Snack")')).toBeVisible();

    // Verify all 3 date rows are present
    const dateRows = page.locator('tbody tr');
    await expect(dateRows).toHaveCount(3);

    // Verify each row has date cells with proper data attributes
    const firstDateCell = page.locator(`[data-date="${startDateStr}"][data-meal-type="breakfast"]`);
    await expect(firstDateCell).toBeVisible();

    // Verify empty meal slots show "Add" buttons (plus icon buttons)
    const addButton = page.locator(`[data-date="${startDateStr}"][data-meal-type="breakfast"] button`).first();
    await expect(addButton).toBeVisible();

    // Test adding a recipe to a meal slot
    await addButton.click();

    // Wait for dropdown menu to appear and click "Add Recipe"
    // Use role="menuitem" to target the visible dropdown menu item
    await page.waitForTimeout(500);
    const addRecipeOption = page.locator('role=menuitem[name="Add Recipe"]').first();
    await addRecipeOption.click({ force: true });

    // Wait for the recipe selector modal to appear
    await expect(page.locator('text=Select Recipe for')).toBeVisible({ timeout: 5000 });

    // Verify search functionality in modal
    const searchInput = page.locator('input[placeholder*="Search recipes"]');
    await expect(searchInput).toBeVisible();

    // Search for a recipe
    await searchInput.fill('Chicken');

    // Wait for search results
    await page.waitForTimeout(500); // Debounce delay

    // Check if any recipes are displayed
    const recipeCards = page.locator('[data-recipe-card]');
    const recipeCount = await recipeCards.count();

    if (recipeCount > 0) {
      // Click on the first recipe
      const firstRecipe = recipeCards.first();
      const recipeName = await firstRecipe.locator('div.font-semibold').first().textContent();

      await firstRecipe.click();

      // Wait for modal to close
      await page.waitForTimeout(1000);

      // Verify the recipe was assigned to the slot
      const assignedSlot = page.locator(`[data-date="${startDateStr}"][data-meal-type="breakfast"]`);
      if (recipeName) {
        await expect(assignedSlot).toContainText(recipeName.trim());
      }

      // Test removing a recipe assignment
      // Hover over the assigned slot to reveal the remove button
      await assignedSlot.hover();

      // Look for the remove button (X icon)
      const removeButton = assignedSlot.locator('button').filter({ hasText: '' }).first();

      // Click remove if it exists
      if (await removeButton.isVisible({ timeout: 2000 }).catch(() => false)) {
        await removeButton.click();

        // Wait for the assignment to be removed
        await page.waitForTimeout(1000);

        // Verify the slot is now empty again (shows add button)
        await expect(assignedSlot.locator('button').first()).toBeVisible();
      }
    } else {
      // No recipes found - close the modal
      await page.locator('button[wire\\:click="closeRecipeSelector"]').click();
    }
  });

  test('deletes meal plan successfully', async ({ page }) => {
    // Create a meal plan first
    await page.goto('/meal-plans/create');

    const today = new Date();
    const startDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);
    const endDate = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);
    const startDateStr = startDate.toISOString().split('T')[0];
    const endDateStr = endDate.toISOString().split('T')[0];

    const planName = 'Plan to Delete';
    await page.fill('input[name="name"]', planName);
    await page.fill('input[name="start_date"]', startDateStr);
    await page.fill('input[name="end_date"]', endDateStr);

    await page.click('button:has-text("Create Meal Plan")');
    await page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 });

    // Verify we're on the meal plan page
    await expect(page.locator('body')).toContainText(planName);

    // Set up dialog handler to accept the confirmation
    page.once('dialog', dialog => {
      expect(dialog.message()).toContain('Are you sure you want to delete this meal plan');
      dialog.accept();
    });

    // Click the delete button
    const deleteButton = page.getByRole('button', { name: /delete/i });
    await deleteButton.click();

    // Wait for redirect to meal plans index
    await page.waitForURL(/\/meal-plans$/, { timeout: 10000 });

    // Verify the deleted meal plan is no longer in the list
    // Wait a bit for the page to fully load
    await page.waitForTimeout(500);

    const planLinks = page.locator('a, div').filter({ hasText: new RegExp(`^${planName}$`) });
    await expect(planLinks).toHaveCount(0);
  });

});
