<?php

require_once __DIR__ . '/bootstrap.php';
/**
 * Core database, schema and CSRF helpers.
 */


/**
 * 数据库连接函数
 * @param bool $withDatabase 是否连接指定数据库
 * @return mysqli 返回数据库连接对象
 */
function dbConnect($withDatabase = true)
{
    $dbName = $withDatabase ? DBNAME : '';
    $conn = new mysqli(HOST, USERNAME, PASSWORD, $dbName, PORT);
    $conn->set_charset(CHARSET);
    return $conn;
}



/**
 * 生成CSRF令牌（跨站请求伪造防护）
 * @return string CSRF令牌值
 */
function csrfCookieName()
{
    $tab = tabToken();
    if ($tab === '') {
        return 'CAMPUS_CSRF';
    }
    return 'CAMPUS_CSRF_' . substr(hash('sha256', $tab), 0, 24);
}

function csrfToken()
{
    if (session_status() === PHP_SESSION_NONE) {
        secureSessionStart();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


/**
 * Generate the hidden CSRF form field.
 * @return string
 */
function csrfField()
{
    $html = '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
    $tab = tabToken();
    if ($tab !== '') {
        $html .= '<input type="hidden" name="tab" value="' . h($tab) . '">';
    }
    return $html;
}


/**
 * Verify the CSRF token from the session or the tab-scoped CSRF cookie.
 * @return bool
 */
function verifyCsrf()
{
    if (session_status() === PHP_SESSION_NONE) {
        secureSessionStart();
    }
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return false;
    }
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        return true;
    }
    $cookie = $_COOKIE[csrfCookieName()] ?? '';
    return is_string($cookie) && hash_equals($cookie, $token);
}


function tableExists($conn, $table)
{
    $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
    $db = DBNAME;
    $stmt->bind_param('ss', $db, $table);
    $stmt->execute();
    $count = 0;
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return (int)$count > 0;
}


/**
 * 确保数据库表结构存在（自动初始化）
 * 首次访问时自动创建数据库和所有表，并初始化管理员账号
 */
function ensureSchema()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    runMigrations();

    $conn = dbConnect();
    $admin = getUserByNameRaw($conn, 'admin');
    $adminHash = password_hash(ADMIN_INITIAL_PASSWORD, PASSWORD_DEFAULT);
    if (!$admin) {
        $stmt = $conn->prepare("INSERT INTO `user` (`userName`, `password`, `userType`, `status`) VALUES ('admin', ?, 'a', 'V')");
        $stmt->bind_param('s', $adminHash);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}
