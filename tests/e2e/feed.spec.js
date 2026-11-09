const { test, expect } = require('@playwright/test');
const { loginAs, gotoTab } = require('./helpers');

test('verified student can publish a dynamic', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await gotoTab(page, '/p_publishDynamic.php');
    const text = 'E2E动态 ' + Date.now();
    await page.fill('#content', text);
    await page.click('button[name="publishDynamic"]');
    await expect(page.locator('.alert-success')).toContainText('动态发布成功');
    await gotoTab(page, '/p_dynamics.php');
    await expect(page.locator('body')).toContainText(text);
});

test('dynamics cards become visible after page load', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await gotoTab(page, '/p_dynamics.php');
    await expect(page.locator('.post-card.reveal').first()).toHaveClass(/is-visible/);
});

test('feed loads more on scroll without pagination', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await gotoTab(page, '/p_dynamics.php');
    await expect(page.locator('.post-card').first()).toBeVisible();
    const initial = await page.locator('.post-card').count();
    await page.locator('[data-feed-sentinel]').scrollIntoViewIfNeeded();
    await page.waitForFunction((n) => document.querySelectorAll('.post-card').length > n, initial, { timeout: 15000 });
    await expect(page.locator('.feed-column a.page-link')).toHaveCount(0);
});