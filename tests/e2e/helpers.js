async function solveCaptcha(page) {
    const code = await page.evaluate(async () => {
        const tab = sessionStorage.getItem('campus_tab_token') || '';
        const response = await fetch('p_captcha.php?reveal=1&tab=' + encodeURIComponent(tab), { credentials: 'same-origin' });
        const data = await response.json();
        return data.code;
    });
    return code;
}

async function closeSse(page) {
    await page.evaluate(() => {
        if (window.closeCampusSse) {
            window.closeCampusSse();
        }
    });
}

async function loginAs(page, username, password, tabToken) {
    let existingTab = tabToken || '';
    if (!existingTab) {
        try {
            existingTab = await page.evaluate(() => sessionStorage.getItem('campus_tab_token') || '');
        } catch (e) {}
    }
    const loginUrl = '/p_loginStu.php' + (existingTab ? '?tab=' + encodeURIComponent(existingTab) : '');
    await page.goto(loginUrl);
    await page.fill('#userName', username);
    await page.fill('#pwd', password);
    await page.fill('#captcha', await solveCaptcha(page));
    await page.check('#agreeTerms');
    await page.click('button[type="submit"]');
    await page.waitForURL(/p_welcomeStu\.php/);
    await closeSse(page);
}

async function gotoTab(page, path) {
    const tab = await page.evaluate(() => sessionStorage.getItem('campus_tab_token') || '');
    const sep = path.includes('?') ? '&' : '?';
    await page.goto(path + (tab ? sep + 'tab=' + encodeURIComponent(tab) : ''));
    await closeSse(page);
}

module.exports = { solveCaptcha, loginAs, gotoTab, closeSse };