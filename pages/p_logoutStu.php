<?php
require_once __DIR__ . '/../lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/../lib/manageDB.php';
if (isset($_SESSION['userName'])) {
    $student = getCurrentStudent($_SESSION['userName']);
    if ($student) {
        clearActiveSessionToken((int)$student['pk'], $_SESSION['auth_token'] ?? '');
    }
}
session_unset();       // 清除所有Session变量
session_destroy();     // 销毁Session
// 删除Session Cookie（设置过期时间为过去）
setcookie(session_name(), '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
header('Location: ' . tabUrl('p_loginStu.php'));  // 跳转回登录页
exit;
?>
