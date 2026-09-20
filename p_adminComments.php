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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $adminRow = getUserByUserName($_SESSION['adminName'] ?? '');
    $adminPk = $adminRow ? (int)$adminRow['pk'] : 0;
    if (isset($_POST['deleteComment'])) {
        $commentPk = (int)($_POST['commentPk'] ?? 0);
        $result = deleteComment($commentPk, $adminPk, true);
        if (isset($result['ok']) && $result['ok']) {
            $operMsg = '评论已删除。';
        } else {
            $operMsg = $result['message'] ?? '评论删除失败。';
            $operType = 'error';
        }
    }
    $_SESSION['admin_flash'] = ['msg' => $operMsg, 'type' => $operType];
    header('Location: ' . tabUrl('p_adminComments.php'));
    exit;
}

$adminFilters = ['keyword' => trim($_GET['q'] ?? '')];
$commentsPage = getCommentsPage($adminFilters, max(1, (int)($_GET['cp'] ?? 1)), 10);
$comments = $commentsPage['items'];

renderHead('评论管理');
renderAdminHeader('评论管理');
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">评论管理</h1>
            <p class="page-sub">当前共 <?=h($commentsPage['total'])?> 条评论，可搜索并删除违规评论。</p>
        </div>
        <div class="flex gap-sm">
            <a class="btn btn-ghost btn-sm" href="p_adminAuth.php"><?=icon('arrow-left', 15)?>返回总览</a><a class="btn btn-soft btn-sm" href="p_exportComments.php"><?=icon('download', 15)?>导出 CSV</a>
        </div>
    </div>

    <?php if ($operMsg): ?>
        <div class="alert <?=$operType === 'success' ? 'alert-success' : 'alert-error'?>">
            <?=icon($operType === 'success' ? 'check' : 'alert')?>
            <div><?=h($operMsg)?></div>
        </div>
    <?php endif; ?>

    <form class="filter-bar" method="GET" action="p_adminComments.php">
        <input class="input" type="search" name="q" value="<?=h($adminFilters['keyword'])?>" placeholder="搜索评论内容或评论者">
        <button class="btn" type="submit"><?=icon('search', 18)?>搜索</button>
        <?php if ($adminFilters['keyword'] !== ''): ?>
            <a class="btn btn-ghost" href="p_adminComments.php">清除</a>
        <?php endif; ?>
    </form>

    <div class="card card-pad" data-paginate="comments">
        <div class="section-head">
            <h2 class="section-title">评论列表</h2>
            <span class="badge badge-neutral">共 <?=h($commentsPage['total'])?> 条</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>评论内容</th>
                        <th>评论者</th>
                        <th>所属动态发布者</th>
                        <th>时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($comments)): ?>
                        <tr><td colspan="5" class="text-center muted">暂无评论。</td></tr>
                    <?php endif; ?>
                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td class="pre-line"><?=h($comment['content'])?></td>
                            <td><strong><?=h($comment['commentName'] ?: $comment['commentUserName'])?></strong></td>
                            <td><?=h($comment['dynamicOwnerName'] ?: ('#' . $comment['dynamicOwnerPk']))?></td>
                            <td><?=h($comment['createTime'])?></td>
                            <td>
                                <form method="POST" data-confirm="确定删除这条评论吗？">
                                    <?=csrfField()?>
                                    <input type="hidden" name="commentPk" value="<?=h($comment['pk'])?>">
                                    <button type="submit" name="deleteComment" class="btn btn-danger-soft btn-sm"><?=icon('trash')?>删除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="list-meta list-meta-bottom">
            <?=paginationLinks('p_adminComments.php', (int)($_GET['cp'] ?? 1), $commentsPage['totalPages'], ['q' => $adminFilters['keyword']], 'cp')?>
        </div>
    </div>
</div>
<?php renderSiteFooter([
    'links' => [
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
        ['label' => '学生注册', 'href' => 'p_registerStu.php'],
    ],
]); ?>