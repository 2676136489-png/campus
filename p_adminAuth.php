<?php
require_once __DIR__ . '/lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

$loginError = '';
$operMsg = '';
$operType = 'success';
$mustChangePassword = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verifyCsrf()) {
    $loginError = 'Invalid form token. Please try again.';
    $operMsg = $loginError;
    $operType = 'error';
} elseif (isset($_POST['adminLogin'])) {
    $adminUser = trim($_POST['adminUser'] ?? '');
    $adminPwd = trim($_POST['adminPwd'] ?? '');
    $adminIp = clientIp();
    if (isLoginRateLimited($adminUser, $adminIp)) {
        recordLoginAttempt($adminUser, $adminIp, false);
        $loginError = '登录尝试次数过多，请 15 分钟后再试。';
        $operMsg = $loginError;
        $operType = 'error';
    } else {
        $result = checkAdminLogin($adminUser, $adminPwd);
        recordLoginAttempt($adminUser, $adminIp, is_array($result));
        if (is_array($result)) {
            if (isAdminTotpEnabled($result['userName'])) {
                $_SESSION['admin_totp_pending'] = $result['userName'];
                header('Location: ' . tabUrl('p_adminAuth.php'));
                exit;
            }
            $previousUser = $_SESSION['userName'] ?? '';
            $previousAdmin = $_SESSION['adminName'] ?? '';
            if ($previousUser !== '' || ($previousAdmin !== '' && $previousAdmin !== $result['userName'])) {
                $rotatedData = $_SESSION;
                unset($rotatedData['userName']);
                $rotatedData['adminName'] = $result['userName'];
                $rotatedData['adminMustChangePassword'] = isInitialAdminPassword($result['userName']);
                $rotatedData['auth_token'] = createActiveSessionToken((int)$result['pk']);
                $newTab = rotateTabSession($rotatedData);
                header('Location: p_adminAuth.php?tab=' . rawurlencode($newTab) . '&rotate=1');
                exit;
            }
            unset($_SESSION['userName']);
            $_SESSION['adminName'] = $result['userName'];
            $_SESSION['adminMustChangePassword'] = isInitialAdminPassword($result['userName']);
            issueActiveSessionToken((int)$result['pk']);
            sessionRegenerate();
            header('Location: ' . tabUrl('p_adminAuth.php'));
            exit;
        }
        $loginError = $result;
    }
} elseif (isset($_POST['adminTotpLogin'])) {
    $pending = $_SESSION['admin_totp_pending'] ?? '';
    $totpInput = trim($_POST['totpCode'] ?? '');
    if ($pending === '' || $totpInput === '') {
        $loginError = '请先完成管理员密码验证。';
        $operMsg = $loginError;
        $operType = 'error';
    } else {
        $result = getUserByUserName($pending);
        if (!$result || $result['userType'] !== 'a' || !isAdminTotpEnabled($pending) || !verifyTotp(getAdminTotpSecret($pending), $totpInput)) {
            $loginError = '动态验证码错误。';
            $operMsg = $loginError;
            $operType = 'error';
        } else {
            unset($_SESSION['admin_totp_pending']);
            $previousUser = $_SESSION['userName'] ?? '';
            $previousAdmin = $_SESSION['adminName'] ?? '';
            if ($previousUser !== '' || ($previousAdmin !== '' && $previousAdmin !== $result['userName'])) {
                $rotatedData = $_SESSION;
                unset($rotatedData['userName']);
                $rotatedData['adminName'] = $result['userName'];
                $rotatedData['adminMustChangePassword'] = isInitialAdminPassword($result['userName']);
                $rotatedData['auth_token'] = createActiveSessionToken((int)$result['pk']);
                $newTab = rotateTabSession($rotatedData);
                header('Location: p_adminAuth.php?tab=' . rawurlencode($newTab) . '&rotate=1');
                exit;
            }
            unset($_SESSION['userName']);
            $_SESSION['adminName'] = $result['userName'];
            $_SESSION['adminMustChangePassword'] = isInitialAdminPassword($result['userName']);
            issueActiveSessionToken((int)$result['pk']);
            sessionRegenerate();
            header('Location: ' . tabUrl('p_adminAuth.php'));
            exit;
        }
    }
}

if (isset($_GET['logout'])) {
    if (isset($_SESSION['adminName'])) {
        $adminRow = getUserByUserName($_SESSION['adminName']);
        if ($adminRow) {
            clearActiveSessionToken((int)$adminRow['pk'], $_SESSION['auth_token'] ?? '');
        }
    }
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
    header('Location: ' . tabUrl('p_adminAuth.php'));
    exit;
}

$isAdminLogin = isset($_SESSION['adminName']);
if ($isAdminLogin) {
    $adminRow = getUserByUserName($_SESSION['adminName']);
    if (!$adminRow || !verifyActiveSessionToken((int)$adminRow['pk'], $_SESSION['auth_token'] ?? '')) {
        session_unset();
        $isAdminLogin = false;
        unset($_SESSION['adminName']);
    }
}
$mustChangePassword = $isAdminLogin
    ? ($_SESSION['adminMustChangePassword'] ?? isInitialAdminPassword($_SESSION['adminName']))
    : false;
$totpEnabled = $isAdminLogin && isAdminTotpEnabled($_SESSION['adminName']);

if ($isAdminLogin && $_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $adminRow = getUserByUserName($_SESSION['adminName'] ?? '');
    $adminPk = $adminRow ? (int)$adminRow['pk'] : 0;
    if (isset($_POST['changeAdminPassword'])) {
        $result = updateAdminPassword($_SESSION['adminName'], $_POST['oldPwd'] ?? '', $_POST['newPwd'] ?? '');
        if ($result === true) {
            $_SESSION['adminMustChangePassword'] = false;
            $mustChangePassword = false;
            logAudit($adminPk, 'user', $adminPk, 'change_password', '管理员修改密码');
            $operMsg = '管理员密码已更新。';
        } else {
            $operMsg = $result;
            $operType = 'error';
        }
    } elseif ($mustChangePassword) {
        $operMsg = '请先修改初始管理员密码，再使用后台功能。';
        $operType = 'error';
    } elseif (isset($_POST['provisionAdminTotp'])) {
        $provision = provisionAdminTotp($_SESSION['adminName']);
        if ($provision === false) {
            $operMsg = '密钥生成失败。';
            $operType = 'error';
        } else {
            $_SESSION['admin_totp_provision'] = $provision;
            $operMsg = '密钥已生成，请在身份验证器中添加。';
        }
    } elseif (isset($_POST['enableAdminTotp'])) {
        $secret = getAdminTotpSecret($_SESSION['adminName']);
        if ($secret === '' || !verifyTotp($secret, $_POST['totpCode'] ?? '')) {
            $operMsg = '动态验证码错误。';
            $operType = 'error';
        } elseif (enableAdminTotp($_SESSION['adminName'])) {
            unset($_SESSION['admin_totp_provision']);
            $operMsg = '二次验证已启用。';
        } else {
            $operMsg = '二次验证启用失败。';
            $operType = 'error';
        }
    } elseif (isset($_POST['disableAdminTotp'])) {
        if (!verifyTotp(getAdminTotpSecret($_SESSION['adminName']), $_POST['totpCode'] ?? '')) {
            $operMsg = '动态验证码错误。';
            $operType = 'error';
        } elseif (disableAdminTotp($_SESSION['adminName'])) {
            $operMsg = '二次验证已关闭。';
        } else {
            $operMsg = '二次验证关闭失败。';
            $operType = 'error';
        }
    }
}

$stats = $isAdminLogin ? getAdminStats() : [];
$activity = $isAdminLogin ? getActivityStats(7) : null;
$adminRole = $isAdminLogin ? getAdminRole($_SESSION['adminName']) : 'operator';
$isSuperAdmin = $adminRole === 'super';

if (!$isAdminLogin) {
    renderAuthShell([
        'title' => '管理员登录',
        'description' => '登录校园圈子管理员后台，审核实名信息并管理校园动态。',
        'quote' => '管理校园圈子，守护每一次真实连接。',
    ]);
    ?>
    <h1 class="auth-title">管理员后台</h1>
    <p class="auth-sub">审核学生实名信息、管理账号状态与校园动态内容。</p>

    <?php if ($loginError): ?>
        <div class="alert alert-error"><?=icon('alert')?><div><?=h($loginError)?></div></div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['admin_totp_pending'])): ?>
        <form method="POST">
            <?=csrfField()?>
            <div class="form-field">
                <label class="form-label" for="totpCode">动态验证码</label>
                <input class="input" type="text" name="totpCode" id="totpCode" required maxlength="6" inputmode="numeric" autocomplete="one-time-code" placeholder="输入 6 位动态码">
            </div>
            <button type="submit" name="adminTotpLogin" class="btn btn-block"><?=icon('shield')?>验证并登录</button>
        </form>
        <p class="auth-rule"><?=icon('shield')?><span>已开启二次验证，请输入身份验证器中的 6 位动态码。</span></p>
    <?php else: ?>
        <form method="POST">
            <?=csrfField()?>
            <div class="form-field">
                <label class="form-label" for="adminUser">管理员账号</label>
                <input class="input" type="text" name="adminUser" id="adminUser" value="admin" required autocomplete="username">
            </div>
            <div class="form-field">
                <label class="form-label" for="adminPwd">管理员密码</label>
                <div class="password-wrap">
                    <input class="input" type="password" name="adminPwd" id="adminPwd" required autocomplete="current-password" placeholder="请输入管理员密码">
                    <button class="js-toggle-pwd toggle-pwd" type="button" data-target="adminPwd" aria-label="显示密码"><?=icon('eye')?></button>
                </div>
            </div>
            <button type="submit" name="adminLogin" class="btn btn-block"><?=icon('shield')?>登录后台</button>
        </form>
    <?php endif; ?>

    <div class="auth-links">
        <a class="btn btn-soft" href="p_loginStu.php"><?=icon('arrow-left')?>返回学生登录</a>
    </div>
    <?php renderAuthShellClose();
    exit;
}

renderHead('管理员后台');
renderAdminHeader('后台总览');
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">管理员后台</h1>
            <p class="page-sub">当前管理员：<?=h($_SESSION['adminName'])?> · 角色：<?=h($isSuperAdmin ? '超级管理员' : '运营管理员')?>。集中管理实名审核、内容治理与安全审计。</p>
        </div>
    </div>

    <?php if ($operMsg): ?>
        <div class="alert <?=$operType === 'success' ? 'alert-success' : 'alert-error'?>">
            <?=icon($operType === 'success' ? 'check' : 'alert')?>
            <div><?=h($operMsg)?></div>
        </div>
    <?php endif; ?>

    <?php if ($mustChangePassword): ?>
        <div class="card card-pad narrow">
            <div class="section-head">
                <h2 class="section-title">修改初始密码</h2>
            </div>
            <div class="alert alert-warning"><?=icon('alert')?><div>当前仍在使用初始管理员密码，请先修改后再使用后台功能。</div></div>
            <form method="POST">
                <?=csrfField()?>
                <div class="form-field">
                    <label class="form-label" for="oldPwd">当前密码</label>
                    <div class="password-wrap">
                        <input class="input" type="password" name="oldPwd" id="oldPwd" required autocomplete="current-password">
                        <button class="js-toggle-pwd toggle-pwd" type="button" data-target="oldPwd" aria-label="显示密码"><?=icon('eye')?></button>
                    </div>
                </div>
                <div class="form-field">
                    <label class="form-label" for="newPwd">新密码</label>
                    <div class="password-wrap">
                        <input class="input" type="password" name="newPwd" id="newPwd" required minlength="8" maxlength="72" autocomplete="new-password" placeholder="至少 8 位，包含字母和数字">
                        <button class="js-toggle-pwd toggle-pwd" type="button" data-target="newPwd" aria-label="显示密码"><?=icon('eye')?></button>
                    </div>
                    <div class="form-hint">至少 8 位，同时包含字母和数字，不能与初始密码相同。</div>
                </div>
                <button type="submit" name="changeAdminPassword" class="btn"><?=icon('check')?>更新密码</button>
            </form>
        </div>
    <?php else: ?>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-icon"><?=icon('user', 21)?></span>
                <span><span class="stat-value"><?=h($stats['pending'])?></span><span class="stat-label">待审核</span></span>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><?=icon('check', 21)?></span>
                <span><span class="stat-value"><?=h($stats['verified'])?></span><span class="stat-label">已认证</span></span>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><?=icon('shield', 21)?></span>
                <span><span class="stat-value"><?=h($stats['disabled'])?></span><span class="stat-label">已停用</span></span>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><?=icon('feed', 21)?></span>
                <span><span class="stat-value"><?=h($stats['dynamics'])?></span><span class="stat-label">正常动态</span></span>
            </div>
            <div class="stat-card">
                <span class="stat-icon"><?=icon('alert', 21)?></span>
                <span><span class="stat-value"><?=h($stats['reports'])?></span><span class="stat-label">待处理举报</span></span>
            </div>
        </div>

        <div class="card card-pad mb-24">
            <div class="section-head">
                <h2 class="section-title">管理员二次验证</h2>
                <span class="badge <?=$totpEnabled ? 'badge-success' : 'badge-neutral'?>"><?=$totpEnabled ? '已开启' : '未开启'?></span>
            </div>
            <?php if ($totpEnabled): ?>
                <p class="muted">登录后台时，除密码外还需输入身份验证器生成的 6 位动态码。关闭二次验证需要输入当前动态码。</p>
                <form method="POST" class="flex gap-sm mt-16">
                    <?=csrfField()?>
                    <input class="input" style="width:160px" type="text" name="totpCode" required maxlength="6" inputmode="numeric" placeholder="6 位动态码" aria-label="当前动态码">
                    <button type="submit" name="disableAdminTotp" class="btn btn-ghost">关闭二次验证</button>
                </form>
            <?php elseif (!empty($_SESSION['admin_totp_provision'])): ?>
                <p class="muted">在身份验证器中添加以下密钥，然后输入动态码完成启用。</p>
                <div class="form-field mt-16">
                    <label class="form-label">密钥</label>
                    <code><?=h($_SESSION['admin_totp_provision']['secret'])?></code>
                </div>
                <div class="form-field">
                    <label class="form-label">OTPAuth 地址</label>
                    <code class="pre-line"><?=h($_SESSION['admin_totp_provision']['url'])?></code>
                </div>
                <form method="POST" class="flex gap-sm">
                    <?=csrfField()?>
                    <input class="input" style="width:160px" type="text" name="totpCode" required maxlength="6" inputmode="numeric" placeholder="6 位动态码" aria-label="动态验证码">
                    <button type="submit" name="enableAdminTotp" class="btn">启用</button>
                </form>
            <?php else: ?>
                <p class="muted">开启后，管理员登录除密码外还需输入 6 位动态验证码，提高后台安全等级。</p>
                <form method="POST" class="mt-16">
                    <?=csrfField()?>
                    <button type="submit" name="provisionAdminTotp" class="btn btn-soft">生成密钥</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card card-pad mb-24">
            <div class="section-head">
                <h2 class="section-title">管理功能</h2>
                <span class="badge badge-neutral">快捷入口</span>
            </div>
            <div class="admin-tool-grid">
                <?php if ($isSuperAdmin): ?>
                    <a class="admin-tool" href="p_adminStudents.php"><?=icon('users', 20)?><span>学生实名审核与账号管理</span></a>
                <?php endif; ?>
                <a class="admin-tool" href="p_adminDynamics.php"><?=icon('feed', 20)?><span>动态内容管理</span></a>
                <a class="admin-tool" href="p_adminComments.php"><?=icon('chat', 20)?><span>评论管理</span></a>
                <a class="admin-tool" href="p_adminMessages.php"><?=icon('mail', 20)?><span>私信审计</span></a>
                <a class="admin-tool" href="p_adminReports.php"><?=icon('alert', 20)?><span>举报审核</span></a>
                <?php if ($isSuperAdmin): ?>
                    <a class="admin-tool" href="p_adminAudit.php"><?=icon('database', 20)?><span>审计日志</span></a>
                <?php endif; ?>
                <a class="admin-tool" href="p_announcements.php"><?=icon('megaphone', 20)?><span>公告管理</span></a>
                <a class="admin-tool" href="p_adminFeedback.php"><?=icon('mail', 20)?><span>反馈管理</span></a>
                <a class="admin-tool" href="p_sensitiveWords.php"><?=icon('shield', 20)?><span>敏感词管理</span></a>
                <?php if ($isSuperAdmin): ?>
                    <a class="admin-tool" href="p_adminAdmins.php"><?=icon('shield', 20)?><span>管理员账号</span></a>
                <?php endif; ?>
                <?php if ($isSuperAdmin): ?>
                    <a class="admin-tool" href="p_exportStudents.php"><?=icon('download', 20)?><span>导出学生 CSV</span></a>
                <?php endif; ?>
                <a class="admin-tool" href="p_trendReport.php"><?=icon('chart', 20)?><span>30 天趋势</span></a>
            </div>
        </div>

        <div class="card card-pad">
            <div class="section-head">
                <h2 class="section-title">近 7 天活跃</h2>
            </div>
            <div class="activity-chart">
                <?php foreach ($activity['labels'] as $i => $label): ?>
                    <div class="activity-col">
                        <div class="activity-bars">
                            <span class="bar-dynamic" style="height: <?=h(round($activity['dynamics'][$i] / max(1, (int)$activity['max']) * 100))?>%"></span>
                            <span class="bar-comment" style="height: <?=h(round($activity['comments'][$i] / max(1, (int)$activity['max']) * 100))?>%"></span>
                            <span class="bar-register" style="height: <?=h(round($activity['registrations'][$i] / max(1, (int)$activity['max']) * 100))?>%"></span>
                        </div>
                        <div class="activity-label"><?=h($label)?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="activity-legend">
                <span><i class="legend-dot dot-dynamic"></i>动态</span>
                <span><i class="legend-dot dot-comment"></i>评论</span>
                <span><i class="legend-dot dot-register"></i>注册</span>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php renderSiteFooter([
    'links' => [
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
        ['label' => '学生注册', 'href' => 'p_registerStu.php'],
    ],
]); ?>