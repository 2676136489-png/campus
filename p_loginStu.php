<?php
require_once __DIR__ . '/lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

renderAuthShell([
    'title' => '学生登录',
    'description' => '登录校园圈子，查看同学资料、发布动态并参与校园讨论。',
    'quote' => '在这里，认识同一个校园里的人。',
]);
?>
<h1 class="auth-title">欢迎回来</h1>
<p class="auth-sub">登录后查看同学资料、发布动态，参与校园里的每一次讨论。</p>

<?php if (isset($_SESSION['login_error'])): ?>
    <div class="alert alert-error"><?=icon('alert')?><div><?=h($_SESSION['login_error'])?></div></div>
    <?php unset($_SESSION['login_error']); ?>
<?php endif; ?>

<form action="p_doLoginStu.php" method="POST" data-login-form>
    <?=csrfField()?>
    <div class="form-field">
        <label class="form-label" for="userName">用户名</label>
        <input class="input" type="text" name="userName" id="userName" required autocomplete="username" placeholder="请输入用户名">
    </div>
    <div class="form-field">
        <label class="form-label" for="pwd">密码</label>
        <div class="password-wrap">
            <input class="input" type="password" name="pwd" id="pwd" required autocomplete="current-password" placeholder="请输入密码">
            <button class="js-toggle-pwd toggle-pwd" type="button" data-target="pwd" aria-label="显示密码"><?=icon('eye')?></button>
        </div>
    </div>
    <div class="form-field">
        <label class="form-label" for="captcha">验证码</label>
        <div class="captcha-row">
            <img class="captcha-image" src="p_captcha.php" alt="验证码" title="看不清？点击图片或换一张" data-captcha-image>
            <button type="button" class="btn btn-ghost btn-sm" data-captcha-refresh>换一张</button>
        </div>
        <input class="input" type="text" name="captcha" id="captcha" required maxlength="6" autocomplete="off" placeholder="输入图片中的字符" autocapitalize="characters" spellcheck="false">
    </div>
    <div class="form-field">
        <label class="auth-consent">
            <input type="checkbox" name="agreeTerms" id="agreeTerms" value="1" required>
            <span>我已认真阅读并同意<a href="p_terms.php" target="_blank" rel="noopener">《用户协议》</a>和<a href="p_privacy.php" target="_blank" rel="noopener">《隐私政策》</a></span>
        </label>
    </div>
    <button type="submit" class="btn btn-block" data-login-submit><?=icon('send')?>登录</button>
</form>

<div class="auth-links">
    <a class="btn btn-soft" href="p_registerStu.php"><?=icon('plus')?>注册学生账号</a>
    <a href="p_forgotPwd.php">忘记密码？</a>
    <a href="p_adminAuth.php?logout=1">管理员入口</a>
</div>

<div class="auth-rule"><?=icon('shield')?><span>注册后需管理员实名审核，通过后即可发布动态、点赞和评论。</span></div>
<?php renderAuthShellClose(); ?>
