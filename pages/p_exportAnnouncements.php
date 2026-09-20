<?php
require_once __DIR__ . '/../lib/manageDB.php';
requireAdminLogin();

$page = getAnnouncementsPage(1, 1000);
$rows = [];
foreach ($page['items'] as $item) {
    $rows[] = [
        'pk' => $item['pk'],
        'title' => $item['title'],
        'content' => $item['content'],
        'published' => (int)$item['isPublished'] === 1 ? '是' : '否',
        'admin' => $item['adminName'],
        'createTime' => $item['createTime'],
        'updateTime' => $item['updateTime']
    ];
}
csvResponse('announcements-' . date('Ymd-His') . '.csv', ['ID', '标题', '内容', '已发布', '管理员', '创建时间', '更新时间'], $rows);