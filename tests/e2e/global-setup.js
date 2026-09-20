const { execSync } = require('child_process');
const path = require('path');

module.exports = async () => {
    const root = path.resolve(__dirname, '..', '..');
    const env = Object.assign({}, process.env, {
        APP_ENV: 'development',
        DB_HOST: process.env.DB_HOST || '127.0.0.1',
        DB_NAME: process.env.DB_NAME || 'circle_e2e',
        DB_USER: process.env.DB_USER || 'root',
        DB_PASS: process.env.DB_PASS || '123456',
        DB_PORT: process.env.DB_PORT || '3306'
    });
    execSync((process.env.PHP_BIN || 'php') + ' bin/migrate.php', { cwd: root, stdio: 'inherit', env });
    execSync((process.env.PHP_BIN || 'php') + ' bin/e2e_reset.php', { cwd: root, stdio: 'inherit', env });
    execSync((process.env.PHP_BIN || 'php') + ' bin/seed.php', { cwd: root, stdio: 'inherit', env });
    execSync((process.env.PHP_BIN || 'php') + ' bin/e2e_extra_feed.php', { cwd: root, stdio: 'inherit', env });
};