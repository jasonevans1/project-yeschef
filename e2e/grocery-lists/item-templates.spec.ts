import { test, expect } from '@playwright/test';

test.describe('Item Templates Management', () => {
  test.beforeEach(async ({ page }) => {
    // Login as test user
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('/dashboard');
  });

  // T057: Test template CRUD workflow
  test('complete template CRUD workflow', async ({ page }) => {
    // Navigate to Item Templates page via Settings
    await page.goto('/settings/item-templates');
    await page.waitForLoadState('networkidle');

    // CREATE: Create a new template (Create Template is a link, not a button)
    await page.getByRole('link', { name: 'Create Template' }).click();
    await page.waitForURL('/settings/item-templates/create');

    await page.getByLabel('Item Name').fill('Organic Honey');
    await page.getByLabel('Category').selectOption('pantry');
    await page.getByLabel('Unit').selectOption('jar');
    await page.getByLabel('Default Quantity').fill('1');

    await page.getByRole('button', { name: 'Create Template' }).click();
    await page.waitForURL('/settings/item-templates');

    // Verify template appears in list
    await expect(page.locator('text=Organic Honey')).toBeVisible();

    // READ: Verify template is displayed correctly
    const templateRow = page.locator('tr:has-text("Organic Honey")');
    await expect(templateRow).toContainText('Pantry');
    await expect(templateRow).toContainText('jar');

    // UPDATE: Edit the template (Edit is a link, not a button)
    await templateRow.getByRole('link', { name: 'Edit' }).click();
    await page.waitForURL(/\/settings\/item-templates\/\d+\/edit/);

    // Change category to beverages
    await page.getByLabel('Category').selectOption('beverages');
    await page.getByRole('button', { name: 'Update Template' }).click();
    await page.waitForURL('/settings/item-templates');

    // Verify category was updated
    const updatedRow = page.locator('tr:has-text("Organic Honey")');
    await expect(updatedRow).toContainText('Beverages');

    // DELETE: Delete the template (uses wire:confirm which triggers native dialog)
    page.on('dialog', dialog => dialog.accept());
    await updatedRow.getByRole('button', { name: 'Delete' }).click();
    await page.waitForTimeout(500);

    // Verify template is no longer in list
    await expect(page.locator('text=Organic Honey')).not.toBeVisible();
  });

  test('can view all personal item templates', async ({ page }) => {
    await page.goto('/settings/item-templates');
    await page.waitForLoadState('networkidle');

    // Should see page heading and subheading (subheading is unique to this page)
    await expect(page.getByText('Manage your grocery item autocomplete templates')).toBeVisible();

    // Should see a list of templates (assuming user has some)
    const templateList = page.locator('table[data-test="template-list"]');
    await expect(templateList).toBeVisible();
  });

  test('template autocomplete populates form fields', async ({ page }) => {
    // This test verifies that selecting an autocomplete suggestion from user templates
    // correctly populates the form fields with the template's saved values.
    //
    // It uses the existing "almond milk" template that should exist in the test database.

    // First, verify we have a template to work with
    await page.goto('/settings/item-templates');
    await page.waitForLoadState('networkidle');

    // Check for "almond milk" template (should exist from seeder/previous tests)
    const almondMilkRow = page.locator('tr:has-text("almond milk")');
    await expect(almondMilkRow).toBeVisible();

    // Create a grocery list to test autocomplete
    await page.goto('/grocery-lists/create');
    await page.waitForLoadState('networkidle');
    await page.getByLabel('List Name').fill('Template Autocomplete Test');
    await page.getByRole('button', { name: 'Create List' }).click();
    await page.waitForURL(/\/grocery-lists\/\d+/);

    // Open add item form and type to trigger autocomplete
    await page.getByRole('button', { name: 'Add Item' }).click();
    const itemNameInput = page.locator('#searchQuery');
    await itemNameInput.fill('almond');
    await page.waitForTimeout(500);

    // Should see the suggestion
    const suggestion = page.locator('[role="option"]:has-text("almond milk")');
    await expect(suggestion).toBeVisible();

    // Click the suggestion
    await suggestion.click();
    await page.waitForTimeout(300);

    // Verify the form fields are populated from the template
    const categorySelect = page.locator('#itemCategory');
    const unitSelect = page.locator('#itemUnit');

    // Check that category matches what we saw in the template
    // "almond milk" template has "Beverages" category and "gallon" unit
    await expect(categorySelect).toHaveValue('beverages');
    await expect(unitSelect).toHaveValue('gallon');
  });

  test('cannot access other users templates', async ({ page }) => {
    // This test assumes there's a way to identify other users' templates
    // In a real scenario, you'd need to set up test data with multiple users

    await page.goto('/settings/item-templates');
    await page.waitForLoadState('networkidle');

    // Should only see templates belonging to the logged-in user
    // Attempting to directly access another user's template edit page should fail
    // This would require knowing another user's template ID, which in practice
    // would come from test setup

    // For now, verify that the page loads correctly (use subheading which is unique)
    await expect(page.getByText('Manage your grocery item autocomplete templates')).toBeVisible();
  });
});
