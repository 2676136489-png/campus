<?php
/**
 * User privacy and conversation settings.
 */

/**
 * @param int $userPk
 * @return array{allowMessages: string, showProfile: bool}
 */
function getPrivacy($userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `allowMessages`, `showProfile` FROM `privacy` WHERE `userPk` = ?");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    if (!$row) {
        return ['allowMessages' => 'all', 'showProfile' => true];
    }
    return [
        'allowMessages' => $row['allowMessages'] === 'followed' ? 'followed' : 'all',
        'showProfile' => (int)$row['showProfile'] === 1
    ];
}

/**
 * @param int $userPk
 * @param string $allowMessages all|followed
 * @param bool $showProfile
 * @return bool
 */
function updatePrivacy($userPk, $allowMessages, $showProfile)
{
    if (!in_array($allowMessages, ['all', 'followed'], true)) {
        $allowMessages = 'all';
    }
    $showProfileInt = $showProfile ? 1 : 0;
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `privacy` (`userPk`, `allowMessages`, `showProfile`) VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE `allowMessages` = VALUES(`allowMessages`), `showProfile` = VALUES(`showProfile`)");
    $stmt->bind_param('isi', $userPk, $allowMessages, $showProfileInt);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}

/**
 * Whether sender is allowed to message receiver based on receiver privacy.
 * @param int $senderPk
 * @param int $receiverPk
 * @return bool
 */
function canMessage($senderPk, $receiverPk)
{
    $privacy = getPrivacy($receiverPk);
    if ($privacy['allowMessages'] === 'all') {
        return true;
    }
    return isFollowing($receiverPk, $senderPk);
}

/**
 * @param int $userPk
 * @param int $otherPk
 * @return array{isPinned: bool, isMuted: bool}
 */
function getConversationSetting($userPk, $otherPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `isPinned`, `isMuted` FROM `conversation_setting` WHERE `userPk` = ? AND `otherPk` = ?");
    $stmt->bind_param('ii', $userPk, $otherPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    if (!$row) {
        return ['isPinned' => false, 'isMuted' => false];
    }
    return [
        'isPinned' => (int)$row['isPinned'] === 1,
        'isMuted' => (int)$row['isMuted'] === 1
    ];
}

/**
 * @param int $userPk
 * @param int $otherPk
 * @param string $field isPinned|isMuted
 * @param bool $value
 * @return bool
 */
function updateConversationSetting($userPk, $otherPk, $field, $value)
{
    if (!in_array($field, ['isPinned', 'isMuted'], true)) {
        return false;
    }
    $valueInt = $value ? 1 : 0;
    $conn = dbConnect();
    $sql = "INSERT INTO `conversation_setting` (`userPk`, `otherPk`, `isPinned`, `isMuted`) VALUES (?, ?, 0, 0)
        ON DUPLICATE KEY UPDATE `" . $field . "` = VALUES(`" . $field . "`)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $userPk, $otherPk);
    $stmt->execute();
    $stmt->close();
    $stmt = $conn->prepare("UPDATE `conversation_setting` SET `" . $field . "` = ? WHERE `userPk` = ? AND `otherPk` = ?");
    $stmt->bind_param('iii', $valueInt, $userPk, $otherPk);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}
