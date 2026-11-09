<?php
/**
 * In-app notifications.
 */


/**
 * 创建站内通知
 * @param int $userPk 接收用户ID
 * @param string $type 类型
 * @param string $content 内容
 * @param string $link 跳转链接
 * @return bool
 */
function createNotification($userPk, $type, $content, $link = '')
{
    $content = trim($content);
    if ($content === '') {
        return false;
    }
    if (!isNotificationEnabled((int)$userPk, $type)) {
        return false;
    }
    if (textLength($content) > 500) {
        $content = function_exists('mb_substr') ? mb_substr($content, 0, 500, 'UTF-8') : substr($content, 0, 500);
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `notification` (`userPk`, `type`, `content`, `link`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $userPk, $type, $content, $link);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}


/**
 * 获取用户通知
 * @param int $userPk 用户ID
 * @param int $limit 条数
 * @return array
 */
function getNotifications($userPk, $limit = 50)
{
    $limit = max(1, min(200, (int)$limit));
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `pk`, `type`, `content`, `link`, `isRead`, `createTime` FROM `notification` WHERE `userPk` = ? ORDER BY `isRead` ASC, `createTime` DESC LIMIT ?");
    $stmt->bind_param('ii', $userPk, $limit);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $data;
}


/**
 * 未读通知数
 * @param int $userPk 用户ID
 * @return int
 */
function getUnreadNotificationCount($userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `notification` WHERE `userPk` = ? AND `isRead` = 0");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $conn->close();
    return $count;
}


/**
 * 全部标记已读
 * @param int $userPk 用户ID
 * @return bool
 */
function markNotificationsRead($userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `notification` SET `isRead` = 1 WHERE `userPk` = ? AND `isRead` = 0");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}

/**
 * 分页查询通知，支持类型筛选
 * @param int $userPk 用户ID
 * @param string $type 类型过滤
 * @param int $page 页码
 * @param int $perPage 每页条数
 * @return array
 */
function getNotificationsPage($userPk, $type = '', $page = 1, $perPage = 20)
{
    $page = max(1, (int)$page);
    $perPage = max(1, min(100, (int)$perPage));
    $offset = ($page - 1) * $perPage;
    $conn = dbConnect();
    $where = '`userPk` = ?';
    $types = 'i';
    $params = [(int)$userPk];
    if ($type !== '' && in_array($type, ['like', 'comment', 'review', 'account', 'system'], true)) {
        $where .= ' AND `type` = ?';
        $types .= 's';
        $params[] = $type;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `notification` WHERE " . $where);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT `pk`, `type`, `content`, `link`, `isRead`, `createTime` FROM `notification` WHERE " . $where . " ORDER BY `createTime` DESC LIMIT ?, ?");
    $types2 = $types . 'ii';
    $params2 = array_merge($params, [$offset, $perPage]);
    $stmt->bind_param($types2, ...$params2);
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
