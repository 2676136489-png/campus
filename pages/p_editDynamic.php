<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireVerifiedStudent();
$dynamicPk = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';

$conn = dbConnect();
$stmt = $conn->prepare("SELECT `pk`, `content`, `tags` FROM `dynamic` WHERE `pk` = ? AND `userPk` = ? AND `status` = 'normal'");
$stmt->bind_param('ii', $dynamicPk, $student['pk']);
$stmt->execute();
$dynamic = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editDynamic'])) {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } else {
        $result = updateDynamic($dynamicPk, (int)$student['pk'], $_POST['content'] ?? '', $_POST['tags'] ?? '');
        if ($result === true) {
            header('Location: ' . tabUrl('p_dynamics.php?focus=' . $dynamicPk));
            exit;
        }
        $error = $result;
    }
}

renderHead('编辑动态');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => 'p_welcomeStu.php'],
        ['label' => '动态广场', 'href' => 'p_dynamics.php', 'active' => true],
        ['label' => '同学', 'href' => 'p_allStudents.php'],
    ],
    'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
]);
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1 class="page-title">编辑动态</h1>
            <p class="page-sub">修改动态内容和标签，图片保持不变。</p>
        </div>
    </div>

    <?php if (!$dynamic): ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><?=icon('feed', 26)?></span>
                <div class="empty-title">动态不存在或无权编辑</div>
                <a class="btn" href="p_dynamics.php">返回动态广场</a>
            </div>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
        <?php endif; ?>

        <div class="card card-pad">
            <form method="POST">
                <?=csrfField()?>
                <div class="form-field">
                    <label class="form-label" for="content">
                        动态内容
                        <span class="label-count" data-count-for="content"></span>
                    </label>
                    <textarea name="content" id="content" required maxlength="1000"><?=h($dynamic['content'])?></textarea>
                </div>
                <div class="form-field">
                    <label class="form-label" for="tags">标签</label>
                    <input class="input" type="text" name="tags" id="tags" value="<?=h($dynamic['tags'])?>" maxlength="220" placeholder="多个标签用逗号分隔">
                </div>
                <div class="flex">
                    <button type="submit" name="editDynamic" class="btn"><?=icon('check')?>保存修改</button>
                    <a class="btn btn-ghost" href="p_dynamics.php?focus=<?=h($dynamicPk)?>"><?=icon('arrow-left')?>取消</a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
<?php renderSiteFooter(); ?>