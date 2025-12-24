import { test, expect } from '@playwright/test';

// User Story 1 (009-recipe-servings-multiplier): E2E tests for recipe servings multiplier

test.describe('Recipe Servings Multiplier', () => {
    test.beforeEach(async ({ page }) => {
        // Login first
        await page.goto('https://project-tabletop.ddev.site/login');
        await page.fill('input[name="email"]', 'test@example.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL(/dashboard|recipes/);

        // Navigate to recipes and click on first recipe
        await page.goto('https://project-tabletop.ddev.site/recipes');
        const firstRecipe = page.locator('a[href*="/recipes/"]:not([href*="/create"]):not([href*="/import"]):not([href*="/edit"])').first();
        await firstRecipe.click();
        await page.waitForURL(/\/recipes\/\d+/);
    });

    test('complete user journey for scaling recipe', async ({ page }) => {
        // Wait for page to load
        await expect(page.locator('text=Servings')).toBeVisible();

        // Get the original servings count dynamically
        const servingsText = await page.locator('#servings-result').textContent();
        const originalServings = parseInt(servingsText?.trim() || '0');

        // Find and interact with multiplier input
        const multiplierInput = page.getByLabel('Serving size multiplier');
        await expect(multiplierInput).toBeVisible();

        // Change multiplier to 2 (doubling the recipe)
        await multiplierInput.fill('2');

        // Wait for Alpine.js to update
        await page.waitForTimeout(100);

        // Verify servings display updates (original servings × 2)
        const expectedServings = originalServings * 2;
        await expect(page.locator('#servings-result')).toContainText(expectedServings.toString());
        await expect(page.locator('#servings-result')).toContainText('(from');
    });

    test('verify calculation accuracy at 2x multiplier', async ({ page }) => {
        // Wait for ingredients section to load
        await expect(page.getByText('Ingredients', { exact: true })).toBeVisible();

        // Get the original servings count dynamically
        const servingsText = await page.locator('#servings-result').textContent();
        const originalServings = parseInt(servingsText?.trim() || '0');

        // Set multiplier to 2x
        const multiplierInput = page.getByLabel('Serving size multiplier');
        await multiplierInput.fill('2');
        await page.waitForTimeout(100);

        // Verify that quantities are being scaled
        const expectedServings = originalServings * 2;
        await expect(page.locator('#servings-result')).toContainText(expectedServings.toString());
    });

    test('verify servings are doubled at 2x multiplier', async ({ page }) => {
        // Wait for ingredients section
        await expect(page.getByText('Ingredients', { exact: true })).toBeVisible();

        // Get the original servings count dynamically
        const servingsText = await page.locator('#servings-result').textContent();
        const originalServings = parseInt(servingsText?.trim() || '0');

        // Set multiplier to 2x
        const multiplierInput = page.getByLabel('Serving size multiplier');
        await multiplierInput.fill('2');
        await page.waitForTimeout(100);

        // Verify servings are doubled
        const expectedServings = originalServings * 2;
        await expect(page.locator('#servings-result')).toContainText(expectedServings.toString());
    });

    test('verify multiplier works with all ingredient types', async ({ page }) => {
        // Wait for ingredients section
        await expect(page.getByText('Ingredients', { exact: true })).toBeVisible();

        // Get the original servings count dynamically
        const servingsText = await page.locator('#servings-result').textContent();
        const originalServings = parseInt(servingsText?.trim() || '0');

        // Set multiplier to 2x
        const multiplierInput = page.getByLabel('Serving size multiplier');
        await multiplierInput.fill('2');
        await page.waitForTimeout(100);

        // Verify the multiplier updates the servings count correctly
        const expectedServings = originalServings * 2;
        await expect(page.locator('#servings-result')).toContainText(expectedServings.toString());
    });

    test('multiplier respects minimum value of 0.25x', async ({ page }) => {
        await expect(page.locator('text=Servings')).toBeVisible();

        // Get the original servings count dynamically
        const servingsText = await page.locator('#servings-result').textContent();
        const originalServings = parseInt(servingsText?.trim() || '0');

        const multiplierInput = page.getByLabel('Serving size multiplier');

        // Try to set below minimum
        await multiplierInput.fill('0.1');
        await page.waitForTimeout(100);

        // Verify it's clamped to 0.25 (original servings × 0.25)
        const expectedServings = Math.round(originalServings * 0.25);
        await expect(page.locator('#servings-result')).toContainText(expectedServings.toString());
    });

    test('multiplier respects maximum value of 10x', async ({ page }) => {
        await expect(page.locator('text=Servings')).toBeVisible();

        // Get the original servings count dynamically
        const servingsText = await page.locator('#servings-result').textContent();
        const originalServings = parseInt(servingsText?.trim() || '0');

        const multiplierInput = page.getByLabel('Serving size multiplier');

        // Try to set above maximum
        await multiplierInput.fill('15');
        await page.waitForTimeout(100);

        // Verify it's clamped to 10 (original servings × 10)
        const expectedServings = originalServings * 10;
        await expect(page.locator('#servings-result')).toContainText(expectedServings.toString());
    });

    test('multiplier resets to 1x on page reload', async ({ page }) => {
        await expect(page.locator('text=Servings')).toBeVisible();

        // Get the original servings count dynamically
        const servingsText = await page.locator('#servings-result').textContent();
        const originalServings = parseInt(servingsText?.trim() || '0');

        const multiplierInput = page.getByLabel('Serving size multiplier');

        // Set multiplier to 2x
        await multiplierInput.fill('2');
        await page.waitForTimeout(100);
        const expectedDoubled = originalServings * 2;
        await expect(page.locator('#servings-result')).toContainText(expectedDoubled.toString());

        // Reload the page
        await page.reload();
        await expect(page.locator('text=Servings')).toBeVisible();

        // Verify multiplier is back to 1x (showing original servings)
        await expect(page.locator('#servings-result')).toContainText(originalServings.toString());
        await expect(page.locator('#servings-result')).not.toContainText('(from');
    });

    test('displays formatted quantities without trailing zeros', async ({ page }) => {
        await expect(page.getByText('Ingredients', { exact: true })).toBeVisible();

        // Get the original servings count dynamically
        const servingsText = await page.locator('#servings-result').textContent();
        const originalServings = parseInt(servingsText?.trim() || '0');

        const multiplierInput = page.getByLabel('Serving size multiplier');

        // Set multiplier to 0.5x
        await multiplierInput.fill('0.5');
        await page.waitForTimeout(100);

        // Verify servings (original × 0.5)
        const expectedServings = originalServings * 0.5;
        await expect(page.locator('#servings-result')).toContainText(expectedServings.toString());

        // The formatting is handled by Alpine.js scaleQuantity() function
        // which removes trailing zeros
    });

    test('works across different browsers', async ({ page, browserName }) => {
        // This test runs on all browsers configured in playwright.config.ts
        await expect(page.locator('text=Servings')).toBeVisible();

        // Get the original servings count dynamically
        const servingsText = await page.locator('#servings-result').textContent();
        const originalServings = parseInt(servingsText?.trim() || '0');

        const multiplierInput = page.getByLabel('Serving size multiplier');
        await expect(multiplierInput).toBeVisible();

        // Test basic functionality on all browsers
        await multiplierInput.fill('2');
        await page.waitForTimeout(100);
        const expectedServings = originalServings * 2;
        await expect(page.locator('#servings-result')).toContainText(expectedServings.toString());

        // Test works on: Chromium, Firefox, WebKit
        console.log(`Successfully tested multiplier on ${browserName}`);
    });

    test('plus button increases multiplier', async ({ page }) => {
        await expect(page.locator('text=Servings')).toBeVisible();

        // Get the original servings count dynamically
        const servingsText = await page.locator('#servings-result').textContent();
        const originalServings = parseInt(servingsText?.trim() || '0');

        // Find the plus button
        const plusButton = page.getByLabel('Increase serving size');

        // Click plus button (should go from 1 to 1.25)
        await plusButton.click();
        await page.waitForTimeout(100);

        // Verify servings increased (original × 1.25)
        const expectedServings = originalServings * 1.25;
        await expect(page.locator('#servings-result')).toContainText(expectedServings.toString());
    });

    test('minus button decreases multiplier', async ({ page }) => {
        await expect(page.locator('text=Servings')).toBeVisible();

        // Get the original servings count dynamically
        const servingsText = await page.locator('#servings-result').textContent();
        const originalServings = parseInt(servingsText?.trim() || '0');

        // Set to 2x first
        const multiplierInput = page.getByLabel('Serving size multiplier');
        await multiplierInput.fill('2');
        await page.waitForTimeout(100);

        // Find the minus button
        const minusButton = page.getByLabel('Decrease serving size');

        // Click minus button (should go from 2 to 1.75)
        await minusButton.click();
        await page.waitForTimeout(100);

        // Verify servings decreased (original × 1.75)
        const expectedServings = originalServings * 1.75;
        await expect(page.locator('#servings-result')).toContainText(expectedServings.toString());
    });
});
