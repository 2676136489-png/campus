<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireStudentLogin();
$isVerified = $student['status'] === 'V';
$mentionCandidates = $isVerified ? getMentionCandidates((int)$student['pk']) : [];
$mentionJson = json_encode($mentionCandidates, JSON_UNESCAPED_UNICODE);
$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $message = 'Invalid form token. Please try again.';
        $messageType = 'error';
    } elseif (!$isVerified) {
        $message = '实名审核通过后才能进行该操作。';
        $messageType = 'error';
    } elseif (isset($_POST['publishDynamic'])) {
        $photos = [];
        if (!empty($_FILES['photos']['name'][0])) {
            $photos = uploadMultipleImages('photos', 'dynamics', 20 * 1024 * 1024, 3);
            if (is_string($photos)) {
                $message = '动态图片上传失败：' . $photos;
                $messageType = 'error';
                $photos = [];
            }
        }

        if ($message === '') {
            $result = publishDynamicWithPhotos((int)$student['pk'], $_POST['content'] ?? '', $_POST['tags'] ?? '', $photos);
            if ($result === true) {
                $message = '动态发布成功。';
            } else {
                foreach ($photos as $path) {
                    deleteUploadedFile($path);
                }
                $message = $result;
                $messageType = 'error';
            }
        }
    } elseif (isset($_POST['likeDynamic'])) {
        $result = toggleLike((int)$_POST['dynamicPk'], (int)$student['pk']);
        if (isset($result['ok']) && !$result['ok']) {
            $message = $result['message'];
            $messageType = 'error';
        }
    } elseif (isset($_POST['addComment'])) {
        $result = addComment((int)$_POST['dynamicPk'], (int)$student['pk'], $_POST['comment'] ?? '');
        if (isset($result['ok']) && !$result['ok']) {
            $message = $result['message'];
            $messageType = 'error';
        } else {
            $message = '评论已发布。';
        }
    } elseif (isset($_POST['deleteDynamic'])) {
        $result = deleteDynamic((int)$_POST['dynamicPk'], (int)$student['pk'], false);
        if ($result === true) {
            $message = '动态已删除。';
        } else {
            $message = $result;
            $messageType = 'error';
        }
    }
}

$filters = [
    'keyword' => trim($_GET['q'] ?? ''),
    'tag' => trim($_GET['tag'] ?? ''),
    'college' => trim($_GET['college'] ?? ''),
];
$page = max(1, (int)($_GET['page'] ?? 1));
$focusPk = (int)($_GET['focus'] ?? 0);
$perPage = 10;
$feed = getDynamicsPage((int)$student['pk'], false, $filters, $page, $perPage);
$dynamics = $feed['items'];
$total = $feed['total'];
$totalPages = $feed['totalPages'];
$commentsByDynamic = getCommentsByDynamicIds(array_column($dynamics, 'pk'));

renderHead('校园动态广场');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => 'p_welcomeStu.php'],
        ['label' => '动态广场', 'href' => 'p_dynamics.php', 'active' => true],
        ['label' => '同学', 'href' => 'p_allStudents.php'],
    ],
    'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
    'composer' => ($student['status'] === 'V'),
]);
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">校园动态广场</h1>
            <p class="page-sub">分享校园生活，按兴趣标签找到同频的圈子。</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert <?=$messageType === 'success' ? 'alert-success' : 'alert-error'?>">
            <?=icon($messageType === 'success' ? 'check' : 'alert')?>
            <div><?=h($message)?></div>
        </div>
    <?php endif; ?>

    <form class="filter-bar" method="GET" action="p_dynamics.php" role="search">
        <input class="input" type="search" name="q" value="<?=h($filters['keyword'])?>" placeholder="搜索动态、作者或姓名" aria-label="搜索动态、作者或姓名">
        <input class="input" type="text" name="tag" value="<?=h($filters['tag'])?>" placeholder="标签，如：篮球" aria-label="标签筛选">
        <input class="input" type="text" name="college" value="<?=h($filters['college'])?>" placeholder="学院" aria-label="学院筛选">
        <button class="btn" type="submit"><?=icon('search', 18)?>搜索</button>
        <?php if ($filters['keyword'] !== '' || $filters['tag'] !== '' || $filters['college'] !== ''): ?>
            <a class="btn btn-ghost" href="p_dynamics.php">清除</a>
        <?php endif; ?>
    </form>

    <div class="feed-layout">
        <div class="feed-column" data-paginate="feed" data-feed-virtual data-feed-infinite data-feed-page="<?=h($page)?>" data-feed-total-pages="<?=h($totalPages)?>">
            <div class="list-meta">
                <span class="muted">共 <?=h($total)?> 条动态</span>

            </div>

            <?php if (empty($dynamics)): ?>
                <div class="card">
                    <div class="empty-state">
                        <span class="empty-icon"><?=icon('feed', 26)?></span>
                        <div class="empty-title">没有找到相关动态</div>
                        <p class="empty-copy">换个关键词或标签试试，也可以发布第一条校园动态。</p>
                    </div>
                </div>
            <?php endif; ?>

            <?php foreach ($dynamics as $dynamic): ?>
                <article class="card post-card reveal <?= (int)$dynamic['pk'] === $focusPk ? 'is-focused' : '' ?>" id="dynamic-<?=h($dynamic['pk'])?>">
                    <div class="post-head">
                        <?php $avatarOk = !empty($dynamic['avatar']) && uploadFileExists($dynamic['avatar']); ?>
                        <?php if ($avatarOk): ?>
                            <img class="avatar" src="<?=h(thumbnailUrl($dynamic['avatar'], 'avatar'))?>" alt="">
                        <?php else: ?>
                            <span class="avatar avatar-placeholder"><?=icon('user', 22)?></span>
                        <?php endif; ?>
                        <div class="post-meta">
                            <span class="post-author"><?=h($dynamic['name'] ?: $dynamic['userName'])?></span>
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
                        <?php $feedPhotos = array_values(array_filter($dynamic['photos'] ?? [], function ($p) { return uploadFileExists($p); })); ?>
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

                    <div class="post-actions">
                        <?php if ($isVerified): ?>
                            <form method="POST" action="p_dynamics.php" data-api-action="like" data-dynamic-pk="<?=h($dynamic['pk'])?>">
                                <?=csrfField()?>
                                <input type="hidden" name="dynamicPk" value="<?=h($dynamic['pk'])?>">
                                <button type="submit" name="likeDynamic" class="action-btn <?=$dynamic['liked'] ? 'is-liked' : ''?>">
                                    <?=icon('heart')?>点赞 <span class="like-count" data-like-count><?=h($dynamic['like_count'])?></span>
                                </button>
                            </form>
                            <form method="POST" action="p_dynamics.php" data-api-action="favorite" data-dynamic-pk="<?=h($dynamic['pk'])?>">
                                <?=csrfField()?>
                                <input type="hidden" name="dynamicPk" value="<?=h($dynamic['pk'])?>">
                                <button type="submit" name="favoriteDynamic" class="action-btn <?=$dynamic['favorited'] ? 'is-favorited' : ''?>">
                                    <?=icon('bookmark')?><span data-favorite-label><?=$dynamic['favorited'] ? '已收藏' : '收藏'?></span> <span class="favorite-count" data-favorite-count><?=h($dynamic['favorite_count'])?></span>
                                </button>
                            </form>
                            <?php if ((int)$dynamic['userPk'] === (int)$student['pk']): ?>
                                <a class="action-link" href="p_editDynamic.php?id=<?=h($dynamic['pk'])?>"><?=icon('edit', 14)?>编辑</a>
                                <form method="POST" data-confirm="确定删除这条动态吗？删除后不可恢复。">
                                    <?=csrfField()?>
                                    <input type="hidden" name="dynamicPk" value="<?=h($dynamic['pk'])?>">
                                    <button type="submit" name="deleteDynamic" class="action-btn danger"><?=icon('trash')?>删除</button>
                                </form>
                            <?php else: ?>
                                <a class="action-link danger" href="p_report.php?targetType=dynamic&targetPk=<?=h($dynamic['pk'])?>"><?=icon('alert', 14)?>举报</a>
                            <?php endif; ?>
                            <span class="action-count"><?=icon('chat')?>评论 <span data-comment-count><?=h($dynamic['comment_count'])?></span></span>
                            <a class="action-link" href="p_post.php?id=<?=h($dynamic['pk'])?>"><?=icon('arrow-right', 14)?>详情</a>
                        <?php else: ?>
                            <span class="action-count"><?=icon('heart')?>点赞 <?=h($dynamic['like_count'])?></span>
                            <span class="action-count"><?=icon('bookmark')?>收藏 <?=h($dynamic['favorite_count'])?></span>
                            <span class="action-count"><?=icon('chat')?>评论 <?=h($dynamic['comment_count'])?></span>
                            <a class="action-link" href="p_post.php?id=<?=h($dynamic['pk'])?>"><?=icon('arrow-right', 14)?>详情</a>
                        <?php endif; ?>
                    </div>

                    <?php $comments = $commentsByDynamic[(int)$dynamic['pk']] ?? []; ?>
                    <?php if ($comments || $isVerified): ?>
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
                                <form method="POST" class="comment-form" action="p_dynamics.php" data-api-action="comment" data-dynamic-pk="<?=h($dynamic['pk'])?>">
                                    <?=csrfField()?>
                                    <input type="hidden" name="dynamicPk" value="<?=h($dynamic['pk'])?>">
                                    <input type="hidden" name="parentPk" value="0" data-reply-input>
                                    <span class="reply-chip" data-reply-chip hidden>回复 <b data-reply-name></b><button type="button" class="reply-chip-cancel" data-cancel-reply>取消</button></span>
                                    <input class="input" type="text" name="comment" required maxlength="300" placeholder="写下评论..." data-mentions="<?=h($mentionJson)?>">
                                    <button type="submit" name="addComment" class="btn btn-sm"><?=icon('send')?>评论</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>

            <div class="list-meta list-meta-bottom" data-feed-sentinel>
                <span data-feed-status></span>

            </div>
        </div>

        <aside class="feed-side feed-sticky">
            <?php if ($isVerified): ?>
                <div class="card card-pad side-card rail-card quick-composer">
                    <div class="section-head">
                        <h2 class="section-title">发布动态</h2>
                    </div>
                    <p class="muted text-sm">记录校园里值得分享的瞬间，最多 3 张照片。</p>
                    <button type="button" class="btn btn-block mt-16" data-open-composer><?=icon('pen')?>写点什么...</button>
                </div>
            <?php else: ?>
                <div class="card card-pad side-card rail-card">
                    <div class="section-head">
                        <h2 class="section-title">实名审核中</h2>
                    </div>
                    <p class="muted text-sm">你当前可以浏览动态；实名审核通过后即可发布、点赞和评论。</p>
                    <a class="btn btn-soft btn-block mt-16" href="p_welcomeStu.php">查看审核状态</a>
                </div>
            <?php endif; ?>

            <div class="card card-pad side-card rail-card">
                <div class="section-head">
                    <h2 class="section-title">广场公约</h2>
                </div>
                <div class="muted rule-text">
                    尊重每一位同学，不发布虚假信息。<br>
                    涉及他人隐私的内容请先征得同意。<br>
                    管理员有权删除违规动态。
                </div>
            </div>
        </aside>
    </div>
</div>
<?php renderSiteFooter(); ?>