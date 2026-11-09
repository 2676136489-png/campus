<?php
/**
 * Campus announcements.
 */

/**
 * 获取已发布的公告
 * @param int $limit
 * @return array
 */
function getPublishedAnnouncements($limit = 3)
{
    $limit = max(1, min(20, (int)$limit));
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `pk`, `title`, `content`, `createTime` FROM `announcement` WHERE `isPublished` = 1 ORDER BY `createTime` DESC LIMIT ?");
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $rows;
}

/**
 * 分页查询公告（管理端）
 * @param int $page
 * @param int $perPage
 * @return array
 */
function getAnnouncementsPage($page = 1, $perPage = 10)
{
    $page = max(1, (int)$page);
    $perPage = max(1, min(100, (int)$perPage));
    $offset = ($page - 1) * $perPage;
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `announcement`");
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT a.pk, a.title, a.content, a.isPublished, a.createTime, a.updateTime, u.userName AS adminName
        FROM `announcement` a
        INNER JOIN `user` u ON u.pk = a.adminPk
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
 * 根据ID获取公告
 * @param int $pk
 * @return array|null
 */
function getAnnouncement($pk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `pk`, `adminPk`, `title`, `content`, `isPublished`, `createTime` FROM `announcement` WHERE `pk` = ?");
    $stmt->bind_param('i', $pk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ?: null;
}

/**
 * 创建公告
 * @param int $adminPk
 * @param string $title
 * @param string $content
 * @param bool $isPublished
 * @return true|string
 */
function createAnnouncement($adminPk, $title, $content, $isPublished = true)
{
    $title = trim($title);
    $content = trim($content);
    if ($title === '' || textLength($title) > 100) {
        return '公告标题不能为空且不能超过 100 字。';
    }
    if ($content === '') {
        return '公告内容不能为空。';
    }
    if (textLength($content) > 2000) {
        return '公告内容不能超过 2000 字。';
    }
    $published = $isPublished ? 1 : 0;
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `announcement` (`adminPk`, `title`, `content`, `isPublished`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('issi', $adminPk, $title, $content, $published);
    $stmt->execute();
    $id = $stmt->insert_id;
    $stmt->close();
    logAudit($adminPk, 'announcement', $id, 'create_announcement', $title);
    $conn->close();
    return true;
}

/**
 * 更新公告
 * @param int $pk
 * @param int $adminPk
 * @param string $title
 * @param string $content
 * @param bool $isPublished
 * @return true|string
 */
function updateAnnouncement($pk, $adminPk, $title, $content, $isPublished = true)
{
    $title = trim($title);
    $content = trim($content);
    if ($title === '' || textLength($title) > 100) {
        return '公告标题不能为空且不能超过 100 字。';
    }
    if ($content === '') {
        return '公告内容不能为空。';
    }
    if (textLength($content) > 2000) {
        return '公告内容不能超过 2000 字。';
    }
    $published = $isPublished ? 1 : 0;
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `announcement` SET `title` = ?, `content` = ?, `isPublished` = ? WHERE `pk` = ?");
    $stmt->bind_param('ssii', $title, $content, $published, $pk);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();
    if ($ok) {
        logAudit($adminPk, 'announcement', $pk, 'update_announcement', $title);
    }
    $conn->close();
    return $ok ? true : '公告更新失败。';
}

/**
 * 删除公告
 * @param int $pk
 * @param int $adminPk
 * @return bool
 */
function deleteAnnouncement($pk, $adminPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("DELETE FROM `announcement` WHERE `pk` = ?");
    $stmt->bind_param('i', $pk);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    if ($ok) {
        logAudit($adminPk, 'announcement', $pk, 'delete_announcement', '');
    }
    $conn->close();
    return $ok;
}
