<?php
require_once __DIR__ . '/p_manageDB.php';
secureSessionStart();
sendSecurityHeaders();

if (!isset($_SESSION['userName'])) {
    http_response_code(401);
    exit;
}
$student = getCurrentStudent($_SESSION['userName']);
if (!$student || $student['status'] !== 'V') {
    http_response_code(403);
    exit;
}
session_write_close();

header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');
@ob_end_flush();

$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? '';
if (stripos($serverSoftware, 'PHP') !== false && stripos($serverSoftware, 'Development Server') !== false) {
    echo "retry: 5000\n\n";
    flush();
    exit;
}

// 单条连接最多占用一个 PHP-FPM worker 20 秒。
// 原来是 60 秒，二三十个在线用户就能把 worker 池占满，
// 其它页面（包括登录）会因为没有 worker 可用而直接卡死。
const SSE_MAX_SECONDS = 20;
const SSE_POLL_SECONDS = 5;

$last = getUnreadNotificationCount((int)$student['pk']);
echo "retry: 5000\n\n";
flush();

$started = time();
while (time() - $started < SSE_MAX_SECONDS) {
    if (connection_aborted()) {
        // 用户关掉页面后立即释放 worker，不必等满时长
        break;
    }
    $count = getUnreadNotificationCount((int)$student['pk']);
    if ($count !== $last) {
        $last = $count;
        echo 'event: unread' . "\n";
        echo 'data: ' . json_encode(['count' => $count], JSON_UNESCAPED_UNICODE) . "\n\n";
    } else {
        // SSE 注释行：既保活，也让 connection_aborted() 有机会感知客户端断开
        echo ": ping\n\n";
    }
    flush();
    sleep(SSE_POLL_SECONDS);
}