const { test, expect } = require('@playwright/test');
const { loginAs } = require('./helpers');

test('recommended students refresh to 3 random people', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await expect(page.locator('[data-recommend-list]')).toBeVisible();
    await expect(page.locator('[data-person-card]')).toHaveCount(3);

    const responsePromise = page.waitForResponse(function (response) {
        return response.url().includes('action=recommendations') && response.ok();
    });
    await page.click('[data-recommend-refresh]');
    await responsePromise;

    await expect(page.locator('[data-person-card]')).toHaveCount(3);
    await expect(page.locator('[data-recommend-list]')).toBeVisible();
});