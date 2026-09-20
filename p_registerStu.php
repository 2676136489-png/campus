<?php
require_once __DIR__ . '/lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

renderAuthShell([
    'title' => '学生注册',
    'description' => '注册校园圈子学生账号并提交实名信息，等待管理员审核。',
    'quote' => '用真实身份，认识真实的校园。',
]);
?>
<h1 class="auth-title">创建学生账号</h1>
<p class="auth-sub">填写基础信息和实名资料，提交后由管理员审核，通过即可加入校园动态广场。</p>

<?php if (isset($_SESSION['reg_error'])): ?>
    <div class="alert alert-error"><?=icon('alert')?><div><?=h($_SESSION['reg_error'])?></div></div>
    <?php unset($_SESSION['reg_error']); ?>
<?php endif; ?>

<form action="p_doRegisterStu.php" method="POST" enctype="multipart/form-data">
    <?=csrfField()?>
    <div class="grid-2">
        <div class="form-field">
            <label class="form-label" for="userName">用户名</label>
            <input class="input" type="text" name="userName" id="userName" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_]+" autocomplete="username" placeholder="3-30 位字母、数字或下划线">
        </div>
        <div class="form-field">
            <label class="form-label" for="pwd">密码</label>
            <div class="password-wrap">
                <input class="input" type="password" name="pwd" id="pwd" required minlength="6" maxlength="72" autocomplete="new-password" placeholder="至少 6 位，包含字母和数字">
                <button class="js-toggle-pwd toggle-pwd" type="button" data-target="pwd" aria-label="显示密码"><?=icon('eye')?></button>
            </div>
        </div>
        <div class="form-field">
            <label class="form-label" for="name">姓名</label>
            <input class="input" type="text" name="name" id="name" required maxlength="50" autocomplete="name" placeholder="请输入真实姓名">
        </div>
        <div class="form-field">
            <span class="form-label">性别</span>
            <div class="radio-pills">
                <label class="radio-pill"><input type="radio" name="gender" value="0" checked><span>男</span></label>
                <label class="radio-pill"><input type="radio" name="gender" value="1"><span>女</span></label>
            </div>
        </div>
        <div class="form-field">
            <label class="form-label" for="birth_date">出生年月</label>
            <input class="input" type="date" name="birth_date" id="birth_date" required>
        </div>
        <div class="form-field">
            <label class="form-label" for="stuNo">学号</label>
            <input class="input" type="text" name="stuNo" id="stuNo" required maxlength="20" pattern="[0-9]+" inputmode="numeric" placeholder="6-20 位数字">
        </div>
        <div class="form-field">
            <label class="form-label" for="college">学院</label>
            <input class="input" type="text" name="college" id="college" required maxlength="100" placeholder="如：计算机学院">
        </div>
        <div class="form-field">
            <label class="form-label" for="grade">年级</label>
            <input class="input" type="text" name="grade" id="grade" required maxlength="20" placeholder="如：2023级">
        </div>
        <div class="form-field">
            <label class="form-label" for="major">专业</label>
            <input class="input" type="text" name="major" id="major" required maxlength="100" placeholder="如：软件工程">
        </div>
        <div class="form-field">
            <label class="form-label" for="phone">手机号</label>
            <input class="input" type="tel" name="phone" id="phone" required maxlength="13" pattern="[0-9]+" inputmode="numeric" placeholder="11-13 位数字">
        </div>
        <div class="form-field">
            <label class="form-label" for="email">Email</label>
            <input class="input" type="email" name="email" id="email" required maxlength="100" autocomplete="email" placeholder="name@example.com">
        </div>
        <div class="form-field">
            <label class="form-label" for="QQ">QQ</label>
            <input class="input" type="text" name="QQ" id="QQ" required maxlength="20" pattern="[0-9]+" inputmode="numeric" placeholder="5-20 位数字">
        </div>
    </div>

    <div class="grid-2">
        <div class="form-field">
            <label class="form-label" for="avatar">头像</label>
            <div class="dropzone">
                <span class="dropzone-thumb is-empty" id="avatarThumb"><?=icon('user', 24)?><img class="thumb-img" src="" alt="头像预览"></span>
                <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/gif" required data-preview="#avatarThumb" data-max-size="20971520" data-max-dim="1280">
                <div class="dropzone-copy">
                    <strong>选择头像图片</strong>
                    <span>JPG、PNG 或 GIF，最大 20MB</span>
                    <span class="file-meta"></span>
                </div>
            </div>
        </div>
        <div class="form-field">
            <label class="form-label" for="student_card">学生证照片</label>
            <div class="dropzone">
                <span class="dropzone-thumb is-empty" id="cardThumb"><?=icon('id', 24)?><img class="thumb-img" src="" alt="学生证照片预览"></span>
                <input type="file" name="student_card" id="student_card" accept="image/jpeg,image/png,image/gif" required data-preview="#cardThumb" data-max-size="20971520" data-max-dim="1280">
                <div class="dropzone-copy">
                    <strong>选择学生证照片</strong>
                    <span>用于管理员实名审核，最大 20MB</span>
                    <span class="file-meta"></span>
                </div>
            </div>
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
    <button type="submit" class="btn btn-block"><?=icon('check')?>注册并提交审核</button>
    <div class="auth-links">
        <a href="p_loginStu.php">已有账号，返回登录</a>
        <a href="p_terms.php">用户协议</a>
        <a href="p_privacy.php">隐私政策</a>
    </div>
</form>
<?php renderAuthShellClose(); ?>
