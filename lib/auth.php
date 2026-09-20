<?php

require_once __DIR__ . '/bootstrap.php';
/**
 * Authentication, sessions, rate limiting and password recovery.
 */


/**
 * 根据用户名查询用户（原始连接版本）
 * @param mysqli $conn 数据库连接
 * @param string $userName 用户名
 * @return array|null 用户数据数组或null
 */
function getUserByNameRaw($conn, $userName)
{
    $stmt = $conn->prepare("SELECT `pk`, `userName`, `password`, `userType`, `status`, `createTime`, `totp_secret`, `totp_enabled`, `adminRole` FROM `user` WHERE `userName` = ?");
    $stmt->bind_param('s', $userName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}


/**
 * 根据用户名查询用户
 * @param string $userName 用户名
 * @return array|null 用户数据数组或null
 */
function getUserByUserName($userName)
{
    $conn = dbConnect();
    $row = getUserByNameRaw($conn, trim($userName));
    $conn->close();
    return $row;
}


/**
 * 根据用户名获取用户ID
 * @param string $userName 用户名
 * @return int 用户ID
 */
function getUserIdByUserName($userName)
{
    $user = getUserByUserName($userName);
    return $user ? (int)$user['pk'] : 0;
}


/**
 * 学生登录验证
 * @param string $userName 用户名
 * @param string $pwd 密码
 * @return array|string 成功返回用户数据，失败返回错误信息
 */
function checkLogin($userName, $pwd)
{
    $conn = dbConnect();
    $user = getUserByNameRaw($conn, trim($userName));
    $conn->close();

    if (!$user || $user['userType'] !== 's') {
        return '学生账号不存在。';
    }
    if ($user['status'] === 'U') {
        return '账号已被停用或注销，请联系管理员。';
    }
    if (!password_verify($pwd, $user['password'])) {
        return '密码错误。';
    }
    return $user;
}


/**
 * 管理员登录验证
 * @param string $userName 用户名
 * @param string $pwd 密码
 * @return array|string 成功返回用户数据，失败返回错误信息
 */
function checkAdminLogin($userName, $pwd)
{
    $conn = dbConnect();
    $user = getUserByNameRaw($conn, trim($userName));
    $conn->close();

    if (!$user || $user['userType'] !== 'a' || $user['status'] !== 'V') {
        return '管理员账号不存在或不可用。';
    }
    if (!password_verify($pwd, $user['password'])) {
        return '管理员密码错误。';
    }
    return $user;
}


/**
 * 检查管理员是否使用初始密码
 * @param string $userName 用户名
 * @return bool 是否使用初始密码
 */
function isInitialAdminPassword($userName)
{
    $user = getUserByUserName($userName);
    return $user
        && $user['userType'] === 'a'
        && password_verify(ADMIN_INITIAL_PASSWORD, $user['password']);
}


/**
 * 修改管理员密码
 * @param string $userName 用户名
 * @param string $oldPwd 旧密码
 * @param string $newPwd 新密码
 * @return true|string 成功返回true，失败返回错误信息
 */
function updateAdminPassword($userName, $oldPwd, $newPwd)
{
    $user = getUserByUserName($userName);
    if (!$user || $user['userType'] !== 'a' || !password_verify($oldPwd, $user['password'])) {
        return 'Current password is incorrect.';
    }
    if (strlen($newPwd) < 8 || !preg_match('/[A-Za-z]/', $newPwd) || !preg_match('/\d/', $newPwd)) {
        return 'New password must be at least 8 characters and include letters and numbers.';
    }
    if (hash_equals($oldPwd, $newPwd) || hash_equals(ADMIN_INITIAL_PASSWORD, $newPwd)) {
        return 'New password cannot be the initial password.';
    }

    $hash = password_hash($newPwd, PASSWORD_DEFAULT);
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `user` SET `password` = ? WHERE `pk` = ? AND `userType` = 'a'");
    $stmt->bind_param('si', $hash, $user['pk']);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $ok ? true : 'Password update failed.';
}


/**
 * 要求学生登录（会话验证）
 * @return array 学生数据
 */

/**
 * Create a fresh active-session token for a user and store it in the database.
 * @param int $userPk
 * @return string
 */
function createActiveSessionToken($userPk)
{
    $token = bin2hex(random_bytes(32));
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `user` SET `active_token` = ?, `active_token_time` = NOW() WHERE `pk` = ?");
    $stmt->bind_param('si', $token, $userPk);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return $token;
}

/**
 * Issue a new active-session token and attach it to the current session.
 * @param int $userPk
 * @return string
 */
function issueActiveSessionToken($userPk)
{
    $token = createActiveSessionToken($userPk);
    $_SESSION['auth_token'] = $token;
    return $token;
}

/**
 * Check whether the current session token is still the active one for the user.
 * @param int $userPk
 * @param string $token
 * @return bool
 */
function verifyActiveSessionToken($userPk, $token)
{
    if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return false;
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `active_token` FROM `user` WHERE `pk` = ?");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row !== null && hash_equals((string)$row['active_token'], $token);
}

/**
 * Clear the active token on logout so the session cannot be reused.
 * @param int $userPk
 * @param string $token
 */
function clearActiveSessionToken($userPk, $token)
{
    if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return;
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `user` SET `active_token` = '' WHERE `pk` = ? AND `active_token` = ?");
    $stmt->bind_param('is', $userPk, $token);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

function requireStudentLogin()
{
    if (session_status() === PHP_SESSION_NONE) {
        secureSessionStart();
    }
    if (!isset($_SESSION['userName'])) {
        header('Location: ' . tabUrl('p_loginStu.php'));
        exit;
    }
    $student = getCurrentStudent($_SESSION['userName']);
    if (!$student || $student['status'] === 'U' || !verifyActiveSessionToken((int)$student['pk'], $_SESSION['auth_token'] ?? '')) {
        session_unset();
        session_destroy();
        header('Location: ' . tabUrl('p_loginStu.php'));
        exit;
    }
    return $student;
}


/**
 * 要求学生已实名验证
 * @return array 学生数据
 */
function requireVerifiedStudent()
{
    $student = requireStudentLogin();
    if ($student['status'] !== 'V') {
        $_SESSION['flash_error'] = '你的实名信息仍在审核中，通过后才能使用该功能。';
        header('Location: ' . tabUrl('p_welcomeStu.php'));
        exit;
    }
    return $student;
}


/**
 * 要求管理员登录
 * @return string 管理员用户名
 */
function requireAdminLogin()
{
    if (session_status() === PHP_SESSION_NONE) {
        secureSessionStart();
    }
    if (!isset($_SESSION['adminName'])) {
        header('Location: ' . tabUrl('p_adminAuth.php'));
        exit;
    }
    $admin = getUserByUserName($_SESSION['adminName']);
    if (!$admin || !verifyActiveSessionToken((int)$admin['pk'], $_SESSION['auth_token'] ?? '')) {
        session_unset();
        session_destroy();
        header('Location: ' . tabUrl('p_adminAuth.php'));
        exit;
    }
    return $_SESSION['adminName'];
}

/**
 * 获取管理员角色
 * @param string $userName 管理员用户名
 * @return string super|operator
 */
function getAdminRole($userName)
{
    $row = getUserByUserName($userName);
    if (!$row || $row['userType'] !== 'a') {
        return 'operator';
    }
    return (string)($row['adminRole'] ?? 'operator');
}

/**
 * 要求超级管理员权限
 * @param string $role 所需角色
 * @return void
 */
function requireAdminRole($role = 'super')
{
    requireAdminLogin();
    if (getAdminRole($_SESSION['adminName']) !== $role) {
        $_SESSION['admin_flash'] = ['msg' => '该功能仅超级管理员可用。', 'type' => 'error'];
        header('Location: ' . tabUrl('p_adminAuth.php'));
        exit;
    }
}

/**
 * 根据用户ID验证密码
 * @param int $userPk 用户ID
 * @param string $pwd 密码
 * @return bool 是否验证通过
 */
function verifyPasswordByPk($userPk, $pwd)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `password` FROM `user` WHERE `pk` = ?");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $stmt->bind_result($hash);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
    return $hash && password_verify($pwd, $hash);
}


/**
 * 修改学生密码
 * @param int $userPk 学生ID
 * @param string $oldPwd 旧密码
 * @param string $newPwd 新密码
 * @return true|string
 */
function changeStudentPassword($userPk, $oldPwd, $newPwd)
{
    if (!verifyPasswordByPk($userPk, $oldPwd)) {
        return '当前密码不正确。';
    }
    if (strlen($newPwd) < 6 || !preg_match('/[A-Za-z]/', $newPwd) || !preg_match('/\d/', $newPwd)) {
        return '新密码至少 6 位，并同时包含字母和数字。';
    }
    if (hash_equals($oldPwd, $newPwd)) {
        return '新密码不能与当前密码相同。';
    }
    $hash = password_hash($newPwd, PASSWORD_DEFAULT);
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `user` SET `password` = ? WHERE `pk` = ? AND `userType` = 's'");
    $stmt->bind_param('si', $hash, $userPk);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();
    $conn->close();
    return $ok ? true : '密码修改失败。';
}


/**
 * 用户名是否可用
 * @param string $userName
 * @param int $excludePk 排除的用户ID
 * @return bool
 */
function isUserNameAvailable($userName, $excludePk = 0)
{
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', trim($userName))) {
        return false;
    }
    if (isReservedUserName($userName)) {
        return false;
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `user` WHERE `userName` = ? AND `pk` != ?");
    $stmt->bind_param('si', $userName, $excludePk);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $conn->close();
    return $count === 0;
}


/**
 * 修改学生用户名
 * @param int $userPk 学生用户ID
 * @param string $newUserName 新用户名
 * @return true|string
 */
function updateStuUserName($userPk, $newUserName)
{
    $newUserName = trim($newUserName);
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $newUserName)) {
        return '用户名需为 3-30 位字母、数字或下划线。';
    }
    if (isReservedUserName($newUserName)) {
        return '该用户名已被系统保留。';
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `userName` FROM `user` WHERE `pk` = ? AND `userType` = 's'");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        $conn->close();
        return '学生账号不存在。';
    }
    if ($row['userName'] === $newUserName) {
        $conn->close();
        return true;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `user` WHERE `userName` = ? AND `pk` != ?");
    $stmt->bind_param('si', $newUserName, $userPk);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    if ($count > 0) {
        $conn->close();
        return '该用户名已被占用。';
    }
    $stmt = $conn->prepare("UPDATE `user` SET `userName` = ? WHERE `pk` = ? AND `userType` = 's'");
    $stmt->bind_param('si', $newUserName, $userPk);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}


/**
 * 发送密码重置验证码（开发模式写入日志并返回，生产模式接入短信/邮件服务商）
 * @param string $contact 手机号或邮箱
 * @param string $code 验证码
 * @param string $channel phone/email
 * @return bool
 */
function sendPasswordResetCode($contact, $code, $channel)
{
    if (APP_ENV === 'development') {
        $logFile = dirname(__DIR__) . '/password_reset.log';
        @file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $channel . ' -> ' . $contact . ' code=' . $code . PHP_EOL, FILE_APPEND);
        return true;
    }
    // 生产环境在这里接入短信/邮件服务商，例如发送模板消息
    return false;
}


/**
 * 发起密码重置
 * @param string $userName 用户名
 * @param string $channel phone/email
 * @return array
 */
function requestPasswordReset($userName, $channel)
{
    if (!in_array($channel, ['phone', 'email'], true)) {
        return ['ok' => false, 'message' => '请选择找回方式。'];
    }
    $student = getCurrentStudent(trim($userName));
    if (!$student || $student['status'] === 'U') {
        return ['ok' => false, 'message' => '账号不存在或不可用。'];
    }
    $contact = $channel === 'phone' ? $student['phone'] : $student['email'];
    if ($contact === '') {
        return ['ok' => false, 'message' => '该账号没有绑定' . ($channel === 'phone' ? '手机号' : '邮箱') . '。'];
    }
    if (isLoginRateLimited('reset:' . $student['userName'], clientIp())) {
        return ['ok' => false, 'message' => '发送过于频繁，请稍后再试。'];
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `createTime` FROM `password_reset` WHERE `userPk` = ? AND `channel` = ? ORDER BY `pk` DESC LIMIT 1");
    $stmt->bind_param('is', $student['pk'], $channel);
    $stmt->execute();
    $last = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($last && strtotime($last['createTime']) > time() - 60) {
        $conn->close();
        return ['ok' => false, 'message' => '发送过于频繁，请 60 秒后再试。'];
    }

    $code = (string)random_int(100000, 999999);
    $codeHash = password_hash($code, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("DELETE FROM `password_reset` WHERE `userPk` = ? AND `channel` = ?");
    $stmt->bind_param('is', $student['pk'], $channel);
    $stmt->execute();
    $stmt->close();

    $expiresAt = date('Y-m-d H:i:s', time() + 600);
    $stmt = $conn->prepare("INSERT INTO `password_reset` (`userPk`, `channel`, `contact`, `codeHash`, `expiresAt`) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('issss', $student['pk'], $channel, $contact, $codeHash, $expiresAt);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    if (!sendPasswordResetCode($contact, $code, $channel)) {
        return ['ok' => false, 'message' => '验证码发送失败，请稍后重试。'];
    }
    return [
        'ok' => true,
        'channel' => $channel,
        'contact' => $contact,
        'contactMasked' => maskContact($contact),
        'devCode' => APP_ENV === 'development' ? $code : ''
    ];
}


/**
 * 校验验证码并完成密码重置
 * @param string $userName 用户名
 * @param string $channel phone/email
 * @param string $code 验证码
 * @param string $newPwd 新密码
 * @return true|string
 */
function completePasswordReset($userName, $channel, $code, $newPwd)
{
    if (!in_array($channel, ['phone', 'email'], true)) {
        return '请选择找回方式。';
    }
    if (strlen($newPwd) < 6 || !preg_match('/[A-Za-z]/', $newPwd) || !preg_match('/\d/', $newPwd)) {
        return '新密码至少 6 位，并同时包含字母和数字。';
    }
    $student = getCurrentStudent(trim($userName));
    if (!$student) {
        return '账号不存在。';
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `pk`, `codeHash`, `attempts`, `expiresAt` FROM `password_reset` WHERE `userPk` = ? AND `channel` = ? AND `used` = 0 ORDER BY `pk` DESC LIMIT 1");
    $stmt->bind_param('is', $student['pk'], $channel);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        $conn->close();
        return '验证码不存在，请重新发送。';
    }
    if ((int)$row['attempts'] >= 5) {
        $conn->close();
        return '验证码尝试次数过多，请重新发送。';
    }
    if (strtotime($row['expiresAt']) < time()) {
        $stmt = $conn->prepare("UPDATE `password_reset` SET `used` = 1 WHERE `pk` = ?");
        $stmt->bind_param('i', $row['pk']);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        return '验证码已过期，请重新发送。';
    }
    if (!password_verify($code, $row['codeHash'])) {
        $stmt = $conn->prepare("UPDATE `password_reset` SET `attempts` = `attempts` + 1 WHERE `pk` = ?");
        $stmt->bind_param('i', $row['pk']);
        $stmt->execute();
        $stmt->close();
        $conn->close();
        return '验证码错误。';
    }

    $hash = password_hash($newPwd, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE `user` SET `password` = ? WHERE `pk` = ? AND `userType` = 's'");
    $stmt->bind_param('si', $hash, $student['pk']);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("UPDATE `user` SET `active_token` = '' WHERE `pk` = ?");
    $stmt->bind_param('i', $student['pk']);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("UPDATE `password_reset` SET `used` = 1 WHERE `pk` = ?");
    $stmt->bind_param('i', $row['pk']);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}



/**
 * 记录登录尝试
 * @param string $loginName 登录名
 * @param string $ip IP地址
 * @param bool $success 是否成功
 */
function recordLoginAttempt($loginName, $ip, $success)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `login_attempt` (`loginName`, `ip`, `success`) VALUES (?, ?, ?)");
    $successInt = $success ? 1 : 0;
    $stmt->bind_param('ssi', $loginName, $ip, $successInt);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    if (random_int(1, 100) === 1) {
        cleanupExpiredRecords();
    }
}


/**
 * 登录限流：同一IP 15分钟内失败20次，或同一账号失败10次，则暂时锁定
 * @param string $loginName 登录名
 * @param string $ip IP地址
 * @return bool 是否被限流
 */
function isLoginRateLimited($loginName, $ip)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `login_attempt` WHERE `ip` = ? AND `success` = 0 AND `createTime` > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->bind_param('s', $ip);
    $stmt->execute();
    $byIp = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    if ($byIp >= LOGIN_RATE_IP_FAILURES) {
        $conn->close();
        return true;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `login_attempt` WHERE `loginName` = ? AND `success` = 0 AND `createTime` > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->bind_param('s', $loginName);
    $stmt->execute();
    $byName = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $conn->close();
    return $byName >= LOGIN_RATE_NAME_FAILURES;
}

/**
 * Base32 编码
 */
function base32Encode($data)
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    foreach (str_split($data) as $char) {
        $binary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
    }
    $result = '';
    foreach (str_split($binary, 5) as $chunk) {
        $result .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
    }
    return $result;
}

/**
 * Base32 解码
 */
function base32Decode($base32)
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32 = strtoupper(trim($base32));
    $binary = '';
    foreach (str_split($base32) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) {
            return '';
        }
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $result = '';
    foreach (str_split($binary, 8) as $chunk) {
        if (strlen($chunk) === 8) {
            $result .= chr(bindec($chunk));
        }
    }
    return $result;
}

/**
 * 生成 TOTP 密钥（Base32）
 */
function generateTotpSecret($length = 20)
{
    return base32Encode(random_bytes($length));
}

/**
 * 生成指定时间片的 6 位动态码
 */
function totpCode($secret, $offset = 0)
{
    $key = base32Decode($secret);
    if ($key === '') {
        return '';
    }
    $counter = floor(time() / 30) + $offset;
    $hash = hash_hmac('sha1', pack('N*', 0, $counter), $key, true);
    $offsetByte = ord(substr($hash, -1)) & 0x0F;
    $code = unpack('N', substr($hash, $offsetByte, 4))[1] & 0x7FFFFFFF;
    return str_pad((string)($code % 1000000), 6, '0', STR_PAD_LEFT);
}

/**
 * 校验 TOTP 动态码（允许前后各 1 个时间片）
 */
function verifyTotp($secret, $code)
{
    $code = trim((string)$code);
    if (!preg_match('/^\d{6}$/', $code)) {
        return false;
    }
    for ($offset = -1; $offset <= 1; $offset++) {
        if (hash_equals(totpCode($secret, $offset), $code)) {
            return true;
        }
    }
    return false;
}

/**
 * 是否已开启管理员 TOTP
 */
function isAdminTotpEnabled($userName)
{
    $row = getUserByUserName($userName);
    return $row && (int)($row['totp_enabled'] ?? 0) === 1;
}

/**
 * 读取管理员 TOTP 密钥
 */
function getAdminTotpSecret($userName)
{
    $row = getUserByUserName($userName);
    return $row ? (string)($row['totp_secret'] ?? '') : '';
}

/**
 * 生成 OTPAUTH URI
 */
function buildTotpUri($userName, $secret)
{
    return 'otpauth://totp/CampusCircle:' . rawurlencode($userName)
        . '?secret=' . rawurlencode($secret)
        . '&issuer=CampusCircle';
}

/**
 * 为管理员生成并暂存 TOTP 密钥
 */
function provisionAdminTotp($userName)
{
    $secret = generateTotpSecret();
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `user` SET `totp_secret` = ? WHERE `userName` = ? AND `userType` = 'a'");
    $stmt->bind_param('ss', $secret, $userName);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $ok ? ['secret' => $secret, 'url' => buildTotpUri($userName, $secret)] : false;
}

/**
 * 启用管理员 TOTP
 */
function enableAdminTotp($userName)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `user` SET `totp_enabled` = 1 WHERE `userName` = ? AND `userType` = 'a' AND `totp_secret` IS NOT NULL");
    $stmt->bind_param('s', $userName);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $ok;
}

/**
 * 关闭管理员 TOTP
 */
function disableAdminTotp($userName)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `user` SET `totp_enabled` = 0, `totp_secret` = NULL WHERE `userName` = ? AND `userType` = 'a'");
    $stmt->bind_param('s', $userName);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $ok;
}

/**
 * 创建管理员账号
 * @param string $userName
 * @param string $password
 * @param string $role
 * @param int $operatorPk
 * @return true|string
 */
function createAdminUser($userName, $password, $role, $operatorPk)
{
    $userName = trim($userName);
    if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $userName)) {
        return '用户名需为 3-30 位字母、数字或下划线。';
    }
    if (isReservedUserName($userName)) {
        return '该用户名已被系统保留。';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        return '密码至少 8 位，并同时包含字母和数字。';
    }
    if (!in_array($role, ['super', 'operator'], true)) {
        return '角色不正确。';
    }
    if (getUserByUserName($userName)) {
        return '该管理员账号已存在。';
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `user` (`userName`, `password`, `userType`, `status`, `adminRole`) VALUES (?, ?, 'a', 'V', ?)");
    $stmt->bind_param('sss', $userName, $hash, $role);
    $ok = $stmt->execute();
    $pk = $stmt->insert_id;
    $stmt->close();
    $conn->close();
    if ($ok && $operatorPk > 0) {
        logAudit((int)$operatorPk, 'user', $pk, 'create_admin', $userName);
    }
    return $ok ? true : '管理员创建失败。';
}

/**
 * 管理员账号列表
 * @return array
 */
function getAdminUsers()
{
    $conn = dbConnect();
    $rows = $conn->query("SELECT `pk`, `userName`, `status`, `adminRole`, `createTime` FROM `user` WHERE `userType` = 'a' ORDER BY `pk` ASC")->fetch_all(MYSQLI_ASSOC);
    $conn->close();
    return $rows ?: [];
}

/**
 * 修改管理员角色
 * @param string $userName
 * @param string $role
 * @param int $operatorPk
 * @return true|string
 */
function setAdminRole($userName, $role, $operatorPk)
{
    if (!in_array($role, ['super', 'operator'], true)) {
        return '角色不正确。';
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `user` SET `adminRole` = ? WHERE `userName` = ? AND `userType` = 'a'");
    $stmt->bind_param('ss', $role, $userName);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    if ($ok && $operatorPk > 0) {
        logAudit((int)$operatorPk, 'user', 0, 'set_admin_role', $userName . ' -> ' . $role);
    }
    return $ok ? true : '角色修改失败。';
}

/**
 * 停用/启用管理员账号
 * @param string $userName
 * @param string $status
 * @param int $operatorPk
 * @return true|string
 */
function setAdminStatus($userName, $status, $operatorPk)
{
    if (!in_array($status, ['V', 'U'], true)) {
        return '状态不正确。';
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `user` SET `status` = ? WHERE `userName` = ? AND `userType` = 'a'");
    $stmt->bind_param('ss', $status, $userName);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    if ($ok && $operatorPk > 0) {
        logAudit((int)$operatorPk, 'user', 0, 'set_admin_status', $userName . ' -> ' . $status);
    }
    return $ok ? true : '状态修改失败。';
}
