<?php
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

$student = requireVerifiedStudent();
$error = '';
$success = '';

if (isset($_POST['saveTags'])) {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
        $result = false;
    } else {
        $tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';
        $result = updateStuTags((int)$student['pk'], $tags);
    }
    if ($result === true) {
        $success = '兴趣/圈子标签已保存。';
        $student = getCurrentStudent($student['userName']);
    } elseif ($error === '') {
        $error = '标签保存失败，请稍后重试。';
    }
}

renderHead('兴趣与圈子标签');
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
            <h1 class="page-title">兴趣 / 圈子标签</h1>
            <p class="page-sub">设置你的兴趣标签，让同学更容易找到你。</p>
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
                <label class="form-label" for="tags">标签</label>
                <textarea name="tags" id="tags" data-tags-preview="#tagPreview" maxlength="220" placeholder="例如：篮球, 编程, 摄影, 志愿服务"><?=h($student['tags'])?></textarea>
                <div class="form-hint">多个标签用逗号分隔，最多保留 10 个，每个不超过 20 字。</div>
            </div>
            <div class="tag-list mb-24" id="tagPreview">
                <?php if ($student['tags'] !== ''): ?>
                    <?php foreach (explode(',', $student['tags']) as $tag): ?>
                        <span class="tag"><?=h($tag)?></span>
                    <?php endforeach; ?>
                <?php else: ?>
                    <span class="muted">标签会自动显示在这里</span>
                <?php endif; ?>
            </div>
            <div class="flex">
                <button type="submit" name="saveTags" class="btn"><?=icon('check')?>保存标签</button>
                <a class="btn btn-ghost" href="p_welcomeStu.php"><?=icon('arrow-left')?>返回学生中心</a>
            </div>
        </form>
    </div>
</div>
<?php renderSiteFooter(); ?>
