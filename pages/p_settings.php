<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireStudentLogin();
$error = '';
$success = '';

if (isset($_POST['savePrefs'])) {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } else {
        $enabled = [];
        foreach (NOTIFICATION_TYPES as $type) {
            $enabled[$type] = !empty($_POST['pref_' . $type]);
        }
        updateNotificationPrefs((int)$student['pk'], $enabled);
        $success = '通知偏好已保存。';
    }
}
if (isset($_POST['savePrivacy'])) {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } else {
        updatePrivacy((int)$student['pk'], $_POST['allowMessages'] ?? 'all', !empty($_POST['showProfile']));
        $success = '隐私设置已保存。';
    }
}

$prefs = getNotificationPrefs((int)$student['pk']);
$privacy = getPrivacy((int)$student['pk']);
$labels = [
    'like' => '点赞通知',
    'comment' => '评论通知',
    'review' => '审核通知',
    'account' => '账号通知',
    'system' => '系统通知',
    'follow' => '关注通知',
];

renderHead('通知设置');
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
            <h1 class="page-title">通知设置</h1>
            <p class="page-sub">选择你希望接收的站内通知类型。</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?=icon('check')?><div><?=h($success)?></div></div>
    <?php endif; ?>

    <div class="card card-pad mb-24">
        <form method="POST">
            <?=csrfField()?>
            <div class="section-head">
                <h2 class="section-title">隐私设置</h2>
            </div>
            <div class="form-field">
                <span class="form-label">谁可以给你发送私信</span>
                <div class="radio-pills">
                    <label class="radio-pill"><input type="radio" name="allowMessages" value="all" <?=$privacy['allowMessages'] === 'all' ? 'checked' : ''?>><span>所有人</span></label>
                    <label class="radio-pill"><input type="radio" name="allowMessages" value="followed" <?=$privacy['allowMessages'] === 'followed' ? 'checked' : ''?>><span>仅我关注的人</span></label>
                </div>
            </div>
            <label class="pref-item mb-24">
                <input type="checkbox" name="showProfile" value="1" <?=$privacy['showProfile'] ? 'checked' : ''?>>
                <span>允许同学查看我的主页</span>
            </label>
            <button type="submit" name="savePrivacy" class="btn"><?=icon('check')?>保存隐私设置</button>
        </form>
    </div>

    <div class="card card-pad">
        <form method="POST">
            <?=csrfField()?>
            <div class="section-head">
                <h2 class="section-title">通知偏好</h2>
            </div>
            <div class="pref-list">
                <?php foreach ($labels as $type => $label): ?>
                    <label class="pref-item">
                        <input type="checkbox" name="pref_<?=h($type)?>" value="1" <?=!empty($prefs[$type]) ? 'checked' : ''?>>
                        <span><?=h($label)?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <div class="flex mt-24">
                <button type="submit" name="savePrefs" class="btn"><?=icon('check')?>保存设置</button>
                <a class="btn btn-ghost" href="p_welcomeStu.php"><?=icon('arrow-left')?>返回学生中心</a>
            </div>
        </form>
    </div>
</div>
<?php renderSiteFooter(); ?>