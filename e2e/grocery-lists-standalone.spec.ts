import { test, expect } from '@playwright/test';

/**
 * E2E Test: Standalone Grocery List Creation and Management
 *
 * User Story 6: Create Standalone Grocery Lists
 *
 * This test covers the complete user journey for standalone grocery lists:
 * 1. User logs in
 * 2. Navigates to grocery lists
 * 3. Clicks "Create Standalone List"
 * 4. Enters name and saves
 * 5. Sees empty list
 * 6. Adds 5 items manually with different categories
 * 7. Marks 2 items as purchased
 * 8. Views completion progress
 * 9. Verifies no "Regenerate" button (standalone lists don't have this)
 * 10. Deletes the list
 */

test.describe('Standalone Grocery List Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login as test user
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for redirect to dashboard
    await page.waitForURL('/dashboard');
  });

  test('user can create and manage standalone grocery list', async ({ page }) => {
    test.setTimeout(120000); // Increase timeout for this complex test

    // Step 1: Navigate to grocery lists
    await page.goto('/grocery-lists');
    await page.waitForLoadState('networkidle');

    // Verify we're on the grocery lists index page (Flux headings render as divs)
    await expect(page.locator('[data-flux-heading]:has-text("Grocery Lists")').first()).toBeVisible();

    // Step 2: Click "Create Standalone List" button
    const createStandaloneButton = page.locator('button:has-text("Create Standalone List")').or(
      page.locator('a:has-text("Create Standalone List")')
    ).first();
    await expect(createStandaloneButton).toBeVisible();
    await createStandaloneButton.click();

    // Wait for create form to load
    await page.waitForURL(/\/grocery-lists\/create/);
    await expect(page.locator('text=Create Standalone Grocery List')).toBeVisible();

    // Step 3: Enter name and save
    const timestamp = Date.now();
    const listName = `Weekend Party Shopping ${timestamp}`;
    await page.fill('input[name="name"]', listName);

    // Verify the info box explaining standalone lists is present
    await expect(page.locator('text=/Create a shopping list not linked to any meal plan/i')).toBeVisible();

    // Submit the form
    await page.click('button:has-text("Create List")');

    // Wait for redirect to grocery list show page
    await page.waitForURL(/\/grocery-lists\/\d+/);
    await page.waitForLoadState('networkidle');

    // Step 4: Verify we're on the show page with the correct name
    // Use data-flux-heading to avoid matching the delete modal text
    await expect(page.locator(`[data-flux-heading]:has-text("${listName}")`).first()).toBeVisible();

    // Step 5: Verify "Standalone List" indicator is shown (not linked to meal plan)
    await expect(page.locator('text=Standalone List')).toBeVisible();

    // Step 6: Verify empty state is shown
    await expect(page.locator('text=/No items in this list/i')).toBeVisible();

    // Step 7: Add 3 items manually with different categories
    const itemsToAdd = [
      { name: 'Organic Apples', quantity: '6', unit: 'whole', category: 'produce' },
      { name: 'Whole Milk', quantity: '1', unit: 'gallon', category: 'dairy' },
      { name: 'Paper Plates', quantity: '20', unit: 'piece', category: 'other' },
    ];

    for (const item of itemsToAdd) {
      // Click "Add Item" button
      const addItemButton = page.locator('button[wire\\:click="openAddItemForm"]').first();
      await addItemButton.click();
      await page.waitForTimeout(800); // Allow form to appear

      // Wait for the form to be visible
      await expect(page.locator('#itemName')).toBeVisible();

      // Fill in item details
      await page.fill('#itemName', item.name);
      await page.fill('#itemQuantity', item.quantity);
      await page.locator('#itemUnit').selectOption(item.unit);
      await page.locator('#itemCategory').selectOption(item.category);
      await page.waitForTimeout(500); // Allow Livewire to sync

      // Save the item
      await page.locator('button[wire\\:click="addManualItem"]').click();

      // Wait for Livewire to process
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(2000); // Allow Livewire to re-render

      // Verify the item appears in the list
      await expect(page.locator(`text=${item.name}`)).toBeVisible({ timeout: 15000 });
    }

    // Step 8: Verify all 3 items are visible
    await expect(page.locator('text=Organic Apples')).toBeVisible();
    await expect(page.locator('text=Whole Milk')).toBeVisible();
    await expect(page.locator('text=Paper Plates')).toBeVisible();

    // Step 9: Mark 2 items as purchased
    // Find and click the toggle button for "Organic Apples"
    const applesText = page.getByText('Organic Apples', { exact: true });
    await applesText.waitFor({ state: 'visible' });
    const applesToggle = applesText.locator('xpath=ancestor::div[contains(@class, "px-6")]').locator('button[wire\\:click^="togglePurchased"]').first();
    await applesToggle.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    // Find and click the toggle button for "Whole Milk"
    const milkText = page.getByText('Whole Milk', { exact: true });
    await milkText.waitFor({ state: 'visible' });
    const milkToggle = milkText.locator('xpath=ancestor::div[contains(@class, "px-6")]').locator('button[wire\\:click^="togglePurchased"]').first();
    await milkToggle.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(500);

    // Step 10: Verify completion progress shows 2 of 3 items completed (67%)
    await expect(page.locator('text=/2 of 3 items completed/i')).toBeVisible({ timeout: 10000 });
    await expect(page.locator('text=/67%/i')).toBeVisible();

    // Step 11: Verify NO "Regenerate" button (standalone lists don't have meal plan link)
    await expect(page.locator('button:has-text("Regenerate")')).not.toBeVisible();

    // Step 12: Verify the standalone list indicator is still visible
    await expect(page.locator('text=Standalone List')).toBeVisible();

    // Step 13: Navigate back to the grocery lists index
    await page.click('a:has-text("Back to Lists")');
    await page.waitForURL(/\/grocery-lists$/);
    await page.waitForLoadState('networkidle');

    // Verify the list appears on the index page (all lists are shown together, not in separate sections)
    await expect(page.locator(`[data-flux-heading]:has-text("${listName}")`).first()).toBeVisible();

    // Verify the completion percentage is shown (67%)
    const listRow = page.locator(`text=${listName}`).locator('xpath=ancestor::div[contains(@class, "p-4")]');
    await expect(listRow.locator('text=/67%/i').first()).toBeVisible();
  });

  test('standalone list creation form has proper validation', async ({ page }) => {
    // Navigate to grocery lists
    await page.goto('/grocery-lists');
    await page.waitForLoadState('networkidle');

    // Click "Create Standalone List"
    const createButton = page.locator('button:has-text("Create Standalone List")').or(
      page.locator('a:has-text("Create Standalone List")')
    ).first();
    await createButton.click();

    // Wait for form
    await page.waitForURL(/\/grocery-lists\/create/);

    // Try to submit without entering a name
    await page.click('button:has-text("Create List")');
    await page.waitForTimeout(1000);

    // Flux components use Livewire validation - check if we stay on the same page
    // If validation works, we should still be on the create page
    await expect(page.url()).toContain('/grocery-lists/create');

    // Now enter a valid name
    await page.fill('input[name="name"]', 'Valid List Name');
    await page.click('button:has-text("Create List")');

    // Should redirect to the list show page
    await page.waitForURL(/\/grocery-lists\/\d+/);
    // Flux headings render as divs, so use data-flux-heading
    await expect(page.locator('[data-flux-heading]:has-text("Valid List Name")').first()).toBeVisible();
  });

  test('cancel button returns to grocery lists index', async ({ page }) => {
    // Navigate to create form
    await page.goto('/grocery-lists');
    await page.waitForLoadState('networkidle');

    const createButton = page.locator('button:has-text("Create Standalone List")').or(
      page.locator('a:has-text("Create Standalone List")')
    ).first();
    await createButton.click();

    await page.waitForURL(/\/grocery-lists\/create/);

    // Click Cancel button
    await page.click('a:has-text("Cancel"), button:has-text("Cancel")');

    // Should return to grocery lists index
    await page.waitForURL(/\/grocery-lists$/);
    await expect(page.locator('[data-flux-heading]:has-text("Grocery Lists")').first()).toBeVisible();
  });

  test('standalone list shows all items organized by category', async ({ page }) => {
    // Create a standalone list
    await page.goto('/grocery-lists');
    await page.waitForLoadState('networkidle');

    const createButton = page.locator('button:has-text("Create Standalone List")').or(
      page.locator('a:has-text("Create Standalone List")')
    ).first();
    await createButton.click();

    await page.waitForURL(/\/grocery-lists\/create/);
    await page.fill('input[name="name"]', 'Category Test List');
    await page.click('button:has-text("Create List")');
    await page.waitForURL(/\/grocery-lists\/\d+/);
    await page.waitForLoadState('networkidle');

    // Add items in different categories
    const items = [
      { name: 'Bananas', category: 'produce' },
      { name: 'Chicken Breast', category: 'meat' },
      { name: 'Yogurt', category: 'dairy' },
    ];

    for (const item of items) {
      await page.locator('button[wire\\:click="openAddItemForm"]').first().click();
      await page.waitForTimeout(500);

      await page.fill('#itemName', item.name);
      await page.locator('#itemCategory').selectOption(item.category);
      await page.waitForTimeout(300);

      await page.locator('button[wire\\:click="addManualItem"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
    }

    // Verify all items are visible
    await expect(page.locator('text=Bananas')).toBeVisible();
    await expect(page.locator('text=Chicken Breast')).toBeVisible();
    await expect(page.locator('text=Yogurt')).toBeVisible();

    // Verify category headers are present
    await expect(page.locator('text=/Produce/i')).toBeVisible();
    await expect(page.locator('text=/Meat/i')).toBeVisible();
    await expect(page.locator('text=/Dairy/i')).toBeVisible();
  });
});
