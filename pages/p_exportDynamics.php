<?php
require_once __DIR__ . '/../lib/manageDB.php';
requireAdminLogin();

$page = getDynamicsPage(0, true, [], 1, 1000);
$rows = [];
foreach ($page['items'] as $item) {
    $rows[] = [
        'pk' => $item['pk'],
        'author' => $item['name'] ?: $item['userName'],
        'content' => $item['content'],
        'tags' => $item['tags'],
        'photos' => count($item['photos'] ?? []),
        'likes' => $item['like_count'],
        'comments' => $item['comment_count'],
        'status' => $item['status'],
        'createTime' => $item['createTime']
    ];
}
csvResponse('dynamics-' . date('Ymd-His') . '.csv', ['ID', '作者', '内容', '标签', '图片数', '点赞', '评论', '状态', '时间'], $rows);