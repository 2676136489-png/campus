const { test, expect } = require('@playwright/test');
const { loginAs, gotoTab } = require('./helpers');

test('student can reply to a comment', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    await gotoTab(page, '/p_dynamics.php');
    const replyBtn = page.locator('.comment-reply-btn').first();
    await replyBtn.click();
    const comments = replyBtn.locator('xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " comments ")][1]');
    const input = comments.locator('input[name="comment"]');
    const text = 'E2E回复 ' + Date.now();
    await input.fill(text);
    await comments.locator('.comment-form button[type="submit"]').click();
    await expect(comments.locator('.comment-item.is-reply').last()).toContainText(text);
});