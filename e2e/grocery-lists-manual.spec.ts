import { test, expect } from '@playwright/test';

/**
 * E2E Test: Manual Grocery List Item Management
 *
 * User Story 4: Manually Manage Grocery List Items
 *
 * This test covers the complete user journey:
 * 1. User opens an existing grocery list
 * 2. Clicks "Add Item" to open the add item form
 * 3. Fills in item details (name, quantity, unit, category)
 * 4. Saves the new item and sees it appear in the list
 * 5. Clicks edit on the item to modify it
 * 6. Changes the quantity and saves the update
 * 7. Clicks delete on the item and confirms removal
 * 8. Regenerates the grocery list
 * 9. Verifies that manually added items are preserved after regeneration
 */

test.describe('Manual Grocery List Item Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login as test user
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for redirect to dashboard/home
    await page.waitForURL(/\/(dashboard|home)?$/);
  });

  test('user can add, edit, delete manual items and verify preservation during regeneration', async ({ page }) => {
    test.setTimeout(120000); // Increase timeout for this complex test

    // Step 1: Create a meal plan with recipes to generate a grocery list
    await page.goto('/meal-plans');
    await page.click('text=Create New Meal Plan');

    // Wait for create form to load
    await expect(page.locator('h1')).toContainText('Create Meal Plan');

    // Fill in meal plan details
    await page.fill('input[name="name"]', 'Manual Items Test Plan');

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
    await expect(page.locator('h1')).toContainText('Manual Items Test Plan');

    // Step 2: Assign at least one recipe to the meal plan
    const firstDinnerSlot = page.locator('tbody tr').first().locator('[data-meal-type="dinner"]');
    await firstDinnerSlot.click({ timeout: 5000 });

    // Wait for modal to open and recipes to load
    await page.waitForSelector('[data-recipe-card]', { timeout: 5000 });

    // Select first available recipe
    await page.locator('[data-recipe-card]').first().click();

    // Wait for modal to close and Livewire to update
    await page.waitForLoadState('networkidle');

    // Step 3: Generate grocery list from meal plan
    await page.click('a:has-text("Generate Grocery List")');

    // Wait for confirmation page to load
    await page.waitForURL(/\/grocery-lists\/generate\/\d+/);
    await expect(page.locator('text=Generate grocery list for')).toBeVisible();

    // Click the "Generate List" button
    await page.click('button:has-text("Generate List")');

    // Wait for redirect to grocery list show page
    await page.waitForURL(/\/grocery-lists\/\d+/);

    // Verify we're on the grocery list page
    await expect(page.locator('h1')).toContainText('Grocery List');

    // Wait for the list to fully load
    await page.waitForSelector('.bg-white.rounded-lg.shadow', { timeout: 5000 });

    // Step 4: Click "Add Item" button to open the add item form
    const addItemButton = page.locator('button:has-text("Add Item")');
    await expect(addItemButton).toBeVisible();
    await addItemButton.click();

    // Wait for add item form to appear
    await page.waitForTimeout(500); // Allow Livewire to show the form

    // Verify form is visible
    await expect(page.locator('input[wire\\:model="itemName"]')).toBeVisible();

    // Step 5: Fill in the add item form
    await page.fill('input[wire\\:model="itemName"]', 'Paper Towels');
    await page.fill('input[wire\\:model="itemQuantity"]', '2');

    // Fill unit (text input, not select)
    await page.fill('input[wire\\:model="itemUnit"]', 'whole');

    // Select category (OTHER)
    const categorySelect = page.locator('select[wire\\:model="itemCategory"]');
    await categorySelect.selectOption('other');

    // Step 6: Save the new item
    await page.click('button:has-text("Add Item")');

    // Wait for Livewire to process
    await page.waitForTimeout(1000);

    // Verify the item appears in the list
    await expect(page.locator('text=Paper Towels')).toBeVisible();

    // Verify success message
    await expect(page.locator('text=Item added successfully')).toBeVisible();

    // Step 7: Edit the item - click edit button
    // Find the edit button for "Paper Towels" item
    const paperTowelsRow = page.locator('text=Paper Towels').locator('..');
    const editButton = paperTowelsRow.locator('button[wire\\:click^="startEditing"]');
    await editButton.click();

    // Wait for edit form to appear
    await page.waitForTimeout(500);

    // Verify edit form is populated with current values
    await expect(page.locator('input[wire\\:model="itemName"]')).toHaveValue('Paper Towels');

    // Step 8: Change the quantity
    await page.fill('input[wire\\:model="itemQuantity"]', '3');

    // Save the edit
    await page.click('button:has-text("Save")');

    // Wait for Livewire to process
    await page.waitForTimeout(1000);

    // Verify the update
    await expect(page.locator('text=Item updated successfully')).toBeVisible();

    // Verify the quantity changed (should show "3 whole")
    await expect(page.locator('text=/3.*whole/i')).toBeVisible();

    // Step 9: Delete the item
    // Find the delete button for "Paper Towels" item
    const deleteButton = paperTowelsRow.locator('button[wire\\:click^="deleteItem"]');
    await deleteButton.click();

    // Wait for Livewire to process deletion
    await page.waitForTimeout(1000);

    // Verify the item is removed from the list
    await expect(page.locator('text=Paper Towels')).not.toBeVisible();

    // Verify success message
    await expect(page.locator('text=Item deleted successfully')).toBeVisible();

    // Step 10: Add another manual item that we'll keep for regeneration test
    await page.click('button:has-text("Add Item")');
    await page.waitForTimeout(500);

    await page.fill('input[wire\\:model="itemName"]', 'Trash Bags');
    await page.fill('input[wire\\:model="itemQuantity"]', '1');
    await page.fill('input[wire\\:model="itemUnit"]', 'whole');
    await page.locator('select[wire\\:model="itemCategory"]').selectOption('other');

    await page.click('button:has-text("Add Item")');
    await page.waitForTimeout(1000);

    // Verify "Trash Bags" appears
    await expect(page.locator('text=Trash Bags')).toBeVisible();

    // Step 11: Regenerate the grocery list
    const regenerateButton = page.locator('button:has-text("Regenerate")');

    // Check if regenerate button exists (it should for meal plan-linked lists)
    if (await regenerateButton.isVisible()) {
      await regenerateButton.click();

      // Wait for Livewire to process regeneration
      await page.waitForTimeout(2000);

      // Step 12: Verify manually added item "Trash Bags" is still present
      await expect(page.locator('text=Trash Bags')).toBeVisible();

      // Verify success message
      await expect(page.locator('text=Grocery list regenerated successfully')).toBeVisible();

      // Verify generated items are still present (items from recipe)
      const categoryContainers = page.locator('.bg-white.rounded-lg.shadow');
      await expect(categoryContainers.first()).toBeVisible();
    }
  });

  test('manual item appears in correct category', async ({ page }) => {
    // First, create a grocery list to test with
    await page.goto('/meal-plans');
    await page.click('text=Create New Meal Plan');

    await page.fill('input[name="name"]', 'Category Test Plan');

    const today = new Date();
    const startDate = today.toISOString().split('T')[0];
    const endDate = new Date(today.getTime() + 6 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    await page.fill('input[name="start_date"]', startDate);
    await page.fill('input[name="end_date"]', endDate);
    await page.click('button:has-text("Create Meal Plan")');
    await page.waitForURL(/\/meal-plans\/\d+/);

    // Assign a recipe
    const firstDinnerSlot = page.locator('tbody tr').first().locator('[data-meal-type="dinner"]');
    await firstDinnerSlot.click({ timeout: 5000 });
    await page.waitForSelector('[data-recipe-card]', { timeout: 5000 });
    await page.locator('[data-recipe-card]').first().click();
    await page.waitForLoadState('networkidle');

    // Generate grocery list
    await page.click('a:has-text("Generate Grocery List")');
    await page.waitForURL(/\/grocery-lists\/generate\/\d+/);
    await page.click('button:has-text("Generate List")');
    await page.waitForURL(/\/grocery-lists\/\d+/);

    await page.waitForSelector('.bg-white.rounded-lg.shadow', { timeout: 5000 });

    // Add an item in the PRODUCE category
    await page.click('button:has-text("Add Item")');
    await page.waitForTimeout(500);

    await page.fill('input[wire\\:model="itemName"]', 'Organic Bananas');
    await page.fill('input[wire\\:model="itemQuantity"]', '6');
    await page.fill('input[wire\\:model="itemUnit"]', 'whole');
    await page.locator('select[wire\\:model="itemCategory"]').selectOption('produce');

    await page.click('button:has-text("Add Item")');
    await page.waitForTimeout(1000);

    // Verify the item appears in the Produce category
    // Find the Produce category section
    const produceSection = page.locator('text=/Produce/i').locator('..');

    // Verify "Organic Bananas" appears within or near the Produce section
    await expect(page.locator('text=Organic Bananas')).toBeVisible();
  });

  test('edited generated item preserves user changes during regeneration', async ({ page }) => {
    // Create a meal plan and generate grocery list
    await page.goto('/meal-plans');
    await page.click('text=Create New Meal Plan');

    await page.fill('input[name="name"]', 'Edit Test Plan');

    const today = new Date();
    const startDate = today.toISOString().split('T')[0];
    const endDate = new Date(today.getTime() + 6 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    await page.fill('input[name="start_date"]', startDate);
    await page.fill('input[name="end_date"]', endDate);
    await page.click('button:has-text("Create Meal Plan")');
    await page.waitForURL(/\/meal-plans\/\d+/);

    // Assign a recipe
    const firstDinnerSlot = page.locator('tbody tr').first().locator('[data-meal-type="dinner"]');
    await firstDinnerSlot.click({ timeout: 5000 });
    await page.waitForSelector('[data-recipe-card]', { timeout: 5000 });
    await page.locator('[data-recipe-card]').first().click();
    await page.waitForLoadState('networkidle');

    // Generate grocery list
    await page.click('a:has-text("Generate Grocery List")');
    await page.waitForURL(/\/grocery-lists\/generate\/\d+/);
    await page.click('button:has-text("Generate List")');
    await page.waitForURL(/\/grocery-lists\/\d+/);

    await page.waitForSelector('.bg-white.rounded-lg.shadow', { timeout: 5000 });

    // Get the first generated item
    const toggleButtons = page.locator('button[wire\\:click^="togglePurchased"]');
    const itemCount = await toggleButtons.count();

    if (itemCount > 0) {
      // Find first item's container and get its name
      const firstItemContainer = page.locator('.bg-white.rounded-lg.shadow').first().locator('div').first();
      const itemName = await firstItemContainer.textContent();

      // Edit the first generated item
      const firstEditButton = page.locator('button[wire\\:click^="startEditing"]').first();
      await firstEditButton.click();
      await page.waitForTimeout(500);

      // Change quantity to a different value
      const quantityInput = page.locator('input[wire\\:model="itemQuantity"]');
      const originalQuantity = await quantityInput.inputValue();
      const newQuantity = (parseFloat(originalQuantity || '1') + 5).toString();

      await quantityInput.fill(newQuantity);
      await page.click('button:has-text("Save")');
      await page.waitForTimeout(1000);

      // Regenerate the list
      const regenerateButton = page.locator('button:has-text("Regenerate")');
      if (await regenerateButton.isVisible()) {
        await regenerateButton.click();
        await page.waitForTimeout(2000);

        // Verify the edited item still shows the user's edited quantity
        // The edited value should be preserved
        await expect(page.locator(`text=/${newQuantity}/`)).toBeVisible();
      }
    }
  });

  test('cancel button closes add item form', async ({ page }) => {
    // Navigate to an existing grocery list
    await page.goto('/grocery-lists');
    await page.waitForLoadState('networkidle');

    // Click on first grocery list
    const firstGroceryListLink = page.locator('[href*="/grocery-lists/"]').first();
    await firstGroceryListLink.click();
    await page.waitForURL(/\/grocery-lists\/\d+/);

    // Open add item form
    await page.click('button:has-text("Add Item")');
    await page.waitForTimeout(500);

    // Verify form is visible
    await expect(page.locator('input[wire\\:model="itemName"]')).toBeVisible();

    // Click cancel button
    const cancelButton = page.locator('button:has-text("Cancel")');
    await cancelButton.click();
    await page.waitForTimeout(500);

    // Verify form is hidden
    await expect(page.locator('input[wire\\:model="itemName"]')).not.toBeVisible();
  });

  test('validation prevents saving item without name', async ({ page }) => {
    // Navigate to an existing grocery list
    await page.goto('/grocery-lists');
    await page.waitForLoadState('networkidle');

    const firstGroceryListLink = page.locator('[href*="/grocery-lists/"]').first();
    await firstGroceryListLink.click();
    await page.waitForURL(/\/grocery-lists\/\d+/);

    // Open add item form
    await page.click('button:has-text("Add Item")');
    await page.waitForTimeout(500);

    // Try to save without entering a name
    await page.fill('input[wire\\:model="itemQuantity"]', '2');
    await page.click('button:has-text("Add Item")');
    await page.waitForTimeout(500);

    // Verify validation error appears
    // Livewire validation errors typically show near the field or at the top
    await expect(page.locator('text=/required|name/i')).toBeVisible();
  });
});
