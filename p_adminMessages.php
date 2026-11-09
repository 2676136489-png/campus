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
    if (isset($_POST['deleteMessageAdmin'])) {
        if (deleteMessageByAdmin($adminPk, (int)($_POST['messagePk'] ?? 0))) {
            $operMsg = '私信已删除。';
        } else {
            $operMsg = '私信删除失败。';
            $operType = 'error';
        }
    }
    $_SESSION['admin_flash'] = ['msg' => $operMsg, 'type' => $operType];
    header('Location: ' . tabUrl('p_adminMessages.php'));
    exit;
}

$adminFilters = ['keyword' => trim($_GET['q'] ?? '')];
$adminMessagesPage = getAdminMessagesPage($adminFilters, max(1, (int)($_GET['mp'] ?? 1)), 10);
$adminMessages = $adminMessagesPage['items'];

renderHead('私信审计');
renderAdminHeader('私信审计');
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">私信审计</h1>
            <p class="page-sub">当前共 <?=h($adminMessagesPage['total'])?> 条私信记录，可搜索并删除违规私信。</p>
        </div>
        <div class="flex gap-sm">
            <a class="btn btn-ghost btn-sm" href="p_adminAuth.php"><?=icon('arrow-left', 15)?>返回总览</a><a class="btn btn-soft btn-sm" href="p_exportMessages.php"><?=icon('download', 15)?>导出 CSV</a>
        </div>
    </div>

    <?php if ($operMsg): ?>
        <div class="alert <?=$operType === 'success' ? 'alert-success' : 'alert-error'?>">
            <?=icon($operType === 'success' ? 'check' : 'alert')?>
            <div><?=h($operMsg)?></div>
        </div>
    <?php endif; ?>

    <form class="filter-bar" method="GET" action="p_adminMessages.php">
        <input class="input" type="search" name="q" value="<?=h($adminFilters['keyword'])?>" placeholder="搜索发送者、接收者或消息内容">
        <button class="btn" type="submit"><?=icon('search', 18)?>搜索</button>
        <?php if ($adminFilters['keyword'] !== ''): ?>
            <a class="btn btn-ghost" href="p_adminMessages.php">清除</a>
        <?php endif; ?>
    </form>

    <div class="card card-pad" data-paginate="messages">
        <div class="section-head">
            <h2 class="section-title">私信记录</h2>
            <span class="badge badge-neutral">共 <?=h($adminMessagesPage['total'])?> 条</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>发送者</th>
                        <th>接收者</th>
                        <th>内容</th>
                        <th>状态</th>
                        <th>时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($adminMessages)): ?>
                        <tr><td colspan="6" class="text-center muted">暂无私信记录。</td></tr>
                    <?php endif; ?>
                    <?php foreach ($adminMessages as $msg): ?>
                        <tr>
                            <td><strong><?=h($msg['senderRealName'] ?: $msg['senderName'])?></strong></td>
                            <td><strong><?=h($msg['receiverRealName'] ?: $msg['receiverName'])?></strong></td>
                            <td class="pre-line muted"><?=h($msg['image'] !== '' ? '[图片]' : ($msg['status'] === 'recalled' ? '撤回了一条消息' : $msg['content']))?></td>
                            <td><?=h($msg['status'] === 'recalled' ? '已撤回' : ((int)$msg['isRead'] === 1 ? '已读' : '未读'))?></td>
                            <td><?=h($msg['createTime'])?></td>
                            <td>
                                <form method="POST" data-confirm="确定删除这条私信吗？">
                                    <?=csrfField()?>
                                    <input type="hidden" name="messagePk" value="<?=h($msg['pk'])?>">
                                    <button type="submit" name="deleteMessageAdmin" class="btn btn-danger-soft btn-sm"><?=icon('trash')?>删除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="list-meta list-meta-bottom">
            <?=paginationLinks('p_adminMessages.php', (int)($_GET['mp'] ?? 1), $adminMessagesPage['totalPages'], ['q' => $adminFilters['keyword']], 'mp')?>
        </div>
    </div>
</div>
<?php renderSiteFooter([
    'links' => [
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
        ['label' => '学生注册', 'href' => 'p_registerStu.php'],
    ],
]); ?>