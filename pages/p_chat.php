<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireStudentLogin();
$otherPk = (int)($_GET['user'] ?? 0);
$other = getStudentByPk($otherPk);
$messages = $other && $other['status'] === 'V' ? getMessagesBetween((int)$student['pk'], $otherPk, 50) : [];
$convSetting = $other && $other['status'] === 'V' ? getConversationSetting((int)$student['pk'], $otherPk) : [];
$now = time();

renderHead('私信聊天');
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
<div class="container narrow chat-page">
    <?php if (!$other || $other['status'] !== 'V'): ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><?=icon('user', 26)?></span>
                <div class="empty-title">用户不存在或不可用</div>
                <a class="btn" href="p_messages.php">返回私信</a>
            </div>
        </div>
    <?php else: ?>
        <?php $avatarOk = !empty($other['avatar']) && uploadFileExists($other['avatar']); ?>
        <div class="chat-card card">
            <div class="chat-head">
                <?php if ($avatarOk): ?>
                    <img class="avatar" src="<?=h(thumbnailUrl($other['avatar'], 'avatar'))?>" alt="">
                <?php else: ?>
                    <span class="avatar avatar-placeholder"><?=icon('user', 20)?></span>
                <?php endif; ?>
                <div>
                    <div class="chat-name"><?=h($other['name'])?></div>
                    <div class="muted small-text">@<?=h($other['userName'])?> · <?=h($other['college'])?></div>
                </div>
                <button type="button" class="chat-setting-btn <?=!empty($convSetting['isPinned']) ? 'is-active' : ''?>" data-conversation-setting data-field="isPinned" data-target-user="<?=h($otherPk)?>" data-value="<?=!empty($convSetting['isPinned']) ? '1' : '0'?>"><?=icon('pin')?><span data-setting-label>置顶</span></button>
                <button type="button" class="chat-setting-btn <?=!empty($convSetting['isMuted']) ? 'is-active' : ''?>" data-conversation-setting data-field="isMuted" data-target-user="<?=h($otherPk)?>" data-value="<?=!empty($convSetting['isMuted']) ? '1' : '0'?>"><?=icon('bell-off')?><span data-setting-label>免打扰</span></button>
                <a class="btn btn-soft btn-sm" href="p_profile.php?user=<?=h($otherPk)?>"><?=icon('user')?>主页</a>
            </div>

            <div class="chat-body" data-message-list data-current-user="<?=h($student['pk'])?>" data-other-user="<?=h($otherPk)?>" data-csrf="<?=h(csrfToken())?>">
                <?php if (count($messages) >= 50): ?>
                    <button type="button" class="chat-load-more" data-chat-load-more>加载更早消息</button>
                <?php endif; ?>
                <?php foreach ($messages as $message): ?>
                    <?php
                        $mine = (int)$message['senderPk'] === (int)$student['pk'];
                        $recalled = $message['status'] === 'recalled';
                        $canRecall = $mine && !$recalled && strtotime($message['createTime']) >= $now - 120;
                        $bubbleText = $recalled
                            ? ($mine ? '你撤回了一条消息' : '对方撤回了一条消息')
                            : $message['content'];
                        $hasImage = !$recalled && $message['image'] !== '' && uploadFileExists($message['image']);
                    ?>
                    <div class="chat-message <?=$mine ? 'is-mine' : ''?> <?=$recalled ? 'is-recalled' : ''?>" data-message-pk="<?=h($message['pk'])?>">
                        <div class="chat-bubble">
                            <?php if ($hasImage): ?>
                                <img class="chat-image" src="<?=h(assetUrl($message['image']))?>" alt="图片消息" loading="lazy">
                            <?php endif; ?>
                            <?php if ($bubbleText !== ''): ?>
                                <div class="chat-text"><?=nl2br(h($bubbleText))?></div>
                            <?php endif; ?>
                        </div>
                        <div class="chat-meta">
                            <span class="chat-time"><?=h($message['createTime'])?></span>
                            <?php if ($mine && !$recalled): ?>
                                <span class="chat-read"><?=(int)$message['isRead'] === 1 ? '已读' : '未读'?></span>
                            <?php endif; ?>
                            <?php if ($canRecall): ?>
                                <button type="button" class="chat-recall" data-recall-pk="<?=h($message['pk'])?>">撤回</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="chat-composer">
                <div class="chat-emoji-panel" data-emoji-panel hidden>
                    <?php foreach (['😀','😂','😊','🥰','😎','🤔','😭','😡','👍','👏','🙏','🎉','🔥','❤️','💔','🌹','🍀','🏀'] as $emoji): ?>
                        <button type="button" class="chat-emoji" data-emoji="<?=h($emoji)?>"><?=$emoji?></button>
                    <?php endforeach; ?>
                </div>
                <form method="POST" class="chat-form" data-api-action="sendMessage" data-target-user="<?=h($otherPk)?>">
                    <?=csrfField()?>
                    <button type="button" class="chat-emoji-toggle" data-emoji-toggle aria-label="表情"><?=icon('smile')?></button>
                    <button type="button" class="chat-image-toggle" data-chat-image-toggle aria-label="发送图片"><?=icon('image')?></button>
                    <input type="file" name="image" accept="image/jpeg,image/png,image/gif" hidden data-chat-image>
                    <img class="chat-image-preview" data-chat-image-preview alt="图片预览" hidden>
                    <textarea name="content" required maxlength="1000" placeholder="输入消息..." data-chat-input></textarea>
                    <button type="submit" class="btn"><?=icon('send')?>发送</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php renderSiteFooter(); ?>