<?php
require_once __DIR__ . '/lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

// 没有待验证的登录（没走密码步骤），回登录页
if (empty($_SESSION['otpPendingPk'])) {
    header('Location: ' . tabUrl('p_loginStu.php'));
    exit;
}

$pendingPk = (int)$_SESSION['otpPendingPk'];
$error = '';
$notice = isset($_SESSION['otp_notice']) ? (string)$_SESSION['otp_notice'] : '';
unset($_SESSION['otp_notice']);

$student = getStudentByPk($pendingPk);
// 验证途中账号被删除或停用：作废 pending，回登录页
if (!$student || $student['status'] === 'U') {
    unset($_SESSION['otpPendingPk'], $_SESSION['otpPendingName']);
    $_SESSION['login_error'] = '账号不可用，请重新登录。';
    header('Location: ' . tabUrl('p_loginStu.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } elseif (isset($_POST['cancel'])) {
        // 放弃本次登录
        unset($_SESSION['otpPendingPk'], $_SESSION['otpPendingName']);
        header('Location: ' . tabUrl('p_loginStu.php'));
        exit;
    } elseif (isset($_POST['resend'])) {
        $result = createLoginOtp($pendingPk, trim((string)$student['email']));
        if ($result === true) {
            $notice = '验证码已重新发送至 ' . maskContact((string)$student['email']) . '。';
        } else {
            $error = $result;
        }
    } elseif (isset($_POST['verifyOtp'])) {
        $result = verifyLoginOtp($pendingPk, $_POST['code'] ?? '');
        if (!empty($result['ok'])) {
            // 第二个参数 true：信任本设备，30 天内登录免验证码
            $sessionResult = establishStudentSession([
                'pk' => $pendingPk,
                'userName' => $student['userName'],
            ], true);
            if ($sessionResult['rotated']) {
                header('Location: p_welcomeStu.php?tab=' . rawurlencode($sessionResult['tab']) . '&rotate=1');
            } else {
                header('Location: ' . tabUrl('p_welcomeStu.php'));
            }
            exit;
        }
        $error = $result['message'];
        if (!empty($result['restart'])) {
            // 尝试次数耗尽：作废 pending，回登录页重走密码流程
            unset($_SESSION['otpPendingPk'], $_SESSION['otpPendingName']);
            $_SESSION['login_error'] = $error;
            header('Location: ' . tabUrl('p_loginStu.php'));
            exit;
        }
    }
}

renderAuthShell([
    'title' => '二步验证',
    'description' => '密码验证通过后，输入发送到绑定邮箱的验证码完成登录。验证通过后本设备 30 天内登录免验证码。',
    'quote' => '多一步验证，多一分安心。',
]);
?>
<h1 class="auth-title">二步验证</h1>
<p class="auth-sub">验证码已发送至 <?=h(maskContact((string)$student['email']))?>，输入以完成登录。</p>

<?php if ($notice !== ''): ?>
    <div class="alert alert-info"><?=icon('info')?><div><?=h($notice)?></div></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
<?php endif; ?>

<form method="POST" autocomplete="off">
    <?=csrfField()?>
    <div class="form-field">
        <label class="form-label" for="otpCode">邮箱验证码</label>
        <input class="input" type="text" name="code" id="otpCode" required
               inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
               autocomplete="one-time-code" placeholder="6 位数字验证码">
        <div class="form-hint">验证码 10 分钟内有效，连续输错 5 次将作废并需重新登录。</div>
    </div>
    <button type="submit" name="verifyOtp" value="1" class="btn btn-block"><?=icon('check')?>验证并登录</button>
</form>

<div class="auth-links">
    <form method="POST" style="display:inline">
        <?=csrfField()?>
        <button type="submit" name="resend" value="1" class="btn btn-soft"><?=icon('mail')?>重新发送</button>
    </form>
    <form method="POST" style="display:inline">
        <?=csrfField()?>
        <button type="submit" name="cancel" value="1" class="btn btn-ghost"><?=icon('arrow-left')?>返回登录</button>
    </form>
</div>

<div class="auth-rule"><?=icon('shield')?><span>没有收到？请检查邮箱垃圾箱，或稍后使用「重新发送」（60 秒一次）。</span></div>
<?php renderAuthShellClose(); ?>
