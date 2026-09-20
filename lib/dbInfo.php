<?php
/**
 * 配置加载：优先读取环境变量，其次读取项目根目录 .env 文件。
 */
function envValue($name, $default) {
    $value = getenv($name);
    if ($value !== false && $value !== '') {
        return $value;
    }
    $value = $_ENV[$name] ?? '';
    return $value === '' ? $default : $value;
}

(function () {
    $envFile = __DIR__ . '/../.env';
    if (!is_file($envFile)) {
        return;
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        $parts = array_map('trim', explode('=', $line, 2));
        $key = $parts[0];
        $value = isset($parts[1]) ? $parts[1] : '';
        if ($key !== '' && envValue($key, '') === '') {
            $_ENV[$key] = $value;
        }
    }
})();

define('APP_ENV', envValue('APP_ENV', 'development'));
define('HOST', envValue('DB_HOST', '127.0.0.1'));
define('DBNAME', envValue('DB_NAME', 'circle'));
define('USERNAME', envValue('DB_USER', 'root'));
define('PASSWORD', envValue('DB_PASS', '123456'));
define('PORT', (int)envValue('DB_PORT', 3306));
define('CHARSET', 'utf8mb4');
define('ADMIN_INITIAL_PASSWORD', envValue('ADMIN_INITIAL_PASSWORD', 'Admin@123456'));
define('LOGIN_RATE_IP_FAILURES', (int)envValue('LOGIN_RATE_IP_FAILURES', 20));
define('LOGIN_RATE_NAME_FAILURES', (int)envValue('LOGIN_RATE_NAME_FAILURES', 10));

if (APP_ENV === 'production') {
    if (envValue('DB_PASS', '') === '') {
        throw new RuntimeException('DB_PASS must be set via environment variables in production.');
    }
    if (envValue('ADMIN_INITIAL_PASSWORD', '') === '') {
        throw new RuntimeException('ADMIN_INITIAL_PASSWORD must be set via environment variables in production.');
    }
}