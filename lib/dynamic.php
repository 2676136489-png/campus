<?php
/**
 * Dynamics, likes, comments and moderation queries.
 */


/**
 * 发布动态
 * @param string|int $author 作者（用户名或ID）
 * @param string $content 内容
 * @param string $tags 标签
 * @param string $photo 图片路径
 * @return true|string 成功返回true，失败返回错误信息
 */
function publishDynamic($author, $content, $tags = '', $photo = '')
{
    $userPk = is_numeric($author) ? (int)$author : getUserIdByUserName($author);
    if ($userPk <= 0) {
        return '发布者不存在。';
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `status`, `userType` FROM `user` WHERE `pk` = ?");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $stmt->bind_result($status, $userType);
    $stmt->fetch();
    $stmt->close();
    if ($userType !== 's' || $status !== 'V') {
        $conn->close();
        return '实名审核通过后才能发布动态。';
    }

    $content = trim($content);
    if ($content === '') {
        $conn->close();
        return '动态内容不能为空。';
    }
    if (textLength($content) > 1000) {
        $conn->close();
        return '动态内容不能超过 1000 字。';
    }
    $sensitive = filterSensitiveText($content);
    if ($sensitive['blocked']) {
        $conn->close();
        return '内容包含违规词：' . $sensitive['word'];
    }
    $tags = normalizeTags($tags);
    $topics = extractTopics($content);
    if (!empty($topics)) {
        $merged = array_values(array_unique(array_merge(explode(',', $tags), $topics)));
        $tags = normalizeTags(implode(',', $merged));
    }
    $limit = enforceActionRateLimit($userPk, 'publish', 5, 60);
    if ($limit !== true) {
        $conn->close();
        return $limit;
    }
    $stmt = $conn->prepare("INSERT INTO `dynamic` (`userPk`, `content`, `tags`, `photo`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $userPk, $content, $tags, $photo);
    $stmt->execute();
    $stmt->close();
    recordAction($userPk, 'publish');
    $conn->close();
    return true;
}



/**
 * 发布动态并保存多张图片
 * @param string|int $author 作者
 * @param string $content 内容
 * @param string $tags 标签
 * @param array $photos 图片路径数组（最多3张）
 * @return true|string
 */
function publishDynamicWithPhotos($author, $content, $tags = '', $photos = [])
{
    $userPk = is_numeric($author) ? (int)$author : getUserIdByUserName($author);
    if ($userPk <= 0) {
        return '发布者不存在。';
    }
    $photos = array_values(array_filter(array_map('strval', (array)$photos)));
    if (count($photos) > 3) {
        return '动态最多上传 3 张图片。';
    }
    $content = trim($content);
    if ($content === '') {
        return '动态内容不能为空。';
    }
    if (textLength($content) > 1000) {
        return '动态内容不能超过 1000 字。';
    }
    $sensitive = filterSensitiveText($content);
    if ($sensitive['blocked']) {
        return '内容包含违规词：' . $sensitive['word'];
    }
    $tags = normalizeTags($tags);
    $topics = extractTopics($content);
    if (!empty($topics)) {
        $merged = array_values(array_unique(array_merge(explode(',', $tags), $topics)));
        $tags = normalizeTags(implode(',', $merged));
    }

    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `status`, `userType` FROM `user` WHERE `pk` = ?");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $stmt->bind_result($status, $userType);
    $stmt->fetch();
    $stmt->close();
    if ($userType !== 's' || $status !== 'V') {
        $conn->close();
        return '实名审核通过后才能发布动态。';
    }

    $limit = enforceActionRateLimit($userPk, 'publish', 5, 60);
    if ($limit !== true) {
        $conn->close();
        return $limit;
    }
    $firstPhoto = $photos[0] ?? '';
    $stmt = $conn->prepare("INSERT INTO `dynamic` (`userPk`, `content`, `tags`, `photo`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $userPk, $content, $tags, $firstPhoto);
    $stmt->execute();
    $dynamicPk = $stmt->insert_id;
    $stmt->close();

    if (!empty($photos)) {
        $stmt = $conn->prepare("INSERT INTO `dynamic_photo` (`dynamicPk`, `path`, `sort`) VALUES (?, ?, ?)");
        foreach ($photos as $i => $path) {
            $sort = $i + 1;
            $stmt->bind_param('isi', $dynamicPk, $path, $sort);
            $stmt->execute();
        }
        $stmt->close();
    }
    recordAction($userPk, 'publish');
    $conn->close();
    return true;
}

/**
 * 批量获取动态图片
 * @param array $dynamicPks
 * @return array<int, string[]>
 */
function getDynamicPhotosGrouped($dynamicPks)
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $dynamicPks), function ($id) {
        return $id > 0;
    })));
    if (empty($ids)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `dynamicPk`, `path` FROM `dynamic_photo` WHERE `dynamicPk` IN ($placeholders) ORDER BY `sort` ASC, `pk` ASC");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    $grouped = [];
    foreach ($rows as $row) {
        $grouped[(int)$row['dynamicPk']][] = $row['path'];
    }
    return $grouped;
}
/**
 * 检查动态是否存在
 * @param int $dynamicPk 动态ID
 * @return bool 是否存在
 */
function dynamicExists($dynamicPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM `dynamic` WHERE `pk` = ? AND `status` = 'normal'");
    $stmt->bind_param('i', $dynamicPk);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    $conn->close();
    return (int)$count > 0;
}


/**
 * 删除动态
 * @param int $dynamicPk 动态ID
 * @param string|int $operator 操作者（用户名或ID）
 * @param bool $isAdmin 是否管理员
 * @return true|string 成功返回true，失败返回错误信息
 */
function deleteDynamic($dynamicPk, $operator, $isAdmin = false)
{
    $operatorPk = is_numeric($operator) ? (int)$operator : getUserIdByUserName($operator);
    $conn = dbConnect();
    $payload = [];
    if ($isAdmin && $operatorPk > 0) {
        $stmt = $conn->prepare("SELECT `pk`, `userPk`, `content`, `tags`, `photo`, `status`, `createTime` FROM `dynamic` WHERE `pk` = ?");
        $stmt->bind_param('i', $dynamicPk);
        $stmt->execute();
        $payload = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
    }
    if ($isAdmin) {
        $stmt = $conn->prepare("UPDATE `dynamic` SET `status` = 'deleted' WHERE `pk` = ?");
        $stmt->bind_param('i', $dynamicPk);
    } else {
        $stmt = $conn->prepare("UPDATE `dynamic` SET `status` = 'deleted' WHERE `pk` = ? AND `userPk` = ?");
        $stmt->bind_param('ii', $dynamicPk, $operatorPk);
    }
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    if ($ok && !empty($payload)) {
        storeRollbackPayload($operatorPk, 'dynamic', (int)$dynamicPk, 'delete_dynamic', '', $payload);
    }
    return $ok ? true : '动态不存在或没有删除权限。';
}


/**
 * 切换点赞状态
 * @param int $dynamicPk 动态ID
 * @param int $userPk 用户ID
 * @return true|string 成功返回true，失败返回错误信息
 */
function toggleLike($dynamicPk, $userPk)
{
    if (!dynamicExists($dynamicPk)) {
        return ['ok' => false, 'message' => '动态不存在或已删除。'];
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `userPk` FROM `dynamic` WHERE `pk` = ?");
    $stmt->bind_param('i', $dynamicPk);
    $stmt->execute();
    $authorRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $authorPk = $authorRow ? (int)$authorRow['userPk'] : 0;
    if ($authorPk === (int)$userPk) {
        $conn->close();
        return ['ok' => false, 'message' => '不能给自己的动态点赞。'];
    }
    if (isBlocked((int)$userPk, $authorPk)) {
        $conn->close();
        return ['ok' => false, 'message' => '无法与该用户互动。'];
    }

    $liked = false;
    $stmt = $conn->prepare("SELECT `pk` FROM `like` WHERE `dynamicPk` = ? AND `userPk` = ?");
    $stmt->bind_param('ii', $dynamicPk, $userPk);
    $stmt->execute();
    $like = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($like) {
        $stmt = $conn->prepare("DELETE FROM `like` WHERE `pk` = ?");
        $stmt->bind_param('i', $like['pk']);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO `like` (`dynamicPk`, `userPk`) VALUES (?, ?)");
        $stmt->bind_param('ii', $dynamicPk, $userPk);
        $stmt->execute();
        $stmt->close();
        $liked = true;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `like` WHERE `dynamicPk` = ?");
    $stmt->bind_param('i', $dynamicPk);
    $stmt->execute();
    $likeCount = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    if ($liked) {
        $stmt = $conn->prepare("SELECT d.userPk, u.userName, s.name
            FROM `dynamic` d
            INNER JOIN `user` u ON u.pk = d.userPk
            LEFT JOIN `student` s ON s.pk = u.pk
            WHERE d.pk = ?");
        $stmt->bind_param('i', $dynamicPk);
        $stmt->execute();
        $author = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($author && (int)$author['userPk'] !== (int)$userPk) {
            $authorName = $author['name'] ?: $author['userName'];
            createNotification((int)$author['userPk'], 'like', $authorName . ' 点赞了你的动态。', 'p_dynamics.php?focus=' . (int)$dynamicPk);
        }
    }
    $conn->close();
    return ['ok' => true, 'liked' => $liked, 'likeCount' => $likeCount];
}


/**
 * 添加评论
 * @param int $dynamicPk 动态ID
 * @param int $userPk 用户ID
 * @param string $content 评论内容
 * @return true|string 成功返回true，失败返回错误信息
 */
function addComment($dynamicPk, $userPk, $content, $parentPk = 0)
{
    if (!dynamicExists($dynamicPk)) {
        return ['ok' => false, 'message' => '动态不存在或已删除。'];
    }
    $content = trim($content);
    if ($content === '') {
        return ['ok' => false, 'message' => '评论内容不能为空。'];
    }
    if (textLength($content) > 300) {
        return ['ok' => false, 'message' => '评论不能超过 300 字。'];
    }
    $sensitive = filterSensitiveText($content);
    if ($sensitive['blocked']) {
        return ['ok' => false, 'message' => '评论包含违规词：' . $sensitive['word']];
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `userPk` FROM `dynamic` WHERE `pk` = ?");
    $stmt->bind_param('i', $dynamicPk);
    $stmt->execute();
    $authorRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $authorPk = $authorRow ? (int)$authorRow['userPk'] : 0;
    if ($authorPk > 0 && isBlocked((int)$userPk, $authorPk)) {
        $conn->close();
        return ['ok' => false, 'message' => '无法与该用户互动。'];
    }
    $parentPk = (int)$parentPk;
    if ($parentPk > 0) {
        $stmt = $conn->prepare("SELECT `pk` FROM `comment` WHERE `pk` = ? AND `dynamicPk` = ? LIMIT 1");
        $stmt->bind_param('ii', $parentPk, $dynamicPk);
        $stmt->execute();
        $parentExists = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$parentExists) {
            $conn->close();
            return ['ok' => false, 'message' => '回复的评论不存在。'];
        }
    }
    $limit = enforceActionRateLimit((int)$userPk, 'comment', 10, 60);
    if ($limit !== true) {
        $conn->close();
        return ['ok' => false, 'message' => $limit];
    }

    $stmt = $conn->prepare("INSERT INTO `comment` (`dynamicPk`, `userPk`, `content`, `parentPk`) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iisi', $dynamicPk, $userPk, $content, $parentPk);
    $stmt->execute();
    $commentPk = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("SELECT c.pk, c.content, c.createTime, c.parentPk, u.userName, s.name,
            p.content AS parentContent, p.userPk AS parentUserPk, pu.userName AS parentUserName, ps.name AS parentName
        FROM `comment` c
        INNER JOIN `user` u ON u.pk = c.userPk
        LEFT JOIN `student` s ON s.pk = u.pk
        LEFT JOIN `comment` p ON p.pk = c.parentPk
        LEFT JOIN `user` pu ON pu.pk = p.userPk
        LEFT JOIN `student` ps ON ps.pk = p.userPk
        WHERE c.pk = ?");
    $stmt->bind_param('i', $commentPk);
    $stmt->execute();
    $comment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $preview = function_exists('mb_substr') ? mb_substr($comment['content'], 0, 50, 'UTF-8') : substr($comment['content'], 0, 50);
    if ($authorPk !== (int)$userPk) {
        $authorName = $comment['name'] ?: $comment['userName'];
        createNotification($authorPk, 'comment', $authorName . ' 评论了你的动态：' . $preview, 'p_dynamics.php?focus=' . (int)$dynamicPk);
    }
    if ($parentPk > 0 && $comment && isset($comment['parentUserPk']) && (int)$comment['parentUserPk'] !== (int)$userPk) {
        $replyName = $comment['name'] ?: $comment['userName'];
        createNotification((int)$comment['parentUserPk'], 'comment', $replyName . ' 回复了你的评论：' . $preview, 'p_dynamics.php?focus=' . (int)$dynamicPk);
    }
    recordAction((int)$userPk, 'comment');
    $conn->close();
    return ['ok' => true, 'comment' => $comment];
}



/**
 * 根据动态ID获取评论
 * @param int $dynamicPk 动态ID
 * @return array 评论列表
 */
function getCommentsByDynamic($dynamicPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT c.pk, c.userPk AS commentUserPk, c.content, c.createTime, c.parentPk, u.userName, s.name,
            p.content AS parentContent, p.userPk AS parentUserPk, pu.userName AS parentUserName, ps.name AS parentName
        FROM `comment` c
        INNER JOIN `user` u ON u.pk = c.userPk
        LEFT JOIN `student` s ON s.pk = u.pk
        LEFT JOIN `comment` p ON p.pk = c.parentPk
        LEFT JOIN `user` pu ON pu.pk = p.userPk
        LEFT JOIN `student` ps ON ps.pk = p.userPk
        WHERE c.dynamicPk = ?
        ORDER BY c.createTime ASC");
    $stmt->bind_param('i', $dynamicPk);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $data;
}


/**
 * 根据多个动态ID批量获取评论
 * @param array $dynamicPks 动态ID数组
 * @return array 评论列表（按动态ID分组）
 */
function getCommentsByDynamicIds($dynamicPks)
{
    $ids = array_values(array_unique(array_filter(array_map('intval', $dynamicPks), function ($id) {
        return $id > 0;
    })));
    if (empty($ids)) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT c.pk, c.dynamicPk, c.userPk AS commentUserPk, c.content, c.createTime, c.parentPk, u.userName, s.name,
            p.content AS parentContent, p.userPk AS parentUserPk, pu.userName AS parentUserName, ps.name AS parentName
        FROM `comment` c
        INNER JOIN `user` u ON u.pk = c.userPk
        LEFT JOIN `student` s ON s.pk = u.pk
        LEFT JOIN `comment` p ON p.pk = c.parentPk
        LEFT JOIN `user` pu ON pu.pk = p.userPk
        LEFT JOIN `student` ps ON ps.pk = p.userPk
        WHERE c.dynamicPk IN ($placeholders)
        ORDER BY c.dynamicPk ASC, c.createTime ASC");
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    $grouped = [];
    foreach ($rows as $row) {
        $grouped[(int)$row['dynamicPk']][] = $row;
    }
    return $grouped;
}


/**
 * 删除评论
 * 允许：管理员、评论作者、被评论动态的发布者
 * @param int $commentPk 评论ID
 * @param int $operatorPk 操作者ID（管理员时为管理员ID）
 * @param bool $isAdmin 是否为管理员
 * @return array
 */
function deleteComment($commentPk, $operatorPk, $isAdmin = false)
{
    $commentPk = (int)$commentPk;
    $operatorPk = (int)$operatorPk;
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT c.pk, c.userPk, d.userPk AS dynamicOwnerPk
        FROM `comment` c
        INNER JOIN `dynamic` d ON d.pk = c.dynamicPk
        WHERE c.pk = ?");
    $stmt->bind_param('i', $commentPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        $conn->close();
        return ['ok' => false, 'message' => '评论不存在。'];
    }
    $canDelete = $isAdmin
        || (int)$row['userPk'] === $operatorPk
        || (int)$row['dynamicOwnerPk'] === $operatorPk;
    if (!$canDelete) {
        $conn->close();
        return ['ok' => false, 'message' => '没有删除该评论的权限。'];
    }

    $payload = [];
    if ($isAdmin && $operatorPk > 0) {
        $stmt = $conn->prepare("SELECT `pk`, `dynamicPk`, `userPk`, `content`, `parentPk`, `createTime` FROM `comment` WHERE `pk` = ?");
        $stmt->bind_param('i', $commentPk);
        $stmt->execute();
        $payload = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
    }
    $stmt = $conn->prepare("DELETE FROM `comment` WHERE `pk` = ?");
    $stmt->bind_param('i', $commentPk);
    $stmt->execute();
    $stmt->close();
    if (!empty($payload)) {
        storeRollbackPayload($operatorPk, 'comment', $commentPk, 'delete_comment', '', $payload);
    }
    $conn->close();
    return ['ok' => true, 'commentPk' => $commentPk];
}


/**
 * 分页查询评论（管理员后台）
 * @param array $filters 搜索过滤条件
 * @param int $page 页码
 * @param int $perPage 每页条数
 * @return array
 */
function getCommentsPage($filters = [], $page = 1, $perPage = 20)
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
        $where[] = "(c.content LIKE ? OR u.userName LIKE ? OR s.name LIKE ? OR d.content LIKE ?)";
        $like = '%' . $keyword . '%';
        $types .= 'ssss';
        array_push($params, $like, $like, $like, $like);
    }
    $whereSql = implode(' AND ', $where);

    $stmt = $conn->prepare("SELECT COUNT(*) AS total
        FROM `comment` c
        INNER JOIN `user` u ON u.pk = c.userPk
        LEFT JOIN `student` s ON s.pk = u.pk
        INNER JOIN `dynamic` d ON d.pk = c.dynamicPk
        WHERE " . $whereSql);
    if ($types !== '') {
        bindDynamicParams($stmt, $types, $params);
    }
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT c.pk, c.dynamicPk, c.content, c.createTime, c.userPk AS commentUserPk,
            u.userName AS commentUserName, s.name AS commentName,
            d.userPk AS dynamicOwnerPk, du.userName AS dynamicOwnerName,
            d.photo AS dynamicPhoto
        FROM `comment` c
        INNER JOIN `user` u ON u.pk = c.userPk
        LEFT JOIN `student` s ON s.pk = u.pk
        INNER JOIN `dynamic` d ON d.pk = c.dynamicPk
        INNER JOIN `user` du ON du.pk = d.userPk
        WHERE " . $whereSql . "
        ORDER BY c.createTime DESC
        LIMIT ?, ?");
    $types2 = $types . 'ii';
    $params2 = array_merge($params, [$offset, $perPage]);
    if ($types2 !== '') {
        $stmt->bind_param($types2, ...$params2);
    }
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (!empty($data)) {
        $photoMap = getDynamicPhotosGrouped(array_column($data, 'dynamicPk'));
        foreach ($data as &$item) {
            $photos = $photoMap[(int)$item['dynamicPk']] ?? [];
            if (empty($photos) && ($item['dynamicPhoto'] ?? '') !== '') {
                $photos = [$item['dynamicPhoto']];
            }
            $item['photos'] = $photos;
        }
        unset($item);
    }
    $conn->close();
    return [
        'items' => $data,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => max(1, (int)ceil($total / $perPage))
    ];
}


/**
 * 分页查询动态列表
 * @param int $viewerPk 查看者ID
 * @param bool $includeDeleted 是否包含已删除
 * @param array $filters 搜索过滤条件
 * @param int $page 页码
 * @param int $perPage 每页条数
 * @return array
 */
function getDynamicsPage($viewerPk = 0, $includeDeleted = false, $filters = [], $page = 1, $perPage = 20)
{
    $page = max(1, (int)$page);
    $perPage = max(1, min(100, (int)$perPage));
    $offset = ($page - 1) * $perPage;
    $conn = dbConnect();
    $where = $includeDeleted ? ['1=1'] : ["d.status = 'normal' AND u.status != 'U'"];
    $types = '';
    $params = [];
    $keyword = trim($filters['keyword'] ?? '');
    if ($keyword !== '') {
        $where[] = "(d.content LIKE ? OR u.userName LIKE ? OR s.name LIKE ?)";
        $like = '%' . $keyword . '%';
        $types .= 'sss';
        array_push($params, $like, $like, $like);
    }
    $tag = trim($filters['tag'] ?? '');
    if ($tag !== '') {
        $where[] = "FIND_IN_SET(?, d.tags)";
        $types .= 's';
        $params[] = $tag;
    }
    $college = trim($filters['college'] ?? '');
    if ($college !== '') {
        $where[] = "s.college LIKE ?";
        $types .= 's';
        $params[] = '%' . $college . '%';
    }
    $authorPk = (int)($filters['authorPk'] ?? 0);
    if ($authorPk > 0) {
        $where[] = "d.userPk = ?";
        $types .= 'i';
        $params[] = $authorPk;
    }
    $favoriteUserPk = (int)($filters['favoriteUserPk'] ?? 0);
    if ($favoriteUserPk > 0) {
        $where[] = "EXISTS(SELECT 1 FROM `dynamic_favorite` fv WHERE fv.dynamicPk = d.pk AND fv.userPk = ?)";
        $types .= 'i';
        $params[] = $favoriteUserPk;
    }
    $whereSql = implode(' AND ', $where);

    $stmt = $conn->prepare("SELECT COUNT(*) AS total
        FROM `dynamic` d
        INNER JOIN `user` u ON u.pk = d.userPk
        LEFT JOIN `student` s ON s.pk = u.pk
        WHERE " . $whereSql);
    if ($types !== '') {
        bindDynamicParams($stmt, $types, $params);
    }
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $sql = "SELECT d.pk, d.userPk, d.content, d.tags, d.photo, d.createTime, d.status,
            u.userName, u.status AS userStatus,
            s.name, s.avatar, s.college, s.major,
            (SELECT COUNT(*) FROM `like` l WHERE l.dynamicPk = d.pk) AS like_count,
            (SELECT COUNT(*) FROM `comment` c WHERE c.dynamicPk = d.pk) AS comment_count,
            EXISTS(SELECT 1 FROM `like` l2 WHERE l2.dynamicPk = d.pk AND l2.userPk = ?) AS liked,
            (SELECT COUNT(*) FROM `dynamic_favorite` fv WHERE fv.dynamicPk = d.pk) AS favorite_count,
            EXISTS(SELECT 1 FROM `dynamic_favorite` fv2 WHERE fv2.dynamicPk = d.pk AND fv2.userPk = ?) AS favorited
        FROM `dynamic` d
        INNER JOIN `user` u ON u.pk = d.userPk
        LEFT JOIN `student` s ON s.pk = u.pk
        WHERE " . $whereSql . "
        ORDER BY d.createTime DESC
        LIMIT ?, ?";
    $types2 = 'ii' . $types . 'ii';
    $params2 = array_merge([(int)$viewerPk, (int)$viewerPk], $params, [$offset, $perPage]);
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types2, ...$params2);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    if (!empty($data)) {
        $photoMap = getDynamicPhotosGrouped(array_column($data, 'pk'));
        foreach ($data as &$item) {
            $photos = $photoMap[(int)$item['pk']] ?? [];
            if (empty($photos) && $item['photo'] !== '') {
                $photos = [$item['photo']];
            }
            $item['photos'] = $photos;
        }
        unset($item);
    }
    $conn->close();
    return [
        'items' => $data,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => max(1, (int)ceil($total / $perPage))
    ];
}


/**
 * 获取单条动态详情（含点赞状态与图片）
 * @param int $dynamicPk 动态ID
 * @param int $viewerPk 当前查看者ID
 * @return array|null
 */
function getDynamicByPk($dynamicPk, $viewerPk = 0)
{
    $dynamicPk = (int)$dynamicPk;
    $viewerPk = (int)$viewerPk;
    $conn = dbConnect();
    $sql = "SELECT d.pk, d.userPk, d.content, d.tags, d.photo, d.createTime, d.status,
            u.userName, u.status AS userStatus,
            s.name, s.avatar, s.college, s.major,
            (SELECT COUNT(*) FROM `like` l WHERE l.dynamicPk = d.pk) AS like_count,
            (SELECT COUNT(*) FROM `comment` c WHERE c.dynamicPk = d.pk) AS comment_count,
            EXISTS(SELECT 1 FROM `like` l2 WHERE l2.dynamicPk = d.pk AND l2.userPk = ?) AS liked,
            (SELECT COUNT(*) FROM `dynamic_favorite` fv WHERE fv.dynamicPk = d.pk) AS favorite_count,
            EXISTS(SELECT 1 FROM `dynamic_favorite` fv2 WHERE fv2.dynamicPk = d.pk AND fv2.userPk = ?) AS favorited
        FROM `dynamic` d
        INNER JOIN `user` u ON u.pk = d.userPk
        LEFT JOIN `student` s ON s.pk = u.pk
        WHERE d.pk = ? AND d.status = 'normal' AND u.status != 'U'
        LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iii', $viewerPk, $viewerPk, $dynamicPk);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$item) {
        $conn->close();
        return null;
    }
    $photoMap = getDynamicPhotosGrouped([$item['pk']]);
    $photos = $photoMap[(int)$item['pk']] ?? [];
    if (empty($photos) && $item['photo'] !== '') {
        $photos = [$item['photo']];
    }
    $item['photos'] = $photos;
    $conn->close();
    return $item;
}
/**
 * 编辑自己的动态内容与标签
 * @param int $dynamicPk 动态ID
 * @param int $operatorPk 操作者ID
 * @param string $content 新内容
 * @param string $tags 新标签
 * @return true|string
 */
function updateDynamic($dynamicPk, $operatorPk, $content, $tags = '')
{
    $dynamicPk = (int)$dynamicPk;
    $operatorPk = (int)$operatorPk;
    $content = trim($content);
    if ($content === '') {
        return '动态内容不能为空。';
    }
    if (textLength($content) > 1000) {
        return '动态内容不能超过 1000 字。';
    }
    $sensitive = filterSensitiveText($content);
    if ($sensitive['blocked']) {
        return '内容包含违规词：' . $sensitive['word'];
    }
    $tags = normalizeTags($tags);
    $topics = extractTopics($content);
    if (!empty($topics)) {
        $merged = array_values(array_unique(array_merge(explode(',', $tags), $topics)));
        $tags = normalizeTags(implode(',', $merged));
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `dynamic` SET `content` = ?, `tags` = ? WHERE `pk` = ? AND `userPk` = ? AND `status` = 'normal'");
    $stmt->bind_param('ssii', $content, $tags, $dynamicPk, $operatorPk);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();
    $conn->close();
    return $ok ? true : '动态不存在或没有编辑权限。';
}

/**
 * 切换收藏状态
 * @param int $dynamicPk 动态ID
 * @param int $userPk 用户ID
 * @return array
 */
function toggleFavorite($dynamicPk, $userPk)
{
    if (!dynamicExists($dynamicPk)) {
        return ['ok' => false, 'message' => '动态不存在或已删除。'];
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `pk` FROM `dynamic_favorite` WHERE `dynamicPk` = ? AND `userPk` = ?");
    $stmt->bind_param('ii', $dynamicPk, $userPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $favorited = false;
    if ($row) {
        $stmt = $conn->prepare("DELETE FROM `dynamic_favorite` WHERE `pk` = ?");
        $stmt->bind_param('i', $row['pk']);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO `dynamic_favorite` (`dynamicPk`, `userPk`) VALUES (?, ?)");
        $stmt->bind_param('ii', $dynamicPk, $userPk);
        $stmt->execute();
        $stmt->close();
        $favorited = true;
    }
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `dynamic_favorite` WHERE `dynamicPk` = ?");
    $stmt->bind_param('i', $dynamicPk);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $conn->close();
    return ['ok' => true, 'favorited' => $favorited, 'favoriteCount' => $count];
}

/**
 * 是否已收藏
 * @param int $dynamicPk 动态ID
 * @param int $userPk 用户ID
 * @return bool
 */
function isFavorited($dynamicPk, $userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `pk` FROM `dynamic_favorite` WHERE `dynamicPk` = ? AND `userPk` = ? LIMIT 1");
    $stmt->bind_param('ii', $dynamicPk, $userPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return (bool)$row;
}

/**
 * 收藏数量
 * @param int $dynamicPk 动态ID
 * @return int
 */
function getFavoriteCount($dynamicPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `dynamic_favorite` WHERE `dynamicPk` = ?");
    $stmt->bind_param('i', $dynamicPk);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $conn->close();
    return $count;
}

/**
 * 保存动态草稿，内容为空时清除草稿
 * @param int $userPk 用户ID
 * @param string $content 内容
 * @param string $tags 标签
 * @return bool
 */
function saveDynamicDraft($userPk, $content, $tags = '')
{
    $userPk = (int)$userPk;
    $content = trim((string)$content);
    $tags = normalizeTags($tags);
    if ($content === '' && $tags === '') {
        return deleteDynamicDraft($userPk);
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("INSERT INTO `dynamic_draft` (`userPk`, `content`, `tags`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `content` = VALUES(`content`), `tags` = VALUES(`tags`)");
    $stmt->bind_param('iss', $userPk, $content, $tags);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}

/**
 * 获取动态草稿
 * @param int $userPk 用户ID
 * @return array|null
 */
function getDynamicDraft($userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `pk`, `content`, `tags`, `updateTime` FROM `dynamic_draft` WHERE `userPk` = ? LIMIT 1");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ?: null;
}

/**
 * 清除动态草稿
 * @param int $userPk 用户ID
 * @return bool
 */
function deleteDynamicDraft($userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("DELETE FROM `dynamic_draft` WHERE `userPk` = ?");
    $stmt->bind_param('i', $userPk);
    $ok = $stmt->execute();
    $stmt->close();
    $conn->close();
    return $ok;
}
