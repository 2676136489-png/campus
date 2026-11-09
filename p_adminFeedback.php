<?php
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

requireAdminLogin();
$admin = getUserByUserName($_SESSION['adminName'] ?? '');
$adminPk = $admin ? (int)$admin['pk'] : 0;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateFeedback'])) {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } elseif (updateFeedbackStatus((int)($_POST['feedbackPk'] ?? 0), $_POST['status'] ?? '', $adminPk)) {
        $success = '反馈状态已更新。';
    } else {
        $error = '反馈状态更新失败。';
    }
}

$status = $_GET['status'] ?? 'open';
$page = getAdminFeedbackPage($status, max(1, (int)($_GET['page'] ?? 1)), 10);
$statusLabels = ['open' => '待处理', 'processing' => '处理中', 'closed' => '已关闭'];
$types = ['bug' => '问题反馈', 'suggestion' => '建议', 'complaint' => '投诉', 'other' => '其他'];

renderHead('反馈管理');
renderSiteHeader([
    'home' => 'p_adminAuth.php',
    'nav' => [
        ['label' => '后台总览', 'href' => 'p_adminAuth.php'],
        ['label' => '反馈管理', 'href' => 'p_adminFeedback.php', 'active' => true],
        ['label' => '学生入口', 'href' => 'p_loginStu.php'],
    ],
    'actions' => '<a class="btn btn-ghost btn-sm" href="p_adminAuth.php?logout=1">' . icon('logout') . '退出</a>',
]);
?>
<div class="container" data-paginate="admin_feedback">
    <div class="page-head">
        <div>
            <h1 class="page-title">反馈管理</h1>
            <p class="page-sub">处理学生提交的问题反馈与建议。</p>
        </div>
        <a class="btn btn-soft" href="p_exportFeedback.php"><?=icon('download')?>导出 CSV</a>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?=icon('check')?><div><?=h($success)?></div></div>
    <?php endif; ?>

    <div class="tab-bar">
        <?php foreach (['open' => '待处理', 'processing' => '处理中', 'closed' => '已关闭', 'all' => '全部'] as $key => $label): ?>
            <a class="tab-link <?=$status === $key ? 'is-active' : ''?>" href="p_adminFeedback.php?status=<?=h($key)?>"><?=h($label)?></a>
        <?php endforeach; ?>
    </div>

    <div class="card card-pad">
        <div class="section-head">
            <h2 class="section-title">反馈列表</h2>
            <span class="badge badge-neutral">共 <?=h($page['total'])?> 条</span>
        </div>
        <?php if (empty($page['items'])): ?>
            <p class="muted">暂无反馈。</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>学生</th><th>类型</th><th>内容</th><th>状态</th><th>时间</th><th>操作</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($page['items'] as $item): ?>
                            <tr>
                                <td><strong><?=h($item['realName'] ?: $item['userName'])?></strong></td>
                                <td><?=h($types[$item['type']] ?? $item['type'])?></td>
                                <td class="pre-line muted">
                                    <?=h($item['content'])?>
                                    <?php if (!empty($item['image']) && uploadFileExists($item['image'])): ?>
                                        <div><img class="feedback-image" src="<?=h(thumbnailUrl($item['image'], 'md'))?>" alt="反馈图片" loading="lazy"></div>
                                    <?php endif; ?>
                                </td>
                                <td><?=h($statusLabels[$item['status']] ?? $item['status'])?></td>
                                <td><?=h($item['createTime'])?></td>
                                <td>
                                    <div class="flex gap-sm">
                                        <?php if ($item['status'] !== 'processing'): ?>
                                            <form method="POST">
                                                <?=csrfField()?>
                                                <input type="hidden" name="feedbackPk" value="<?=h($item['pk'])?>">
                                                <input type="hidden" name="status" value="processing">
                                                <button type="submit" name="updateFeedback" class="btn btn-sm">开始处理</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($item['status'] !== 'closed'): ?>
                                            <form method="POST">
                                                <?=csrfField()?>
                                                <input type="hidden" name="feedbackPk" value="<?=h($item['pk'])?>">
                                                <input type="hidden" name="status" value="closed">
                                                <button type="submit" name="updateFeedback" class="btn btn-ghost btn-sm">关闭</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="list-meta list-meta-bottom">
                <?=paginationLinks('p_adminFeedback.php', $page['page'], $page['totalPages'], ['status' => $status])?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php renderSiteFooter([
    'links' => [
        ['label' => '后台总览', 'href' => 'p_adminAuth.php'],
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
    ],
]); ?>