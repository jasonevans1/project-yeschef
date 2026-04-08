import { test, expect } from '@playwright/test';

// E2E Tests: Fraction Display for Ingredient Quantities (007-format-ingredient-quantities)

async function login(page: any) {
    await page.goto('/login');
    await page.fill('input[name="email"]', 'test@example.com');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard|recipes/);
}

async function createRecipeWithIngredient(page: any, recipeName: string, quantity: string, unit: string = 'cup') {
    await page.goto('/recipes/create');
    await page.waitForURL(/\/recipes\/create/);

    await page.fill('input[name="name"]', recipeName);
    await page.fill('input[name="servings"]', '4');

    // Fill the first ingredient
    await page.fill('input[id^="ingredient_name_0"]', 'Test Ingredient');
    await page.fill('input[id^="quantity_0"]', quantity);
    await page.selectOption('select[id^="unit_0"]', unit);

    await page.fill('textarea[name="instructions"]', 'Test instructions.');

    await page.click('button:has-text("Create Recipe")');
    await page.waitForURL(/\/recipes\/\d+/, { timeout: 10000 });
    await page.waitForLoadState('networkidle');
}

test.describe('Fraction Quantity Display', () => {
    test.beforeEach(async ({ page }) => {
        await login(page);
    });

    test('recipe show page displays ingredient quantity as fraction (½) not decimal (0.5)', async ({ page }) => {
        const recipeName = `Fraction Test Half ${Date.now()}`;
        await createRecipeWithIngredient(page, recipeName, '0.5');

        // Verify the ingredient shows ½ instead of 0.5
        const ingredientQuantity = page.locator('li').filter({ hasText: 'Test Ingredient' }).locator('span[x-text]');
        await expect(ingredientQuantity).toContainText('½');
        await expect(ingredientQuantity).not.toContainText('0.5');
    });

    test('recipe show page displays mixed number (1½) not decimal (1.5) for ingredient quantity', async ({ page }) => {
        const recipeName = `Fraction Test OneHalf ${Date.now()}`;
        await createRecipeWithIngredient(page, recipeName, '1.5');

        // Verify the ingredient shows 1½ instead of 1.5
        const ingredientQuantity = page.locator('li').filter({ hasText: 'Test Ingredient' }).locator('span[x-text]');
        await expect(ingredientQuantity).toContainText('1½');
        await expect(ingredientQuantity).not.toContainText('1.5');
    });

    test('servings multiplier shows fraction quantities when multiplier is applied', async ({ page }) => {
        const recipeName = `Fraction Multiplier Test ${Date.now()}`;
        // Create recipe with quantity 1 — at 0.5x multiplier it becomes 0.5 = ½
        await createRecipeWithIngredient(page, recipeName, '1');

        await expect(page.locator('#servings-heading')).toBeVisible();

        // Set multiplier to 0.5x
        const multiplierInput = page.getByLabel('Serving size multiplier');
        await multiplierInput.fill('0.5');
        await page.waitForTimeout(500);

        // Verify the ingredient shows ½ instead of 0.5
        const ingredientQuantity = page.locator('li').filter({ hasText: 'Test Ingredient' }).locator('span[x-text]');
        await expect(ingredientQuantity).toContainText('½');
        await expect(ingredientQuantity).not.toContainText('0.5');
    });

    test('servings multiplier shows fraction after scaling (2x of 0.25 shows ½)', async ({ page }) => {
        const recipeName = `Fraction 2x Quarter Test ${Date.now()}`;
        // Create recipe with quantity 0.25 — at 2x multiplier it becomes 0.5 = ½
        await createRecipeWithIngredient(page, recipeName, '0.25');

        await expect(page.locator('#servings-heading')).toBeVisible();

        // Set multiplier to 2x
        const multiplierInput = page.getByLabel('Serving size multiplier');
        await multiplierInput.fill('2');
        await page.waitForTimeout(500);

        // Verify the scaled ingredient shows ½ not 0.5
        const ingredientQuantity = page.locator('li').filter({ hasText: 'Test Ingredient' }).locator('span[x-text]');
        await expect(ingredientQuantity).toContainText('½');
        await expect(ingredientQuantity).not.toContainText('0.5');
    });

    test('recipe create form accepts fraction string input (1/2) and saves correctly', async ({ page }) => {
        await page.goto('/recipes/create');
        await page.waitForURL(/\/recipes\/create/);

        const recipeName = `Fraction Input Test ${Date.now()}`;
        await page.fill('input[name="name"]', recipeName);
        await page.fill('input[name="servings"]', '4');

        // Fill ingredient with fraction string "1/2"
        await page.fill('input[id^="ingredient_name_0"]', 'Butter');
        await page.fill('input[id^="quantity_0"]', '1/2');
        await page.selectOption('select[id^="unit_0"]', 'cup');
        await page.fill('textarea[name="instructions"]', 'Test instructions.');

        await page.click('button:has-text("Create Recipe")');
        await page.waitForURL(/\/recipes\/\d+/, { timeout: 10000 });
        await page.waitForLoadState('networkidle');

        // Verify the ingredient was saved and shows ½
        const ingredientQuantity = page.locator('li').filter({ hasText: 'Butter' }).locator('span[x-text]');
        await expect(ingredientQuantity).toContainText('½');
    });

    test('recipe create form accepts mixed fraction input (1 1/2) and saves correctly', async ({ page }) => {
        await page.goto('/recipes/create');
        await page.waitForURL(/\/recipes\/create/);

        const recipeName = `Mixed Fraction Input Test ${Date.now()}`;
        await page.fill('input[name="name"]', recipeName);
        await page.fill('input[name="servings"]', '4');

        // Fill ingredient with mixed fraction "1 1/2"
        await page.fill('input[id^="ingredient_name_0"]', 'Flour');
        await page.fill('input[id^="quantity_0"]', '1 1/2');
        await page.selectOption('select[id^="unit_0"]', 'cup');
        await page.fill('textarea[name="instructions"]', 'Test instructions.');

        await page.click('button:has-text("Create Recipe")');
        await page.waitForURL(/\/recipes\/\d+/, { timeout: 10000 });
        await page.waitForLoadState('networkidle');

        // Verify the ingredient was saved and shows 1½
        const ingredientQuantity = page.locator('li').filter({ hasText: 'Flour' }).locator('span[x-text]');
        await expect(ingredientQuantity).toContainText('1½');
    });

    test('recipe show page displays created recipe fraction quantity correctly after fraction input', async ({ page }) => {
        await page.goto('/recipes/create');
        await page.waitForURL(/\/recipes\/create/);

        const recipeName = `Fraction Display Verify Test ${Date.now()}`;
        await page.fill('input[name="name"]', recipeName);
        await page.fill('input[name="servings"]', '2');

        // Use fraction input "3/4"
        await page.fill('input[id^="ingredient_name_0"]', 'Sugar');
        await page.fill('input[id^="quantity_0"]', '3/4');
        await page.selectOption('select[id^="unit_0"]', 'cup');
        await page.fill('textarea[name="instructions"]', 'Mix ingredients.');

        await page.click('button:has-text("Create Recipe")');
        await page.waitForURL(/\/recipes\/\d+/, { timeout: 10000 });
        await page.waitForLoadState('networkidle');

        // Verify shows ¾ not 0.75
        const ingredientQuantity = page.locator('li').filter({ hasText: 'Sugar' }).locator('span[x-text]');
        await expect(ingredientQuantity).toContainText('¾');
        await expect(ingredientQuantity).not.toContainText('0.75');
    });

    test('meal plan ingredient list displays fraction quantity not decimal', async ({ page }) => {
        // Create a recipe with 0.5 quantity
        const recipeName = `Meal Plan Fraction Test ${Date.now()}`;
        await createRecipeWithIngredient(page, recipeName, '0.5');

        // Get recipe ID from URL
        const recipeUrl = page.url();
        const recipeId = recipeUrl.match(/\/recipes\/(\d+)/)?.[1];
        expect(recipeId).toBeTruthy();

        // Create a meal plan
        await page.goto('/meal-plans');
        await page.waitForLoadState('networkidle');
        await page.click('text=Create New Meal Plan');
        await page.waitForURL(/\/meal-plans\/create/);

        const mealPlanName = `Fraction Meal Plan ${Date.now()}`;
        await page.fill('input[name="name"]', mealPlanName);

        const today = new Date();
        const startDate = today.toISOString().split('T')[0];
        const endDate = new Date(today.getTime() + 6 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

        await page.fill('input[name="start_date"]', startDate);
        await page.fill('input[name="end_date"]', endDate);

        await page.click('button:has-text("Create Meal Plan")');
        await page.waitForURL(/\/meal-plans\/\d+/, { timeout: 10000 });
        await page.waitForLoadState('networkidle');

        // Assign the recipe to the first dinner slot
        const firstDinnerSlot = page.locator('tbody tr').first().locator('[data-meal-type="dinner"]');
        const addButton = firstDinnerSlot.locator('button').first();
        await addButton.click({ timeout: 5000 });

        await page.waitForTimeout(500);
        const addRecipeOption = page.getByRole('menuitem', { name: 'Add Recipe' });
        await addRecipeOption.click({ force: true });

        // Wait for modal
        await expect(page.locator('text=Select Recipe for')).toBeVisible({ timeout: 5000 });

        // Search for our recipe
        const modalSearchInput = page.locator('input[placeholder*="Search recipes"]');
        await modalSearchInput.fill(recipeName);
        await page.waitForTimeout(500);

        // Click our recipe card
        const recipeCard = page.locator(`[data-recipe-card]:has-text("${recipeName}")`).first();
        await recipeCard.click({ timeout: 5000 });

        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1000);

        // Click on the assigned recipe in the meal plan to view ingredient details
        const assignedRecipe = firstDinnerSlot.getByText(recipeName);
        await assignedRecipe.click({ timeout: 5000 });

        await page.waitForTimeout(500);

        // Look for ingredient quantity — should show ½ not 0.5
        const ingredientDisplay = page.locator('text=½');
        await expect(ingredientDisplay).toBeVisible({ timeout: 5000 });
    });
});
