<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireStudentLogin();
$error = '';
$done = false;

if (isset($_POST['delUser'])) {
    $pwd = isset($_POST['pwd']) ? trim($_POST['pwd']) : '';

    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } elseif (delUser($student['userName'], $pwd)) {
        session_unset();
        session_destroy();
        $done = true;
    } else {
        $error = '密码错误，注销失败。';
    }
}

renderHead('注销账号');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => 'p_welcomeStu.php'],
        ['label' => '动态广场', 'href' => 'p_dynamics.php'],
        ['label' => '同学', 'href' => 'p_allStudents.php'],
    ],
    'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
]);
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1 class="page-title">注销账号</h1>
            <p class="page-sub">注销后账号将被停用，无法继续登录。</p>
        </div>
    </div>

    <div class="card card-pad">
        <?php if ($done): ?>
            <div class="alert alert-success"><?=icon('check')?><div>账号已注销。</div></div>
            <div class="flex">
                <a class="btn" href="p_registerStu.php"><?=icon('plus')?>重新注册</a>
                <a class="btn btn-ghost" href="p_loginStu.php">返回登录</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
            <?php endif; ?>
            <div class="alert alert-warning"><?=icon('alert')?><div>注销后账号将被停用，无法继续登录。请谨慎操作。</div></div>

            <form method="POST" data-confirm="确定要注销当前账号吗？注销后无法登录。">
                <?=csrfField()?>
                <div class="form-field">
                    <label class="form-label" for="pwd">输入当前密码确认注销</label>
                    <div class="password-wrap">
                        <input class="input" type="password" name="pwd" id="pwd" required autocomplete="current-password">
                        <button class="js-toggle-pwd toggle-pwd" type="button" data-target="pwd" aria-label="显示密码"><?=icon('eye')?></button>
                    </div>
                </div>
                <div class="flex">
                    <button type="submit" name="delUser" class="btn btn-danger"><?=icon('trash')?>确认注销</button>
                    <a class="btn btn-ghost" href="p_welcomeStu.php">取消</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php renderSiteFooter(); ?>
