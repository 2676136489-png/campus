<?php
require_once __DIR__ . '/lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

requireAdminLogin();
if ($_SESSION['adminMustChangePassword'] ?? isInitialAdminPassword($_SESSION['adminName'])) {
    header('Location: ' . tabUrl('p_adminAuth.php'));
    exit;
}

$operMsg = '';
$operType = 'success';
if (isset($_SESSION['admin_flash'])) {
    $operMsg = $_SESSION['admin_flash']['msg'] ?? '';
    $operType = $_SESSION['admin_flash']['type'] ?? 'success';
    unset($_SESSION['admin_flash']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $operMsg = '操作验证失败，请刷新页面后重试。';
        $operType = 'error';
    } else {
        $adminRow = getUserByUserName($_SESSION['adminName'] ?? '');
        $adminPk = $adminRow ? (int)$adminRow['pk'] : 0;
        if (isset($_POST['deleteDynamic'])) {
            $dynamicPk = (int)($_POST['dynamicPk'] ?? 0);
            $result = deleteDynamic($dynamicPk, $adminPk, true);
            if ($result === true) {
                $operMsg = '动态已删除。';
            } else {
                $operMsg = $result;
                $operType = 'error';
            }
        }
    }
    $_SESSION['admin_flash'] = ['msg' => $operMsg, 'type' => $operType];
    header('Location: ' . tabUrl('p_adminDynamics.php'));
    exit;
}
$adminFilters = ['keyword' => trim($_GET['q'] ?? '')];
$dynamicsPage = getDynamicsPage(0, true, $adminFilters, max(1, (int)($_GET['dp'] ?? 1)), 10);
$dynamics = $dynamicsPage['items'];

renderHead('动态内容管理');
renderAdminHeader('动态管理');
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">动态内容管理</h1>
            <p class="page-sub">当前共 <?=h($dynamicsPage['total'])?> 条动态，可查看内容并删除违规动态。</p>
        </div>
        <div class="flex gap-sm">
            <a class="btn btn-ghost btn-sm" href="p_adminAuth.php"><?=icon('arrow-left', 15)?>返回总览</a><a class="btn btn-soft btn-sm" href="p_exportDynamics.php"><?=icon('download', 15)?>导出 CSV</a>
        </div>
    </div>

    <?php if ($operMsg): ?>
        <div class="alert <?=$operType === 'success' ? 'alert-success' : 'alert-error'?>">
            <?=icon($operType === 'success' ? 'check' : 'alert')?>
            <div><?=h($operMsg)?></div>
        </div>
    <?php endif; ?>

    <form class="filter-bar" method="GET" action="p_adminDynamics.php">
        <input class="input" type="search" name="q" value="<?=h($adminFilters['keyword'])?>" placeholder="搜索发布者或动态内容">
        <button class="btn" type="submit"><?=icon('search', 18)?>搜索</button>
        <?php if ($adminFilters['keyword'] !== ''): ?>
            <a class="btn btn-ghost" href="p_adminDynamics.php">清除</a>
        <?php endif; ?>
    </form>

    <div class="card card-pad" data-paginate="dynamics">
        <div class="section-head">
            <h2 class="section-title">动态列表</h2>
            <span class="badge badge-neutral">共 <?=h($dynamicsPage['total'])?> 条</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>发布者</th>
                        <th>内容</th>
                        <th>图片</th>
                        <th>数据</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dynamics)): ?>
                        <tr><td colspan="6" class="text-center muted">暂无动态。</td></tr>
                    <?php endif; ?>
                    <?php foreach ($dynamics as $dynamic): ?>
                        <tr>
                            <td>
                                <strong><?=h($dynamic['name'] ?: $dynamic['userName'])?></strong>
                                <div class="muted small-text"><?=h($dynamic['createTime'])?></div>
                            </td>
                            <td>
                                <?php if ($dynamic['tags']): ?>
                                    <div class="tag-list mb-16">
                                        <?php foreach (explode(',', $dynamic['tags']) as $tag): ?>
                                            <span class="tag"><?=h($tag)?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <div class="pre-line"><?=h($dynamic['content'])?></div>
                            </td>
                            <td>
                                <?php if ($dynamic['photo']): ?>
                                    <a class="thumb-link" href="<?=h(thumbnailUrl($dynamic['photo'], 'md'))?>" target="_blank" rel="noopener">
                                        <span class="avatar avatar-placeholder thumb-md">
                                            <?=icon('image', 18)?>
                                            <img src="<?=h(thumbnailUrl($dynamic['photo'], 'md'))?>" alt="" loading="lazy" onerror="this.remove()">
                                        </span>
                                        <span>动态图</span>
                                    </a>
                                <?php else: ?>
                                    <span class="muted">无图片</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div>点赞 <?=h($dynamic['like_count'])?></div>
                                <div>评论 <?=h($dynamic['comment_count'])?></div>
                            </td>
                            <td>
                                <?php if ($dynamic['status'] === 'normal'): ?>
                                    <span class="badge badge-success">正常</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">已删除</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($dynamic['status'] === 'normal'): ?>
                                    <form method="POST" onsubmit="return confirm('确定删除这条动态吗？删除后不可恢复。')">
                                        <?=csrfField()?>
                                        <input type="hidden" name="dynamicPk" value="<?=h($dynamic['pk'])?>">
                                        <button type="submit" name="deleteDynamic" class="btn btn-danger-soft btn-sm"><?=icon('trash')?>删除</button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">无操作</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="list-meta list-meta-bottom">
            <?=paginationLinks('p_adminDynamics.php', (int)($_GET['dp'] ?? 1), $dynamicsPage['totalPages'], ['q' => $adminFilters['keyword']], 'dp')?>
        </div>
    </div>
</div>
<?php renderSiteFooter([
    'links' => [
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
        ['label' => '学生注册', 'href' => 'p_registerStu.php'],
    ],
]); ?>