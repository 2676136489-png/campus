const { test, expect } = require('@playwright/test');
const { loginAs, gotoTab } = require('./helpers');

test('student can favorite and unfavorite a dynamic', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await gotoTab(page, '/p_dynamics.php');
    const fav = page.locator('form[data-api-action="favorite"]').first();
    const btn = fav.locator('button[type="submit"]');
    await btn.click();
    await expect(btn).toHaveClass(/is-favorited/);
    await expect(btn).toBeEnabled();
    await btn.click();
    await expect(btn).not.toHaveClass(/is-favorited/);
});