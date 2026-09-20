<?php
/**
 * Notification preferences.
 */

const NOTIFICATION_TYPES = ['like', 'comment', 'review', 'account', 'system', 'follow', 'feedback'];

/**
 * @param int $userPk
 * @return array<string, bool>
 */
function getNotificationPrefs($userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `type`, `enabled` FROM `notification_pref` WHERE `userPk` = ?");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    $prefs = [];
    foreach (NOTIFICATION_TYPES as $type) {
        $prefs[$type] = true;
    }
    foreach ($rows as $row) {
        $prefs[$row['type']] = (int)$row['enabled'] === 1;
    }
    return $prefs;
}

/**
 * @param int $userPk
 * @param array<string, bool> $enabledTypes
 * @return bool
 */
function updateNotificationPrefs($userPk, $enabledTypes)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("DELETE FROM `notification_pref` WHERE `userPk` = ?");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO `notification_pref` (`userPk`, `type`, `enabled`) VALUES (?, ?, ?)");
    foreach (NOTIFICATION_TYPES as $type) {
        $enabled = !empty($enabledTypes[$type]) ? 1 : 0;
        $stmt->bind_param('isi', $userPk, $type, $enabled);
        $stmt->execute();
    }
    $stmt->close();
    $conn->close();
    return true;
}

/**
 * @param int $userPk
 * @param string $type
 * @return bool
 */
function isNotificationEnabled($userPk, $type)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `enabled` FROM `notification_pref` WHERE `userPk` = ? AND `type` = ?");
    $stmt->bind_param('is', $userPk, $type);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ? (int)$row['enabled'] === 1 : true;
}
