const { test, expect } = require('@playwright/test');
const { closeSse } = require('./helpers');

test('admin can change initial password and open dashboard', async ({ page }) => {
    await page.goto('/p_adminAuth.php');
    await page.fill('#adminUser', 'admin');
    await page.fill('#adminPwd', 'Admin@123456');
    await page.click('button[name="adminLogin"]');
    await expect(page.locator('#oldPwd')).toBeVisible();
    await page.fill('#oldPwd', 'Admin@123456');
    await page.fill('#newPwd', 'NewAdmin@123');
    await page.click('button[name="changeAdminPassword"]');
    await expect(page.locator('.page-title')).toContainText('管理员后台');
    await closeSse(page);
});