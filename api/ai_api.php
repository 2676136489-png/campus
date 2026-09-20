<?php
/**
 * AI 助手只读接口：仅暴露学生端公开信息。
 */
require_once __DIR__ . '/../lib/dbInfo.php';
require_once __DIR__ . '/../lib/autoload.php';

header('Content-Type: application/json; charset=utf-8');

$apiKey = envValue('AI_API_KEY', '');
$providedKey = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
if ($providedKey === '') {
    $providedKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
}
$providedKey = preg_replace('/^Bearer\s+/i', '', trim((string)$providedKey));

if ($apiKey === '' || $providedKey === '' || !hash_equals($apiKey, $providedKey)) {
    aiJson(['ok' => false, 'error' => 'unauthorized'], 403);
}

$action = trim($_GET['action'] ?? '');
switch ($action) {
    case 'students':
        aiStudents();
        break;
    case 'dynamics':
        aiDynamics();
        break;
    case 'announcements':
        aiAnnouncements();
        break;
    default:
        aiJson(['ok' => false, 'error' => 'unknown_action', 'actions' => ['students', 'dynamics', 'announcements']], 400);
}

function aiJson($payload, $code = 200)
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function aiStudents()
{
    $conn = dbConnect();
    $where = ["u.status = 'V'"];
    $types = '';
    $params = [];

    $q = trim($_GET['q'] ?? '');
    if ($q !== '') {
        $where[] = "(s.name LIKE ? OR u.userName LIKE ? OR s.major LIKE ? OR s.college LIKE ?)";
        $like = '%' . $q . '%';
        $types .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }
    $college = trim($_GET['college'] ?? '');
    if ($college !== '') {
        $where[] = "s.college LIKE ?";
        $types .= 's';
        $params[] = '%' . $college . '%';
    }
    $major = trim($_GET['major'] ?? '');
    if ($major !== '') {
        $where[] = "s.major LIKE ?";
        $types .= 's';
        $params[] = '%' . $major . '%';
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $whereSql = implode(' AND ', $where);

    $countStmt = $conn->prepare("SELECT COUNT(*) FROM `student` s INNER JOIN `user` u ON u.pk = s.pk WHERE " . $whereSql);
    if ($types !== '') {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int)$countStmt->get_result()->fetch_row()[0];
    $countStmt->close();

    $sql = "SELECT s.pk, u.userName, s.name, s.gender, s.college, s.grade, s.major, s.tags, s.avatar
            FROM `student` s
            INNER JOIN `user` u ON u.pk = s.pk
            WHERE " . $whereSql . " ORDER BY s.pk ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $types2 = $types . 'ii';
    $params2 = array_merge($params, [$limit, $offset]);
    if ($types2 !== '') {
        $stmt->bind_param($types2, ...$params2);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    foreach ($rows as &$row) {
        $row['gender'] = (int)$row['gender'];
        $row['tags'] = $row['tags'] !== '' ? explode(',', $row['tags']) : [];
    }
    unset($row);

    aiJson(['ok' => true, 'data' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit]);
}

function aiDynamics()
{
    $conn = dbConnect();
    $where = ["d.status = 'normal'", "u.status = 'V'"];
    $types = '';
    $params = [];

    $q = trim($_GET['q'] ?? '');
    if ($q !== '') {
        $where[] = "(d.content LIKE ? OR s.name LIKE ? OR u.userName LIKE ? OR d.tags LIKE ?)";
        $like = '%' . $q . '%';
        $types .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }
    $author = trim($_GET['author'] ?? '');
    if ($author !== '') {
        $where[] = "(s.name LIKE ? OR u.userName LIKE ?)";
        $types .= 'ss';
        array_push($params, '%' . $author . '%', '%' . $author . '%');
    }

    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(30, max(1, (int)($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;
    $whereSql = implode(' AND ', $where);

    $countStmt = $conn->prepare("SELECT COUNT(*) FROM `dynamic` d INNER JOIN `user` u ON u.pk = d.userPk LEFT JOIN `student` s ON s.pk = u.pk WHERE " . $whereSql);
    if ($types !== '') {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = (int)$countStmt->get_result()->fetch_row()[0];
    $countStmt->close();

    $sql = "SELECT d.pk, d.content, d.tags, d.photo, d.createTime, u.userName, s.name, s.college, s.major
            FROM `dynamic` d
            INNER JOIN `user` u ON u.pk = d.userPk
            LEFT JOIN `student` s ON s.pk = u.pk
            WHERE " . $whereSql . " ORDER BY d.createTime DESC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $types2 = $types . 'ii';
    $params2 = array_merge($params, [$limit, $offset]);
    if ($types2 !== '') {
        $stmt->bind_param($types2, ...$params2);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $photoMap = [];
    if (!empty($rows)) {
        $photoRows = aiFetchPhotos($conn, array_column($rows, 'pk'));
        foreach ($photoRows as $p) {
            $photoMap[(int)$p['dynamicPk']][] = $p['path'];
        }
    }
    $conn->close();

    foreach ($rows as &$row) {
        $photos = $photoMap[(int)$row['pk']] ?? [];
        if (empty($photos) && $row['photo'] !== '') {
            $photos = [$row['photo']];
        }
        $row['photos'] = $photos;
        unset($row['photo']);
        $row['tags'] = $row['tags'] !== '' ? explode(',', $row['tags']) : [];
    }
    unset($row);

    aiJson(['ok' => true, 'data' => $rows, 'total' => $total, 'page' => $page, 'limit' => $limit]);
}

function aiFetchPhotos($conn, array $dynamicPks)
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $dynamicPks))));
    if (empty($ids)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $stmt = $conn->prepare("SELECT `dynamicPk`, `path` FROM `dynamic_photo` WHERE `dynamicPk` IN ($placeholders) ORDER BY `sort` ASC, `pk` ASC");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function aiAnnouncements()
{
    $conn = dbConnect();
    $limit = min(20, max(1, (int)($_GET['limit'] ?? 10)));
    $stmt = $conn->prepare("SELECT title, content, createTime FROM `announcement` WHERE `isPublished` = 1 ORDER BY `createTime` DESC LIMIT ?");
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    aiJson(['ok' => true, 'data' => $rows]);
}