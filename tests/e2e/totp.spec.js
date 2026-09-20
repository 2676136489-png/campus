const { test, expect } = require('@playwright/test');
const crypto = require('crypto');
const { closeSse, gotoTab } = require('./helpers');

const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

function base32Decode(input) {
    let bits = '';
    for (const ch of String(input).toUpperCase()) {
        const idx = BASE32.indexOf(ch);
        if (idx < 0) continue;
        bits += idx.toString(2).padStart(5, '0');
    }
    const bytes = [];
    for (let i = 0; i + 8 <= bits.length; i += 8) bytes.push(parseInt(bits.slice(i, i + 8), 2));
    return Buffer.from(bytes);
}

function totp(secret) {
    const counter = BigInt(Math.floor(Date.now() / 1000 / 30));
    const buf = Buffer.alloc(8);
    buf.writeBigUInt64BE(counter);
    const hmac = crypto.createHmac('sha1', base32Decode(secret)).update(buf).digest();
    const offset = hmac[hmac.length - 1] & 0x0f;
    const code = ((hmac[offset] & 0x7f) << 24 | hmac[offset + 1] << 16 | hmac[offset + 2] << 8 | hmac[offset + 3]) % 1000000;
    return String(code).padStart(6, '0');
}

async function loginAdmin(page) {
    await page.goto('/p_adminAuth.php');
    await page.fill('#adminUser', 'admin');
    await page.fill('#adminPwd', 'Admin@123456');
    await page.click('button[name="adminLogin"]');
    await page.waitForTimeout(1000);
    const change = await page.locator('#oldPwd').isVisible().catch(() => false);
    if (change) {
        await page.fill('#oldPwd', 'Admin@123456');
        await page.fill('#newPwd', 'NewAdmin@123');
        await page.click('button[name="changeAdminPassword"]');
        await page.waitForTimeout(1000);
        await page.waitForSelector('.site-header');
        await closeSse(page);
        return;
    }
    if (!(await page.locator('.site-header').isVisible().catch(() => false))) {
        await page.fill('#adminUser', 'admin');
        await page.fill('#adminPwd', 'NewAdmin@123');
        await page.click('button[name="adminLogin"]');
        await page.waitForTimeout(1000);
    }
    await page.waitForSelector('.site-header');
    await closeSse(page);
}

test('admin totp enable login disable flow', async ({ page }) => {
    test.setTimeout(90000);
    await loginAdmin(page);
    await page.click('button[name="provisionAdminTotp"]');
    await page.waitForSelector('button[name="enableAdminTotp"]');
    await closeSse(page);
    const secret = (await page.locator('.form-field code').first().textContent()).trim();
    await page.fill('input[name="totpCode"]', totp(secret));
    await page.click('button[name="enableAdminTotp"]');
    await expect(page.locator('.alert-success')).toContainText('二次验证已启用。');
    await closeSse(page);

    await gotoTab(page, '/p_adminAuth.php?logout=1');
    await page.waitForSelector('#adminUser');
    await page.fill('#adminUser', 'admin');
    await page.fill('#adminPwd', 'NewAdmin@123');
    await page.click('button[name="adminLogin"]');
    await page.waitForSelector('#totpCode');
    await page.fill('#totpCode', totp(secret));
    await page.click('button[name="adminTotpLogin"]');
    await expect(page.locator('.page-title')).toContainText('管理员后台');
    await closeSse(page);

    await page.fill('input[name="totpCode"]', totp(secret));
    await page.click('button[name="disableAdminTotp"]');
    await expect(page.locator('.alert-success')).toContainText('二次验证已关闭。');
});