const { test, expect } = require('@playwright/test');
const { loginAs, gotoTab } = require('./helpers');
const path = require('path');

test('student can submit feedback with image attachment', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await gotoTab(page, '/p_feedback.php');
    await page.selectOption('#type', 'suggestion');
    await page.fill('#content', 'E2E feedback ' + Date.now());
    await page.setInputFiles('#image', path.resolve(__dirname, 'fixtures', 'avatar.png'));
    await page.click('button[name="submitFeedback"]');
    await expect(page.locator('.alert-success')).toContainText('\u53cd\u9988\u5df2\u63d0\u4ea4');
});
