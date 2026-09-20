<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireStudentLogin();
$isVerified = $student['status'] === 'V';
$tagCount = $student['tags'] === '' ? 0 : count(array_filter(explode(',', $student['tags'])));
$unreadNotifications = getUnreadNotificationCount((int)$student['pk']);
$unreadMessages = getUnreadMessageCount((int)$student['pk']);

renderHead('学生中心');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => 'p_welcomeStu.php', 'active' => true],
        ['label' => '动态广场', 'href' => 'p_dynamics.php'],
        ['label' => '同学', 'href' => 'p_allStudents.php'],
    ],
    'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
    'composer' => ($student['status'] === 'V'),
]);
?>
<div class="container">
    <section class="welcome-hero reveal">
        <div>
            <div class="hero-kicker">Campus Circle</div>
            <h1>你好，<?=h($student['name'])?></h1>
            <p class="hero-copy">@<?=h($student['userName'])?> · <?=h($student['college'])?> · <?=h($student['major'])?></p>
            <?php if ($isVerified): ?>
                <span class="badge badge-success"><?=icon('check', 14)?>已实名认证</span>
            <?php else: ?>
                <span class="badge badge-warning"><?=icon('clock', 14)?>待审核</span>
            <?php endif; ?>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><strong><?=$isVerified ? '已通过' : '待审核'?></strong><span>实名状态</span></div>
            <div class="hero-stat"><strong data-count="<?=h($tagCount)?>"><?=h($tagCount)?></strong><span>兴趣标签</span></div>
            <div class="hero-stat"><strong><?=h($unreadNotifications)?></strong><span>未读通知</span></div>
            <div class="hero-stat"><strong><?=h($unreadMessages)?></strong><span>未读私信</span></div>
        </div>
    </section>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-error"><?=icon('alert')?><div><?=h($_SESSION['flash_error'])?></div></div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if ($isVerified): ?>
        <div class="alert alert-success"><?=icon('check')?><div>实名状态：已通过。你可以发布动态、点赞评论，并维护自己的圈子标签。</div></div>
    <?php else: ?>
        <div class="alert alert-info"><?=icon('clock')?><div>实名状态：待管理员审核。审核通过后即可发布动态、点赞评论和设置圈子标签。</div></div>
    <?php endif; ?>

    <?php $announcements = getPublishedAnnouncements(3); ?>
    <?php if ($announcements): ?>
        <div class="card card-pad mb-24">
            <div class="section-head">
                <h2 class="section-title">校园公告</h2>
                <span class="badge badge-neutral">最新 <?=count($announcements)?> 条</span>
            </div>
            <div class="announcement-list">
                <?php foreach ($announcements as $item): ?>
                    <div class="announcement-item">
                        <div class="announcement-title"><?=h($item['title'])?></div>
                        <div class="announcement-content muted"><?=h(function_exists('mb_substr') ? mb_substr($item['content'], 0, 120, 'UTF-8') : substr($item['content'], 0, 120))?></div>
                        <div class="muted small-text"><?=h($item['createTime'])?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <span class="stat-icon"><?=icon('shield', 21)?></span>
            <span>
                <?php if ($isVerified): ?>
                    <span class="stat-value stat-value-md">已通过</span>
                <?php else: ?>
                    <span class="stat-value stat-value-md">待审核</span>
                <?php endif; ?>
                <span class="stat-label">实名状态</span>
            </span>
        </div>
        <div class="stat-card">
            <span class="stat-icon"><?=icon('id', 21)?></span>
            <span><span class="stat-value"><?=h($student['stuNo'])?></span><span class="stat-label">学号</span></span>
        </div>
        <div class="stat-card">
            <span class="stat-icon"><?=icon('school', 21)?></span>
            <span><span class="stat-value"><?=h($student['grade'])?></span><span class="stat-label">年级</span></span>
        </div>
        <div class="stat-card">
            <span class="stat-icon"><?=icon('tag', 21)?></span>
            <span><span class="stat-value" data-count="<?=h($tagCount)?>"><?=h($tagCount)?></span><span class="stat-label">兴趣标签</span></span>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card card-pad">
            <div class="section-head">
                <h2 class="section-title">个人资料</h2>
                <a class="btn btn-soft btn-sm" href="p_editStuInfo.php"><?=icon('edit')?>编辑</a>
            </div>
            <div class="flex mb-16">
                <?php $avatarOk = !empty($student['avatar']) && uploadFileExists($student['avatar']); ?>
                <?php if ($avatarOk): ?>
                    <img class="avatar avatar-xl" src="<?=h(thumbnailUrl($student['avatar'], 'avatar'))?>" alt="我的头像">
                <?php else: ?>
                    <span class="avatar avatar-xl avatar-placeholder"><?=icon('user', 26)?></span>
                <?php endif; ?>
                <div>
                    <div class="name-line"><?=h($student['name'])?></div>
                    <div class="muted small-text"><?=h($student['userName'])?> · <?=h(genderText($student['gender']))?></div>
                </div>
            </div>
            <dl class="profile-list">
                <div class="profile-item"><dt>学号</dt><dd><?=h($student['stuNo'])?></dd></div>
                <div class="profile-item"><dt>出生年月</dt><dd><?=h($student['birth_date'])?></dd></div>
                <div class="profile-item"><dt>学院</dt><dd><?=h($student['college'])?></dd></div>
                <div class="profile-item"><dt>专业</dt><dd><?=h($student['major'])?></dd></div>
                <div class="profile-item"><dt>手机</dt><dd><?=h($student['phone'])?></dd></div>
                <div class="profile-item"><dt>Email</dt><dd><?=h($student['email'])?></dd></div>
                <div class="profile-item"><dt>QQ</dt><dd><?=h($student['QQ'])?></dd></div>
            </dl>
            <?php if ($student['tags'] !== ''): ?>
                <div class="tag-list mt-16">
                    <?php foreach (explode(',', $student['tags']) as $tag): ?>
                        <span class="tag"><?=h($tag)?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="card card-pad">
            <div class="section-head">
                <h2 class="section-title">快捷操作</h2>
            </div>
            <div class="quick-actions">
                <a class="quick-action" href="p_editStuInfo.php"><?=icon('edit')?>修改个人资料</a>
                <?php if ($isVerified): ?>
                    <a class="quick-action" href="p_editStuTags.php"><?=icon('tag')?>设置兴趣标签</a>
                <?php endif; ?>
                <a class="quick-action" href="p_dynamics.php"><?=icon('feed')?>校园动态广场</a>
                <?php if ($isVerified): ?>
                    <a class="quick-action" href="p_publishDynamic.php"><?=icon('plus')?>发布新动态</a>
                <?php endif; ?>
                <a class="quick-action" href="p_allStudents.php"><?=icon('users')?>查看同学</a>
<a class="quick-action" href="p_myFavorites.php"><?=icon('bookmark')?>我的收藏</a>
                <a class="quick-action" href="p_notifications.php"><?=icon('bell')?>通知中心</a>
                <a class="quick-action" href="p_settings.php"><?=icon('settings')?>通知设置</a>
                <a class="quick-action" href="p_feedback.php"><?=icon('mail')?>意见反馈</a>
                <a class="quick-action" href="p_changePwdStu.php"><?=icon('lock')?>修改密码</a>
                <a class="quick-action danger" href="p_delUser.php"><?=icon('trash')?>注销账号</a>
            </div>
        </div>
    </div>

    <?php $recommended = $isVerified ? getRecommendedStudents((int)$student['pk'], 3) : []; ?>
    <?php if ($isVerified): ?>
        <div class="section-head mt-24">
            <h2 class="section-title">可能认识的人</h2>
            <div class="flex gap-sm">
                <button type="button" class="btn btn-soft btn-sm" data-recommend-refresh><?=icon('refresh')?>换一批</button>
                <a class="btn btn-soft btn-sm" href="p_allStudents.php"><?=icon('users')?>查看全部同学</a>
            </div>
        </div>
        <div class="recommend-list" data-recommend-list>
            <?php if (empty($recommended)): ?>
                <p class="muted">暂无推荐，去同学列表看看吧。</p>
            <?php endif; ?>
            <?php foreach ($recommended as $person): ?>
                <?php $personAvatar = !empty($person['avatar']) && uploadFileExists($person['avatar']); ?>
                <a class="card recommend-card" href="p_profile.php?user=<?=h($person['pk'])?>" data-person-card>
                    <?php if ($personAvatar): ?>
                        <img class="avatar" src="<?=h(thumbnailUrl($person['avatar'], 'avatar'))?>" alt="">
                    <?php else: ?>
                        <span class="avatar avatar-placeholder"><?=icon('user', 20)?></span>
                    <?php endif; ?>
                    <div class="recommend-main">
                        <div class="recommend-name"><?=h($person['name'])?></div>
                        <div class="muted small-text"><?=h($person['college'])?></div>
                        <?php if (!empty($person['sharedTags'])): ?>
                            <div class="tag-list recommend-tags">
                                <?php foreach (array_slice($person['sharedTags'], 0, 2) as $tag): ?>
                                    <span class="tag"><?=h($tag)?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php renderSiteFooter(); ?>
