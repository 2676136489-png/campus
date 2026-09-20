<?php
// 安全配置：防止Session劫持和固定攻击
require_once __DIR__ . '/lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/p_manageDB.php';

unset($_SESSION['login_error']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf()) {
    $_SESSION['login_error'] = 'Invalid form token. Please try again.';
    header('Location: ' . tabUrl('p_loginStu.php'));
    exit;
}

if (empty($_POST['agreeTerms'])) {
    $_SESSION['login_error'] = '请先勾选已阅读并同意《用户协议》和《隐私政策》。';
    header('Location: ' . tabUrl('p_loginStu.php'));
    exit;
}

if (!verifyCaptcha($_POST['captcha'] ?? '')) {
    $_SESSION['login_error'] = '验证码错误，请重试。';
    header('Location: ' . tabUrl('p_loginStu.php'));
    exit;
}

$userName = isset($_POST['userName']) ? trim($_POST['userName']) : '';
$pwd = isset($_POST['pwd']) ? trim($_POST['pwd']) : '';
$ip = clientIp();

if ($userName === '' || $pwd === '') {
    $_SESSION['login_error'] = '请填写用户名和密码。';
    header('Location: ' . tabUrl('p_loginStu.php'));
    exit;
}

if (isLoginRateLimited($userName, $ip)) {
    recordLoginAttempt($userName, $ip, false);
    $_SESSION['login_error'] = '登录尝试次数过多，请 15 分钟后再试。';
    header('Location: ' . tabUrl('p_loginStu.php'));
    exit;
}

$loginResult = checkLogin($userName, $pwd);
recordLoginAttempt($userName, $ip, is_array($loginResult));

if (is_array($loginResult)) {
    $previousUser = $_SESSION['userName'] ?? '';
    if ($previousUser !== '' && $previousUser !== $loginResult['userName']) {
        $rotatedData = $_SESSION;
        unset($rotatedData['adminName'], $rotatedData['adminMustChangePassword']);
        $rotatedData['userName'] = $loginResult['userName'];
        $rotatedData['auth_token'] = createActiveSessionToken((int)$loginResult['pk']);
        $newTab = rotateTabSession($rotatedData);
        header('Location: p_welcomeStu.php?tab=' . rawurlencode($newTab) . '&rotate=1');
        exit;
    }
    unset($_SESSION['adminName'], $_SESSION['adminMustChangePassword']);
    $_SESSION['userName'] = $loginResult['userName'];
    issueActiveSessionToken((int)$loginResult['pk']);
    sessionRegenerate();
    header('Location: ' . tabUrl('p_welcomeStu.php'));
    exit;
}
$_SESSION['login_error'] = $loginResult;
header('Location: ' . tabUrl('p_loginStu.php'));
exit;
