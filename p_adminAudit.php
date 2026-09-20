<?php
require_once __DIR__ . '/lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

requireAdminLogin();
requireAdminRole('super');
if ($_SESSION['adminMustChangePassword'] ?? isInitialAdminPassword($_SESSION['adminName'])) {
    header('Location: ' . tabUrl('p_adminAuth.php'));
    exit;
}

$auditPage = getAuditLogsPage(max(1, (int)($_GET['ap'] ?? 1)), 20);
$auditLogs = $auditPage['items'];
$operMsg = '';
$operType = 'success';
if (isset($_SESSION['admin_flash'])) {
    $operMsg = $_SESSION['admin_flash']['msg'] ?? '';
    $operType = $_SESSION['admin_flash']['type'] ?? 'success';
    unset($_SESSION['admin_flash']);
}
renderHead('审计日志');
renderAdminHeader('审计日志');
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">审计日志</h1>
            <p class="page-sub">当前共 <?=h($auditPage['total'])?> 条管理员操作记录，用于安全追踪与责任审计。</p>
        </div>
        <div class="flex gap-sm">
            <a class="btn btn-ghost btn-sm" href="p_adminAuth.php"><?=icon('arrow-left', 15)?>返回总览</a><a class="btn btn-soft btn-sm" href="p_exportAudit.php"><?=icon('download', 15)?>导出 CSV</a>
        </div>
    </div>

<?php if ($operMsg): ?>
        <div class="alert <?=$operType === 'success' ? 'alert-success' : 'alert-error'?>">
            <?=icon($operType === 'success' ? 'check' : 'alert')?>
            <div><?=h($operMsg)?></div>
        </div>
    <?php endif; ?>

    <div class="card card-pad" data-paginate="audit">
        <div class="section-head">
            <h2 class="section-title">操作记录</h2>
            <span class="badge badge-neutral">共 <?=h($auditPage['total'])?> 条</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>管理员</th>
                        <th>目标</th>
                        <th>操作</th>
                        <th>详情</th>
                        <th>时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($auditLogs)): ?>
                        <tr><td colspan="6" class="text-center muted">暂无审计记录。</td></tr>
                    <?php endif; ?>
                    <?php foreach ($auditLogs as $log): ?>
                        <tr>
                            <td><strong><?=h($log['adminName'])?></strong></td>
                            <td>
                                <?=h($log['targetType'])?> #<?=h($log['targetPk'])?>
                                <?php if (!empty($log['targetName'])): ?>
                                    <div class="muted"><?=h($log['targetName'])?></div>
                                <?php endif; ?>
                            </td>
                            <td><code><?=h($log['action'])?></code></td>
                            <td><?=h($log['detail'] ?: '-')?></td>
                            <td><?=h($log['createTime'])?></td>
                            <td>
                                <?php if (strpos($log['action'], 'delete_') === 0 && getRollbackEntry((int)$log['pk'])): ?>
                                    <form method="POST">
                                        <?=csrfField()?>
                                        <input type="hidden" name="auditPk" value="<?=h($log['pk'])?>">
                                        <button type="submit" name="rollbackAudit" class="btn btn-soft btn-sm"><?=icon('refresh', 14)?>回滚</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="list-meta list-meta-bottom">
            <?=paginationLinks('p_adminAudit.php', (int)($_GET['ap'] ?? 1), $auditPage['totalPages'], [], 'ap')?>
        </div>
    </div>
</div>
<?php renderSiteFooter([
    'links' => [
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
        ['label' => '学生注册', 'href' => 'p_registerStu.php'],
    ],
]); ?>