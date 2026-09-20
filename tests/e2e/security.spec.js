const { test, expect } = require('@playwright/test');

test('login page sends baseline security headers', async ({ request }) => {
    const res = await request.get('/p_loginStu.php');
    expect(res.status()).toBe(200);
    const headers = res.headers();
    expect(headers['x-frame-options']).toBe('SAMEORIGIN');
    expect(headers['x-content-type-options']).toBe('nosniff');
    expect(headers['referrer-policy']).toBe('strict-origin-when-cross-origin');
    expect(headers['content-security-policy']).toContain("default-src 'self'");
});

test('api endpoint rejects anonymous requests with JSON', async ({ request }) => {
    const res = await request.get('/api.php?action=unread');
    expect(res.status()).toBe(401);
    const body = await res.json();
    expect(body.ok).toBe(false);
});