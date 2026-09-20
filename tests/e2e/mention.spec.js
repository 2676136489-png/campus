const { test, expect } = require('@playwright/test');
const { loginAs, gotoTab } = require('./helpers');

test('comment @ mention dropdown inserts username', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await gotoTab(page, '/p_dynamics.php');
    const input = page.locator('.comment-form input[name="comment"]').first();
    await input.fill('@dem');
    await expect(page.locator('.mention-dropdown').first()).toBeVisible();
    await page.locator('.mention-option').first().click();
    const value = await input.inputValue();
    expect(value).toMatch(/@demo\d\d /);
});