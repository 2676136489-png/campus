<?php
/**
 * Maintenance helpers for expired records.
 */

/**
 * Remove expired rate-limit, password-reset, notification and report records.
 */
function cleanupExpiredRecords()
{
    $conn = dbConnect();
    $conn->query("DELETE FROM `login_attempt` WHERE `createTime` < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $conn->query("DELETE FROM `password_reset` WHERE `used` = 1 AND `createTime` < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $conn->query("DELETE FROM `password_reset` WHERE `used` = 0 AND `expiresAt` < DATE_SUB(NOW(), INTERVAL 1 DAY)");
    $conn->query("DELETE FROM `notification` WHERE `createTime` < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    $conn->query("DELETE FROM `report` WHERE `status` != 'pending' AND `createTime` < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    $conn->close();
}
