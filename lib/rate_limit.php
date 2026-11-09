<?php
/**
 * Per-user action rate limiting for content creation and social actions.
 */

/**
 * Record a user action.
 * @param int $userPk
 * @param string $action
 */
function recordAction($userPk, $action)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `action_log` (`userPk`, `action`) VALUES (?, ?)");
    $stmt->bind_param('is', $userPk, $action);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

/**
 * Check whether the user has exceeded the action limit in the window.
 * @param int $userPk
 * @param string $action
 * @param int $max
 * @param int $windowSeconds
 * @return bool
 */
function isActionRateLimited($userPk, $action, $max, $windowSeconds = 60)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `action_log` WHERE `userPk` = ? AND `action` = ? AND `createTime` > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->bind_param('isi', $userPk, $action, $windowSeconds);
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $conn->close();
    return $total >= $max;
}

/**
 * Enforce a rate limit and return a user-facing message when exceeded.
 * @param int $userPk
 * @param string $action
 * @param int $max
 * @param int $windowSeconds
 * @return true|string
 */
function enforceActionRateLimit($userPk, $action, $max, $windowSeconds = 60)
{
    if (isActionRateLimited($userPk, $action, $max, $windowSeconds)) {
        return '操作过于频繁，请稍后再试。';
    }
    return true;
}
