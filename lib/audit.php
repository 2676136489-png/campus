<?php
/**
 * Admin audit logging.
 */


/**
 * 写管理员审计日志
 * @param int $adminPk 管理员ID
 * @param string $targetType 目标类型
 * @param int $targetPk 目标ID
 * @param string $action 操作
 * @param string $detail 详情
 * @return bool
 */
function logAudit($adminPk, $targetType, $targetPk, $action, $detail = '')
{
    if ($adminPk <= 0) {
        return false;
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `audit_log` (`adminPk`, `targetType`, `targetPk`, `action`, `detail`) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('isiss', $adminPk, $targetType, $targetPk, $action, $detail);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}


/**
 * 获取管理员审计日志
 * @param int $limit 条数
 * @return array
 */
function getAuditLogs($limit = 100)
{
    $limit = max(1, min(300, (int)$limit));
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT a.pk, a.targetType, a.targetPk, a.action, a.detail, a.createTime,
            u.userName AS adminName, t.userName AS targetName
        FROM `audit_log` a
        INNER JOIN `user` u ON u.pk = a.adminPk
        LEFT JOIN `user` t ON t.pk = a.targetPk
        ORDER BY a.createTime DESC
        LIMIT ?");
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $data;
}

/**
 * 分页查询审计日志
 * @param int $page
 * @param int $perPage
 * @return array
 */
function getAuditLogsPage($page = 1, $perPage = 50)
{
    $page = max(1, (int)$page);
    $perPage = max(1, min(100, (int)$perPage));
    $offset = ($page - 1) * $perPage;
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `audit_log`");
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT a.pk, a.targetType, a.targetPk, a.action, a.detail, a.createTime,
            u.userName AS adminName, t.userName AS targetName
        FROM `audit_log` a
        INNER JOIN `user` u ON u.pk = a.adminPk
        LEFT JOIN `user` t ON t.pk = a.targetPk
        ORDER BY a.createTime DESC
        LIMIT ?, ?");
    $stmt->bind_param('ii', $offset, $perPage);
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
 * 写入审计日志并返回审计ID
 * @param int $adminPk
 * @param string $targetType
 * @param int $targetPk
 * @param string $action
 * @param string $detail
 * @return int
 */
function logAuditReturnId($adminPk, $targetType, $targetPk, $action, $detail = '')
{
    if ($adminPk <= 0) {
        return 0;
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `audit_log` (`adminPk`, `targetType`, `targetPk`, `action`, `detail`) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('isiss', $adminPk, $targetType, $targetPk, $action, $detail);
    $stmt->execute();
    $id = (int)$stmt->insert_id;
    $stmt->close();
    $conn->close();
    return $id;
}

/**
 * 保存可回滚的删除负载并写审计日志
 * @param int $adminPk
 * @param string $targetType
 * @param int $targetPk
 * @param string $action
 * @param string $detail
 * @param array $payload
 * @return bool
 */
function storeRollbackPayload($adminPk, $targetType, $targetPk, $action, $detail, array $payload)
{
    $auditPk = logAuditReturnId((int)$adminPk, $targetType, (int)$targetPk, $action, $detail);
    if ($auditPk <= 0) {
        return false;
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `admin_rollback` (`auditPk`, `targetType`, `targetPk`, `payload`) VALUES (?, ?, ?, ?)");
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $targetPkInt = (int)$targetPk;
    $stmt->bind_param('isis', $auditPk, $targetType, $targetPkInt, $json);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}

/**
 * 获取回滚记录
 * @param int $auditPk
 * @return array|null
 */
function getRollbackEntry($auditPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `pk`, `auditPk`, `targetType`, `targetPk`, `payload` FROM `admin_rollback` WHERE `auditPk` = ? LIMIT 1");
    $stmt->bind_param('i', $auditPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ?: null;
}

/**
 * 执行回滚恢复
 * @param int $auditPk
 * @param int $operatorPk
 * @return true|string
 */
function performRollback($auditPk, $operatorPk)
{
    $entry = getRollbackEntry((int)$auditPk);
    if (!$entry) {
        return '没有可回滚的记录。';
    }
    $payload = json_decode($entry['payload'], true);
    if (!is_array($payload)) {
        return '回滚数据已损坏。';
    }
    $targetType = $entry['targetType'];
    $targetPk = (int)$entry['targetPk'];
    $conn = dbConnect();
    $ok = false;
    if ($targetType === 'dynamic') {
        $stmt = $conn->prepare("UPDATE `dynamic` SET `status` = 'normal' WHERE `pk` = ?");
        $stmt->bind_param('i', $targetPk);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        if (!$ok) {
            $userPkInt = (int)$payload['userPk'];
            $contentStr = (string)$payload['content'];
            $tagsStr = (string)($payload['tags'] ?? '');
            $photoStr = (string)($payload['photo'] ?? '');
            $timeStr = (string)$payload['createTime'];
            $stmt = $conn->prepare("INSERT INTO `dynamic` (`pk`, `userPk`, `content`, `tags`, `photo`, `status`, `createTime`) VALUES (?, ?, ?, ?, ?, 'normal', ?)");
            $stmt->bind_param('iissss', $targetPk, $userPkInt, $contentStr, $tagsStr, $photoStr, $timeStr);
            $ok = $stmt->execute();
            $stmt->close();
        }
    } elseif ($targetType === 'comment') {
        $stmt = $conn->prepare("INSERT INTO `comment` (`pk`, `dynamicPk`, `userPk`, `content`, `parentPk`, `createTime`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `content` = VALUES(`content`), `parentPk` = VALUES(`parentPk`)");
        $dynPkInt = (int)$payload['dynamicPk'];
        $userPkInt = (int)$payload['userPk'];
        $contentStr = (string)$payload['content'];
        $parentPkInt = (int)($payload['parentPk'] ?? 0);
        $timeStr = (string)$payload['createTime'];
        $stmt->bind_param('iiisis', $targetPk, $dynPkInt, $userPkInt, $contentStr, $parentPkInt, $timeStr);
        $ok = $stmt->execute();
        $stmt->close();
    } elseif ($targetType === 'message') {
        $stmt = $conn->prepare("INSERT INTO `message` (`pk`, `senderPk`, `receiverPk`, `content`, `image`, `status`, `isRead`, `createTime`) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `content` = VALUES(`content`), `image` = VALUES(`image`), `status` = VALUES(`status`), `isRead` = VALUES(`isRead`)");
        $senderPkInt = (int)$payload['senderPk'];
        $receiverPkInt = (int)$payload['receiverPk'];
        $contentStr = (string)$payload['content'];
        $imageStr = (string)($payload['image'] ?? '');
        $statusStr = (string)($payload['status'] ?? 'normal');
        $isReadInt = (int)($payload['isRead'] ?? 0);
        $timeStr = (string)$payload['createTime'];
        $stmt->bind_param('iiisssis', $targetPk, $senderPkInt, $receiverPkInt, $contentStr, $imageStr, $statusStr, $isReadInt, $timeStr);
        $ok = $stmt->execute();
        $stmt->close();
    }
    $conn->close();
    if (!$ok) {
        return '回滚失败，目标记录无法恢复。';
    }
    logAudit((int)$operatorPk, $targetType, $targetPk, 'rollback_' . $targetType, '回滚恢复');
    $conn = dbConnect();
    $stmt = $conn->prepare("DELETE FROM `admin_rollback` WHERE `pk` = ?");
    $entryPk = (int)$entry['pk'];
    $stmt->bind_param('i', $entryPk);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}
