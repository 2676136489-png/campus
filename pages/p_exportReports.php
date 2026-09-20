<?php
require_once __DIR__ . '/../lib/manageDB.php';
requireAdminLogin();

$page = getReportsPage('all', 1, 1000);
$rows = [];
foreach ($page['items'] as $item) {
    $rows[] = [
        'pk' => $item['pk'],
        'reporter' => $item['reporterRealName'] ?: $item['reporterName'],
        'targetType' => $item['targetType'],
        'targetPk' => $item['targetPk'],
        'reason' => $item['reason'],
        'status' => $item['status'],
        'targetContent' => $item['targetContent'],
        'createTime' => $item['createTime']
    ];
}
csvResponse('reports-' . date('Ymd-His') . '.csv', ['ID', '举报人', '对象类型', '对象ID', '原因', '状态', '目标内容', '时间'], $rows);