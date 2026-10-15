import { test, expect } from '@playwright/test';

test.describe('Torch Theme', () => {
  test.use({ colorScheme: 'light' });

  test('login submit button renders with the steel blue accent background', async ({ page }) => {
    await page.goto('/login');

    await expect(page.locator('[data-test="login-button"]')).toHaveCSS('background-color', 'rgb(6, 55, 126)');
  });
});
