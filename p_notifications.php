<?php
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

$student = requireStudentLogin();
markNotificationsRead((int)$student['pk']);

$type = $_GET['type'] ?? '';
if (!in_array($type, ['like', 'comment', 'review', 'account', 'system'], true)) {
    $type = '';
}
$page = max(1, (int)($_GET['page'] ?? 1));
$result = getNotificationsPage((int)$student['pk'], $type, $page, 20);
$notifications = $result['items'];

$tabs = [
    '' => '全部',
    'like' => '点赞',
    'comment' => '评论',
    'review' => '审核',
    'account' => '账号',
    'system' => '系统',
    'feedback' => '反馈',
];

renderHead('通知中心');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => 'p_welcomeStu.php'],
        ['label' => '动态广场', 'href' => 'p_dynamics.php'],
        ['label' => '同学', 'href' => 'p_allStudents.php'],
    ],
    'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
    'composer' => ($student['status'] === 'V'),
]);
?>
<div class="container narrow" data-paginate="notifications">
    <div class="page-head">
        <div>
            <h1 class="page-title">通知中心</h1>
            <p class="page-sub">审核结果、点赞和评论互动都会出现在这里。</p>
        </div>
    </div>

    <div class="tab-bar" role="tablist" aria-label="通知分类">
        <?php foreach ($tabs as $key => $label): ?>
            <a class="tab-link <?=$type === $key ? 'is-active' : ''?>" href="p_notifications.php<?=$key !== '' ? '?type=' . h($key) : ''?>"><?=h($label)?></a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><?=icon('bell', 26)?></span>
                <div class="empty-title">暂无通知</div>
                <p class="empty-copy">当有人点赞或评论你的动态，审核结果更新时，你会在这里看到。</p>
            </div>
        </div>
    <?php else: ?>
        <div class="notification-list">
            <?php foreach ($notifications as $item): ?>
                <?php
                    $typeClass = in_array($item['type'], ['review', 'account'], true) ? ' is-system' : '';
                    $iconName = $item['type'] === 'like' ? 'heart' : ($item['type'] === 'comment' ? 'chat' : 'shield');
                ?>
                <div class="notification-item<?=$typeClass?> reveal">
                    <span class="notification-icon"><?=icon($iconName, 18)?></span>
                    <div class="notification-main">
                        <div class="notification-content"><?=h($item['content'])?></div>
                        <div class="notification-time"><?=h($item['createTime'])?></div>
                    </div>
                    <?php if ($item['link'] !== ''): ?>
                        <a class="btn btn-soft btn-sm" href="<?=h($item['link'])?>">查看</a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="list-meta list-meta-bottom">
            <?=paginationLinks('p_notifications.php', $page, $result['totalPages'], $type !== '' ? ['type' => $type] : [])?>
        </div>
    <?php endif; ?>
</div>
<?php renderSiteFooter(); ?>