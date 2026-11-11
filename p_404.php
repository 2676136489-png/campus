<?php
require_once __DIR__ . '/p_layout.php';

// 自定义 404：地址不存在时由站点配置指向本页（Apache ErrorDocument / Nginx error_page）。
// 本页不要求登录——用户可能在任何状态点进失效链接，直接给出回程入口比踢去登录页更有用。
if (function_exists('secureSessionStart')) {
    secureSessionStart();
}
http_response_code(404);

$studentName = isset($_SESSION['userName']) ? (string)$_SESSION['userName'] : '';
$homeHref = $studentName !== '' ? 'p_welcomeStu.php' : 'p_loginStu.php';

$actions = $studentName !== ''
    ? '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>'
    : '<a class="btn btn-sm" href="p_loginStu.php">学生登录</a>';

renderHead('页面走丢了', '你访问的页面不存在，可能已经被移动或删除。');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => $homeHref],
        ['label' => '动态广场', 'href' => 'p_dynamics.php'],
        ['label' => '同学', 'href' => 'p_allStudents.php'],
        ['label' => 'AI 助手', 'href' => 'p_aiChat.php'],
    ],
    'home' => $homeHref,
    'actions' => $actions,
]);
?>
<div class="container">
    <div class="notfound">
        <p class="notfound-code" aria-hidden="true">404</p>
        <h1 class="notfound-title">这个页面不在校园里</h1>
        <p class="notfound-copy">地址可能写错了，或者这条内容已经被作者删除。</p>
        <div class="notfound-actions">
            <a class="btn" href="<?= h($homeHref) ?>"><?= icon('home') ?>回到首页</a>
            <a class="btn btn-ghost" href="p_dynamics.php"><?= icon('feed') ?>逛逛动态</a>
            <a class="btn btn-ghost" href="p_allStudents.php"><?= icon('users') ?>找同学</a>
        </div>
        <p class="notfound-hint">如果是从站内链接点进来的，欢迎到 <a href="p_feedback.php">意见反馈</a> 告诉我们。</p>
    </div>
</div>
<?php renderSiteFooter(); ?>
