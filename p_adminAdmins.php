<?php
require_once __DIR__ . '/lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

requireAdminLogin();
requireAdminRole('super');
if ($_SESSION['adminMustChangePassword'] ?? isInitialAdminPassword($_SESSION['adminName'])) {
    header('Location: ' . tabUrl('p_adminAuth.php'));
    exit;
}

$operMsg = '';
$operType = 'success';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $adminPk = (int)getUserByUserName($_SESSION['adminName'])['pk'];
    if (isset($_POST['createAdmin'])) {
        $result = createAdminUser($_POST['adminUser'] ?? '', $_POST['adminPwd'] ?? '', $_POST['adminRole'] ?? 'operator', $adminPk);
        if ($result === true) {
            $operMsg = '管理员账号已创建。';
        } else {
            $operMsg = $result;
            $operType = 'error';
        }
    } elseif (isset($_POST['setAdminRole'])) {
        $target = trim($_POST['adminUser'] ?? '');
        if ($target === $_SESSION['adminName']) {
            $operMsg = '不能修改自己的角色。';
            $operType = 'error';
        } else {
            $result = setAdminRole($target, $_POST['adminRole'] ?? 'operator', $adminPk);
            $operMsg = $result === true ? '角色已更新。' : $result;
            $operType = $result === true ? 'success' : 'error';
        }
    } elseif (isset($_POST['setAdminStatus'])) {
        $target = trim($_POST['adminUser'] ?? '');
        if ($target === $_SESSION['adminName']) {
            $operMsg = '不能停用当前账号。';
            $operType = 'error';
        } else {
            $status = ($_POST['adminStatus'] ?? '') === 'U' ? 'U' : 'V';
            $result = setAdminStatus($target, $status, $adminPk);
            $operMsg = $result === true ? '账号状态已更新。' : $result;
            $operType = $result === true ? 'success' : 'error';
        }
    }
}

$admins = getAdminUsers();
renderHead('管理员账号');
renderAdminHeader('管理员账号');
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">管理员账号</h1>
            <p class="page-sub">超级管理员可以创建运营管理员，并管理角色与账号状态。</p>
        </div>
    </div>

    <?php if ($operMsg): ?>
        <div class="alert <?=$operType === 'success' ? 'alert-success' : 'alert-error'?>">
            <?=icon($operType === 'success' ? 'check' : 'alert')?>
            <div><?=h($operMsg)?></div>
        </div>
    <?php endif; ?>

    <div class="card card-pad mb-24">
        <div class="section-head">
            <h2 class="section-title">新建管理员</h2>
        </div>
        <form method="POST">
            <?=csrfField()?>
            <div class="form-field">
                <label class="form-label" for="adminUser">用户名</label>
                <input class="input" type="text" name="adminUser" id="adminUser" required maxlength="30" autocomplete="off">
            </div>
            <div class="form-field">
                <label class="form-label" for="adminPwd">初始密码</label>
                <input class="input" type="password" name="adminPwd" id="adminPwd" required maxlength="64" autocomplete="new-password">
            </div>
            <div class="form-field">
                <label class="form-label" for="adminRole">角色</label>
                <select class="input" name="adminRole" id="adminRole">
                    <option value="operator">运营管理员</option>
                    <option value="super">超级管理员</option>
                </select>
            </div>
            <button type="submit" name="createAdmin" class="btn"><?=icon('plus')?>创建管理员</button>
        </form>
    </div>

    <div class="card card-pad">
        <div class="section-head">
            <h2 class="section-title">管理员列表</h2>
            <span class="badge badge-neutral">共 <?=h(count($admins))?> 人</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>账号</th>
                        <th>角色</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td>
                                <strong><?=h($admin['userName'])?></strong>
                                <?php if ($admin['userName'] === $_SESSION['adminName']): ?>
                                    <span class="badge badge-neutral">当前账号</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($admin['userName'] === $_SESSION['adminName']): ?>
                                    <span class="badge badge-success"><?=h($admin['adminRole'] === 'super' ? '超级管理员' : '运营管理员')?></span>
                                <?php else: ?>
                                    <form method="POST" class="flex gap-sm">
                                        <?=csrfField()?>
                                        <input type="hidden" name="adminUser" value="<?=h($admin['userName'])?>">
                                        <select class="input" name="adminRole" style="width:130px">
                                            <option value="operator" <?=$admin['adminRole'] === 'operator' ? 'selected' : ''?>>运营管理员</option>
                                            <option value="super" <?=$admin['adminRole'] === 'super' ? 'selected' : ''?>>超级管理员</option>
                                        </select>
                                        <button type="submit" name="setAdminRole" class="btn btn-ghost btn-sm">更新角色</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?=$admin['status'] === 'V' ? 'badge-success' : 'badge-error'?>">
                                    <?=$admin['status'] === 'V' ? '启用' : '停用'?>
                                </span>
                            </td>
                            <td class="muted"><?=h($admin['createTime'])?></td>
                            <td>
                                <?php if ($admin['userName'] !== $_SESSION['adminName']): ?>
                                    <form method="POST">
                                        <?=csrfField()?>
                                        <input type="hidden" name="adminUser" value="<?=h($admin['userName'])?>">
                                        <input type="hidden" name="adminStatus" value="<?=$admin['status'] === 'V' ? 'U' : 'V'?>">
                                        <button type="submit" name="setAdminStatus" class="btn btn-ghost btn-sm <?=$admin['status'] === 'V' ? 'danger' : ''?>">
                                            <?=$admin['status'] === 'V' ? '停用' : '启用'?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php renderSiteFooter(); ?>