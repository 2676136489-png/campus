<?php
/**
 * 「猜你喜欢」换一批接口。
 *
 * 从动态标签池里重抽 3 个标签，直接返回可插入侧栏的 HTML 片段，
 * 首屏与这里共用 railPickTagHtml()，样式不会漂移。
 * 只读接口：唯一副作用是把这批标签记进 session，因此不校验 CSRF。
 */
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

if (session_status() === PHP_SESSION_NONE) {
    secureSessionStart();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

// 连点保护：同一会话 1.5 秒内只允许换一次，避免刷新按钮被当成接口压测入口。
$now = microtime(true);
if (isset($_SESSION['rail_pick_at']) && ($now - (float)$_SESSION['rail_pick_at']) < 1.5) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'message' => '手速太快了，歇一下'], JSON_UNESCAPED_UNICODE);
    exit;
}
$_SESSION['rail_pick_at'] = $now;

$exclude = [];
if (isset($_GET['exclude'])) {
    foreach (explode(',', (string)$_GET['exclude']) as $piece) {
        $piece = trim($piece);
        if ($piece !== '') {
            $exclude[] = $piece;
        }
    }
}

$tags = railPickTags(3, $exclude, true);

if (empty($tags)) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => '暂时没有可推荐的标签'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'tags' => $tags,
    'html' => railPickTagHtml($tags),
], JSON_UNESCAPED_UNICODE);
