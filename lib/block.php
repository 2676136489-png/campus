<?php
/**
 * User blocking.
 */

/**
 * Toggle block state.
 * @param int $blockerPk
 * @param int $blockedPk
 * @return array
 */
function toggleBlock($blockerPk, $blockedPk)
{
    $blockerPk = (int)$blockerPk;
    $blockedPk = (int)$blockedPk;
    if ($blockerPk === $blockedPk) {
        return ['ok' => false, 'message' => '不能拉黑自己。'];
    }
    $target = getStudentByPk($blockedPk);
    if (!$target || $target['status'] !== 'V') {
        return ['ok' => false, 'message' => '用户不存在或不可用。'];
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `pk` FROM `block` WHERE `blockerPk` = ? AND `blockedPk` = ?");
    $stmt->bind_param('ii', $blockerPk, $blockedPk);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($existing) {
        $stmt = $conn->prepare("DELETE FROM `block` WHERE `pk` = ?");
        $stmt->bind_param('i', $existing['pk']);
        $stmt->execute();
        $stmt->close();
        $blocked = false;
    } else {
        $stmt = $conn->prepare("INSERT INTO `block` (`blockerPk`, `blockedPk`) VALUES (?, ?)");
        $stmt->bind_param('ii', $blockerPk, $blockedPk);
        $stmt->execute();
        $stmt->close();
        $blocked = true;
    }
    $conn->close();
    return ['ok' => true, 'blocked' => $blocked];
}

/**
 * Check whether either direction of a block exists between two users.
 * @param int $userPk
 * @param int $otherPk
 * @return bool
 */
function isBlocked($userPk, $otherPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `block` WHERE (blockerPk = ? AND blockedPk = ?) OR (blockerPk = ? AND blockedPk = ?)");
    $stmt->bind_param('iiii', $userPk, $otherPk, $otherPk, $userPk);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $conn->close();
    return $total > 0;
}

/**
 * @param int $userPk
 * @return int[]
 */
function getBlockedUserPks($userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `blockedPk` FROM `block` WHERE `blockerPk` = ?");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return array_map('intval', array_column($rows, 'blockedPk'));
}
