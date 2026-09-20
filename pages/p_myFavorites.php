<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireVerifiedStudent();
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$feed = getDynamicsPage((int)$student['pk'], false, ['favoriteUserPk' => (int)$student['pk']], $page, $perPage);
$dynamics = $feed['items'];
$total = $feed['total'];
$totalPages = $feed['totalPages'];

renderHead('我的收藏');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => 'p_welcomeStu.php'],
        ['label' => '动态广场', 'href' => 'p_dynamics.php'],
        ['label' => '同学', 'href' => 'p_allStudents.php'],
    ],
    'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
    'composer' => true,
]);
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">我的收藏</h1>
            <p class="page-sub">收藏过的校园动态都在这里，方便你随时回看。</p>
        </div>
        <span class="badge badge-neutral">共 <?=h($total)?> 条</span>
    </div>

    <?php if (empty($dynamics)): ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><?=icon('bookmark', 26)?></span>
                <div class="empty-title">还没有收藏动态</div>
                <p class="empty-copy">在动态广场点击收藏按钮，就能在这里找到它们。</p>
                <a class="btn" href="p_dynamics.php">去动态广场看看</a>
            </div>
        </div>
    <?php else: ?>
        <div class="feed-column">
            <?php foreach ($dynamics as $dynamic): ?>
                <article class="card post-card favorite-item">
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
                        <form method="POST" action="p_myFavorites.php" data-api-action="favorite" data-dynamic-pk="<?=h($dynamic['pk'])?>" data-remove-on-unfavorite="1">
                            <?=csrfField()?>
                            <input type="hidden" name="dynamicPk" value="<?=h($dynamic['pk'])?>">
                            <button type="submit" name="favoriteDynamic" class="action-btn is-favorited">
                                <?=icon('bookmark')?><span data-favorite-label>已收藏</span> <span class="favorite-count" data-favorite-count><?=h($dynamic['favorite_count'])?></span>
                            </button>
                        </form>
                        <a class="action-link" href="p_post.php?id=<?=h($dynamic['pk'])?>"><?=icon('arrow-right', 14)?>查看详情</a>
                    </div>
                </article>
            <?php endforeach; ?>
            <div class="list-meta list-meta-bottom">
                <?=paginationLinks('p_myFavorites.php', $page, $totalPages, [])?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php renderSiteFooter(); ?>