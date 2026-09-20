const { test, expect } = require('@playwright/test');
const { loginAs, gotoTab, closeSse } = require('./helpers');
const path = require('path');

test('student can send a private message', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await gotoTab(page, '/p_allStudents.php');
    const card = page.locator('.people-card', { hasText: '陈思远' }).first();
    await card.locator('a', { hasText: '查看主页' }).click();
    await page.waitForURL(/p_profile\.php/);
    await closeSse(page);
    await page.locator('a', { hasText: '私信' }).first().click();
    await expect(page).toHaveURL(/p_chat\.php/);
    await closeSse(page);
    const text = 'E2E私信 ' + Date.now();
    await page.fill('[data-chat-input]', text);
    await page.click('button[type="submit"]');
    await expect(page.locator('.chat-message.is-mine').last()).toContainText(text);
});

test('chat image preview shows selected file', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await gotoTab(page, '/p_allStudents.php');
    const card = page.locator('.people-card', { hasText: '陈思远' }).first();
    await card.locator('a', { hasText: '查看主页' }).click();
    await page.waitForURL(/p_profile\.php/);
    await closeSse(page);
    await page.locator('a', { hasText: '私信' }).first().click();
    await expect(page).toHaveURL(/p_chat\.php/);
    await closeSse(page);

    const preview = page.locator('[data-chat-image-preview]');
    await expect(preview).toBeHidden();

    const fixture = path.resolve(__dirname, 'fixtures', 'avatar.png');
    await page.setInputFiles('[data-chat-image]', fixture);

    await expect(preview).toBeVisible();
    const src = await preview.getAttribute('src');
    expect(src.startsWith('data:image/')).toBeTruthy();
});