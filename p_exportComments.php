<?php
require_once __DIR__ . '/p_manageDB.php';
requireAdminLogin();

$page = getCommentsPage([], 1, 1000);
$rows = [];
foreach ($page['items'] as $item) {
    $rows[] = [
        'pk' => $item['pk'],
        'content' => $item['content'],
        'commenter' => $item['commentName'] ?: $item['commentUserName'],
        'dynamicOwner' => $item['dynamicOwnerName'] ?: ('#' . $item['dynamicOwnerPk']),
        'createTime' => $item['createTime']
    ];
}
csvResponse('comments-' . date('Ymd-His') . '.csv', ['ID', '评论内容', '评论者', '所属动态发布者', '时间'], $rows);