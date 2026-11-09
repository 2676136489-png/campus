const { test, expect } = require('@playwright/test');
const { solveCaptcha, loginAs } = require('./helpers');

test('login page shows captcha and rejects wrong password', async ({ page }) => {
    await page.goto('/p_loginStu.php');
    await expect(page.locator('#captcha')).toBeVisible();
    await expect(page.locator('[data-captcha-image]')).toBeVisible();
    await page.fill('#userName', 'demo01');
    await page.fill('#pwd', 'wrong-password');
    await page.fill('#captcha', await solveCaptcha(page));
    await page.check('#agreeTerms');
    await page.click('button[type="submit"]');
    await expect(page.locator('.alert-error')).toContainText('密码错误');
});

test('login submit stays disabled until agreement is checked', async ({ page }) => {
    await page.goto('/p_loginStu.php');
    await expect(page.locator('#agreeTerms')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeDisabled();
    await page.check('#agreeTerms');
    await expect(page.locator('button[type="submit"]')).toBeEnabled();
});

test('admin login page does not require agreement checkbox', async ({ page }) => {
    await page.goto('/p_adminAuth.php');
    await expect(page.locator('#agreeTerms')).toHaveCount(0);
});

test('demo student can login', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await expect(page).toHaveURL(/p_welcomeStu\.php/);
});