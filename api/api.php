<?php
require_once __DIR__ . '/../lib/manageDB.php';

header('Content-Type: application/json; charset=utf-8');

function apiJson($status, $payload) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_GET['action'] ?? '') === 'health') {
    $dbOk = false;
    try {
        $conn = dbConnect();
        $dbOk = (bool)$conn->query('SELECT 1');
        $conn->close();
    } catch (Throwable $e) {
        $dbOk = false;
    }
    apiJson(200, [
        'ok' => true,
        'service' => 'campus-circle',
        'version' => '1.0.0',
        'db' => $dbOk ? 'ok' : 'error',
        'time' => date('c'),
    ]);
}

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
}
if (!isset($_SESSION['userName'])) {
    apiJson(401, ['ok' => false, 'message' => '请先登录。']);
}
$student = getCurrentStudent($_SESSION['userName']);
if (!$student || $student['status'] === 'U' || !verifyActiveSessionToken((int)$student['pk'], $_SESSION['auth_token'] ?? '')) {
    apiJson(401, ['ok' => false, 'message' => '账号不可用，请重新登录。']);
}
if ($student['status'] !== 'V') {
    if (($_GET['action'] ?? '') === 'unread') {
        apiJson(200, ['ok' => true, 'unread' => 0]);
    }
    apiJson(403, ['ok' => false, 'message' => '实名审核通过后才能操作。']);
}
if (($_GET['action'] ?? '') === 'unread') {
    apiJson(200, ['ok' => true, 'unread' => getUnreadNotificationCount((int)$student['pk'])]);
}
if (($_GET['action'] ?? '') === 'recommendations') {
    $people = getRecommendedStudents((int)$student['pk'], 3);
    apiJson(200, ['ok' => true, 'people' => $people]);
}
if (($_GET['action'] ?? '') === 'messages') {
    $otherPk = (int)($_GET['user'] ?? 0);
    $after = (int)($_GET['after'] ?? 0);
    $rows = getMessagesAfter((int)$student['pk'], $otherPk, $after, 20);
    apiJson(200, ['ok' => true, 'messages' => $rows, 'unread' => getUnreadMessageCount((int)$student['pk'])]);
}
if (($_GET['action'] ?? '') === 'messagesBefore') {
    $otherPk = (int)($_GET['user'] ?? 0);
    $before = (int)($_GET['before'] ?? 0);
    $rows = getMessagesBefore((int)$student['pk'], $otherPk, $before, 30);
    apiJson(200, ['ok' => true, 'messages' => $rows]);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiJson(405, ['ok' => false, 'message' => '仅支持 POST 请求。']);
}
if (!verifyCsrf()) {
    apiJson(403, ['ok' => false, 'message' => '表单令牌无效，请刷新页面重试。']);
}

$action = $_POST['action'] ?? '';
if ($action === 'like') {
    $result = toggleLike((int)($_POST['dynamicPk'] ?? 0), (int)$student['pk']);
    if (isset($result['ok']) && $result['ok']) {
        apiJson(200, $result);
    }
    apiJson(400, $result);
}

if ($action === 'favorite') {
    $result = toggleFavorite((int)($_POST['dynamicPk'] ?? 0), (int)$student['pk']);
    if (isset($result['ok']) && $result['ok']) {
        apiJson(200, $result);
    }
    apiJson(400, $result);
}

if ($action === 'comment') {
    $result = addComment((int)($_POST['dynamicPk'] ?? 0), (int)$student['pk'], $_POST['comment'] ?? '', (int)($_POST['parentPk'] ?? 0));
    if (isset($result['ok']) && $result['ok']) {
        apiJson(200, $result);
    }
    apiJson(400, $result);
}

if ($action === 'deleteComment') {
    $result = deleteComment((int)($_POST['commentPk'] ?? 0), (int)$student['pk'], false);
    if (isset($result['ok']) && $result['ok']) {
        apiJson(200, $result);
    }
    apiJson(400, $result);
}

if ($action === 'conversationSetting') {
    $target = (int)($_POST['targetUser'] ?? 0);
    $field = $_POST['field'] ?? '';
    $value = !empty($_POST['value']);
    if (updateConversationSetting((int)$student['pk'], $target, $field, $value)) {
        apiJson(200, ['ok' => true, 'field' => $field, 'value' => $value]);
    }
    apiJson(400, ['ok' => false, 'message' => '会话设置更新失败。']);
}

if ($action === 'privacy') {
    $allowMessages = $_POST['allowMessages'] ?? 'all';
    $showProfile = !empty($_POST['showProfile']);
    updatePrivacy((int)$student['pk'], $allowMessages, $showProfile);
    apiJson(200, ['ok' => true]);
}

if ($action === 'block') {
    $result = toggleBlock((int)$student['pk'], (int)($_POST['targetUser'] ?? 0));
    if (isset($result['ok']) && $result['ok']) {
        apiJson(200, $result);
    }
    apiJson(400, $result);
}

if ($action === 'recallMessage') {
    $result = recallMessage((int)$student['pk'], (int)($_POST['messagePk'] ?? 0));
    if (isset($result['ok']) && $result['ok']) {
        apiJson(200, $result);
    }
    apiJson(400, $result);
}

if ($action === 'sendMessage') {
    $image = '';
    if (!empty($_FILES['image']['name'])) {
        $image = uploadImageFile('image', 'chat', 20 * 1024 * 1024, false);
        if (strpos($image, 'uploads/') !== 0) {
            apiJson(400, ['ok' => false, 'message' => '图片上传失败：' . $image]);
        }
    }
    $result = sendMessage((int)$student['pk'], (int)($_POST['targetUser'] ?? 0), $_POST['content'] ?? '', $image);
    if (isset($result['ok']) && $result['ok']) {
        apiJson(200, $result);
    }
    if ($image !== '') {
        deleteUploadedFile($image);
    }
    apiJson(400, $result);
}

if ($action === 'follow') {
    $result = toggleFollow((int)$student['pk'], (int)($_POST['targetUser'] ?? 0));
    if (isset($result['ok']) && $result['ok']) {
        apiJson(200, $result);
    }
    apiJson(400, $result);
}

if ($action === 'readNotifications') {
    markNotificationsRead((int)$student['pk']);
    apiJson(200, ['ok' => true, 'unread' => 0]);
}

if ($action === 'saveDraft') {
    $ok = saveDynamicDraft((int)$student['pk'], $_POST['content'] ?? '', $_POST['tags'] ?? '');
    apiJson($ok ? 200 : 400, ['ok' => $ok]);
}

apiJson(400, ['ok' => false, 'message' => '未知操作。']);