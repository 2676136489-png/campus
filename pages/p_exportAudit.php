<?php
require_once __DIR__ . '/../lib/manageDB.php';
requireAdminLogin();

$page = getAuditLogsPage(1, 1000);
$rows = [];
foreach ($page['items'] as $item) {
    $rows[] = [
        'pk' => $item['pk'],
        'admin' => $item['adminName'],
        'targetType' => $item['targetType'],
        'targetPk' => $item['targetPk'],
        'targetName' => $item['targetName'] ?? '',
        'action' => $item['action'],
        'detail' => $item['detail'],
        'createTime' => $item['createTime']
    ];
}
csvResponse('audit-' . date('Ymd-His') . '.csv', ['ID', '管理员', '目标类型', '目标ID', '目标用户', '操作', '详情', '时间'], $rows);