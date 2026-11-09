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
// 异常 IP 判断要在记录本次登录之前做：maybeNotifyNewIpLogin 查询的是
// login_attempt 里的历史记录，本次记录写入后就查不出来了
if (is_array($loginResult)) {
    maybeNotifyNewIpLogin($userName, (int)$loginResult['pk'], $ip);
}
recordLoginAttempt($userName, $ip, is_array($loginResult));

if (is_array($loginResult)) {
    // 登录二步验证：开关关闭时下面这段零开销（不查 student 表），登录路径与原来一致
    $pendingEmail = '';
    if (loginEmailOtpEnabled()) {
        // email 在 student 表，checkLogin 返回的 user 行里没有
        $studentRow = getStudentByPk((int)$loginResult['pk']);
        $pendingEmail = $studentRow ? trim((string)$studentRow['email']) : '';
        // 受信任设备（30 天内通过过一次验证码）免收码，直接登录
        if ($pendingEmail !== '' && isTrustedDevice((int)$loginResult['pk'])) {
            $pendingEmail = '';
        }
    }
    if ($pendingEmail !== '') {
        // 二步验证：此时不建立会话、不发 token，pending 状态访问不了任何登录页
        $_SESSION['otpPendingPk'] = (int)$loginResult['pk'];
        $_SESSION['otpPendingName'] = $loginResult['userName'];
        unset($_SESSION['adminName'], $_SESSION['adminMustChangePassword']);
        $sendResult = createLoginOtp((int)$loginResult['pk'], $pendingEmail);
        if ($sendResult !== true) {
            unset($_SESSION['otpPendingPk'], $_SESSION['otpPendingName']);
            $_SESSION['login_error'] = $sendResult;
            header('Location: ' . tabUrl('p_loginStu.php'));
            exit;
        }
        $_SESSION['otp_notice'] = '验证码已发送至 ' . maskContact($pendingEmail) . '，10 分钟内有效。';
        header('Location: ' . tabUrl('p_verifyLoginOtp.php'));
        exit;
    }
    $sessionResult = establishStudentSession($loginResult);
    if ($sessionResult['rotated']) {
        header('Location: p_welcomeStu.php?tab=' . rawurlencode($sessionResult['tab']) . '&rotate=1');
    } else {
        header('Location: ' . tabUrl('p_welcomeStu.php'));
    }
    exit;
}
$_SESSION['login_error'] = $loginResult;
header('Location: ' . tabUrl('p_loginStu.php'));
exit;
