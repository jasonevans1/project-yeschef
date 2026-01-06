import { test, expect } from '@playwright/test';

test.describe('Grocery Item Autocomplete', () => {
  let groceryListId: number;

  test.beforeAll(async ({ browser }) => {
    // Create a test grocery list once for all tests
    const page = await browser.newPage();
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');

    // Create a new grocery list
    await page.goto('/grocery-lists/create');
    await page.fill('input[name="name"]', 'Autocomplete Test List');
    await page.click('button:has-text("Create List")');
    await page.waitForURL(/\/grocery-lists\/\d+/);

    // Extract the grocery list ID from the URL
    const url = page.url();
    const match = url.match(/\/grocery-lists\/(\d+)/);
    groceryListId = match ? parseInt(match[1]) : 0;

    await page.close();
  });

  test.beforeEach(async ({ page }) => {
    // Login and navigate to the test grocery list
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');

    // Navigate directly to the test grocery list
    await page.goto(`/grocery-lists/${groceryListId}`);
    await page.waitForLoadState('networkidle');
  });

  // T019: Test typing triggers dropdown
  test('typing triggers autocomplete dropdown', async ({ page }) => {
    // Open add item form
    await page.click('button:has-text("Add Item")');

    // Type in the item name field
    const itemNameInput = page.locator('#searchQuery');
    await itemNameInput.fill('mil');

    // Wait for debounce (300ms) + some buffer
    await page.waitForTimeout(400);

    // Check that dropdown appears with suggestions
    const dropdown = page.locator('[role="listbox"]');
    await expect(dropdown).toBeVisible();

    // Verify suggestions contain "milk"
    const suggestions = page.locator('[role="option"]');
    await expect(suggestions.first()).toContainText('milk');
  });

  // T020: Test selecting suggestion populates fields
  test('selecting suggestion populates fields correctly', async ({ page }) => {
    // Open add item form
    await page.click('button:has-text("Add Item")');

    // Type to trigger autocomplete
    const itemNameInput = page.locator('#searchQuery');
    await itemNameInput.fill('mil');
    await page.waitForTimeout(400);

    // Wait for dropdown to appear and click first option
    await page.waitForSelector('[role="listbox"]', { state: 'visible' });
    const firstOption = page.locator('[role="option"]').first();
    await firstOption.click();

    // Wait for the form to update
    await page.waitForTimeout(200);

    // Search query should be cleared after selection
    await expect(itemNameInput).toHaveValue('');

    // Category should be auto-populated to "dairy"
    const categorySelect = page.locator('#itemCategory');
    await expect(categorySelect).toHaveValue('dairy');

    // Unit should be auto-populated to "gallon"
    const unitSelect = page.locator('#itemUnit');
    await expect(unitSelect).toHaveValue('gallon');

    // Quantity should be auto-populated to "1"
    const quantityInput = page.locator('#itemQuantity');
    await expect(quantityInput).toHaveValue('1');
  });

  // T021: Test keyboard navigation
  test('keyboard navigation works correctly', async ({ page }) => {
    // Open add item form
    await page.click('button:has-text("Add Item")');

    // Type to trigger autocomplete
    const itemNameInput = page.locator('#searchQuery');
    await itemNameInput.fill('mil');
    await page.waitForTimeout(400);

    // Test Arrow Down
    await itemNameInput.press('ArrowDown');

    // First suggestion should be highlighted
    const firstOption = page.locator('[role="option"]').first();
    await expect(firstOption).toHaveAttribute('aria-selected', 'true');

    // Test Arrow Down again
    await itemNameInput.press('ArrowDown');

    // Second suggestion should be highlighted
    const secondOption = page.locator('[role="option"]').nth(1);
    await expect(secondOption).toHaveAttribute('aria-selected', 'true');

    // Test Arrow Up
    await itemNameInput.press('ArrowUp');

    // First suggestion should be highlighted again
    await expect(firstOption).toHaveAttribute('aria-selected', 'true');

    // Test Enter to select
    await itemNameInput.press('Enter');

    // Wait for selection to process
    await page.waitForTimeout(200);

    // Dropdown should close
    const dropdown = page.locator('[role="listbox"]');
    await expect(dropdown).not.toBeVisible();

    // Search query should be cleared and category should be populated
    await expect(itemNameInput).toHaveValue('');
    const categorySelect = page.locator('#itemCategory');
    await expect(categorySelect).not.toHaveValue('');
  });
});
