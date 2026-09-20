<?php
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

$student = requireStudentLogin();
$error = '';
$success = '';

if (isset($_POST['changePwd'])) {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } elseif (($_POST['newPwd'] ?? '') !== ($_POST['confirmPwd'] ?? '')) {
        $error = '两次输入的新密码不一致。';
    } else {
        $result = changeStudentPassword((int)$student['pk'], $_POST['oldPwd'] ?? '', $_POST['newPwd'] ?? '');
        if ($result === true) {
            $success = '密码修改成功。';
            issueActiveSessionToken((int)$student['pk']);
        } else {
            $error = $result;
        }
    }
}

renderHead('修改密码');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => 'p_welcomeStu.php'],
        ['label' => '动态广场', 'href' => 'p_dynamics.php'],
        ['label' => '同学', 'href' => 'p_allStudents.php'],
    ],
    'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
    'composer' => ($student['status'] === 'V'),
]);
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1 class="page-title">修改密码</h1>
            <p class="page-sub">定期修改密码可以更好地保护你的账号。</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?=icon('check')?><div><?=h($success)?></div></div>
    <?php endif; ?>

    <div class="card card-pad">
        <form method="POST">
            <?=csrfField()?>
            <div class="form-field">
                <label class="form-label" for="oldPwd">当前密码</label>
                <div class="password-wrap">
                    <input class="input" type="password" name="oldPwd" id="oldPwd" required autocomplete="current-password">
                    <button class="js-toggle-pwd toggle-pwd" type="button" data-target="oldPwd" aria-label="显示密码"><?=icon('eye')?></button>
                </div>
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
            <div class="flex">
                <button type="submit" name="changePwd" class="btn"><?=icon('check')?>确认修改</button>
                <a class="btn btn-ghost" href="p_welcomeStu.php"><?=icon('arrow-left')?>返回学生中心</a>
            </div>
        </form>
    </div>
</div>
<?php renderSiteFooter(); ?>