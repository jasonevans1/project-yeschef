import { test, expect } from '@playwright/test';

test.describe('Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        // Login as the test user
        await page.goto('/login');
        await page.fill('input[name="email"]', 'test@example.com');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');

        // Wait for navigation to dashboard
        await page.waitForURL('/dashboard');
    });

    test('displays welcome message with user name', async ({ page }) => {
        await expect(page.getByText('Welcome back, Test User!')).toBeVisible();
        await expect(page.getByText(/Here's what's happening with your meal planning/i)).toBeVisible();
    });

    test('displays quick action buttons', async ({ page }) => {
        await expect(page.getByRole('link', { name: 'Create Meal Plan' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Browse Recipes' })).toBeVisible();
        await expect(page.getByRole('link', { name: 'Create Shopping List' })).toBeVisible();
    });

    test('quick action buttons navigate correctly', async ({ page }) => {
        // Test Create Meal Plan button
        await page.getByRole('link', { name: 'Create Meal Plan' }).click();
        await expect(page).toHaveURL('/meal-plans/create');
        await page.goBack();

        // Test Browse Recipes button
        await page.getByRole('link', { name: 'Browse Recipes' }).click();
        await expect(page).toHaveURL('/recipes');
        await page.goBack();

        // Test Create Shopping List button
        await page.getByRole('link', { name: 'Create Shopping List' }).click();
        await expect(page).toHaveURL('/grocery-lists/create');
    });

    test('displays upcoming meal plans section', async ({ page }) => {
        await expect(page.getByText('Upcoming Meal Plans (Next 7 Days)')).toBeVisible();
    });

    test('displays upcoming meal plans with data', async ({ page }) => {
        // Check if we have meal plans or empty state
        const emptyState = page.getByText('No upcoming meal plans');
        const viewAllButton = page.getByRole('link', { name: 'View All Meal Plans' });

        // Either we have meal plans or empty state
        const hasData = await viewAllButton.isVisible().catch(() => false);
        const isEmpty = await emptyState.isVisible().catch(() => false);

        expect(hasData || isEmpty).toBeTruthy();

        if (hasData) {
            // Verify meal plan details are shown
            await expect(viewAllButton).toBeVisible();
        } else {
            // Verify empty state
            await expect(emptyState).toBeVisible();
            await expect(page.getByText(/Create a meal plan to get started/i)).toBeVisible();
            await expect(page.getByRole('link', { name: 'Create Your First Meal Plan' })).toBeVisible();
        }
    });

    test('displays recent grocery lists section', async ({ page }) => {
        await expect(page.getByText('Recent Grocery Lists')).toBeVisible();
    });

    test('displays recent grocery lists with data', async ({ page }) => {
        // Check if we have grocery lists or empty state
        const emptyState = page.getByText('No grocery lists yet');
        const viewAllButton = page.getByRole('link', { name: 'View All Grocery Lists' });

        // Either we have lists or empty state
        const hasData = await viewAllButton.isVisible().catch(() => false);
        const isEmpty = await emptyState.isVisible().catch(() => false);

        expect(hasData || isEmpty).toBeTruthy();

        if (hasData) {
            // Verify grocery list details are shown
            await expect(viewAllButton).toBeVisible();
        } else {
            // Verify empty state
            await expect(emptyState).toBeVisible();
            await expect(page.getByText(/Create a grocery list to start shopping efficiently/i)).toBeVisible();
            await expect(page.getByRole('link', { name: 'Create Your First List' })).toBeVisible();
        }
    });

    test('meal plan items are clickable and navigate correctly', async ({ page }) => {
        // Only run if we have meal plans
        const viewAllButton = page.getByRole('link', { name: 'View All Meal Plans' });
        const hasData = await viewAllButton.isVisible().catch(() => false);

        if (hasData) {
            // Find first meal plan link that matches the show pattern (href ends with /\d+)
            // Exclude "View All Meal Plans" and "Create Your First Meal Plan" links
            const mealPlanLink = page.locator('a[href*="/meal-plans/"]')
                .filter({ hasNotText: 'View All' })
                .filter({ hasNotText: 'Create' })
                .first();
            await expect(mealPlanLink).toBeVisible();

            // Click and verify navigation
            await mealPlanLink.click();
            await expect(page).toHaveURL(/\/meal-plans\/\d+/);
        }
    });

    test('grocery list items are clickable and navigate correctly', async ({ page }) => {
        // Only run if we have grocery lists
        const viewAllButton = page.getByRole('link', { name: 'View All Grocery Lists' });
        const hasData = await viewAllButton.isVisible().catch(() => false);

        if (hasData) {
            // Find first grocery list link that matches the show pattern (has /\d+/ in href)
            const groceryListLink = page.locator('a[href*="/grocery-lists/"]').filter({ hasNotText: 'Create' }).filter({ hasText: /Grocery List|Trip|Staples|Week/ }).first();
            await expect(groceryListLink).toBeVisible();

            // Click and verify navigation
            await groceryListLink.click();
            await expect(page).toHaveURL(/\/grocery-lists\/\d+/);
        }
    });

    test('navigation menu is visible and functional', async ({ page }) => {
        // Verify main navigation items (using first() since some appear in both header nav and quick actions)
        await expect(page.getByRole('link', { name: 'Dashboard' }).first()).toBeVisible();
        await expect(page.getByRole('link', { name: 'Recipes' }).first()).toBeVisible();
        await expect(page.getByRole('link', { name: 'Meal Plans' }).first()).toBeVisible();
        await expect(page.getByRole('link', { name: 'Grocery Lists' }).first()).toBeVisible();

        // Test navigation to Recipes
        await page.getByRole('link', { name: 'Recipes' }).first().click();
        await expect(page).toHaveURL('/recipes');
    });

    test('displays badges for active and upcoming meal plans', async ({ page }) => {
        // Only check if we have meal plans
        const viewAllButton = page.getByRole('link', { name: 'View All Meal Plans' });
        const hasData = await viewAllButton.isVisible().catch(() => false);

        if (hasData) {
            // Check for badges (could be "Active" or "Upcoming")
            const activeBadge = page.locator('text=Active').first();
            const upcomingBadge = page.locator('text=Upcoming').first();

            // At least one type of badge should be visible
            const hasActiveBadge = await activeBadge.isVisible().catch(() => false);
            const hasUpcomingBadge = await upcomingBadge.isVisible().catch(() => false);

            expect(hasActiveBadge || hasUpcomingBadge).toBeTruthy();
        }
    });

    test('displays badges for grocery list types', async ({ page }) => {
        // Only check if we have grocery lists
        const viewAllButton = page.getByRole('link', { name: 'View All Grocery Lists' });
        const hasData = await viewAllButton.isVisible().catch(() => false);

        if (hasData) {
            // Check for badges (could be "From Meal Plan" or "Standalone")
            const mealPlanBadge = page.locator('text=From Meal Plan').first();
            const standaloneBadge = page.locator('text=Standalone').first();

            // At least one type of badge should be visible
            const hasMealPlanBadge = await mealPlanBadge.isVisible().catch(() => false);
            const hasStandaloneBadge = await standaloneBadge.isVisible().catch(() => false);

            expect(hasMealPlanBadge || hasStandaloneBadge).toBeTruthy();
        }
    });

    test('displays grocery list completion progress', async ({ page }) => {
        // Only check if we have grocery lists
        const viewAllButton = page.getByRole('link', { name: 'View All Grocery Lists' });
        const hasData = await viewAllButton.isVisible().catch(() => false);

        if (hasData) {
            // Check for progress text (e.g., "0/5 items", "3/10 items")
            const progressText = page.locator('text=/\\d+\\/\\d+ items/').first();
            await expect(progressText).toBeVisible();
        }
    });

    test('page has no console errors', async ({ page }) => {
        const errors: string[] = [];
        page.on('console', message => {
            if (message.type() === 'error') {
                errors.push(message.text());
            }
        });

        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');

        expect(errors).toHaveLength(0);
    });
});
