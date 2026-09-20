<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireStudentLogin();
$isVerified = $student['status'] === 'V';
$mentionCandidates = $isVerified ? getMentionCandidates((int)$student['pk']) : [];
$mentionJson = json_encode($mentionCandidates, JSON_UNESCAPED_UNICODE);
$dynamicPk = (int)($_GET['id'] ?? 0);
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dynamicPk > 0) {
    if (!verifyCsrf()) {
        $message = 'Invalid form token. Please try again.';
        $messageType = 'error';
    } elseif (!$isVerified) {
        $message = '实名审核通过后才能进行该操作。';
        $messageType = 'error';
    } elseif (isset($_POST['likeDynamic'])) {
        $result = toggleLike($dynamicPk, (int)$student['pk']);
        if (isset($result['ok']) && !$result['ok']) {
            $message = $result['message'];
            $messageType = 'error';
        }
    } elseif (isset($_POST['addComment'])) {
        $result = addComment($dynamicPk, (int)$student['pk'], $_POST['comment'] ?? '');
        if (isset($result['ok']) && !$result['ok']) {
            $message = $result['message'];
            $messageType = 'error';
        }
    } elseif (isset($_POST['deleteComment'])) {
        $result = deleteComment((int)($_POST['commentPk'] ?? 0), (int)$student['pk'], false);
        if (isset($result['ok']) && !$result['ok']) {
            $message = $result['message'];
            $messageType = 'error';
        }
    } elseif (isset($_POST['deleteDynamic'])) {
        $result = deleteDynamic($dynamicPk, (int)$student['pk'], false);
        if ($result === true) {
            header('Location: p_dynamics.php');
            exit;
        }
        $message = $result;
        $messageType = 'error';
    }
}

$dynamic = $dynamicPk > 0 ? getDynamicByPk($dynamicPk, (int)$student['pk']) : null;

renderHead('动态详情');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => 'p_welcomeStu.php'],
        ['label' => '动态广场', 'href' => 'p_dynamics.php', 'active' => true],
        ['label' => '同学', 'href' => 'p_allStudents.php'],
    ],
    'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
    'composer' => $isVerified,
]);
?>
<div class="container post-detail-layout">
    <?php if (!$dynamic): ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><?=icon('feed', 26)?></span>
                <div class="empty-title">动态不存在或已删除</div>
                <p class="empty-copy">这条动态可能已被作者删除，或正在等待内容审核。</p>
                <a class="btn" href="p_dynamics.php">返回动态广场</a>
            </div>
        </div>
    <?php else: ?>
        <?php
            $avatarOk = !empty($dynamic['avatar']) && uploadFileExists($dynamic['avatar']);
            $feedPhotos = array_values(array_filter($dynamic['photos'] ?? [], function ($p) { return uploadFileExists($p); }));
            $comments = getCommentsByDynamic($dynamicPk);
        ?>
        <article class="card post-card post-detail reveal is-visible" id="dynamic-<?=h($dynamic['pk'])?>">
            <div class="post-head">
                <a class="detail-author" href="p_profile.php?user=<?=h($dynamic['userPk'])?>">
                    <?php if ($avatarOk): ?>
                        <img class="avatar" src="<?=h(thumbnailUrl($dynamic['avatar'], 'avatar'))?>" alt="">
                    <?php else: ?>
                        <span class="avatar avatar-placeholder"><?=icon('user', 22)?></span>
                    <?php endif; ?>
                </a>
                <div class="post-meta">
                    <a class="post-author" href="p_profile.php?user=<?=h($dynamic['userPk'])?>"><?=h($dynamic['name'] ?: $dynamic['userName'])?></a>
                    <span class="post-time"><?=h($dynamic['college'] ?: '未知学院')?> · <?=h($dynamic['major'] ?: '未知专业')?> · <?=h($dynamic['createTime'])?></span>
                </div>
            </div>

            <div class="post-body">
                <?php if ($dynamic['tags'] !== ''): ?>
                    <div class="tag-list post-tags">
                        <?php foreach (explode(',', $dynamic['tags']) as $tag): ?>
                            <a class="tag tag-link" href="p_dynamics.php?tag=<?=h(urlencode($tag))?>"><?=h($tag)?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <p class="post-content"><?=topicHtml(nl2br(h($dynamic['content'])))?></p>
                <?php if ($feedPhotos): ?>
                    <div class="post-gallery <?=count($feedPhotos) === 1 ? 'is-single' : ''?>">
                        <?php foreach ($feedPhotos as $photoPath): ?>
                            <figure class="post-photo">
                                <img class="js-lightbox" src="<?=h(thumbnailUrl($photoPath, 'md'))?>" alt="动态图片" loading="lazy">
                            </figure>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($message): ?>
                <div class="alert <?=$messageType === 'success' ? 'alert-success' : 'alert-error'?> mt-24">
                    <?=icon($messageType === 'success' ? 'check' : 'alert')?>
                    <div><?=h($message)?></div>
                </div>
            <?php endif; ?>

            <div class="post-actions">
                <?php if ($isVerified): ?>
                    <form method="POST" action="p_post.php?id=<?=h($dynamic['pk'])?>" data-api-action="like" data-dynamic-pk="<?=h($dynamic['pk'])?>">
                        <?=csrfField()?>
                        <input type="hidden" name="dynamicPk" value="<?=h($dynamic['pk'])?>">
                        <button type="submit" name="likeDynamic" class="action-btn <?=$dynamic['liked'] ? 'is-liked' : ''?>">
                            <?=icon('heart')?>点赞 <span class="like-count" data-like-count><?=h($dynamic['like_count'])?></span>
                        </button>
                    </form>
                    <form method="POST" action="p_post.php?id=<?=h($dynamic['pk'])?>" data-api-action="favorite" data-dynamic-pk="<?=h($dynamic['pk'])?>">
                        <?=csrfField()?>
                        <input type="hidden" name="dynamicPk" value="<?=h($dynamic['pk'])?>">
                        <button type="submit" name="favoriteDynamic" class="action-btn <?=$dynamic['favorited'] ? 'is-favorited' : ''?>">
                            <?=icon('bookmark')?><span data-favorite-label><?=$dynamic['favorited'] ? '已收藏' : '收藏'?></span> <span class="favorite-count" data-favorite-count><?=h($dynamic['favorite_count'])?></span>
                        </button>
                    </form>
                    <?php if ((int)$dynamic['userPk'] === (int)$student['pk']): ?>
                        <a class="action-link" href="p_editDynamic.php?id=<?=h($dynamic['pk'])?>"><?=icon('edit', 14)?>编辑</a>
                        <form method="POST" action="p_post.php?id=<?=h($dynamic['pk'])?>" data-confirm="确定删除这条动态吗？删除后不可恢复。">
                            <?=csrfField()?>
                            <input type="hidden" name="dynamicPk" value="<?=h($dynamic['pk'])?>">
                            <button type="submit" name="deleteDynamic" class="action-btn danger"><?=icon('trash')?>删除</button>
                        </form>
                    <?php else: ?>
                        <a class="action-link danger" href="p_report.php?targetType=dynamic&targetPk=<?=h($dynamic['pk'])?>"><?=icon('alert', 14)?>举报</a>
                    <?php endif; ?>
                    <span class="action-count"><?=icon('chat')?>评论 <span data-comment-count><?=h($dynamic['comment_count'])?></span></span>
                <?php else: ?>
                    <span class="action-count"><?=icon('heart')?>点赞 <?=h($dynamic['like_count'])?></span>
                    <span class="action-count"><?=icon('bookmark')?>收藏 <?=h($dynamic['favorite_count'])?></span>
                    <span class="action-count"><?=icon('chat')?>评论 <?=h($dynamic['comment_count'])?></span>
                <?php endif; ?>
            </div>

            <div class="comments">
                <div class="comment-list" data-comment-list>
                    <?php foreach ($comments as $comment): ?>
                        <?php
                            $canDeleteComment = $isVerified
                                && ((int)$comment['commentUserPk'] === (int)$student['pk']
                                    || (int)$dynamic['userPk'] === (int)$student['pk']);
                        ?>
                        <div class="comment-item<?=!empty($comment['parentPk']) ? ' is-reply' : ''?>">
                            <div class="comment-head">
                                <span class="comment-author"><?=h($comment['name'] ?: $comment['userName'])?></span>
                                <span class="comment-time"><?=h($comment['createTime'])?></span>
                                <?php if ($canDeleteComment): ?>
                                    <form method="POST" class="comment-delete" data-api-action="deleteComment" data-comment-pk="<?=h($comment['pk'])?>">
                                        <?=csrfField()?>
                                        <input type="hidden" name="commentPk" value="<?=h($comment['pk'])?>">
                                        <button type="submit" class="comment-delete-btn" aria-label="删除评论"><?=icon('trash', 14)?></button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($isVerified && (int)$comment['commentUserPk'] !== (int)$student['pk']): ?>
                                    <a class="comment-report" href="p_report.php?targetType=comment&targetPk=<?=h($comment['pk'])?>" aria-label="举报评论"><?=icon('alert', 13)?></a>
                                <?php endif; ?>
                                <?php if ($isVerified): ?>
                                    <button type="button" class="comment-reply-btn" data-reply-pk="<?=h($comment['pk'])?>" data-reply-name="<?=h($comment['name'] ?: $comment['userName'])?>"><?=icon('chat', 13)?>回复</button>
                                <?php endif; ?>
                            </div>
<div class="comment-body"><?php if (!empty($comment['parentPk'])): ?><span class="comment-reply-to">回复 <?=h($comment['parentName'] ?: $comment['parentUserName'])?>：</span><?php endif; ?><?=mentionHtml($comment['content'])?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($isVerified): ?>
                    <form method="POST" class="comment-form" action="p_post.php?id=<?=h($dynamic['pk'])?>" data-api-action="comment" data-dynamic-pk="<?=h($dynamic['pk'])?>">
                        <?=csrfField()?>
                        <input type="hidden" name="dynamicPk" value="<?=h($dynamic['pk'])?>">
                        <input type="hidden" name="parentPk" value="0" data-reply-input>
                        <span class="reply-chip" data-reply-chip hidden>回复 <b data-reply-name></b><button type="button" class="reply-chip-cancel" data-cancel-reply>取消</button></span>
                        <input class="input" type="text" name="comment" required maxlength="300" placeholder="写下评论..." data-mentions="<?=h($mentionJson)?>">
                        <button type="submit" name="addComment" class="btn btn-sm"><?=icon('send')?>评论</button>
                    </form>
                <?php endif; ?>
            </div>
        </article>

        <aside class="detail-aside">
            <div class="card rail-card">
                <div class="detail-author">
                    <?php if ($avatarOk): ?>
                        <img class="avatar avatar-lg" src="<?=h(thumbnailUrl($dynamic['avatar'], 'avatar'))?>" alt="">
                    <?php else: ?>
                        <span class="avatar avatar-lg avatar-placeholder"><?=icon('user', 24)?></span>
                    <?php endif; ?>
                    <div>
                        <a class="detail-author-name" href="p_profile.php?user=<?=h($dynamic['userPk'])?>"><?=h($dynamic['name'] ?: $dynamic['userName'])?></a>
                        <div class="muted small-text">@<?=h($dynamic['userName'])?></div>
                    </div>
                </div>
            </div>
            <div class="card rail-card">
                <div class="section-head">
                    <h2 class="section-title">广场公约</h2>
                </div>
                <div class="muted rule-text">
                    尊重每一位同学，不发布虚假信息。<br>
                    涉及他人隐私的内容请先征得同意。<br>
                    管理员有权删除违规动态。
                </div>
            </div>
            <a class="btn btn-soft" href="p_dynamics.php"><?=icon('arrow-left')?>返回动态广场</a>
        </aside>
    <?php endif; ?>
</div>
<?php renderSiteFooter(); ?>
