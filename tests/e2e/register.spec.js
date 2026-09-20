const { test, expect } = require('@playwright/test');
const { solveCaptcha, closeSse } = require('./helpers');
const path = require('path');

test('student can register and enter pending review', async ({ page }) => {
    const stamp = Date.now().toString().slice(-8);
    const username = 'e2e_' + stamp;
    await page.goto('/p_registerStu.php');
    await page.fill('#userName', username);
    await page.fill('#pwd', 'E2ePass123');
    await page.fill('#name', '测试同学');
    await page.locator('.radio-pill', { hasText: '女' }).click();
    await page.fill('#birth_date', '2004-01-01');
    await page.fill('#stuNo', '20' + stamp);
    await page.fill('#college', '测试学院');
    await page.fill('#grade', '2024级');
    await page.fill('#major', '测试专业');
    await page.fill('#phone', '13800' + stamp);
    await page.fill('#email', username + '@example.com');
    await page.fill('#QQ', '12345678' + stamp.slice(0, 2));
    const fixture = path.resolve(__dirname, 'fixtures', 'avatar.png');
    await page.setInputFiles('#avatar', fixture);
    await page.setInputFiles('#student_card', fixture);
    await page.fill('#captcha', await solveCaptcha(page));
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/p_welcomeStu\.php/);
    await expect(page.locator('body')).toContainText('待审核');
    await closeSse(page);
});