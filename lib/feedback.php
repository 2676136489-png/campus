<?php
/**
 * User feedback and admin handling.
 */

const FEEDBACK_TYPES = ['bug', 'suggestion', 'complaint', 'other'];
const FEEDBACK_STATUSES = ['open', 'processing', 'closed'];

/**
 * 提交反馈
 * @param int $userPk
 * @param string $type
 * @param string $content
 * @return true|string
 */
function createFeedback($userPk, $type, $content, $image = '')
{
    if (!in_array($type, FEEDBACK_TYPES, true)) {
        return '请选择反馈类型。';
    }
    $content = trim($content);
    $image = trim($image);
    if ($content === '') {
        return '反馈内容不能为空。';
    }
    if (textLength($content) > 1000) {
        return '反馈内容不能超过 1000 字。';
    }
    if ($image !== '' && assetUrl($image) === '') {
        return '反馈图片无效。';
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `feedback` (`userPk`, `type`, `content`, `image`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $userPk, $type, $content, $image);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}

/**
 * 学生自己的反馈列表
 * @param int $userPk
 * @param int $page
 * @param int $perPage
 * @return array
 */
function getMyFeedbackPage($userPk, $page = 1, $perPage = 10)
{
    $page = max(1, (int)$page);
    $perPage = max(1, min(100, (int)$perPage));
    $offset = ($page - 1) * $perPage;
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `feedback` WHERE `userPk` = ?");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT `pk`, `type`, `content`, `image`, `status`, `createTime`, `updateTime` FROM `feedback` WHERE `userPk` = ? ORDER BY `createTime` DESC LIMIT ?, ?");
    $stmt->bind_param('iii', $userPk, $offset, $perPage);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => max(1, (int)ceil($total / $perPage))
    ];
}

/**
 * 管理端反馈队列
 * @param string $status open|processing|closed|all
 * @param int $page
 * @param int $perPage
 * @return array
 */
function getAdminFeedbackPage($status = 'open', $page = 1, $perPage = 10)
{
    if (!in_array($status, array_merge(FEEDBACK_STATUSES, ['all']), true)) {
        $status = 'open';
    }
    $page = max(1, (int)$page);
    $perPage = max(1, min(100, (int)$perPage));
    $offset = ($page - 1) * $perPage;
    $conn = dbConnect();
    $whereCount = $status === 'all' ? '1=1' : '`status` = ?';
    $whereSelect = $status === 'all' ? '1=1' : 'f.`status` = ?';

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `feedback` WHERE " . $whereCount);
    if ($status !== 'all') {
        $stmt->bind_param('s', $status);
    }
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT f.pk, f.type, f.content, f.image, f.status, f.createTime, f.updateTime,
            u.userName, s.name AS realName
        FROM `feedback` f
        INNER JOIN `user` u ON u.pk = f.userPk
        LEFT JOIN `student` s ON s.pk = u.pk
        WHERE " . $whereSelect . "
        ORDER BY f.createTime DESC
        LIMIT ?, ?");
    if ($status !== 'all') {
        $stmt->bind_param('sii', $status, $offset, $perPage);
    } else {
        $stmt->bind_param('ii', $offset, $perPage);
    }
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => max(1, (int)ceil($total / $perPage))
    ];
}

/**
 * 更新反馈状态
 * @param int $feedbackPk
 * @param string $status
 * @return bool
 */
function updateFeedbackStatus($feedbackPk, $status, $adminPk = 0)
{
    if (!in_array($status, FEEDBACK_STATUSES, true)) {
        return false;
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `userPk` FROM `feedback` WHERE `pk` = ?");
    $stmt->bind_param('i', $feedbackPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        $conn->close();
        return false;
    }
    $stmt = $conn->prepare("UPDATE `feedback` SET `status` = ? WHERE `pk` = ?");
    $stmt->bind_param('si', $status, $feedbackPk);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();
    $conn->close();

    if ($ok && $adminPk > 0) {
        $label = ['open' => '待处理', 'processing' => '处理中', 'closed' => '已关闭'];
        createNotification((int)$row['userPk'], 'feedback', '你的反馈状态已更新为：' . ($label[$status] ?? $status), 'p_feedback.php');
    }
    return $ok;
}
