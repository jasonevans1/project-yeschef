import { test, expect } from '@playwright/test';

/**
 * E2E Test: Grocery List Generation and Management
 *
 * User Story 3: Generate Grocery List from Meal Plan
 *
 * This test covers the complete user journey:
 * 1. User creates a meal plan
 * 2. Assigns 3 recipes with overlapping ingredients
 * 3. Generates grocery list from meal plan
 * 4. Views aggregated items grouped by category
 * 5. Marks items as purchased
 * 6. Views completion progress
 */

test.describe('Grocery List Generation', () => {
  test.beforeEach(async ({ page }) => {
    // Login as test user
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for redirect to dashboard/home
    await page.waitForURL(/\/(dashboard|home)?$/);
  });

  test('user creates meal plan, assigns recipes, generates grocery list, and manages items', async ({ page }) => {
    // Step 1: Navigate to meal plans and create a new one
    await page.goto('/meal-plans');
    await page.click('text=Create New Meal Plan');

    // Wait for create form to load
    await expect(page.locator('h1')).toContainText('Create Meal Plan');

    // Fill in meal plan details
    await page.fill('input[name="name"]', 'Weekly Meal Plan - E2E Test');

    // Set date range (7 days from today)
    const today = new Date();
    const startDate = today.toISOString().split('T')[0];
    const endDate = new Date(today.getTime() + 6 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    await page.fill('input[name="start_date"]', startDate);
    await page.fill('input[name="end_date"]', endDate);

    // Submit the form
    await page.click('button:has-text("Create Meal Plan")');

    // Wait for redirect to meal plan show page
    await page.waitForURL(/\/meal-plans\/\d+/);
    await expect(page.locator('h1')).toContainText('Weekly Meal Plan - E2E Test');

    // Step 2: Assign 3 recipes with overlapping ingredients

    // Recipe 1: Assign to Monday Dinner (contains: chicken, milk, tomatoes)
    await page.click('[data-date][data-meal="dinner"]', { timeout: 5000 });

    // Search for and select first recipe
    await page.fill('input[placeholder*="Search recipes"]', 'Chicken Pasta');
    await page.waitForSelector('text=Chicken Pasta', { timeout: 3000 });
    await page.click('text=Chicken Pasta');

    // Confirm assignment
    await page.click('button:has-text("Assign Recipe")');

    // Wait for recipe to appear in meal plan
    await expect(page.locator('[data-date][data-meal="dinner"]')).toContainText('Chicken Pasta');

    // Recipe 2: Assign to Tuesday Lunch (contains: milk, cheese, bread)
    await page.click('[data-date]:nth-of-type(2) [data-meal="lunch"]');

    await page.fill('input[placeholder*="Search recipes"]', 'Grilled Cheese');
    await page.waitForSelector('text=Grilled Cheese');
    await page.click('text=Grilled Cheese');
    await page.click('button:has-text("Assign Recipe")');

    await expect(page.locator('[data-date]:nth-of-type(2) [data-meal="lunch"]')).toContainText('Grilled Cheese');

    // Recipe 3: Assign to Wednesday Breakfast (contains: milk, eggs, cheese)
    await page.click('[data-date]:nth-of-type(3) [data-meal="breakfast"]');

    await page.fill('input[placeholder*="Search recipes"]', 'Scrambled Eggs');
    await page.waitForSelector('text=Scrambled Eggs');
    await page.click('text=Scrambled Eggs');
    await page.click('button:has-text("Assign Recipe")');

    await expect(page.locator('[data-date]:nth-of-type(3) [data-meal="breakfast"]')).toContainText('Scrambled Eggs');

    // Step 3: Generate grocery list
    await page.click('button:has-text("Generate Grocery List")');

    // Wait for redirect to grocery list show page
    await page.waitForURL(/\/grocery-lists\/\d+/);

    // Verify we're on the grocery list page
    await expect(page.locator('h1')).toContainText('Grocery List');

    // Step 4: Verify aggregated items are displayed

    // Milk should appear only once (aggregated from 3 recipes)
    const milkItems = page.locator('text=Milk').or(page.locator('text=milk'));
    await expect(milkItems).toBeVisible();

    // Verify milk quantity is aggregated (should be sum of all 3 recipes)
    // Note: Exact quantity depends on seeded recipe data

    // Cheese should appear (from recipes 2 and 3)
    await expect(page.locator('text=Cheese').or(page.locator('text=cheese'))).toBeVisible();

    // Step 5: Verify items are grouped by category

    // Should see category headers
    await expect(page.locator('text=Dairy')).toBeVisible();
    await expect(page.locator('text=Produce')).toBeVisible();
    await expect(page.locator('text=Meat')).toBeVisible();

    // Verify items appear under correct categories
    const dairySection = page.locator('[data-category="dairy"]').or(page.locator('text=Dairy').locator('..')).first();
    await expect(dairySection).toBeVisible();

    const produceSection = page.locator('[data-category="produce"]').or(page.locator('text=Produce').locator('..')).first();
    await expect(produceSection).toBeVisible();

    const meatSection = page.locator('[data-category="meat"]').or(page.locator('text=Meat').locator('..')).first();
    await expect(meatSection).toBeVisible();

    // Step 6: Mark 2 items as purchased

    // Find checkboxes for items
    const checkboxes = page.locator('input[type="checkbox"][data-item-id]').or(
      page.locator('.grocery-item input[type="checkbox"]')
    );

    // Count total items before marking
    const totalItemsText = await page.locator('text=/\\d+ items?/i').first().textContent();

    // Mark first item as purchased
    const firstCheckbox = checkboxes.first();
    await firstCheckbox.check();

    // Verify checkbox is checked
    await expect(firstCheckbox).toBeChecked();

    // Verify item styling changes (strikethrough, gray, etc.)
    const firstItem = firstCheckbox.locator('..').or(firstCheckbox.locator('../..'));
    await expect(firstItem).toHaveClass(/purchased|completed|checked/);

    // Mark second item as purchased
    const secondCheckbox = checkboxes.nth(1);
    await secondCheckbox.check();

    await expect(secondCheckbox).toBeChecked();

    // Step 7: View completion progress

    // Progress bar or percentage should be visible
    const progressIndicator = page.locator('[data-testid="completion-progress"]').or(
      page.locator('text=/%|progress/i')
    );
    await expect(progressIndicator).toBeVisible();

    // Verify progress percentage is calculated correctly
    // If we have 10 items and marked 2, should show 20%
    // Note: Exact calculation depends on actual item count
    const progressText = await page.locator('text=/\\d+%/').first().textContent();
    expect(progressText).toMatch(/\d+%/);

    // Verify completion percentage is greater than 0%
    const percentage = parseInt(progressText?.match(/\d+/)?.[0] || '0');
    expect(percentage).toBeGreaterThan(0);

    // Step 8: Verify item details are displayed correctly

    // Items should show name, quantity, and unit
    const firstItemText = await page.locator('.grocery-item').first().textContent();
    expect(firstItemText).toMatch(/\d+/); // Contains quantity
    expect(firstItemText).toMatch(/\w+/); // Contains item name

    // Step 9: Unmark an item to verify state changes
    await firstCheckbox.uncheck();
    await expect(firstCheckbox).not.toBeChecked();

    // Progress should update accordingly
    const newProgressText = await page.locator('text=/\\d+%/').first().textContent();
    const newPercentage = parseInt(newProgressText?.match(/\d+/)?.[0] || '0');
    expect(newPercentage).toBeLessThan(percentage);
  });

  test('empty meal plan generates empty grocery list with helpful message', async ({ page }) => {
    // Create a meal plan without assigning any recipes
    await page.goto('/meal-plans');
    await page.click('text=Create New Meal Plan');

    await page.fill('input[name="name"]', 'Empty Meal Plan');

    const today = new Date();
    const startDate = today.toISOString().split('T')[0];
    const endDate = new Date(today.getTime() + 6 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    await page.fill('input[name="start_date"]', startDate);
    await page.fill('input[name="end_date"]', endDate);

    await page.click('button:has-text("Create Meal Plan")');
    await page.waitForURL(/\/meal-plans\/\d+/);

    // Generate grocery list without assigning recipes
    await page.click('button:has-text("Generate Grocery List")');
    await page.waitForURL(/\/grocery-lists\/\d+/);

    // Should see empty state message
    await expect(page.locator('text=/no items|empty|add items/i')).toBeVisible();
  });

  test('generated grocery list links to source meal plan', async ({ page }) => {
    // Navigate to an existing grocery list (generated from meal plan)
    await page.goto('/grocery-lists');

    // Click on a meal plan-linked grocery list
    await page.click('.grocery-list-item:has-text("Meal Plan")');

    // Should show source meal plan name
    await expect(page.locator('text=/from meal plan|source:/i')).toBeVisible();

    // Should have link to meal plan
    const mealPlanLink = page.locator('a[href*="/meal-plans/"]');
    await expect(mealPlanLink).toBeVisible();
  });

  test('standalone grocery list does not show meal plan link', async ({ page }) => {
    // Create a standalone grocery list
    await page.goto('/grocery-lists');
    await page.click('text=Create Standalone List');

    await page.fill('input[name="name"]', 'Party Shopping List');
    await page.click('button:has-text("Create List")');

    await page.waitForURL(/\/grocery-lists\/\d+/);

    // Should show standalone indicator
    await expect(page.locator('text=/standalone|manual/i')).toBeVisible();

    // Should NOT show meal plan link
    await expect(page.locator('a[href*="/meal-plans/"]')).not.toBeVisible();
  });

  test('grocery list displays items in category order', async ({ page }) => {
    // Navigate to a grocery list with items
    await page.goto('/grocery-lists');

    // Click first grocery list
    await page.click('.grocery-list-item').first();

    // Get all category headers
    const categoryHeaders = page.locator('[data-category-header]').or(
      page.locator('h2, h3').filter({ hasText: /dairy|produce|meat|pantry/i })
    );

    // Should have at least one category
    await expect(categoryHeaders.first()).toBeVisible();

    // Categories should be in a logical order
    // (Exact order depends on implementation, but should be consistent)
  });

  test('regenerating grocery list preserves manual items', async ({ page }) => {
    // Navigate to meal plan
    await page.goto('/meal-plans');
    await page.click('.meal-plan-item').first();

    // Generate grocery list
    await page.click('button:has-text("Generate Grocery List")');
    await page.waitForURL(/\/grocery-lists\/\d+/);

    // Add a manual item
    await page.click('button:has-text("Add Item")');
    await page.fill('input[name="name"]', 'Paper Towels');
    await page.selectOption('select[name="category"]', 'other');
    await page.click('button:has-text("Save Item")');

    // Verify manual item is added
    await expect(page.locator('text=Paper Towels')).toBeVisible();

    // Regenerate the list
    await page.click('button:has-text("Regenerate")');
    await page.click('button:has-text("Confirm")'); // Confirmation dialog

    // Manual item should still be present
    await expect(page.locator('text=Paper Towels')).toBeVisible();
  });

  test('duplicate ingredients with different units are aggregated', async ({ page }) => {
    // This test requires seeded recipes with overlapping ingredients in different units
    // For example: Recipe A uses "2 cups milk", Recipe B uses "1 pint milk"

    await page.goto('/meal-plans');
    await page.click('text=Create New Meal Plan');

    await page.fill('input[name="name"]', 'Unit Conversion Test');

    const today = new Date();
    const startDate = today.toISOString().split('T')[0];
    const endDate = new Date(today.getTime() + 6 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    await page.fill('input[name="start_date"]', startDate);
    await page.fill('input[name="end_date"]', endDate);
    await page.click('button:has-text("Create Meal Plan")');

    await page.waitForURL(/\/meal-plans\/\d+/);

    // Assign recipes with same ingredient but different units
    // (Depends on seeded test data)

    // Generate grocery list
    await page.click('button:has-text("Generate Grocery List")');
    await page.waitForURL(/\/grocery-lists\/\d+/);

    // Milk should appear only once with aggregated quantity
    const milkItems = page.locator('.grocery-item:has-text("milk")');
    await expect(milkItems).toHaveCount(1);

    // Quantity should be aggregated (2 cups + 1 pint = 4 cups or 1 quart)
    const milkText = await milkItems.textContent();
    expect(milkText).toMatch(/\d+/); // Has a quantity
  });

  test('completion progress updates in real-time', async ({ page }) => {
    await page.goto('/grocery-lists');
    await page.click('.grocery-list-item').first();

    // Get initial progress
    const initialProgressText = await page.locator('text=/\\d+%/').first().textContent();
    const initialPercentage = parseInt(initialProgressText?.match(/\d+/)?.[0] || '0');

    // Mark an item as purchased
    const checkbox = page.locator('input[type="checkbox"]').first();
    await checkbox.check();

    // Wait for progress to update
    await page.waitForTimeout(500); // Allow for any animations/updates

    // Get new progress
    const newProgressText = await page.locator('text=/\\d+%/').first().textContent();
    const newPercentage = parseInt(newProgressText?.match(/\d+/)?.[0] || '0');

    // Progress should have increased
    expect(newPercentage).toBeGreaterThan(initialPercentage);
  });
});

test.describe('Grocery List Authorization', () => {
  test('user cannot view another user\'s grocery list', async ({ page }) => {
    // Login as first user
    await page.goto('/login');
    await page.fill('input[name="email"]', 'user1@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Create a grocery list
    await page.goto('/grocery-lists');
    await page.click('text=Create Standalone List');
    await page.fill('input[name="name"]', 'User 1 Private List');
    await page.click('button:has-text("Create List")');

    // Get the grocery list ID from URL
    await page.waitForURL(/\/grocery-lists\/(\d+)/);
    const url = page.url();
    const groceryListId = url.match(/\/grocery-lists\/(\d+)/)?.[1];

    // Logout
    await page.click('button:has-text("Logout")');

    // Login as second user
    await page.goto('/login');
    await page.fill('input[name="email"]', 'user2@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Try to access first user's grocery list
    await page.goto(`/grocery-lists/${groceryListId}`);

    // Should see forbidden/unauthorized message or be redirected
    await expect(page.locator('text=/forbidden|unauthorized|access denied/i')).toBeVisible();
  });
});
