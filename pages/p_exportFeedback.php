<?php
require_once __DIR__ . '/../lib/manageDB.php';
requireAdminLogin();

$page = getAdminFeedbackPage('all', 1, 1000);
$rows = [];
$labels = ['bug' => '问题反馈', 'suggestion' => '建议', 'complaint' => '投诉', 'other' => '其他'];
foreach ($page['items'] as $item) {
    $rows[] = [
        'pk' => $item['pk'],
        'student' => $item['realName'] ?: $item['userName'],
        'type' => $labels[$item['type']] ?? $item['type'],
        'content' => $item['content'],
        'hasImage' => $item['image'] !== '' ? '是' : '否',
        'status' => $item['status'],
        'createTime' => $item['createTime']
    ];
}
csvResponse('feedback-' . date('Ymd-His') . '.csv', ['ID', '学生', '类型', '内容', '含图片', '状态', '时间'], $rows);