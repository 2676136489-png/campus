<?php
require_once __DIR__ . '/p_manageDB.php';
requireAdminLogin();

$page = getAdminMessagesPage([], 1, 1000);
$rows = [];
foreach ($page['items'] as $item) {
    $rows[] = [
        'pk' => $item['pk'],
        'sender' => $item['senderRealName'] ?: $item['senderName'],
        'receiver' => $item['receiverRealName'] ?: $item['receiverName'],
        'content' => $item['image'] !== '' ? '[图片]' : ($item['status'] === 'recalled' ? '[已撤回]' : $item['content']),
        'status' => $item['status'],
        'read' => (int)$item['isRead'] === 1 ? '已读' : '未读',
        'createTime' => $item['createTime']
    ];
}
csvResponse('messages-' . date('Ymd-His') . '.csv', ['ID', '发送者', '接收者', '内容', '状态', '已读', '时间'], $rows);