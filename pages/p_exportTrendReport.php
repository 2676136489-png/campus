<?php
require_once __DIR__ . '/../lib/manageDB.php';
requireAdminLogin();

$trend = getTrendReport(30);
$rows = [];
foreach ($trend['labels'] as $i => $label) {
    $rows[] = [
        'date' => $label,
        'registrations' => $trend['registrations'][$i],
        'dynamics' => $trend['dynamics'][$i],
        'comments' => $trend['comments'][$i],
        'messages' => $trend['messages'][$i],
        'active' => $trend['active'][$i]
    ];
}
csvResponse('trend-report-' . date('Ymd-His') . '.csv', ['日期', '注册', '动态', '评论', '私信', '活跃学生'], $rows);