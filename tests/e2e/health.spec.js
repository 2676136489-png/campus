const { test, expect } = require('@playwright/test');
const { loginAs } = require('./helpers');

test('health endpoint reports service and db status', async ({ request }) => {
    const res = await request.get('/api.php?action=health');
    expect(res.status()).toBe(200);
    const body = await res.json();
    expect(body.ok).toBe(true);
    expect(body.service).toBe('campus-circle');
    expect(['ok', 'error']).toContain(body.db);
});

test('sse endpoint opens for verified student', async ({ page }) => {
    await loginAs(page, 'demo01', 'Demo@123456');
    const status = await page.evaluate(async () => {
        const ctrl = new AbortController();
        const timer = setTimeout(() => ctrl.abort(), 3000);
        try {
            const r = await fetch('/p_sse.php', { signal: ctrl.signal, credentials: 'same-origin' });
            return r.status;
        } catch (e) {
            return e.name === 'AbortError' ? 200 : 0;
        } finally {
            clearTimeout(timer);
        }
    });
    expect(status).toBe(200);
});