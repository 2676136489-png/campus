<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

requireAdminLogin();
$admin = getUserByUserName($_SESSION['adminName'] ?? '');
$adminPk = $admin ? (int)$admin['pk'] : 0;
$error = '';
$success = '';
$editPk = (int)($_GET['edit'] ?? 0);
$editing = $editPk > 0 ? getAnnouncement($editPk) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } elseif (isset($_POST['saveAnnouncement'])) {
        $result = $editPk > 0
            ? updateAnnouncement($editPk, $adminPk, $_POST['title'] ?? '', $_POST['content'] ?? '', !empty($_POST['isPublished']))
            : createAnnouncement($adminPk, $_POST['title'] ?? '', $_POST['content'] ?? '', !empty($_POST['isPublished']));
        if ($result === true) {
            $success = $editPk > 0 ? '公告已更新。' : '公告已发布。';
            $editPk = 0;
            $editing = null;
        } else {
            $error = $result;
        }
    } elseif (isset($_POST['deleteAnnouncement'])) {
        deleteAnnouncement((int)($_POST['pk'] ?? 0), $adminPk);
        $success = '公告已删除。';
    }
}

$page = getAnnouncementsPage(max(1, (int)($_GET['page'] ?? 1)), 10);
$announcements = $page['items'];

renderHead('公告管理');
renderSiteHeader([
    'home' => 'p_adminAuth.php',
    'nav' => [
        ['label' => '后台总览', 'href' => 'p_adminAuth.php'],
        ['label' => '公告管理', 'href' => 'p_announcements.php', 'active' => true],
        ['label' => '敏感词管理', 'href' => 'p_sensitiveWords.php'],
        ['label' => '学生入口', 'href' => 'p_loginStu.php'],
    ],
    'actions' => '<a class="btn btn-ghost btn-sm" href="p_adminAuth.php?logout=1">' . icon('logout') . '退出</a>',
]);
?>
<div class="container narrow" data-paginate="announcements">
    <div class="page-head">
        <div>
            <h1 class="page-title">公告管理</h1>
            <p class="page-sub">发布的公告会展示在学生中心首页。</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?=icon('check')?><div><?=h($success)?></div></div>
    <?php endif; ?>

    <div class="card card-pad mb-24">
        <div class="section-head">
            <h2 class="section-title"><?=$editing ? '编辑公告' : '发布公告'?></h2>
        </div>
        <form method="POST">
            <?=csrfField()?>
            <div class="form-field">
                <label class="form-label" for="title">标题</label>
                <input class="input" type="text" name="title" id="title" required maxlength="100" value="<?=h($editing['title'] ?? '')?>">
            </div>
            <div class="form-field">
                <label class="form-label" for="content">内容</label>
                <textarea name="content" id="content" required maxlength="2000"><?=h($editing['content'] ?? '')?></textarea>
            </div>
            <label class="pref-item mb-24">
                <input type="checkbox" name="isPublished" value="1" <?=($editing && !$editing['isPublished']) ? '' : 'checked'?>>
                <span>立即发布</span>
            </label>
            <div class="flex">
                <button type="submit" name="saveAnnouncement" class="btn"><?=icon('check')?>保存</button>
                <?php if ($editing): ?>
                    <a class="btn btn-ghost" href="p_announcements.php">取消编辑</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="card card-pad">
        <div class="section-head">
            <h2 class="section-title">公告列表</h2>
            <span class="badge badge-neutral">共 <?=h($page['total'])?> 条</span>
        </div>
        <?php if (empty($announcements)): ?>
            <p class="muted">暂无公告。</p>
        <?php else: ?>
            <div class="announcement-list">
                <?php foreach ($announcements as $item): ?>
                    <div class="announcement-item">
                        <div>
                            <div class="announcement-title"><?=h($item['title'])?>
                                <?php if ((int)$item['isPublished'] !== 1): ?><span class="badge badge-neutral">草稿</span><?php endif; ?>
                            </div>
                            <div class="muted small-text"><?=h($item['adminName'])?> · <?=h($item['createTime'])?></div>
                        </div>
                        <div class="flex gap-sm">
                            <a class="btn btn-soft btn-sm" href="p_announcements.php?edit=<?=h($item['pk'])?>"><?=icon('edit')?>编辑</a>
                            <form method="POST" data-confirm="确定删除这条公告吗？">
                                <?=csrfField()?>
                                <input type="hidden" name="pk" value="<?=h($item['pk'])?>">
                                <button type="submit" name="deleteAnnouncement" class="btn btn-danger-soft btn-sm"><?=icon('trash')?>删除</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="list-meta list-meta-bottom">
                <?=paginationLinks('p_announcements.php', $page['page'], $page['totalPages'])?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php renderSiteFooter([
    'links' => [
        ['label' => '后台总览', 'href' => 'p_adminAuth.php'],
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
    ],
]); ?>