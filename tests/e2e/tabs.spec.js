const { test, expect } = require('@playwright/test');
const { loginAs, gotoTab, closeSse } = require('./helpers');

test('two tabs can stay logged into different accounts', async ({ context }) => {
    const page1 = await context.newPage();
    const page2 = await context.newPage();

    await loginAs(page1, 'demo01', 'Demo@123456');
    await loginAs(page2, 'demo02', 'Demo@123456');

    await page1.reload();
    await closeSse(page1);
    await expect(page1.locator('h1')).toContainText('你好，林晓');
    await expect(page1).toHaveURL(/tab=/);

    await page2.reload();
    await closeSse(page2);
    await expect(page2.locator('h1')).toContainText('你好，陈思远');
    await expect(page2).toHaveURL(/tab=/);
});

test('duplicated tab gets a new session when logging in as another account', async ({ context }) => {
    const page1 = await context.newPage();
    await loginAs(page1, 'demo01', 'Demo@123456');
    const token = await page1.evaluate(() => sessionStorage.getItem('campus_tab_token'));

    const page2 = await context.newPage();
    await page2.addInitScript((t) => sessionStorage.setItem('campus_tab_token', t), token);
    await loginAs(page2, 'demo02', 'Demo@123456', token);

    await page1.reload();
    await closeSse(page1);
    await page2.reload();
    await closeSse(page2);
    await expect(page1.locator('h1')).toContainText('你好，林晓');
    await expect(page2.locator('h1')).toContainText('你好，陈思远');
});

test('same account in a second page logs out the first page', async ({ context }) => {
    const page1 = await context.newPage();
    await loginAs(page1, 'demo01', 'Demo@123456');

    const page2 = await context.newPage();
    await loginAs(page2, 'demo01', 'Demo@123456');

    await gotoTab(page1, '/p_welcomeStu.php');
    await expect(page1).toHaveURL(/p_loginStu\.php/);
    await expect(page2.locator('h1')).toContainText('你好，林晓');
});