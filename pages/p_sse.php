<?php
require_once __DIR__ . '/../lib/manageDB.php';
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
    echo "retry: 10000\n\n";
    flush();
    exit;
}

$last = getUnreadNotificationCount((int)$student['pk']);
echo "retry: 10000\n\n";
flush();

$started = time();
while (time() - $started < 60) {
    $count = getUnreadNotificationCount((int)$student['pk']);
    if ($count !== $last) {
        $last = $count;
        echo 'event: unread' . "\n";
        echo 'data: ' . json_encode(['count' => $count], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
    }
    sleep(5);
}