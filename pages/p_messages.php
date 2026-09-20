<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireStudentLogin();
$conversations = getConversations((int)$student['pk']);

renderHead('私信');
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
<div class="container narrow messages-page">
    <div class="page-head">
        <div>
            <h1 class="page-title">私信</h1>
            <p class="page-sub">和同学一对一交流，消息实时送达。</p>
        </div>
    </div>

    <?php if (empty($conversations)): ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><?=icon('chat', 26)?></span>
                <div class="empty-title">还没有私信</div>
                <p class="empty-copy">去同学列表找到感兴趣的人，从 TA 的主页发起私信。</p>
                <a class="btn" href="p_allStudents.php"><?=icon('users')?>查看同学</a>
            </div>
        </div>
    <?php else: ?>
        <?php
            $groups = [];
            $unread = array_values(array_filter($conversations, function ($c) { return (int)$c['unread'] > 0; }));
            $read = array_values(array_filter($conversations, function ($c) { return (int)$c['unread'] === 0; }));
            if ($unread) {
                $groups[] = ['title' => '未读', 'items' => $unread];
            }
            if ($read) {
                $groups[] = ['title' => '其他', 'items' => $read];
            }
        ?>
        <div class="conversation-list">
            <?php foreach ($groups as $group): ?>
                <?php if (count($groups) > 1): ?>
                    <div class="conversation-group-title"><?=h($group['title'])?></div>
                <?php endif; ?>
                <?php foreach ($group['items'] as $conv): ?>
                    <?php $avatarOk = !empty($conv['avatar']) && uploadFileExists($conv['avatar']); ?>
                    <a class="conversation-item reveal" href="p_chat.php?user=<?=h($conv['otherPk'])?>">
                        <?php if ($avatarOk): ?>
                            <img class="avatar" src="<?=h(thumbnailUrl($conv['avatar'], 'avatar'))?>" alt="">
                        <?php else: ?>
                            <span class="avatar avatar-placeholder"><?=icon('user', 20)?></span>
                        <?php endif; ?>
                        <div class="conversation-main">
                            <div class="conversation-name"><?=h($conv['name'] ?: $conv['userName'])?></div>
                            <div class="conversation-preview muted"><?=h($conv['lastContent'])?></div>
                        </div>
                        <div class="conversation-side">
                            <div class="flex gap-sm">
                                <?php if ((int)$conv['isPinned'] === 1): ?><span class="badge badge-success">置顶</span><?php endif; ?>
                                <?php if ((int)$conv['isMuted'] === 1): ?><span class="badge badge-neutral">免打扰</span><?php endif; ?>
                            </div>
                            <span class="muted small-text"><?=h($conv['lastTime'])?></span>
                            <?php if ((int)$conv['unread'] > 0): ?>
                                <span class="notify-badge"><?=h($conv['unread'] > 99 ? '99+' : $conv['unread'])?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php renderSiteFooter(); ?>