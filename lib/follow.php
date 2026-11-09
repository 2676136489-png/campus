<?php
/**
 * Follow relationships.
 */

/**
 * Toggle follow state between two students.
 * @param int $followerPk
 * @param int $followingPk
 * @return array
 */
function toggleFollow($followerPk, $followingPk)
{
    $followerPk = (int)$followerPk;
    $followingPk = (int)$followingPk;
    if ($followerPk === $followingPk) {
        return ['ok' => false, 'message' => '不能关注自己。'];
    }
    if (isBlocked($followerPk, $followingPk)) {
        return ['ok' => false, 'message' => '你们之间存在拉黑关系，无法关注。'];
    }
    $target = getStudentByPk($followingPk);
    if (!$target || $target['status'] !== 'V') {
        return ['ok' => false, 'message' => '关注对象不存在或不可用。'];
    }

    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `pk` FROM `follow` WHERE `followerPk` = ? AND `followingPk` = ?");
    $stmt->bind_param('ii', $followerPk, $followingPk);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $following = false;
    if ($existing) {
        $stmt = $conn->prepare("DELETE FROM `follow` WHERE `pk` = ?");
        $stmt->bind_param('i', $existing['pk']);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO `follow` (`followerPk`, `followingPk`) VALUES (?, ?)");
        $stmt->bind_param('ii', $followerPk, $followingPk);
        $stmt->execute();
        $stmt->close();
        $following = true;
        $follower = getStudentByPk($followerPk);
        $followerName = $follower ? ($follower['name'] ?: $follower['userName']) : '一位同学';
        createNotification($followingPk, 'follow', $followerName . ' 关注了你。', 'p_profile.php?user=' . $followerPk);
    }
    $conn->close();

    $counts = getFollowCounts($followingPk);
    return [
        'ok' => true,
        'following' => $following,
        'followerCount' => $counts['followers'],
        'followingCount' => $counts['following']
    ];
}

/**
 * @param int $followerPk
 * @param int $followingPk
 * @return bool
 */
function isFollowing($followerPk, $followingPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `follow` WHERE `followerPk` = ? AND `followingPk` = ?");
    $stmt->bind_param('ii', $followerPk, $followingPk);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $conn->close();
    return $count > 0;
}

/**
 * @param int $userPk
 * @return array{followers: int, following: int}
 */
function getFollowCounts($userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT
        (SELECT COUNT(*) FROM `follow` WHERE `followingPk` = ?) AS followers,
        (SELECT COUNT(*) FROM `follow` WHERE `followerPk` = ?) AS following");
    $stmt->bind_param('ii', $userPk, $userPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return [
        'followers' => (int)($row['followers'] ?? 0),
        'following' => (int)($row['following'] ?? 0)
    ];
}
