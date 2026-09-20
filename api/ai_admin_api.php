<?php
/**
 * 管理员 AI 接口：全量数据查询 + 核心管理操作。
 * 仅允许持有 AI_ADMIN_API_KEY 的调用方使用。
 */
require_once __DIR__ . '/../lib/dbInfo.php';
require_once __DIR__ . '/../lib/autoload.php';

header('Content-Type: application/json; charset=utf-8');

$apiKey = envValue('AI_ADMIN_API_KEY', '');
$providedKey = $_SERVER['HTTP_X_API_KEY'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? ''));
$providedKey = preg_replace('/^Bearer\s+/i', '', trim((string)$providedKey));

if ($apiKey === '' || $providedKey === '' || !hash_equals($apiKey, $providedKey)) {
    aiJson(['ok' => false, 'error' => 'unauthorized'], 403);
}

$json = json_decode(file_get_contents('php://input'), true);
if (is_array($json)) {
    $_POST = array_merge($_POST, $json);
}

$action = trim($_POST['action'] ?? ($_GET['action'] ?? ''));

switch ($action) {
    case 'students':
        adminStudents();
        break;
    case 'dynamics':
        adminDynamics();
        break;
    case 'comments':
        adminComments();
        break;
    case 'messages':
        adminMessages();
        break;
    case 'reports':
        adminReports();
        break;
    case 'feedback':
        adminFeedback();
        break;
    case 'announcements':
        adminAnnouncements();
        break;
    case 'audit_logs':
        adminAuditLogs();
        break;
    case 'admins':
        aiJson(['ok' => true, 'data' => getAdminUsers()]);
        break;
    case 'delete_dynamic':
        adminWriteDeleteDynamic();
        break;
    case 'restore_dynamic':
        adminWriteRestoreDynamic();
        break;
    case 'delete_comment':
        adminWriteDeleteComment();
        break;
    case 'delete_message':
        adminWriteDeleteMessage();
        break;
    case 'set_user_status':
        adminWriteSetUserStatus();
        break;
    case 'create_announcement':
        adminWriteCreateAnnouncement();
        break;
    case 'update_announcement':
        adminWriteUpdateAnnouncement();
        break;
    case 'delete_announcement':
        adminWriteDeleteAnnouncement();
        break;
    case 'set_report_status':
        adminWriteSetReportStatus();
        break;
    case 'update_feedback_status':
        adminWriteUpdateFeedbackStatus();
        break;
    case 'set_admin_status':
        adminWriteSetAdminStatus();
        break;
    case 'set_admin_role':
        adminWriteSetAdminRole();
        break;
    case 'create_admin':
        adminWriteCreateAdmin();
        break;
    default:
        aiJson(['ok' => false, 'error' => 'unknown_action'], 400);
}

function aiJson($payload, $code = 200)
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function aiAdminPk()
{
    $name = envValue('AI_ADMIN_OPERATOR', 'admin');
    $user = getUserByUserName($name);
    return $user ? (int)$user['pk'] : 0;
}

function aiPageParams()
{
    return [
        max(1, (int)($_GET['page'] ?? ($_POST['page'] ?? 1))),
        min(100, max(1, (int)($_GET['limit'] ?? ($_POST['limit'] ?? 20))))
    ];
}

function adminStudents()
{
    [$page, $perPage] = aiPageParams();
    $status = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
    $filters = ['keyword' => trim($_GET['q'] ?? ($_POST['q'] ?? ''))];
    aiJson(['ok' => true, 'data' => getStudentsPage($status, true, $filters, $page, $perPage)]);
}

function adminDynamics()
{
    [$page, $perPage] = aiPageParams();
    $filters = ['keyword' => trim($_GET['q'] ?? ($_POST['q'] ?? ''))];
    aiJson(['ok' => true, 'data' => getDynamicsPage(0, true, $filters, $page, $perPage)]);
}

function adminComments()
{
    [$page, $perPage] = aiPageParams();
    $filters = ['keyword' => trim($_GET['q'] ?? ($_POST['q'] ?? ''))];
    aiJson(['ok' => true, 'data' => getCommentsPage($filters, $page, $perPage)]);
}

function adminMessages()
{
    [$page, $perPage] = aiPageParams();
    $filters = ['keyword' => trim($_GET['q'] ?? ($_POST['q'] ?? ''))];
    aiJson(['ok' => true, 'data' => getAdminMessagesPage($filters, $page, $perPage)]);
}

function adminReports()
{
    [$page, $perPage] = aiPageParams();
    $status = trim($_GET['status'] ?? ($_POST['status'] ?? 'all'));
    aiJson(['ok' => true, 'data' => getReportsPage($status, $page, $perPage)]);
}

function adminFeedback()
{
    [$page, $perPage] = aiPageParams();
    $status = trim($_GET['status'] ?? ($_POST['status'] ?? 'all'));
    aiJson(['ok' => true, 'data' => getAdminFeedbackPage($status, $page, $perPage)]);
}

function adminAnnouncements()
{
    [$page, $perPage] = aiPageParams();
    aiJson(['ok' => true, 'data' => getAnnouncementsPage($page, $perPage)]);
}

function adminAuditLogs()
{
    [$page, $perPage] = aiPageParams();
    aiJson(['ok' => true, 'data' => getAuditLogsPage($page, $perPage)]);
}

function adminWriteDeleteDynamic()
{
    $pk = (int)($_POST['pk'] ?? 0);
    if ($pk <= 0) {
        aiJson(['ok' => false, 'message' => '缺少动态ID。'], 400);
    }
    $r = deleteDynamic($pk, aiAdminPk(), true);
    aiJson(['ok' => $r === true, 'message' => $r === true ? '动态已删除。' : $r]);
}

function adminWriteRestoreDynamic()
{
    $pk = (int)($_POST['pk'] ?? 0);
    $adminPk = aiAdminPk();
    if ($pk <= 0) {
        aiJson(['ok' => false, 'message' => '缺少动态ID。'], 400);
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `dynamic` SET `status` = 'normal' WHERE `pk` = ?");
    $stmt->bind_param('i', $pk);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    if ($ok && $adminPk > 0) {
        logAudit($adminPk, 'dynamic', $pk, 'restore_dynamic', 'AI 恢复动态');
    }
    aiJson(['ok' => $ok, 'message' => $ok ? '动态已恢复。' : '恢复失败，动态可能不存在或状态未变化。']);
}

function adminWriteDeleteComment()
{
    $pk = (int)($_POST['pk'] ?? 0);
    if ($pk <= 0) {
        aiJson(['ok' => false, 'message' => '缺少评论ID。'], 400);
    }
    $r = deleteComment($pk, aiAdminPk(), true);
    aiJson(['ok' => $r === true, 'message' => $r === true ? '评论已删除。' : $r]);
}

function adminWriteDeleteMessage()
{
    $pk = (int)($_POST['pk'] ?? 0);
    $adminPk = aiAdminPk();
    if ($pk <= 0) {
        aiJson(['ok' => false, 'message' => '缺少私信ID。'], 400);
    }
    $r = deleteMessageByAdmin($adminPk, $pk);
    aiJson(['ok' => (bool)$r, 'message' => $r ? '私信已删除。' : '私信删除失败。']);
}

function adminWriteSetUserStatus()
{
    $pk = (int)($_POST['pk'] ?? 0);
    $status = strtoupper(trim($_POST['status'] ?? ''));
    if ($pk <= 0 || !in_array($status, ['V', 'U', 'B'], true)) {
        aiJson(['ok' => false, 'message' => '参数错误，status 仅支持 V/U/B。'], 400);
    }
    $r = setUserStatus($pk, $status, aiAdminPk(), trim($_POST['note'] ?? ''));
    aiJson(['ok' => (bool)$r, 'message' => $r ? '用户状态已更新。' : '用户状态更新失败。']);
}

function adminWriteCreateAnnouncement()
{
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $published = !empty($_POST['isPublished']);
    $r = createAnnouncement(aiAdminPk(), $title, $content, $published);
    aiJson(['ok' => $r === true, 'message' => $r === true ? '公告已发布。' : $r]);
}

function adminWriteUpdateAnnouncement()
{
    $pk = (int)($_POST['pk'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $published = !empty($_POST['isPublished']);
    if ($pk <= 0) {
        aiJson(['ok' => false, 'message' => '缺少公告ID。'], 400);
    }
    $r = updateAnnouncement($pk, aiAdminPk(), $title, $content, $published);
    aiJson(['ok' => $r === true, 'message' => $r === true ? '公告已更新。' : $r]);
}

function adminWriteDeleteAnnouncement()
{
    $pk = (int)($_POST['pk'] ?? 0);
    if ($pk <= 0) {
        aiJson(['ok' => false, 'message' => '缺少公告ID。'], 400);
    }
    $r = deleteAnnouncement($pk, aiAdminPk());
    aiJson(['ok' => (bool)$r, 'message' => $r ? '公告已删除。' : '公告删除失败。']);
}

function adminWriteSetReportStatus()
{
    $pk = (int)($_POST['pk'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    if ($pk <= 0 || !in_array($status, ['resolved', 'dismissed'], true)) {
        aiJson(['ok' => false, 'message' => '参数错误，status 仅支持 resolved/dismissed。'], 400);
    }
    $r = setReportStatus($pk, $status);
    aiJson(['ok' => (bool)$r, 'message' => $r ? '举报状态已更新。' : '举报状态更新失败。']);
}

function adminWriteUpdateFeedbackStatus()
{
    $pk = (int)($_POST['pk'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    if ($pk <= 0) {
        aiJson(['ok' => false, 'message' => '缺少反馈ID。'], 400);
    }
    $r = updateFeedbackStatus($pk, $status, aiAdminPk());
    aiJson(['ok' => (bool)$r, 'message' => $r ? '反馈状态已更新。' : '反馈状态更新失败。']);
}

function adminWriteSetAdminStatus()
{
    $userName = trim($_POST['userName'] ?? '');
    $status = strtoupper(trim($_POST['status'] ?? ''));
    if ($userName === '' || !in_array($status, ['V', 'U'], true)) {
        aiJson(['ok' => false, 'message' => '参数错误。'], 400);
    }
    $r = setAdminStatus($userName, $status, aiAdminPk());
    aiJson(['ok' => $r === true, 'message' => $r === true ? '管理员状态已更新。' : $r]);
}

function adminWriteSetAdminRole()
{
    $userName = trim($_POST['userName'] ?? '');
    $role = trim($_POST['role'] ?? '');
    if ($userName === '' || !in_array($role, ['super', 'operator'], true)) {
        aiJson(['ok' => false, 'message' => '参数错误。'], 400);
    }
    $r = setAdminRole($userName, $role, aiAdminPk());
    aiJson(['ok' => $r === true, 'message' => $r === true ? '管理员角色已更新。' : $r]);
}

function adminWriteCreateAdmin()
{
    $userName = trim($_POST['userName'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'operator');
    $r = createAdminUser($userName, $password, $role, aiAdminPk());
    aiJson(['ok' => $r === true, 'message' => $r === true ? '管理员已创建。' : $r]);
}