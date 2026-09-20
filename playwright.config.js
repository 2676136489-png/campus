const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/e2e',
    globalSetup: './tests/e2e/global-setup.js',
    timeout: 60000,
    retries: 1,
    workers: 1,
    use: {
        baseURL: process.env.E2E_BASE_URL || 'http://127.0.0.1:8765',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure'
    },
    projects: [
        { name: 'chromium', use: { browserName: 'chromium' } }
    ],
    webServer: {
        command: (process.env.PHP_BIN || 'php') + ' -S 127.0.0.1:8765 -t .',
        url: 'http://127.0.0.1:8765/p_loginStu.php',
        reuseExistingServer: true,
        env: {
            APP_ENV: 'development',
            DB_HOST: process.env.DB_HOST || '127.0.0.1',
            DB_NAME: process.env.DB_NAME || 'circle_e2e',
            DB_USER: process.env.DB_USER || 'root',
            DB_PASS: process.env.DB_PASS || '123456',
            DB_PORT: process.env.DB_PORT || '3306'
        }
    }
});