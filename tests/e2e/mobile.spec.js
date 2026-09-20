const { test, expect } = require('@playwright/test');
const { loginAs, gotoTab } = require('./helpers');
const path = require('path');

test.use({
    viewport: { width: 390, height: 844 },
    isMobile: true,
    hasTouch: true
});

test('mobile login page has no horizontal overflow', async ({ page }) => {
    await page.goto('/p_loginStu.php');
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(overflow).toBe(false);
    await expect(page.locator('button[type="submit"]')).toBeVisible();
});

test('mobile student center and feed have no horizontal overflow', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    const homeOverflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(homeOverflow).toBe(false);

    await gotoTab(page, '/p_dynamics.php');
    await expect(page.locator('#main')).toBeVisible();
    const feedOverflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(feedOverflow).toBe(false);
});

test('mobile edit profile and publish dynamic pages have no horizontal overflow', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');

    await gotoTab(page, '/p_editStuInfo.php');
    await expect(page.locator('#userName')).toBeVisible();
    const editOverflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(editOverflow).toBe(false);

    await gotoTab(page, '/p_publishDynamic.php');
    await expect(page.locator('#main')).toBeVisible();
    const publishOverflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(publishOverflow).toBe(false);
});
test('mobile avatar and photo pickers preview files without overflow', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    const fixture = path.join(__dirname, 'fixtures', 'avatar.png');

    await gotoTab(page, '/p_editStuInfo.php');
    await page.setInputFiles('#avatar', fixture);
    await expect(page.locator('#avatarThumb img')).toHaveAttribute('src', /^data:image/);
    await expect(page.locator('button[data-clear-input="avatar"]')).toBeVisible();
    const editOverflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(editOverflow).toBe(false);

    await gotoTab(page, '/p_publishDynamic.php');
    await page.setInputFiles('#photos', [fixture, fixture]);
    await expect(page.locator('#publishPhotoGrid .photo-preview-item')).toHaveCount(2);
    const publishOverflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    expect(publishOverflow).toBe(false);

    await page.click('button[data-clear-input="photos"]');
    await expect(page.locator('#publishPhotoGrid')).toBeHidden();
});