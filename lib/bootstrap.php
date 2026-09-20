<?php

require_once __DIR__ . '/logger.php';

/**
 * Append the current tab token to a redirect URL so flash messages survive
 * the app.js tab rewrite and the session stays on the same tab.
 */
function tabUrl($url)
{
    if (tabToken() === '' || strpos($url, 'tab=') !== false) {
        return $url;
    }
    $sep = strpos($url, '?') === false ? '?' : '&';
    return $url . $sep . 'tab=' . rawurlencode(tabToken());
}


/**
 * Application bootstrap: secure sessions and baseline security headers.
 */

/**
 * 当前标签页会话 Token：不同标签页使用不同 Token，实现同浏览器多账号隔离。
 * @return string
 */
function tabToken()
{
    static $token = null;
    if ($token !== null) {
        return $token;
    }
    $candidates = [
        $_SERVER['HTTP_X_TAB_SESSION'] ?? '',
        $_GET['tab'] ?? '',
        $_POST['tab'] ?? '',
        $_COOKIE['TAB_SESSION'] ?? ''
    ];
    $token = '';
    foreach ($candidates as $candidate) {
        $candidate = trim((string)$candidate);
        if (preg_match('/^[A-Za-z0-9-]{8,64}$/', $candidate)) {
            $token = $candidate;
            break;
        }
    }
    return $token;
}

/**
 * Derive a server-side session id from a public tab token plus the browser secret.
 */
function tabSessionId($tab)
{
    $browserSecret = $_COOKIE['CAMPUS_TAB_SECRET'] ?? '';
    if (!preg_match('/^[a-f0-9]{64}$/', $browserSecret)) {
        $browserSecret = bin2hex(random_bytes(32));
        setcookie('CAMPUS_TAB_SECRET', $browserSecret, [
            'expires' => 0,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    return 'tab' . substr(hash_hmac('sha256', $tab, $browserSecret), 0, 32);
}

/**
 * Start a fresh tab session when a duplicated tab logs into another account.
 */
function rotateTabSession(array $sessionData)
{
    $newTab = 'tab' . bin2hex(random_bytes(16));
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    session_id(tabSessionId($newTab));
    ini_set('session.use_strict_mode', '0');
    session_start();
    $_SESSION = $sessionData;
    session_write_close();
    return $newTab;
}

/**
 * Start a session with hardened cookie settings.
 */
function secureSessionStart()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $tab = tabToken();
    if ($tab !== '') {
        session_id(tabSessionId($tab));
        // A new tab session has no file yet; strict mode would silently replace the ID.
        ini_set('session.use_strict_mode', '0');
    }
    if ($tab === '') {
        ini_set('session.use_strict_mode', '1');
    }
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? '1' : '0');
    session_start();

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfCookieName = $tab === '' ? 'CAMPUS_CSRF' : 'CAMPUS_CSRF_' . substr(hash('sha256', $tab), 0, 24);
    setcookie($csrfCookieName, $_SESSION['csrf_token'], [
        'expires' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

/**
 * 登录后刷新会话：使用标签页 Token 时不更换 session_id，避免 URL 会话失效。
 */
function sessionRegenerate()
{
    if (tabToken() !== '') {
        return;
    }
    session_regenerate_id(true);
}

/**
 * Send baseline security headers before any output.
 */
function sendSecurityHeaders()
{
    if (headers_sent()) {
        return;
    }
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cache-Control: no-cache, must-revalidate');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://udify.app; style-src 'self' 'unsafe-inline' https://udify.app; script-src 'self' 'unsafe-inline' https://udify.app; frame-src https://udify.app; connect-src 'self' https://udify.app; frame-ancestors 'self'; base-uri 'self'; form-action 'self'");
}

register_shutdown_function('logFatalErrors');
