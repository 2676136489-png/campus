const { test, expect } = require('@playwright/test');
const { loginAs, gotoTab, closeSse } = require('./helpers');

test('publish page autosaves and restores draft', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await gotoTab(page, '/p_publishDynamic.php');
    const text = 'E2E草稿 ' + Date.now();
    await page.fill('#content', text);
    await page.waitForTimeout(1500);
    await page.reload();
    await closeSse(page);
    await expect(page.locator('#content')).toHaveValue(text);
    await page.click('[data-clear-draft]');
    await expect(page.locator('#content')).toHaveValue('');
});