<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireStudentLogin();
$targetPk = (int)($_GET['user'] ?? 0);
$profile = getStudentByPk($targetPk);

renderHead('同学主页');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => 'p_welcomeStu.php'],
        ['label' => '动态广场', 'href' => 'p_dynamics.php'],
        ['label' => '同学', 'href' => 'p_allStudents.php', 'active' => true],
    ],
    'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
    'composer' => ($student['status'] === 'V'),
]);
?>
<div class="container profile-page" data-paginate="profile">
    <?php if (!$profile || $profile['status'] !== 'V'): ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><?=icon('user', 26)?></span>
                <div class="empty-title">同学不存在或未公开</div>
                <p class="empty-copy">该账号可能已注销或还未通过实名审核。</p>
                <a class="btn" href="p_allStudents.php">返回同学列表</a>
            </div>
        </div>
    <?php else: ?>
        <?php
            $page = max(1, (int)($_GET['page'] ?? 1));
            $feed = getDynamicsPage((int)$student['pk'], false, ['authorPk' => $targetPk], $page, 10);
            $dynamics = $feed['items'];
            $avatarOk = !empty($profile['avatar']) && uploadFileExists($profile['avatar']);
            $isSelf = (int)$profile['pk'] === (int)$student['pk'];
            $followCounts = getFollowCounts($targetPk);
            $isFollowing = !$isSelf && isFollowing((int)$student['pk'], $targetPk);
            $isBlockedWith = !$isSelf && isBlocked((int)$student['pk'], $targetPk);
        ?>
        <section class="card profile-hero profile-cover reveal is-visible">
            <?php if ($avatarOk): ?>
                <img class="avatar avatar-xl" src="<?=h(thumbnailUrl($profile['avatar'], 'avatar'))?>" alt="">
            <?php else: ?>
                <span class="avatar avatar-xl avatar-placeholder"><?=icon('user', 30)?></span>
            <?php endif; ?>
            <div class="profile-hero-main">
                <div class="profile-name"><?=h($profile['name'])?><span class="gender"><?=h(genderText($profile['gender']))?></span></div>
                <div class="muted">@<?=h($profile['userName'])?><?= $isSelf ? ' · 这是你的主页' : '' ?></div>
                <div class="muted"><?=h($profile['college'])?> · <?=h($profile['grade'])?> · <?=h($profile['major'])?></div>
                <?php if ($profile['tags'] !== ''): ?>
                    <div class="tag-list mt-16">
                        <?php foreach (array_filter(explode(',', $profile['tags'])) as $tag): ?>
                            <a class="tag tag-link" href="p_allStudents.php?tag=<?=h(urlencode($tag))?>"><?=h($tag)?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="profile-hero-actions">
                <div class="follow-stats">
                    <span><strong data-follower-count><?=h($followCounts['followers'])?></strong> 关注者</span>
                    <span><strong data-following-count><?=h($followCounts['following'])?></strong> 正在关注</span>
                </div>
                <span class="badge badge-success"><?=icon('check', 14)?>已实名认证</span>
                <?php if (!$isSelf): ?>
                    <form method="POST" data-api-action="follow" data-target-user="<?=h($profile['pk'])?>">
                        <?=csrfField()?>
                        <button type="submit" class="btn <?=$isFollowing ? 'btn-ghost' : ''?>">
                            <?=icon('users')?><span data-follow-label><?=$isFollowing ? '已关注' : '关注'?></span>
                        </button>
                    </form>
                    <form method="POST" data-api-action="block" data-target-user="<?=h($profile['pk'])?>">
                        <?=csrfField()?>
                        <button type="submit" class="btn <?=$isBlockedWith ? 'btn-ghost' : 'btn-danger-soft'?>">
                            <?=icon('lock')?><span data-block-label><?=$isBlockedWith ? '解除拉黑' : '拉黑'?></span>
                        </button>
                    </form>
                    <a class="btn btn-soft btn-sm" href="p_chat.php?user=<?=h($profile['pk'])?>"><?=icon('chat')?>私信</a>
                    <a class="btn btn-soft btn-sm" href="p_allStudents.php"><?=icon('users')?>返回同学</a>
                <?php endif; ?>
            </div>
        </section>

        <div class="section-head mt-24">
            <h2 class="section-title">TA 的校园动态</h2>
            <span class="badge badge-neutral">共 <?=h($feed['total'])?> 条</span>
        </div>

        <?php if (empty($dynamics)): ?>
            <div class="card">
                <div class="empty-state">
                    <span class="empty-icon"><?=icon('feed', 26)?></span>
                    <div class="empty-title">还没有动态</div>
                    <p class="empty-copy">等 TA 发布第一条校园动态后，会出现在这里。</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($dynamics as $dynamic): ?>
                <article class="card post-card reveal is-visible">
                    <div class="post-head">
                        <?php if ($avatarOk): ?>
                            <img class="avatar" src="<?=h(thumbnailUrl($profile['avatar'], 'avatar'))?>" alt="">
                        <?php else: ?>
                            <span class="avatar avatar-placeholder"><?=icon('user', 22)?></span>
                        <?php endif; ?>
                        <div class="post-meta">
                            <span class="post-author"><?=h($profile['name'])?></span>
                            <span class="post-time"><?=h($dynamic['createTime'])?></span>
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
                        <span class="action-count"><?=icon('heart')?>点赞 <?=h($dynamic['like_count'])?></span>
                        <span class="action-count"><?=icon('chat')?>评论 <?=h($dynamic['comment_count'])?></span>
                        <a class="action-link" href="p_dynamics.php?focus=<?=h($dynamic['pk'])?>">查看详情</a>
                    </div>
                </article>
            <?php endforeach; ?>

            <div class="list-meta list-meta-bottom">
                <?=paginationLinks('p_profile.php', $page, $feed['totalPages'], ['user' => $targetPk])?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php renderSiteFooter(); ?>