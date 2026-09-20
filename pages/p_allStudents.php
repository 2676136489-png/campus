<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

$student = requireVerifiedStudent();
$filters = [
    'keyword' => trim($_GET['q'] ?? ''),
    'tag' => trim($_GET['tag'] ?? ''),
];
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$result = getStudentsPage('V', false, $filters, $page, $perPage);
$allStu = $result['items'];
$total = $result['total'];
$totalPages = $result['totalPages'];

renderHead('同学列表');
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
<div class="container directory" data-paginate="students">
    <div class="page-head">
        <div>
            <h1 class="page-title">已认证同学</h1>
            <p class="page-sub">通过实名认证的同学，可以按兴趣找到同频的人。</p>
        </div>
        <span class="badge badge-neutral">共 <?=h($total)?> 人</span>
    </div>

    <form class="filter-bar" method="GET" action="p_allStudents.php" role="search">
        <input class="input" type="search" name="q" value="<?=h($filters['keyword'])?>" placeholder="搜索姓名、学号、学院或专业" aria-label="搜索姓名、学号、学院或专业">
        <input class="input" type="text" name="tag" value="<?=h($filters['tag'])?>" placeholder="兴趣标签，如：摄影" aria-label="兴趣标签筛选">
        <button class="btn" type="submit"><?=icon('search', 18)?>搜索</button>
        <?php if ($filters['keyword'] !== '' || $filters['tag'] !== ''): ?>
            <a class="btn btn-ghost" href="p_allStudents.php">清除</a>
        <?php endif; ?>
    </form>

    <?php if (empty($allStu)): ?>
        <div class="card">
            <div class="empty-state">
                <span class="empty-icon"><?=icon('users', 26)?></span>
                <div class="empty-title">没有找到匹配的同学</div>
                <p class="empty-copy">换个关键词或标签试试。</p>
            </div>
        </div>
    <?php else: ?>
        <div class="people-list">
            <?php foreach ($allStu as $stu): ?>
                <div class="card people-card reveal">
                    <?php $avatarOk = !empty($stu['avatar']) && uploadFileExists($stu['avatar']); ?>
                    <?php if ($avatarOk): ?>
                        <img class="avatar avatar-lg" src="<?=h(thumbnailUrl($stu['avatar'], 'avatar'))?>" alt="">
                    <?php else: ?>
                        <span class="avatar avatar-lg avatar-placeholder"><?=icon('user', 24)?></span>
                    <?php endif; ?>
                    <div class="people-main">
                        <div class="people-name"><?=h($stu['name'])?><span class="gender"><?=h(genderText($stu['gender']))?></span></div>
                        <div class="people-sub">@<?=h($stu['userName'])?></div>
                        <div class="people-sub"><?=h($stu['college'])?><br><?=h($stu['grade'])?> · <?=h($stu['major'])?></div>
                    </div>
                    <div class="people-side">
                        <div class="tag-list people-tags">
                            <?php if ($stu['tags'] !== ''): ?>
                                <?php foreach (array_filter(explode(',', $stu['tags'])) as $tag): ?>
                                    <a class="tag tag-link" href="p_allStudents.php?tag=<?=h(urlencode($tag))?>"><?=h($tag)?></a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="people-action">
                        <a class="btn btn-soft btn-sm people-view" href="p_profile.php?user=<?=h($stu['pk'])?>"><?=icon('user')?>查看主页</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="list-meta list-meta-bottom">
            <?=paginationLinks('p_allStudents.php', $page, $totalPages, array_filter($filters))?>
        </div>
    <?php endif; ?>
</div>
<?php renderSiteFooter(); ?>