<?php
require_once __DIR__ . '/../lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

requireAdminLogin();
requireAdminRole('super');
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
    if (isset($_POST['setStatus'])) {
        $userPk = (int)($_POST['userPk'] ?? 0);
        $status = $_POST['status'] ?? '';
        $note = trim($_POST['reviewNote'] ?? '');
        if ($status === 'V' && empty($_POST['reviewed_card'])) {
            $operMsg = '请先查看学生证并确认资料无误。';
            $operType = 'error';
        } elseif (setUserStatus($userPk, $status, $adminPk, $note)) {
            $operMsg = '学生账号状态已更新。';
        } else {
            $operMsg = '状态更新失败。';
            $operType = 'error';
        }
    }
    $_SESSION['admin_flash'] = ['msg' => $operMsg, 'type' => $operType];
    header('Location: ' . tabUrl('p_adminStudents.php'));
    exit;
}

$adminFilters = ['keyword' => trim($_GET['q'] ?? '')];
$studentStatus = $_GET['status'] ?? '';
$studentStatusFilter = in_array($studentStatus, ['N', 'V', 'U'], true) ? $studentStatus : null;
$studentPage = getStudentsPage($studentStatusFilter, true, $adminFilters, max(1, (int)($_GET['sp'] ?? 1)), 10);
$students = $studentPage['items'];

renderHead('学生实名审核与账号管理');
renderAdminHeader('学生管理');
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">学生实名审核与账号管理</h1>
            <p class="page-sub">当前共 <?=h($studentPage['total'])?> 名学生，可审核、退回、停用或恢复账号。</p>
        </div>
        <div class="flex gap-sm">
            <a class="btn btn-ghost btn-sm" href="p_adminAuth.php"><?=icon('arrow-left', 15)?>返回总览</a><a class="btn btn-soft btn-sm" href="p_exportStudents.php"><?=icon('download', 15)?>导出 CSV</a>
        </div>
    </div>

    <?php if ($operMsg): ?>
        <div class="alert <?=$operType === 'success' ? 'alert-success' : 'alert-error'?>">
            <?=icon($operType === 'success' ? 'check' : 'alert')?>
            <div><?=h($operMsg)?></div>
        </div>
    <?php endif; ?>

    <form class="filter-bar" method="GET" action="p_adminStudents.php">
        <input class="input" type="search" name="q" value="<?=h($adminFilters['keyword'])?>" placeholder="搜索学生账号或姓名">
        <select class="input input-select" name="status">
            <option value="">全部状态</option>
            <option value="N" <?=$studentStatus === 'N' ? 'selected' : ''?>>待审核</option>
            <option value="V" <?=$studentStatus === 'V' ? 'selected' : ''?>>已通过</option>
            <option value="U" <?=$studentStatus === 'U' ? 'selected' : ''?>>已停用</option>
        </select>
        <button class="btn" type="submit"><?=icon('search', 18)?>搜索</button>
        <?php if ($adminFilters['keyword'] !== '' || $studentStatus !== ''): ?>
            <a class="btn btn-ghost" href="p_adminStudents.php">清除</a>
        <?php endif; ?>
    </form>

    <div class="card card-pad" data-paginate="students">
        <div class="section-head">
            <h2 class="section-title">学生名单</h2>
            <span class="badge badge-neutral">共 <?=h($studentPage['total'])?> 人</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>账号</th>
                        <th>实名信息</th>
                        <th>联系方式</th>
                        <th>照片</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="6" class="text-center muted">暂无学生数据。</td></tr>
                    <?php endif; ?>
                    <?php foreach ($students as $stu): ?>
                        <tr>
                            <td>
                                <strong><?=h($stu['userName'])?></strong>
                                <div class="muted small-text"><?=h($stu['createTime'])?></div>
                            </td>
                            <td>
                                <strong><?=h($stu['name'])?> · <?=h(genderText($stu['gender']))?></strong>
                                <div>学号：<?=h($stu['stuNo'])?></div>
                                <div><?=h($stu['college'])?> · <?=h($stu['grade'])?> · <?=h($stu['major'])?></div>
                                <div class="muted">出生：<?=h($stu['birth_date'])?></div>
                            </td>
                            <td>
                                <div><?=h($stu['phone'])?></div>
                                <div><?=h($stu['email'])?></div>
                                <div>QQ：<?=h($stu['QQ'])?></div>
                            </td>
                            <td>
                                <span class="avatar avatar-placeholder">
                                    <?=icon('user', 18)?>
                                    <?php if ($stu['avatar']): ?><img src="<?=h(thumbnailUrl($stu['avatar'], 'avatar'))?>" alt="" loading="lazy" onerror="this.remove()"><?php endif; ?>
                                </span>
                                <?php if ($stu['student_card']): ?>
                                    <a class="thumb-link mt-16" href="<?=h(assetUrl($stu['student_card']))?>" target="_blank" rel="noopener">
                                        <span class="avatar avatar-placeholder thumb-md">
                                            <?=icon('id', 18)?>
                                            <img src="<?=h(thumbnailUrl($stu['student_card'], 'md'))?>" alt="" loading="lazy" onerror="this.remove()">
                                        </span>
                                        <span>学生证</span>
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($stu['status'] === 'V'): ?>
                                    <span class="badge badge-success">已通过</span>
                                <?php elseif ($stu['status'] === 'U'): ?>
                                    <span class="badge badge-danger">已停用</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">待审核</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="flex gap-sm">
                                    <?php if ($stu['status'] !== 'V'): ?>
                                        <form method="POST">
                                            <?=csrfField()?>
                                            <input type="hidden" name="userPk" value="<?=h($stu['pk'])?>">
                                            <input type="hidden" name="status" value="V">
                                            <label class="checkbox-line">
                                                <input type="checkbox" name="reviewed_card" value="1" required>
                                                已核实学生证
                                            </label>
                                            <button type="submit" name="setStatus" class="btn btn-sm">通过</button>
                                        </form>
                                    <?php endif; ?>
                                    <?php if ($stu['status'] !== 'N'): ?>
                                        <details class="inline-details">
                                            <summary class="btn btn-ghost btn-sm">退回</summary>
                                            <form method="POST" class="details-form">
                                                <?=csrfField()?>
                                                <input type="hidden" name="userPk" value="<?=h($stu['pk'])?>">
                                                <input type="hidden" name="status" value="N">
                                                <input class="input input-sm" type="text" name="reviewNote" required maxlength="200" placeholder="填写退回原因">
                                                <button type="submit" name="setStatus" class="btn btn-sm">确认退回</button>
                                            </form>
                                        </details>
                                    <?php endif; ?>
                                    <?php if ($stu['status'] !== 'U'): ?>
                                        <form method="POST" data-confirm="确定停用该学生账号吗？停用后无法登录。">
                                            <?=csrfField()?>
                                            <input type="hidden" name="userPk" value="<?=h($stu['pk'])?>">
                                            <input type="hidden" name="status" value="U">
                                            <button type="submit" name="setStatus" class="btn btn-danger-soft btn-sm">停用</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST">
                                            <?=csrfField()?>
                                            <input type="hidden" name="userPk" value="<?=h($stu['pk'])?>">
                                            <input type="hidden" name="status" value="V">
                                            <button type="submit" name="setStatus" class="btn btn-sm">恢复</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="list-meta list-meta-bottom">
            <?=paginationLinks('p_adminStudents.php', (int)($_GET['sp'] ?? 1), $studentPage['totalPages'], ['q' => $adminFilters['keyword'], 'status' => $studentStatus], 'sp')?>
        </div>
    </div>
</div>
<?php renderSiteFooter([
    'links' => [
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
        ['label' => '学生注册', 'href' => 'p_registerStu.php'],
    ],
]); ?>