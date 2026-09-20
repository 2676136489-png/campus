<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireStudentLogin();
$error = '';
$success = '';
$targetType = $_GET['targetType'] ?? $_POST['targetType'] ?? '';
$targetPk = (int)($_GET['targetPk'] ?? $_POST['targetPk'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } else {
        $result = createReport((int)$student['pk'], $targetType, $targetPk, $_POST['reason'] ?? '');
        if ($result === true) {
            $success = '举报已提交，管理员会尽快处理。';
        } else {
            $error = $result;
        }
    }
}

renderHead('举报内容');
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
            <h1 class="page-title">举报内容</h1>
            <p class="page-sub">提交违规内容后，管理员会进入审核队列处理。</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?=icon('check')?><div><?=h($success)?></div></div>
        <a class="btn btn-soft" href="p_dynamics.php"><?=icon('arrow-left')?>返回动态广场</a>
    <?php elseif ($targetType === '' || $targetPk <= 0): ?>
        <div class="card card-pad">
            <div class="empty-state">
                <span class="empty-icon"><?=icon('alert', 26)?></span>
                <div class="empty-title">缺少举报对象</div>
                <p class="empty-copy">请从动态或评论的举报入口发起。</p>
                <a class="btn" href="p_dynamics.php">返回动态广场</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card card-pad">
            <form method="POST" action="p_report.php">
                <?=csrfField()?>
                <input type="hidden" name="targetType" value="<?=h($targetType)?>">
                <input type="hidden" name="targetPk" value="<?=h($targetPk)?>">
                <div class="form-field">
                    <span class="form-label">举报对象</span>
                    <div class="muted"><?=h($targetType === 'dynamic' ? '校园动态 #' . $targetPk : '评论 #' . $targetPk)?></div>
                </div>
                <div class="form-field">
                    <label class="form-label" for="reason">举报原因</label>
                    <textarea name="reason" id="reason" required maxlength="500" placeholder="请说明违规原因，例如：虚假信息、人身攻击、隐私泄露"></textarea>
                    <div class="form-hint">最多 500 字，管理员会看到你的举报原因。</div>
                </div>
                <div class="flex">
                    <button type="submit" class="btn btn-danger"><?=icon('send')?>提交举报</button>
                    <a class="btn btn-ghost" href="p_dynamics.php"><?=icon('arrow-left')?>取消</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php renderSiteFooter(); ?>