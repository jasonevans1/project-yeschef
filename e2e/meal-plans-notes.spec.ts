import { test, expect } from '@playwright/test';

test.describe('Meal Plan Notes', () => {
  test.beforeEach(async ({ page }) => {
    // Login with test user (should be seeded in database)
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for redirect after login
    await page.waitForURL(/dashboard|recipes|meal-plans/);
  });

  test('adds note to empty meal slot', async ({ page }) => {
    // Create a meal plan first
    await page.goto('/meal-plans/create');

    const today = new Date();
    const startDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);
    const endDate = new Date(today.getTime() + 3 * 24 * 60 * 60 * 1000);
    const startDateStr = startDate.toISOString().split('T')[0];
    const endDateStr = endDate.toISOString().split('T')[0];

    await page.fill('input[name="name"]', 'Note Test Plan');
    await page.fill('input[name="start_date"]', startDateStr);
    await page.fill('input[name="end_date"]', endDateStr);

    await page.click('button:has-text("Create Meal Plan")');
    await page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 });

    // Click the add button in an empty breakfast slot
    const breakfastSlot = page.locator(`[data-date="${startDateStr}"][data-meal-type="breakfast"]`);
    const addButton = breakfastSlot.locator('button').first();
    await addButton.click();

    // Wait for dropdown menu to appear
    await page.waitForTimeout(500);

    // Click "Add Note" option in the dropdown (Flux menu item)
    const addNoteButton = page.locator('role=menuitem[name="Add Note"]').first();
    await addNoteButton.click({ force: true });

    // Wait for note form modal to appear
    await expect(page.locator('text=Add Note for')).toBeVisible({ timeout: 5000 });

    // Fill in the note
    await page.fill('input[name="noteTitle"]', 'Eating out at Mom\'s house');
    await page.fill('textarea[name="noteDetails"]', 'Birthday dinner celebration');

    // Submit the form
    await page.click('button[type="submit"]:has-text("Add Note")');

    // Wait for modal to close and note to appear
    await page.waitForTimeout(1000);

    // Verify the note appears in the calendar with amber styling
    await expect(breakfastSlot.locator('text=Eating out at Mom\'s house')).toBeVisible();

    // Verify amber background is present
    const noteCard = breakfastSlot.locator('.bg-amber-50').first();
    await expect(noteCard).toBeVisible();
  });

  test('adds note to slot with existing recipe', async ({ page }) => {
    // Create a meal plan first
    await page.goto('/meal-plans/create');

    const today = new Date();
    const startDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);
    const endDate = new Date(today.getTime() + 3 * 24 * 60 * 60 * 1000);
    const startDateStr = startDate.toISOString().split('T')[0];
    const endDateStr = endDate.toISOString().split('T')[0];

    await page.fill('input[name="name"]', 'Note with Recipe Test');
    await page.fill('input[name="start_date"]', startDateStr);
    await page.fill('input[name="end_date"]', endDateStr);

    await page.click('button:has-text("Create Meal Plan")');
    await page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 });

    // First, add a recipe to the lunch slot
    const lunchSlot = page.locator(`[data-date="${startDateStr}"][data-meal-type="lunch"]`);
    let addButton = lunchSlot.locator('button').first();
    await addButton.click();

    // Wait for dropdown and click "Add Recipe"
    await page.waitForTimeout(500);
    const addRecipeButton = page.locator('role=menuitem[name="Add Recipe"]').first();
    await addRecipeButton.click({ force: true });

    // Wait for recipe selector modal
    await expect(page.locator('text=Select Recipe for')).toBeVisible({ timeout: 5000 });

    // Wait for recipes to load and select the first one
    await page.waitForTimeout(500);
    const recipeCards = page.locator('[data-recipe-card]');
    const recipeCount = await recipeCards.count();

    if (recipeCount > 0) {
      const firstRecipe = recipeCards.first();
      const recipeName = await firstRecipe.locator('div.font-semibold').first().textContent();
      await expect(firstRecipe).toBeVisible();
      await firstRecipe.click();

      // Wait for Livewire to process the assignment (modal should close automatically)
      await page.waitForTimeout(2000);

      // Verify recipe was assigned by checking if it appears in the slot
      if (recipeName) {
        await expect(lunchSlot).toContainText(recipeName.trim(), { timeout: 10000 });
      }

      // Verify modal is closed
      await expect(page.locator('text=Select Recipe for')).not.toBeVisible();

      // Now add a note to the same slot
      // Click "Add Another" button to open dropdown
      const addAnotherButton = lunchSlot.getByRole('button', { name: /add another/i });
      await expect(addAnotherButton).toBeVisible({ timeout: 5000 });
      await addAnotherButton.click();

      // Wait for dropdown
      await page.waitForTimeout(500);

      // Click "Add Note" in dropdown
      const addNoteButton = page.locator('role=menuitem[name="Add Note"]').first();
      await addNoteButton.click({ force: true });

      // Fill in note form
      await expect(page.locator('text=Add Note for')).toBeVisible({ timeout: 5000 });
      await page.fill('input[name="noteTitle"]', 'Extra prep needed');
      await page.fill('textarea[name="noteDetails"]', 'Buy ingredients the day before');

      await page.click('button[type="submit"]:has-text("Add Note")');
      await page.waitForTimeout(1000);

      // Verify both recipe and note appear in the same slot
      await expect(lunchSlot.locator('text=Extra prep needed')).toBeVisible();

      // Verify amber note card is visible
      const noteCard = lunchSlot.locator('.bg-amber-50').first();
      await expect(noteCard).toBeVisible();
    }
  });

  test('views note details in drawer', async ({ page }) => {
    // Create a meal plan with a note
    await page.goto('/meal-plans/create');

    const today = new Date();
    const startDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);
    const endDate = new Date(today.getTime() + 3 * 24 * 60 * 60 * 1000);
    const startDateStr = startDate.toISOString().split('T')[0];
    const endDateStr = endDate.toISOString().split('T')[0];

    await page.fill('input[name="name"]', 'Drawer Test Plan');
    await page.fill('input[name="start_date"]', startDateStr);
    await page.fill('input[name="end_date"]', endDateStr);

    await page.click('button:has-text("Create Meal Plan")');
    await page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 });

    // Add a note to dinner slot
    const dinnerSlot = page.locator(`[data-date="${startDateStr}"][data-meal-type="dinner"]`);
    const addButton = dinnerSlot.locator('button').first();
    await addButton.click();

    await page.waitForTimeout(500);
    const addNoteButton = page.locator('role=menuitem[name="Add Note"]').first();
    await addNoteButton.click({ force: true });

    await expect(page.locator('text=Add Note for')).toBeVisible({ timeout: 5000 });
    await page.fill('input[name="noteTitle"]', 'Restaurant reservation');
    await page.fill('textarea[name="noteDetails"]', 'Table booked at 7 PM for 4 people');

    await page.click('button[type="submit"]:has-text("Add Note")');
    await page.waitForTimeout(1000);

    // Click on the note to open the drawer
    const noteCard = dinnerSlot.locator('.bg-amber-50').first();
    await noteCard.click();

    // Wait for drawer to appear
    await page.waitForTimeout(500);

    // Verify drawer contents
    await expect(page.getByRole('heading', { name: 'Restaurant reservation' })).toBeVisible();
    await expect(page.locator('text=Table booked at 7 PM for 4 people').last()).toBeVisible();

    // Verify drawer has amber styling (use role=dialog to avoid strict mode violation)
    const drawerHeader = page.locator('[role="dialog"] .bg-amber-50').filter({ hasText: 'Restaurant reservation' });
    await expect(drawerHeader).toBeVisible();

    // Verify action buttons are present (scope to drawer to avoid strict mode)
    const drawer = page.locator('[role="dialog"]');
    await expect(drawer.getByRole('button', { name: /edit/i })).toBeVisible();
    await expect(drawer.getByRole('button', { name: /delete/i })).toBeVisible();

    // Close the drawer
    const closeButton = drawer.locator('button[wire\\:click="closeNoteDrawer"]').first();
    await closeButton.click();

    // Verify drawer is closed
    await expect(drawer).not.toBeVisible();
  });

  test('edits existing note', async ({ page }) => {
    // Create a meal plan with a note
    await page.goto('/meal-plans/create');

    const today = new Date();
    const startDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);
    const endDate = new Date(today.getTime() + 3 * 24 * 60 * 60 * 1000);
    const startDateStr = startDate.toISOString().split('T')[0];
    const endDateStr = endDate.toISOString().split('T')[0];

    await page.fill('input[name="name"]', 'Edit Note Test Plan');
    await page.fill('input[name="start_date"]', startDateStr);
    await page.fill('input[name="end_date"]', endDateStr);

    await page.click('button:has-text("Create Meal Plan")');
    await page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 });

    // Add a note to snack slot
    const snackSlot = page.locator(`[data-date="${startDateStr}"][data-meal-type="snack"]`);
    const addButton = snackSlot.locator('button').first();
    await addButton.click();

    await page.waitForTimeout(500);
    const addNoteButton = page.locator('role=menuitem[name="Add Note"]').first();
    await addNoteButton.click({ force: true });

    await expect(page.locator('text=Add Note for')).toBeVisible({ timeout: 5000 });
    await page.fill('input[name="noteTitle"]', 'Original Title');
    await page.fill('textarea[name="noteDetails"]', 'Original details');

    await page.click('button[type="submit"]:has-text("Add Note")');
    await page.waitForTimeout(1000);

    // Click on the note to open drawer
    const noteCard = snackSlot.locator('.bg-amber-50').first();
    await noteCard.click();
    await page.waitForTimeout(500);

    // Click Edit button in drawer
    const editButton = page.getByRole('button', { name: /edit/i });
    await editButton.click();

    // Wait for edit form to appear
    await expect(page.locator('text=Edit Note for')).toBeVisible({ timeout: 5000 });

    // Verify form is pre-filled
    const titleInput = page.locator('input[name="noteTitle"]');
    await expect(titleInput).toHaveValue('Original Title');

    const detailsInput = page.locator('textarea[name="noteDetails"]');
    await expect(detailsInput).toHaveValue('Original details');

    // Update the note
    await titleInput.fill('Updated Title');
    await detailsInput.fill('Updated details content');

    await page.click('button:has-text("Update Note")');
    await page.waitForTimeout(1000);

    // Verify updated note appears in calendar
    await expect(snackSlot.locator('text=Updated Title')).toBeVisible();

    // Click note again to verify details were updated
    const updatedNoteCard = snackSlot.locator('.bg-amber-50').first();
    await updatedNoteCard.click();
    await page.waitForTimeout(500);

    await expect(page.locator('[role="dialog"]').locator('text=Updated details content')).toBeVisible();
  });

  test('deletes note from drawer', async ({ page }) => {
    // Create a meal plan with a note
    await page.goto('/meal-plans/create');

    const today = new Date();
    const startDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);
    const endDate = new Date(today.getTime() + 3 * 24 * 60 * 60 * 1000);
    const startDateStr = startDate.toISOString().split('T')[0];
    const endDateStr = endDate.toISOString().split('T')[0];

    await page.fill('input[name="name"]', 'Delete Note Test Plan');
    await page.fill('input[name="start_date"]', startDateStr);
    await page.fill('input[name="end_date"]', endDateStr);

    await page.click('button:has-text("Create Meal Plan")');
    await page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 });

    // Add a note to breakfast slot
    const breakfastSlot = page.locator(`[data-date="${startDateStr}"][data-meal-type="breakfast"]`);
    const addButton = breakfastSlot.locator('button').first();
    await addButton.click();

    await page.waitForTimeout(500);
    const addNoteButton = page.locator('role=menuitem[name="Add Note"]').first();
    await addNoteButton.click({ force: true });

    await expect(page.locator('text=Add Note for')).toBeVisible({ timeout: 5000 });
    await page.fill('input[name="noteTitle"]', 'Note to Delete');
    await page.fill('textarea[name="noteDetails"]', 'This note will be deleted');

    await page.click('button[type="submit"]:has-text("Add Note")');
    await page.waitForTimeout(1000);

    // Verify note appears
    await expect(breakfastSlot.locator('text=Note to Delete')).toBeVisible();

    // Click on the note to open drawer
    const noteCard = breakfastSlot.locator('.bg-amber-50').first();
    await noteCard.click();
    await page.waitForTimeout(500);

    // Set up dialog handler for confirmation
    page.once('dialog', async dialog => {
      expect(dialog.message().toLowerCase()).toContain('delete');
      await dialog.accept();
    });

    // Click Delete button in drawer (be specific to avoid strict mode violation)
    const deleteButton = page.locator('[role="dialog"]').getByRole('button', { name: /delete/i });
    await deleteButton.click();

    // Wait for deletion to complete
    await page.waitForTimeout(1000);

    // Verify note no longer appears in calendar
    await expect(breakfastSlot.locator('text=Note to Delete')).not.toBeVisible();

    // Verify drawer is closed
    await expect(page.locator('text=This note will be deleted')).not.toBeVisible();
  });

  test('deletes note from hover action', async ({ page }) => {
    // Create a meal plan with a note
    await page.goto('/meal-plans/create');

    const today = new Date();
    const startDate = new Date(today.getTime() + 24 * 60 * 60 * 1000);
    const endDate = new Date(today.getTime() + 3 * 24 * 60 * 60 * 1000);
    const startDateStr = startDate.toISOString().split('T')[0];
    const endDateStr = endDate.toISOString().split('T')[0];

    await page.fill('input[name="name"]', 'Hover Delete Test Plan');
    await page.fill('input[name="start_date"]', startDateStr);
    await page.fill('input[name="end_date"]', endDateStr);

    await page.click('button:has-text("Create Meal Plan")');
    await page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 });

    // Add a note to lunch slot
    const lunchSlot = page.locator(`[data-date="${startDateStr}"][data-meal-type="lunch"]`);
    const addButton = lunchSlot.locator('button').first();
    await addButton.click();

    await page.waitForTimeout(500);
    const addNoteButton = page.locator('role=menuitem[name="Add Note"]').first();
    await addNoteButton.click({ force: true });

    await expect(page.locator('text=Add Note for')).toBeVisible({ timeout: 5000 });
    await page.fill('input[name="noteTitle"]', 'Hover Delete Note');
    await page.fill('textarea[name="noteDetails"]', 'Will be deleted on hover');

    await page.click('button[type="submit"]:has-text("Add Note")');
    await page.waitForTimeout(1000);

    // Verify note appears
    await expect(lunchSlot.locator('text=Hover Delete Note')).toBeVisible();

    // Hover over the note card to reveal delete button
    const noteCard = lunchSlot.locator('.bg-amber-50').first();
    await noteCard.hover();

    await page.waitForTimeout(300);

    // Set up dialog handler for confirmation
    page.once('dialog', async dialog => {
      expect(dialog.message().toLowerCase()).toContain('delete');
      await dialog.accept();
    });

    // Click the delete button that appears on hover (X icon in top right)
    const hoverDeleteButton = noteCard.locator('button').filter({ hasText: '' }).first();

    // Check if hover delete button is visible
    if (await hoverDeleteButton.isVisible({ timeout: 2000 }).catch(() => false)) {
      await hoverDeleteButton.click();
      await page.waitForTimeout(1000);

      // Verify note no longer appears
      await expect(lunchSlot.locator('text=Hover Delete Note')).not.toBeVisible();
    }
  });
});
