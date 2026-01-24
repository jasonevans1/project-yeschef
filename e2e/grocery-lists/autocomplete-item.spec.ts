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

    // Verify suggestions contain "milk" (case-insensitive, matches "milk", "Milk", "Whole Milk", etc.)
    const suggestions = page.locator('[role="option"]');
    const firstSuggestion = suggestions.first();
    await expect(firstSuggestion.locator('.font-medium').first()).toContainText(/milk/i);
  });

  // T020: Test selecting suggestion populates fields
  test('selecting suggestion populates fields correctly', async ({ page }) => {
    // Open add item form
    await page.click('button:has-text("Add Item")');

    // Type to trigger autocomplete (use "butt" for "butter")
    const itemNameInput = page.locator('#searchQuery');
    await itemNameInput.fill('butt');
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

    // Unit should be auto-populated to "lb"
    const unitSelect = page.locator('#itemUnit');
    await expect(unitSelect).toHaveValue('lb');

    // Quantity should be auto-populated to "1" (displayed without trailing zeros)
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

  // T039: Test personal suggestions appear first
  test('personal suggestions appear before common defaults', async ({ page }) => {
    // Step 1: Add "almond milk" with custom category "beverages"
    await page.click('button:has-text("Add Item")');

    const itemNameInput = page.locator('#searchQuery');
    await itemNameInput.fill('almond milk');

    // Wait for searchQuery debounce to sync (300ms + buffer)
    await page.waitForTimeout(400);

    const categorySelect = page.locator('#itemCategory');
    await categorySelect.selectOption('beverages');

    const unitSelect = page.locator('#itemUnit');
    await unitSelect.selectOption('gallon');

    const quantityInput = page.locator('#itemQuantity');
    await quantityInput.fill('1');

    // Save the item
    await page.click('button:has-text("Save Item")');
    await page.waitForTimeout(1000); // Wait for observer job to process

    // Step 2: Open add item form again
    await page.click('button:has-text("Add Item")');

    // Step 3: Type "alm" to trigger autocomplete
    await itemNameInput.fill('alm');
    await page.waitForTimeout(400);

    // Step 4: Verify "almond milk" appears in suggestions
    const dropdown = page.locator('[role="listbox"]');
    await expect(dropdown).toBeVisible();

    const firstSuggestion = page.locator('[role="option"]').first();
    await expect(firstSuggestion).toContainText('almond milk');

    // Step 5: Click on the personal suggestion
    await firstSuggestion.click();
    await page.waitForTimeout(200);

    // Step 6: Verify it uses the user's preferred category (beverages, not dairy)
    await expect(categorySelect).toHaveValue('beverages');
    await expect(unitSelect).toHaveValue('gallon');
    await expect(quantityInput).toHaveValue('1.000');
  });
});
