<?php
require_once __DIR__ . '/lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

requireAdminLogin();
if ($_SESSION['adminMustChangePassword'] ?? isInitialAdminPassword($_SESSION['adminName'])) {
    header('Location: ' . tabUrl('p_adminAuth.php'));
    exit;
}

$operMsg = '';
$operType = 'success';
if (isset($_SESSION['admin_flash'])) {
    $operMsg = $_SESSION['admin_flash']['msg'] ?? '';
    $operType = $_SESSION['admin_flash']['type'] ?? 'success';
    unset($_SESSION['admin_flash']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $adminRow = getUserByUserName($_SESSION['adminName'] ?? '');
    $adminPk = $adminRow ? (int)$adminRow['pk'] : 0;
    if (isset($_POST['moderateReport'])) {
        $reportPk = (int)($_POST['reportPk'] ?? 0);
        $reportAction = $_POST['reportAction'] ?? '';
        $targetType = $_POST['targetType'] ?? '';
        $targetPk = (int)($_POST['targetPk'] ?? 0);
        if ($reportAction === 'deleteDynamic' && $targetType === 'dynamic') {
            $result = deleteDynamic($targetPk, 0, true);
            if ($result === true) {
                logAudit($adminPk, 'dynamic', $targetPk, 'delete_dynamic', '举报处理');
                setReportStatus($reportPk, 'resolved');
                $operMsg = '已删除被举报的动态。';
            } else {
                $operMsg = $result;
                $operType = 'error';
            }
        } elseif ($reportAction === 'deleteComment' && $targetType === 'comment') {
            $result = deleteComment($targetPk, $adminPk, true);
            if (isset($result['ok']) && $result['ok']) {
                logAudit($adminPk, 'comment', $targetPk, 'delete_comment', '举报处理');
                setReportStatus($reportPk, 'resolved');
                $operMsg = '已删除被举报的评论。';
            } else {
                $operMsg = $result['message'] ?? '评论删除失败。';
                $operType = 'error';
            }
        } elseif ($reportAction === 'dismiss') {
            setReportStatus($reportPk, 'dismissed');
            logAudit($adminPk, 'report', $reportPk, 'dismiss_report', '');
            $operMsg = '举报已忽略。';
        } else {
            $operMsg = '举报处理失败。';
            $operType = 'error';
        }
    }
    $_SESSION['admin_flash'] = ['msg' => $operMsg, 'type' => $operType];
    header('Location: ' . tabUrl('p_adminReports.php'));
    exit;
}

$reportsPage = getReportsPage('pending', max(1, (int)($_GET['rp'] ?? 1)), 10);
$reports = $reportsPage['items'];

renderHead('举报审核');
renderAdminHeader('举报审核');
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">举报审核</h1>
            <p class="page-sub">当前待处理举报 <?=h($reportsPage['total'])?> 条，可删除被举报内容或忽略举报。</p>
        </div>
        <div class="flex gap-sm">
            <a class="btn btn-ghost btn-sm" href="p_adminAuth.php"><?=icon('arrow-left', 15)?>返回总览</a><a class="btn btn-soft btn-sm" href="p_exportReports.php"><?=icon('download', 15)?>导出 CSV</a>
        </div>
    </div>

    <?php if ($operMsg): ?>
        <div class="alert <?=$operType === 'success' ? 'alert-success' : 'alert-error'?>">
            <?=icon($operType === 'success' ? 'check' : 'alert')?>
            <div><?=h($operMsg)?></div>
        </div>
    <?php endif; ?>

    <div class="card card-pad" data-paginate="reports">
        <div class="section-head">
            <h2 class="section-title">举报队列</h2>
            <span class="badge badge-neutral">待处理 <?=h($reportsPage['total'])?> 条</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>举报人</th>
                        <th>对象</th>
                        <th>原因</th>
                        <th>目标内容</th>
                        <th>时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reports)): ?>
                        <tr><td colspan="6" class="text-center muted">暂无待处理举报。</td></tr>
                    <?php endif; ?>
                    <?php foreach ($reports as $report): ?>
                        <tr>
                            <td><strong><?=h($report['reporterRealName'] ?: $report['reporterName'])?></strong></td>
                            <td><?=h($report['targetType'] === 'dynamic' ? '动态' : '评论')?> #<?=h($report['targetPk'])?></td>
                            <td><?=h($report['reason'])?></td>
                            <td class="pre-line muted"><?=h(function_exists('mb_substr') ? mb_substr($report['targetContent'], 0, 80, 'UTF-8') : substr($report['targetContent'], 0, 80))?></td>
                            <td><?=h($report['createTime'])?></td>
                            <td>
                                <div class="flex gap-sm">
                                    <?php if ($report['targetType'] === 'dynamic'): ?>
                                        <form method="POST" data-confirm="确定删除被举报动态吗？">
                                            <?=csrfField()?>
                                            <input type="hidden" name="reportPk" value="<?=h($report['pk'])?>">
                                            <input type="hidden" name="targetType" value="dynamic">
                                            <input type="hidden" name="targetPk" value="<?=h($report['targetPk'])?>">
                                            <input type="hidden" name="reportAction" value="deleteDynamic">
                                            <button type="submit" name="moderateReport" class="btn btn-danger-soft btn-sm">删除动态</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" data-confirm="确定删除被举报评论吗？">
                                            <?=csrfField()?>
                                            <input type="hidden" name="reportPk" value="<?=h($report['pk'])?>">
                                            <input type="hidden" name="targetType" value="comment">
                                            <input type="hidden" name="targetPk" value="<?=h($report['targetPk'])?>">
                                            <input type="hidden" name="reportAction" value="deleteComment">
                                            <button type="submit" name="moderateReport" class="btn btn-danger-soft btn-sm">删除评论</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST">
                                        <?=csrfField()?>
                                        <input type="hidden" name="reportPk" value="<?=h($report['pk'])?>">
                                        <input type="hidden" name="reportAction" value="dismiss">
                                        <button type="submit" name="moderateReport" class="btn btn-ghost btn-sm">忽略</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="list-meta list-meta-bottom">
            <?=paginationLinks('p_adminReports.php', (int)($_GET['rp'] ?? 1), $reportsPage['totalPages'], [], 'rp')?>
        </div>
    </div>
</div>
<?php renderSiteFooter([
    'links' => [
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
        ['label' => '学生注册', 'href' => 'p_registerStu.php'],
    ],
]); ?>