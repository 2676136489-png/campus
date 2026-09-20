<?php
/**
 * Report and moderation helpers.
 */

/**
 * Create a report for a dynamic or comment.
 * @param int $reporterPk
 * @param string $targetType dynamic|comment
 * @param int $targetPk
 * @param string $reason
 * @return true|string
 */
function createReport($reporterPk, $targetType, $targetPk, $reason)
{
    if (!in_array($targetType, ['dynamic', 'comment'], true)) {
        return '举报对象类型不正确。';
    }
    $targetPk = (int)$targetPk;
    $reason = trim($reason);
    if ($targetPk <= 0) {
        return '举报对象不存在。';
    }
    if ($reason === '') {
        return '请填写举报原因。';
    }
    if (textLength($reason) > 500) {
        return '举报原因不能超过 500 字。';
    }

    $conn = dbConnect();
    if ($targetType === 'dynamic') {
        $stmt = $conn->prepare("SELECT `pk` FROM `dynamic` WHERE `pk` = ? AND `status` = 'normal'");
    } else {
        $stmt = $conn->prepare("SELECT `pk` FROM `comment` WHERE `pk` = ?");
    }
    $stmt->bind_param('i', $targetPk);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$target) {
        $conn->close();
        return '举报对象不存在或已删除。';
    }
    if ($targetType === 'dynamic') {
        $stmt = $conn->prepare("SELECT `userPk` FROM `dynamic` WHERE `pk` = ?");
    } else {
        $stmt = $conn->prepare("SELECT `userPk` FROM `comment` WHERE `pk` = ?");
    }
    $stmt->bind_param('i', $targetPk);
    $stmt->execute();
    $owner = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($owner && (int)$owner['userPk'] === (int)$reporterPk) {
        $conn->close();
        return '不能举报自己的内容。';
    }

    $stmt = $conn->prepare("SELECT `pk` FROM `report` WHERE `reporterPk` = ? AND `targetType` = ? AND `targetPk` = ? AND `status` = 'pending'");
    $stmt->bind_param('isi', $reporterPk, $targetType, $targetPk);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($exists) {
        $conn->close();
        return '你已举报过该内容，请等待管理员处理。';
    }

    $stmt = $conn->prepare("INSERT INTO `report` (`reporterPk`, `targetType`, `targetPk`, `reason`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isis', $reporterPk, $targetType, $targetPk, $reason);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}

/**
 * Paginated report list for the admin moderation queue.
 * @param string $status pending|resolved|dismissed
 * @param int $page
 * @param int $perPage
 * @return array
 */
function getReportsPage($status = 'pending', $page = 1, $perPage = 20)
{
    if (!in_array($status, ['pending', 'resolved', 'dismissed', 'all'], true)) {
        $status = 'pending';
    }
    $page = max(1, (int)$page);
    $perPage = max(1, min(100, (int)$perPage));
    $offset = ($page - 1) * $perPage;
    $conn = dbConnect();
    $whereCount = $status === 'all' ? '1=1' : '`status` = ?';
    $whereSelect = $status === 'all' ? '1=1' : 'r.`status` = ?';

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `report` WHERE " . $whereCount);
    if ($status !== 'all') {
        $stmt->bind_param('s', $status);
    }
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT r.pk, r.targetType, r.targetPk, r.reason, r.status, r.createTime,
            u.userName AS reporterName, s.name AS reporterRealName
        FROM `report` r
        INNER JOIN `user` u ON u.pk = r.reporterPk
        LEFT JOIN `student` s ON s.pk = u.pk
        WHERE " . $whereSelect . "
        ORDER BY r.createTime DESC
        LIMIT ?, ?");
    if ($status !== 'all') {
        $stmt->bind_param('sii', $status, $offset, $perPage);
    } else {
        $stmt->bind_param('ii', $offset, $perPage);
    }
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($items as &$item) {
        if ($item['targetType'] === 'dynamic') {
            $stmt = $conn->prepare("SELECT `content`, `userPk` FROM `dynamic` WHERE `pk` = ?");
        } else {
            $stmt = $conn->prepare("SELECT `content`, `userPk` FROM `comment` WHERE `pk` = ?");
        }
        $stmt->bind_param('i', $item['targetPk']);
        $stmt->execute();
        $target = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $item['targetContent'] = $target ? $target['content'] : '(已删除)';
        $item['targetOwnerPk'] = $target ? (int)$target['userPk'] : 0;
    }
    unset($item);
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
 * Mark a report resolved or dismissed.
 * @param int $reportPk
 * @param string $status
 * @return bool
 */
function setReportStatus($reportPk, $status)
{
    if (!in_array($status, ['resolved', 'dismissed'], true)) {
        return false;
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `report` SET `status` = ? WHERE `pk` = ?");
    $stmt->bind_param('si', $status, $reportPk);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();
    $conn->close();
    return $ok;
}
