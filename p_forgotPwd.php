<?php
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

$error = '';
$success = false;
$resetUser = $_SESSION['reset_user'] ?? '';
$resetChannel = $_SESSION['reset_channel'] ?? '';
$contactMasked = $_SESSION['reset_contact'] ?? '';
$devCode = $_SESSION['reset_dev_code'] ?? '';

if (isset($_GET['change'])) {
    unset($_SESSION['reset_user'], $_SESSION['reset_channel'], $_SESSION['reset_contact'], $_SESSION['reset_dev_code']);
    $resetUser = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } elseif (isset($_POST['requestReset'])) {
        $userName = trim($_POST['userName'] ?? '');
        $channel = $_POST['channel'] ?? '';
        if (!verifyCaptcha($_POST['captcha'] ?? '')) {
            $error = '验证码错误，请重试。';
        } else {
            $result = requestPasswordReset($userName, $channel);
            if (!empty($result['ok'])) {
                $_SESSION['reset_user'] = $userName;
                $_SESSION['reset_channel'] = $channel;
                $_SESSION['reset_contact'] = $result['contactMasked'];
                $_SESSION['reset_dev_code'] = $result['devCode'];
                $resetUser = $userName;
                $resetChannel = $channel;
                $contactMasked = $result['contactMasked'];
                $devCode = $result['devCode'];
            } else {
                $error = $result['message'] ?? '验证码发送失败。';
            }
        }
    } elseif (isset($_POST['verifyReset'])) {
        $userName = trim($_POST['userName'] ?? $resetUser);
        $channel = $_POST['channel'] ?? $resetChannel;
        $code = trim($_POST['code'] ?? '');
        $newPwd = $_POST['newPwd'] ?? '';
        $confirmPwd = $_POST['confirmPwd'] ?? '';
        if ($newPwd !== $confirmPwd) {
            $error = '两次输入的新密码不一致。';
        } else {
            $result = completePasswordReset($userName, $channel, $code, $newPwd);
            if ($result === true) {
                unset($_SESSION['reset_user'], $_SESSION['reset_channel'], $_SESSION['reset_contact'], $_SESSION['reset_dev_code']);
                $success = true;
                $resetUser = '';
            } else {
                $error = $result;
            }
        }
    }
}

renderAuthShell([
    'title' => '找回密码',
    'description' => '通过手机短信或邮箱验证码重置校园圈子账号密码。',
    'quote' => '验证身份，重新找回你的校园账号。',
]);

if ($success):
?>
<h1 class="auth-title">密码已重置</h1>
<p class="auth-sub">你的密码已经更新，可以使用新密码登录了。</p>
<div class="alert alert-success"><?=icon('check')?><div>密码重置成功。</div></div>
<div class="auth-links">
    <a class="btn btn-block" href="p_loginStu.php"><?=icon('arrow-left')?>返回登录</a>
</div>
<?php elseif ($resetUser === ''): ?>
<h1 class="auth-title">找回密码</h1>
<p class="auth-sub">输入用户名并选择验证方式，我们会把验证码发送到你的手机或邮箱。</p>

<?php if ($error): ?>
    <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
<?php endif; ?>

<form method="POST">
    <?=csrfField()?>
    <div class="form-field">
        <label class="form-label" for="resetUser">用户名</label>
        <input class="input" type="text" name="userName" id="resetUser" required minlength="3" maxlength="30" autocomplete="username" placeholder="请输入账号名称">
    </div>
    <div class="form-field">
        <span class="form-label">验证方式</span>
        <div class="radio-pills method-pills">
            <label class="radio-pill">
                <input type="radio" name="channel" value="phone" checked>
                <span><?=icon('phone', 15)?>手机短信</span>
            </label>
            <label class="radio-pill">
                <input type="radio" name="channel" value="email">
                <span><?=icon('mail', 15)?>邮箱验证</span>
            </label>
        </div>
        <div class="form-hint">验证码 10 分钟内有效，请尽快完成重置。</div>
    </div>
    <div class="form-field">
        <label class="form-label" for="captcha">验证码</label>
        <div class="captcha-row">
            <img class="captcha-image" src="p_captcha.php" alt="验证码" title="看不清？点击图片或换一张" data-captcha-image>
            <button type="button" class="btn btn-ghost btn-sm" data-captcha-refresh>换一张</button>
        </div>
        <input class="input" type="text" name="captcha" id="captcha" required maxlength="6" autocomplete="off" placeholder="输入图片中的字符" autocapitalize="characters" spellcheck="false">
    </div>
    <button type="submit" name="requestReset" class="btn btn-block"><?=icon('send')?>发送验证码</button>
</form>

<div class="auth-links">
    <a class="btn btn-soft" href="p_loginStu.php"><?=icon('arrow-left')?>返回登录</a>
</div>
<?php else: ?>
<h1 class="auth-title">输入验证码</h1>
<p class="auth-sub">验证码已发送至 <?=h($contactMasked ?: ($resetChannel === 'phone' ? '手机' : '邮箱'))?>。</p>

<?php if ($error): ?>
    <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
<?php endif; ?>

<?php if ($devCode !== ''): ?>
    <div class="alert alert-info dev-hint"><?=icon('info')?><div>开发模式验证码：<strong><?=h($devCode)?></strong>，生产环境会通过短信或邮件发送。</div></div>
<?php endif; ?>

<form method="POST">
    <?=csrfField()?>
    <input type="hidden" name="userName" value="<?=h($resetUser)?>">
    <input type="hidden" name="channel" value="<?=h($resetChannel)?>">
    <div class="form-field">
        <label class="form-label" for="resetCode">验证码</label>
        <input class="input" type="text" name="code" id="resetCode" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="6 位数字验证码">
    </div>
    <div class="form-field">
        <label class="form-label" for="newPwd">新密码</label>
        <div class="password-wrap">
            <input class="input" type="password" name="newPwd" id="newPwd" required minlength="6" maxlength="72" autocomplete="new-password" placeholder="至少 6 位，包含字母和数字">
            <button class="js-toggle-pwd toggle-pwd" type="button" data-target="newPwd" aria-label="显示密码"><?=icon('eye')?></button>
        </div>
    </div>
    <div class="form-field">
        <label class="form-label" for="confirmPwd">确认新密码</label>
        <div class="password-wrap">
            <input class="input" type="password" name="confirmPwd" id="confirmPwd" required minlength="6" maxlength="72" autocomplete="new-password">
            <button class="js-toggle-pwd toggle-pwd" type="button" data-target="confirmPwd" aria-label="显示密码"><?=icon('eye')?></button>
        </div>
    </div>
    <button type="submit" name="verifyReset" class="btn btn-block"><?=icon('check')?>重置密码</button>
</form>

<div class="auth-links">
    <a class="btn btn-soft" href="p_forgotPwd.php?change=1"><?=icon('arrow-left')?>更换账号</a>
</div>
<?php endif; ?>

<?php renderAuthShellClose(); ?>