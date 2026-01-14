import { test, expect } from '@playwright/test';

/**
 * E2E Test: Delete Grocery List with Confirmation
 *
 * User Story 1: Delete Grocery List with Confirmation (P1)
 * User Story 2: Cancel Deletion to Avoid Mistakes (P2)
 *
 * This test covers the complete user journey for deleting grocery lists:
 * 1. User logs in
 * 2. Creates a test grocery list
 * 3. Navigates to the list
 * 4. Tests cancel deletion flow (US2)
 * 5. Tests complete deletion flow (US1)
 * 6. Verifies list is deleted and returns 404
 */

test.describe('Delete Grocery List', () => {
  test.beforeEach(async ({ page }) => {
    // Login as test user
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for redirect to dashboard
    await page.waitForURL('/dashboard');
  });

  test('user can cancel deletion without losing data (US2)', async ({ page }) => {
    test.setTimeout(90000);

    // Step 1: Create a test grocery list
    await page.goto('/grocery-lists');
    await page.waitForLoadState('networkidle');

    const createButton = page.locator('button:has-text("Create Standalone List")').or(
      page.locator('a:has-text("Create Standalone List")')
    ).first();
    await createButton.click();

    await page.waitForURL(/\/grocery-lists\/create/);
    const timestamp = Date.now();
    const listName = `Delete Test List ${timestamp}`;
    await page.fill('input[name="name"]', listName);
    await page.click('button:has-text("Create List")');

    // Wait for redirect to show page
    await page.waitForURL(/\/grocery-lists\/\d+/);
    await page.waitForLoadState('networkidle');
    const listUrl = page.url();

    // Step 2: Add a test item to the list
    await page.locator('button[wire\\:click="openAddItemForm"]').first().click();
    await page.waitForTimeout(500);
    await page.fill('#searchQuery','Test Item');
    await page.waitForTimeout(300);
    await page.locator('button[wire\\:click="addManualItem"]').click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Verify item is visible
    await expect(page.locator('text=Test Item')).toBeVisible();

    // Step 3: Click delete button to open confirmation modal
    const deleteButton = page.locator('button[wire\\:click="confirmDelete"]').or(
      page.locator('button:has-text("Delete")')
    ).first();
    await expect(deleteButton).toBeVisible();
    await deleteButton.click();
    await page.waitForTimeout(800);

    // Step 4: Verify confirmation modal appears
    await expect(page.locator('text=Delete Grocery List?')).toBeVisible({ timeout: 5000 });
    await expect(page.locator(`text="${listName}"`)).toBeVisible();

    // Step 5: Click Cancel button
    const cancelButton = page.locator('button[wire\\:click="cancelDelete"]').or(
      page.locator('button:has-text("Cancel")')
    ).first();
    await expect(cancelButton).toBeVisible();
    await cancelButton.click();
    await page.waitForTimeout(800);

    // Step 6: Verify modal is closed
    await expect(page.locator('text=Delete Grocery List?')).not.toBeVisible();

    // Step 7: Verify we're still on the show page
    expect(page.url()).toBe(listUrl);

    // Step 8: Verify list still exists (check for heading specifically)
    await expect(page.locator('h1').filter({ hasText: listName })).toBeVisible();
    await expect(page.locator('text=Test Item')).toBeVisible();

    // Step 9: Navigate to grocery lists index to verify list is still there
    await page.click('a:has-text("Back to Lists")');
    await page.waitForURL(/\/grocery-lists$/);
    await page.waitForLoadState('networkidle');
    await expect(page.locator(`text=${listName}`)).toBeVisible();

    // Cleanup: Actually delete the list for cleanup
    await page.goto(listUrl);
    await page.waitForLoadState('networkidle');
    const deleteBtn = page.locator('button[wire\\:click="confirmDelete"]').first();
    await deleteBtn.click();
    await page.waitForTimeout(500);
    const confirmDeleteBtn = page.locator('button[wire\\:click="delete"]').first();
    await confirmDeleteBtn.click();
    await page.waitForURL(/\/grocery-lists$/);
  });

  test('user can delete grocery list with confirmation (US1)', async ({ page }) => {
    test.setTimeout(90000);

    // Step 1: Create a test grocery list
    await page.goto('/grocery-lists');
    await page.waitForLoadState('networkidle');

    const createButton = page.locator('button:has-text("Create Standalone List")').or(
      page.locator('a:has-text("Create Standalone List")')
    ).first();
    await createButton.click();

    await page.waitForURL(/\/grocery-lists\/create/);
    const timestamp = Date.now();
    const listName = `Delete Test Complete ${timestamp}`;
    await page.fill('input[name="name"]', listName);
    await page.click('button:has-text("Create List")');

    // Wait for redirect to show page
    await page.waitForURL(/\/grocery-lists\/\d+/);
    await page.waitForLoadState('networkidle');
    const listUrl = page.url();
    const listId = listUrl.match(/\/grocery-lists\/(\d+)/)?.[1];

    // Step 2: Add multiple test items
    const itemsToAdd = ['Apples', 'Bananas', 'Milk'];
    for (const itemName of itemsToAdd) {
      await page.locator('button[wire\\:click="openAddItemForm"]').first().click();
      await page.waitForTimeout(500);
      await page.fill('#searchQuery',itemName);
      await page.waitForTimeout(300);
      await page.locator('button[wire\\:click="addManualItem"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(1000);
    }

    // Verify all items are visible
    await expect(page.locator('text=Apples')).toBeVisible();
    await expect(page.locator('text=Bananas')).toBeVisible();
    await expect(page.locator('text=Milk')).toBeVisible();

    // Step 3: Click delete button to open confirmation modal
    const deleteButton = page.locator('button[wire\\:click="confirmDelete"]').or(
      page.locator('button:has-text("Delete")')
    ).first();
    await expect(deleteButton).toBeVisible();
    await deleteButton.click();
    await page.waitForTimeout(800);

    // Step 4: Verify confirmation modal appears with correct information
    await expect(page.locator('text=Delete Grocery List?')).toBeVisible({ timeout: 5000 });
    await expect(page.locator(`text="${listName}"`)).toBeVisible();
    await expect(page.locator('text=/3 item\\(s\\)/i')).toBeVisible();
    await expect(page.locator('text=/cannot be undone/i')).toBeVisible();

    // Step 5: Click Delete button to confirm
    const confirmDeleteButton = page.locator('button[wire\\:click="delete"]').or(
      page.locator('button:has-text("Delete List")').or(
        page.locator('button:has-text("Delete Permanently")')
      )
    ).first();
    await expect(confirmDeleteButton).toBeVisible();
    await confirmDeleteButton.click();

    // Step 6: Verify redirect to grocery lists index
    await page.waitForURL(/\/grocery-lists$/);
    await page.waitForLoadState('networkidle');

    // Step 7: Verify success message appears
    await expect(page.locator('text=/deleted successfully/i')).toBeVisible({ timeout: 5000 });

    // Step 8: Verify list no longer appears in index
    await expect(page.locator(`text=${listName}`)).not.toBeVisible();

    // Step 9: Try to access deleted list URL directly - should get 404
    const response = await page.goto(`/grocery-lists/${listId}`);
    await page.waitForLoadState('networkidle');

    // Verify we get a 404 response
    expect(response?.status()).toBe(404);
  });

  test('delete button appears only for list owner', async ({ page }) => {
    test.setTimeout(60000);

    // Create a list as the test user
    await page.goto('/grocery-lists');
    await page.waitForLoadState('networkidle');

    const createButton = page.locator('button:has-text("Create Standalone List")').or(
      page.locator('a:has-text("Create Standalone List")')
    ).first();
    await createButton.click();

    await page.waitForURL(/\/grocery-lists\/create/);
    const listName = `Owner Test ${Date.now()}`;
    await page.fill('input[name="name"]', listName);
    await page.click('button:has-text("Create List")');
    await page.waitForURL(/\/grocery-lists\/\d+/);
    await page.waitForLoadState('networkidle');

    // Verify delete button is visible for owner
    const deleteButton = page.locator('button[wire\\:click="confirmDelete"]').or(
      page.locator('button:has-text("Delete")')
    );
    await expect(deleteButton.first()).toBeVisible();

    // Cleanup
    await deleteButton.first().click();
    await page.waitForTimeout(500);
    const confirmBtn = page.locator('button[wire\\:click="delete"]').first();
    await confirmBtn.click();
    await page.waitForURL(/\/grocery-lists$/);
  });

  test('confirmation modal shows correct item count', async ({ page }) => {
    test.setTimeout(60000);

    // Create a list
    await page.goto('/grocery-lists');
    await page.waitForLoadState('networkidle');

    const createButton = page.locator('button:has-text("Create Standalone List")').or(
      page.locator('a:has-text("Create Standalone List")')
    ).first();
    await createButton.click();

    await page.waitForURL(/\/grocery-lists\/create/);
    await page.fill('input[name="name"]', `Count Test ${Date.now()}`);
    await page.click('button:has-text("Create List")');
    await page.waitForURL(/\/grocery-lists\/\d+/);
    await page.waitForLoadState('networkidle');

    // Add 5 items
    for (let i = 1; i <= 5; i++) {
      await page.locator('button[wire\\:click="openAddItemForm"]').first().click();
      await page.waitForTimeout(500);
      await page.fill('#searchQuery',`Item ${i}`);
      await page.waitForTimeout(300);
      await page.locator('button[wire\\:click="addManualItem"]').click();
      await page.waitForLoadState('networkidle');
      await page.waitForTimeout(800);
    }

    // Open delete confirmation
    const deleteButton = page.locator('button[wire\\:click="confirmDelete"]').first();
    await deleteButton.click();
    await page.waitForTimeout(800);

    // Verify modal shows "5 item(s)"
    await expect(page.locator('text=/5 item\\(s\\)/i')).toBeVisible({ timeout: 5000 });

    // Cleanup
    const confirmBtn = page.locator('button[wire\\:click="delete"]').first();
    await confirmBtn.click();
    await page.waitForURL(/\/grocery-lists$/);
  });
});
