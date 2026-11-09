const { test, expect } = require('@playwright/test');
const { solveCaptcha, loginAs, gotoTab, closeSse } = require('./helpers');

async function loginAdmin(page) {
    await page.goto('/p_adminAuth.php');
    await page.fill('#adminUser', 'admin');
    await page.fill('#adminPwd', 'NewAdmin@123');
    await page.click('button[name="adminLogin"]');

    const dashboardVisible = await page.locator('.page-title').isVisible().catch(() => false);
    if (!dashboardVisible) {
        await page.fill('#adminUser', 'admin');
        await page.fill('#adminPwd', 'Admin@123456');
        await page.click('button[name="adminLogin"]');
        const changeForm = await page.locator('#oldPwd').isVisible().catch(() => false);
        if (changeForm) {
            await page.fill('#oldPwd', 'Admin@123456');
            await page.fill('#newPwd', 'NewAdmin@123');
            await page.click('button[name="changeAdminPassword"]');
        }
    }
    await expect(page.locator('.page-title')).toContainText('管理员后台');
    await closeSse(page);
}

test('admin must re-login after visiting student entry', async ({ page }) => {
    await loginAdmin(page);
    await page.locator('a', { hasText: '学生入口' }).first().click();
    await expect(page).toHaveURL(/p_loginStu\.php/);
    await page.locator('a', { hasText: '管理员入口' }).click();
    await expect(page.locator('#adminUser')).toBeVisible();
    await expect(page.locator('.auth-title')).toBeVisible();
});

test('student login clears admin session', async ({ page }) => {
    await loginAdmin(page);
    await loginAs(page, 'demo01', 'Demo@123456');
    await gotoTab(page, '/p_adminAuth.php');
    await expect(page.locator('#adminUser')).toBeVisible();
});