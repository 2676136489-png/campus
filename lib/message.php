<?php
/**
 * In-app direct messaging with recall, read state and paging.
 */

/**
 * Send a direct message.
 * @param int $senderPk
 * @param int $receiverPk
 * @param string $content
 * @return array
 */
function sendMessage($senderPk, $receiverPk, $content, $image = '')
{
    $senderPk = (int)$senderPk;
    $receiverPk = (int)$receiverPk;
    $content = trim($content);
    $image = trim($image);
    if ($image !== '' && assetUrl($image) === '') {
        return ['ok' => false, 'message' => '图片消息无效。'];
    }
    if ($senderPk === $receiverPk) {
        return ['ok' => false, 'message' => '不能给自己发送私信。'];
    }
    if ($content === '') {
        return ['ok' => false, 'message' => '消息内容不能为空。'];
    }
    if (textLength($content) > 1000) {
        return ['ok' => false, 'message' => '消息不能超过 1000 字。'];
    }
    $sensitive = filterSensitiveText($content);
    if ($sensitive['blocked']) {
        return ['ok' => false, 'message' => '消息包含违规词：' . $sensitive['word']];
    }
    $receiver = getStudentByPk($receiverPk);
    if (!$receiver || $receiver['status'] !== 'V') {
        return ['ok' => false, 'message' => '收件人不存在或不可用。'];
    }
    if (isBlocked($senderPk, $receiverPk)) {
        return ['ok' => false, 'message' => '你们之间存在拉黑关系，无法发送私信。'];
    }
    if (!canMessage($senderPk, $receiverPk)) {
        return ['ok' => false, 'message' => '对方仅允许自己关注的人发送私信。'];
    }
    $limit = enforceActionRateLimit($senderPk, 'message', 20, 60);
    if ($limit !== true) {
        return ['ok' => false, 'message' => $limit];
    }

    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `message` (`senderPk`, `receiverPk`, `content`, `image`, `status`) VALUES (?, ?, ?, ?, 'normal')");
    $stmt->bind_param('iiss', $senderPk, $receiverPk, $content, $image);
    $stmt->execute();
    $messagePk = $stmt->insert_id;
    $stmt->close();
    recordAction($senderPk, 'message');
    $conn->close();

    $sender = getStudentByPk($senderPk);
    $senderName = $sender ? ($sender['name'] ?: $sender['userName']) : '一位同学';
    createNotification($receiverPk, 'message', $senderName . ' 给你发来一条私信。', 'p_chat.php?user=' . $senderPk);

    return [
        'ok' => true,
        'message' => [
            'pk' => $messagePk,
            'senderPk' => $senderPk,
            'receiverPk' => $receiverPk,
            'content' => $content,
            'image' => $image,
            'status' => 'normal',
            'recalledAt' => null,
            'isRead' => 0,
            'createTime' => date('Y-m-d H:i:s')
        ]
    ];
}

/**
 * Recall a message within 2 minutes.
 * @param int $userPk
 * @param int $messagePk
 * @return array
 */
function recallMessage($userPk, $messagePk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `pk`, `senderPk`, `status`, `createTime` FROM `message` WHERE `pk` = ?");
    $stmt->bind_param('i', $messagePk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        $conn->close();
        return ['ok' => false, 'message' => '消息不存在。'];
    }
    if ((int)$row['senderPk'] !== (int)$userPk) {
        $conn->close();
        return ['ok' => false, 'message' => '只能撤回自己发送的消息。'];
    }
    if ($row['status'] === 'recalled') {
        $conn->close();
        return ['ok' => false, 'message' => '消息已撤回。'];
    }
    if (strtotime($row['createTime']) < time() - 120) {
        $conn->close();
        return ['ok' => false, 'message' => '发送超过 2 分钟的消息不能撤回。'];
    }

    $stmt = $conn->prepare("UPDATE `message` SET `status` = 'recalled', `recalledAt` = NOW() WHERE `pk` = ?");
    $stmt->bind_param('i', $messagePk);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return ['ok' => true, 'messagePk' => (int)$messagePk];
}

/**
 * @param int $userPk
 * @return int
 */
function getUnreadMessageCount($userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `message` WHERE `receiverPk` = ? AND `isRead` = 0 AND `status` = 'normal'");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $conn->close();
    return $count;
}

/**
 * Conversation list for the message inbox.
 * @param int $userPk
 * @return array
 */
function getConversations($userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT t.otherPk, m.pk AS lastPk, m.content AS lastContent, m.image AS lastImage, m.status AS lastStatus, m.createTime AS lastTime,
            u.userName, s.name, s.avatar, cs.isPinned, cs.isMuted,
            (SELECT COUNT(*) FROM `message` m2 WHERE m2.receiverPk = ? AND m2.senderPk = t.otherPk AND m2.isRead = 0 AND m2.status = 'normal') AS unread
        FROM (
            SELECT CASE WHEN senderPk = ? THEN receiverPk ELSE senderPk END AS otherPk, MAX(pk) AS lastPk
            FROM `message`
            WHERE senderPk = ? OR receiverPk = ?
            GROUP BY otherPk
        ) t
        INNER JOIN `message` m ON m.pk = t.lastPk
        INNER JOIN `user` u ON u.pk = t.otherPk
        LEFT JOIN `student` s ON s.pk = u.pk
        LEFT JOIN `conversation_setting` cs ON cs.userPk = ? AND cs.otherPk = t.otherPk
        ORDER BY cs.isPinned DESC, m.createTime DESC");
    $stmt->bind_param('iiiii', $userPk, $userPk, $userPk, $userPk, $userPk);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    foreach ($rows as &$row) {
        if ($row['lastStatus'] === 'recalled') {
            $row['lastContent'] = '撤回了一条消息';
        } elseif (!empty($row['lastImage'])) {
            $row['lastContent'] = '[图片]';
        }
    }
    unset($row);
    return $rows;
}

/**
 * Messages between two users, newest last, and marks incoming as read.
 * @param int $userPk
 * @param int $otherPk
 * @param int $limit
 * @return array
 */
function getMessagesBetween($userPk, $otherPk, $limit = 50)
{
    $limit = max(1, min(200, (int)$limit));
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT pk, senderPk, receiverPk, content, image, status, recalledAt, isRead, createTime
        FROM `message`
        WHERE (senderPk = ? AND receiverPk = ?) OR (senderPk = ? AND receiverPk = ?)
        ORDER BY pk DESC
        LIMIT ?");
    $stmt->bind_param('iiiii', $userPk, $otherPk, $otherPk, $userPk, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("UPDATE `message` SET `isRead` = 1 WHERE `receiverPk` = ? AND `senderPk` = ? AND `isRead` = 0 AND `status` = 'normal'");
    $stmt->bind_param('ii', $userPk, $otherPk);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    $rows = array_reverse($rows);
    return $rows;
}

/**
 * Get messages older than a cursor, oldest first.
 * @param int $userPk
 * @param int $otherPk
 * @param int $beforePk
 * @param int $limit
 * @return array
 */
function getMessagesBefore($userPk, $otherPk, $beforePk = 0, $limit = 30)
{
    $beforePk = (int)$beforePk;
    $limit = max(1, min(100, (int)$limit));
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT pk, senderPk, receiverPk, content, image, status, recalledAt, isRead, createTime
        FROM `message`
        WHERE pk < ? AND ((senderPk = ? AND receiverPk = ?) OR (senderPk = ? AND receiverPk = ?))
        ORDER BY pk DESC
        LIMIT ?");
    $stmt->bind_param('iiiiii', $beforePk, $userPk, $otherPk, $otherPk, $userPk, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return array_reverse($rows);
}

/**
 * 获取某个会话中指定消息之后的新消息，并标记已读。
 * @param int $userPk
 * @param int $otherPk
 * @param int $afterPk
 * @param int $limit
 * @return array
 */
function getMessagesAfter($userPk, $otherPk, $afterPk = 0, $limit = 20)
{
    $afterPk = (int)$afterPk;
    $limit = max(1, min(100, (int)$limit));
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT pk, senderPk, receiverPk, content, image, status, recalledAt, isRead, createTime
        FROM `message`
        WHERE pk > ? AND ((senderPk = ? AND receiverPk = ?) OR (senderPk = ? AND receiverPk = ?))
        ORDER BY pk ASC
        LIMIT ?");
    $stmt->bind_param('iiiiii', $afterPk, $userPk, $otherPk, $otherPk, $userPk, $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conn->prepare("UPDATE `message` SET `isRead` = 1 WHERE `receiverPk` = ? AND `senderPk` = ? AND `isRead` = 0 AND `status` = 'normal'");
    $stmt->bind_param('ii', $userPk, $otherPk);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return $rows;
}

/**
 * 管理端私信审计分页
 * @param array $filters
 * @param int $page
 * @param int $perPage
 * @return array
 */
function getAdminMessagesPage($filters = [], $page = 1, $perPage = 20)
{
    $page = max(1, (int)$page);
    $perPage = max(1, min(100, (int)$perPage));
    $offset = ($page - 1) * $perPage;
    $conn = dbConnect();
    $where = ['1=1'];
    $types = '';
    $params = [];
    $keyword = trim($filters['keyword'] ?? '');
    if ($keyword !== '') {
        $where[] = "(m.content LIKE ? OR su.userName LIKE ? OR ru.userName LIKE ? OR ss.name LIKE ? OR rs.name LIKE ?)";
        $like = '%' . $keyword . '%';
        $types .= 'sssss';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $whereSql = implode(' AND ', $where);

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `message` m WHERE " . $whereSql);
    if ($types !== '') {
        bindDynamicParams($stmt, $types, $params);
    }
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT m.pk, m.content, m.image, m.status, m.isRead, m.createTime,
            su.userName AS senderName, ss.name AS senderRealName,
            ru.userName AS receiverName, rs.name AS receiverRealName
        FROM `message` m
        INNER JOIN `user` su ON su.pk = m.senderPk
        LEFT JOIN `student` ss ON ss.pk = su.pk
        INNER JOIN `user` ru ON ru.pk = m.receiverPk
        LEFT JOIN `student` rs ON rs.pk = ru.pk
        WHERE " . $whereSql . "
        ORDER BY m.createTime DESC
        LIMIT ?, ?");
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

/**
 * 管理员删除私信并写审计日志
 * @param int $adminPk
 * @param int $messagePk
 * @return bool
 */
function deleteMessageByAdmin($adminPk, $messagePk)
{
    $conn = dbConnect();
    $payload = [];
    $stmt = $conn->prepare("SELECT `pk`, `senderPk`, `receiverPk`, `content`, `image`, `status`, `isRead`, `createTime` FROM `message` WHERE `pk` = ?");
    $stmt->bind_param('i', $messagePk);
    $stmt->execute();
    $payload = $stmt->get_result()->fetch_assoc() ?: [];
    $stmt->close();
    $stmt = $conn->prepare("DELETE FROM `message` WHERE `pk` = ?");
    $stmt->bind_param('i', $messagePk);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    if ($ok && !empty($payload)) {
        storeRollbackPayload($adminPk, 'message', (int)$messagePk, 'delete_message', '私信审计删除', $payload);
    }
    return $ok;
}
