<?php
/**
 * Student registration, profiles, tags and verification workflow.
 */


/**
 * 注册学生用户（事务操作）
 * @param string $user 用户名
 * @param string $pwd 密码
 * @param string $stuName 姓名
 * @param int $stuGender 性别
 * @param string $birthDate 出生年月
 * @param string $college 学院
 * @param string $grade 年级
 * @param string $major 专业
 * @param string $stuNo 学号
 * @param string $phone 手机号
 * @param string $email 邮箱
 * @param string $QQ QQ号
 * @param string $avatar 头像路径
 * @param string $studentCard 学生证照片路径
 * @return true|string 成功返回true，失败返回错误信息
 */
function regStuUser($user, $pwd, $stuName, $stuGender, $birthDate, $college, $grade, $major, $stuNo, $phone, $email, $QQ, $avatar, $studentCard)
{
    $data = [
        'userName' => trim($user),
        'pwd' => $pwd,
        'name' => trim($stuName),
        'gender' => (string)$stuGender,
        'birth_date' => trim($birthDate),
        'college' => trim($college),
        'grade' => trim($grade),
        'major' => trim($major),
        'stuNo' => trim($stuNo),
        'phone' => trim($phone),
        'email' => trim($email),
        'QQ' => trim($QQ),
    ];
    $valid = validateStudentFields($data, true);
    if ($valid !== true) {
        return $valid;
    }

    $conn = dbConnect();
    $stmtUser = null;
    $stmtStu = null;
    try {
        $conn->begin_transaction();
        $hash = password_hash($pwd, PASSWORD_DEFAULT);
        $stmtUser = $conn->prepare("INSERT INTO `user` (`userName`, `password`, `userType`, `status`) VALUES (?, ?, 's', 'N')");
        $stmtUser->bind_param('ss', $data['userName'], $hash);
        $stmtUser->execute();
        $userPk = $stmtUser->insert_id;

        $stmtStu = $conn->prepare("INSERT INTO `student`
            (`pk`, `name`, `gender`, `birth_date`, `college`, `grade`, `major`, `stuNo`, `phone`, `email`, `QQ`, `avatar`, `student_card`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $gender = (int)$data['gender'];
        $stmtStu->bind_param(
            'isissssssssss',
            $userPk,
            $data['name'],
            $gender,
            $data['birth_date'],
            $data['college'],
            $data['grade'],
            $data['major'],
            $data['stuNo'],
            $data['phone'],
            $data['email'],
            $data['QQ'],
            $avatar,
            $studentCard
        );
        $stmtStu->execute();
        $conn->commit();
        return true;
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        if ((int)$e->getCode() === 1062) {
            return '用户名或学号已存在。';
        }
        return '注册失败：' . $e->getMessage();
    } finally {
        if ($stmtUser) {
            $stmtUser->close();
        }
        if ($stmtStu) {
            $stmtStu->close();
        }
        $conn->close();
    }
}


/**
 * 获取当前学生完整信息
 * @param string $userName 用户名
 * @return array|null 学生数据或null
 */
function getCurrentStudent($userName)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT u.pk, u.userName, u.status, u.createTime,
            s.name, s.gender, s.birth_date, s.college, s.grade, s.major, s.stuNo,
            s.phone, s.email, s.QQ, s.avatar, s.student_card, s.tags
        FROM `user` u
        INNER JOIN `student` s ON s.pk = u.pk
        WHERE u.userName = ? AND u.userType = 's'");
    $stmt->bind_param('s', $userName);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ?: null;
}




/**
 * 更新学生信息
 * @param int $userPk 用户ID
 * @param array $data 表单数据
 * @param string $avatar 头像路径（可选）
 * @param string $studentCard 学生证路径（可选）
 * @return true|string 成功返回true，失败返回错误信息
 */
function updateStuInfo($userPk, $data, $avatar = '', $studentCard = '')
{
    $valid = validateStudentFields([
        'name' => trim($data['name']),
        'gender' => (string)$data['gender'],
        'birth_date' => trim($data['birth_date']),
        'college' => trim($data['college']),
        'grade' => trim($data['grade']),
        'major' => trim($data['major']),
        'phone' => trim($data['phone']),
        'email' => trim($data['email']),
        'QQ' => trim($data['QQ']),
        'stuNo' => '00000000',
        'userName' => 'user123',
        'pwd' => 'abc123'
    ], false);
    if ($valid !== true) {
        return $valid;
    }

    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT s.name, s.gender, s.birth_date, s.college, s.grade, s.major, s.student_card, u.status
        FROM `student` s INNER JOIN `user` u ON u.pk = s.pk WHERE s.pk = ?");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$old) {
        $conn->close();
        return '学生资料不存在。';
    }

    $identityChanged = (
        trim($data['name']) !== $old['name']
        || (int)$data['gender'] !== (int)$old['gender']
        || trim($data['birth_date']) !== $old['birth_date']
        || trim($data['college']) !== $old['college']
        || trim($data['grade']) !== $old['grade']
        || trim($data['major']) !== $old['major']
    );
    $cardChanged = $studentCard !== '' && $studentCard !== $old['student_card'];
    $needsReview = $old['status'] === 'V' && ($identityChanged || $cardChanged);

    $fields = "`name` = ?, `gender` = ?, `birth_date` = ?, `college` = ?, `grade` = ?, `major` = ?, `phone` = ?, `email` = ?, `QQ` = ?";
    $types = 'sisssssss';
    $gender = (int)$data['gender'];
    $params = [
        trim($data['name']),
        $gender,
        trim($data['birth_date']),
        trim($data['college']),
        trim($data['grade']),
        trim($data['major']),
        trim($data['phone']),
        trim($data['email']),
        trim($data['QQ'])
    ];
    if ($avatar !== '') {
        $fields .= ", `avatar` = ?";
        $types .= 's';
        $params[] = $avatar;
    }
    if ($studentCard !== '') {
        $fields .= ", `student_card` = ?";
        $types .= 's';
        $params[] = $studentCard;
    }
    $types .= 'i';
    $params[] = (int)$userPk;

    try {
        $conn->begin_transaction();
        $stmt = $conn->prepare("UPDATE `student` SET $fields WHERE `pk` = ?");
        bindDynamicParams($stmt, $types, $params);
        $stmt->execute();
        $stmt->close();
        if ($needsReview) {
            $stmt = $conn->prepare("UPDATE `user` SET `status` = 'N' WHERE `pk` = ? AND `userType` = 's'");
            $stmt->bind_param('i', $userPk);
            $stmt->execute();
            $stmt->close();
        }
        $conn->commit();
        $conn->close();
        if ($needsReview) {
            createNotification($userPk, 'review', '你的实名资料已更新，需要管理员重新审核。', 'p_welcomeStu.php');
        }
        return ['ok' => true, 'needsReview' => $needsReview];
    } catch (mysqli_sql_exception $e) {
        $conn->rollback();
        $conn->close();
        return '资料保存失败：' . $e->getMessage();
    }
}


/**
 * 更新学生标签
 * @param int $userPk 用户ID
 * @param string $tags 标签
 * @return bool 是否成功
 */
function updateStuTags($userPk, $tags)
{
    $tags = normalizeTags($tags);
    $conn = dbConnect();
    $stmt = $conn->prepare("UPDATE `student` SET `tags` = ? WHERE `pk` = ?");
    $stmt->bind_param('si', $tags, $userPk);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    return true;
}


/**
 * 设置用户状态
 * @param int $userPk 用户ID
 * @param string $status 状态码
 * @return bool 是否成功
 */
function setUserStatus($userPk, $status, $adminPk = 0, $note = '')
{
    if (!in_array($status, ['N', 'V', 'U'], true)) {
        return false;
    }
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT `userName` FROM `user` WHERE `pk` = ? AND `userType` = 's'");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $target = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$target) {
        $conn->close();
        return false;
    }

    $stmt = $conn->prepare("UPDATE `user` SET `status` = ? WHERE `pk` = ? AND `userType` = 's'");
    $stmt->bind_param('si', $status, $userPk);
    $stmt->execute();
    $ok = $stmt->affected_rows >= 0;
    $stmt->close();

    if ($ok) {
        if ($status === 'V') {
            createNotification($userPk, 'review', '实名审核已通过，现在可以发布动态、点赞和评论了。', 'p_welcomeStu.php');
        } elseif ($status === 'N') {
            $reason = trim($note) !== '' ? '退回原因：' . trim($note) : '请检查并重新提交实名资料。';
            createNotification($userPk, 'review', '实名审核被退回。' . $reason, 'p_editStuInfo.php');
        } elseif ($status === 'U') {
            createNotification($userPk, 'account', '你的账号已被停用，如有疑问请联系管理员。', '');
        }
        if ($adminPk > 0) {
            logAudit($adminPk, 'student', $userPk, 'set_status_' . $status, trim($note));
        }
    }
    $conn->close();
    return $ok;
}


/**
 * 用户注销（设置状态为U）
 * @param string $user 用户名
 * @param string $pwd 密码
 * @return bool 是否成功
 */
function delUser($user, $pwd)
{
    $student = getCurrentStudent($user);
    if (!$student || !verifyPasswordByPk((int)$student['pk'], $pwd)) {
        return false;
    }
    return setUserStatus((int)$student['pk'], 'U');
}


function getMentionCandidates($excludePk = 0)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT u.pk, u.userName, s.name FROM `user` u INNER JOIN `student` s ON s.pk = u.pk WHERE u.userType = 's' AND u.status = 'V' AND u.pk != ? ORDER BY s.name ASC LIMIT 200");
    $stmt->bind_param('i', $excludePk);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();
    return $rows;
}

/**
 * 分页查询学生列表
 * @param string|null $status 状态筛选
 * @param bool $includeDisabled 是否包含停用
 * @param array $filters 搜索过滤条件
 * @param int $page 页码
 * @param int $perPage 每页条数
 * @return array
 */
function getStudentsPage($status = null, $includeDisabled = false, $filters = [], $page = 1, $perPage = 20)
{
    $page = max(1, (int)$page);
    $perPage = max(1, min(100, (int)$perPage));
    $offset = ($page - 1) * $perPage;
    $conn = dbConnect();
    $where = ["u.userType = 's'"];
    $types = '';
    $params = [];
    if (!$includeDisabled) {
        $where[] = "u.status != 'U'";
    }
    if ($status !== null) {
        $where[] = "u.status = ?";
        $types .= 's';
        $params[] = $status;
    }
    $keyword = trim($filters['keyword'] ?? '');
    if ($keyword !== '') {
        $where[] = "(u.userName LIKE ? OR s.name LIKE ? OR s.stuNo LIKE ? OR s.college LIKE ? OR s.major LIKE ?)";
        $like = '%' . $keyword . '%';
        $types .= 'sssss';
        array_push($params, $like, $like, $like, $like, $like);
    }
    $tag = trim($filters['tag'] ?? '');
    if ($tag !== '') {
        $where[] = "FIND_IN_SET(?, s.tags)";
        $types .= 's';
        $params[] = $tag;
    }
    $whereSql = implode(' AND ', $where);

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM `user` u INNER JOIN `student` s ON s.pk = u.pk WHERE " . $whereSql);
    if ($types !== '') {
        bindDynamicParams($stmt, $types, $params);
    }
    $stmt->execute();
    $total = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT u.pk, u.userName, u.userType, u.status, u.createTime,
            s.name, s.stuNo, s.gender, s.birth_date, s.college, s.grade, s.major,
            s.phone, s.email, s.QQ, s.avatar, s.student_card, s.tags
        FROM `user` u
        INNER JOIN `student` s ON s.pk = u.pk
        WHERE " . $whereSql . "
        ORDER BY FIELD(u.status, 'N', 'V', 'U'), u.createTime DESC
        LIMIT ?, ?");
    $types2 = $types . 'ii';
    $params2 = array_merge($params, [$offset, $perPage]);
    $stmt->bind_param($types2, ...$params2);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
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
 * 根据用户ID获取学生完整信息
 * @param int $userPk
 * @return array|null
 */
function getStudentByPk($userPk)
{
    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT u.pk, u.userName, u.status, u.createTime,
            s.name, s.gender, s.birth_date, s.college, s.grade, s.major, s.stuNo,
            s.phone, s.email, s.QQ, s.avatar, s.student_card, s.tags
        FROM `user` u
        INNER JOIN `student` s ON s.pk = u.pk
        WHERE u.pk = ? AND u.userType = 's'");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $row ?: null;
}

/**
 * 同频同学推荐：按学院、年级、共同标签计算得分
 * @param int $userPk 当前用户ID
 * @param int $limit 推荐数量
 * @return array
 */
function getRecommendedStudents($userPk, $limit = 3)
{
    $userPk = (int)$userPk;
    $limit = max(1, min(12, (int)$limit));
    $me = getStudentByPk($userPk);
    if (!$me) {
        return [];
    }
    $myTags = array_filter(array_map('trim', explode(',', $me['tags'] ?? '')));
    $myCollege = trim($me['college'] ?? '');
    $myGrade = trim($me['grade'] ?? '');

    $conn = dbConnect();
    $stmt = $conn->prepare("SELECT s.pk, s.name, s.avatar, s.college, s.grade, s.major, s.tags, u.userName
        FROM `student` s
        INNER JOIN `user` u ON u.pk = s.pk
        WHERE u.userType = 's' AND u.status = 'V' AND u.pk != ?
        ORDER BY s.pk ASC");
    $stmt->bind_param('i', $userPk);
    $stmt->execute();
    $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $conn->close();

    $scored = [];
    foreach ($candidates as $candidate) {
        $score = 0;
        if ($myCollege !== '' && $candidate['college'] === $myCollege) {
            $score += 2;
        }
        if ($myGrade !== '' && $candidate['grade'] === $myGrade) {
            $score += 1;
        }
        $candidateTags = array_filter(array_map('trim', explode(',', $candidate['tags'] ?? '')));
        $shared = array_values(array_intersect($myTags, $candidateTags));
        $score += count($shared);
        if ($score > 0) {
            $candidate['sharedTags'] = $shared;
            $candidate['score'] = $score;
            $scored[] = $candidate;
        }
    }
    shuffle($scored);
    return array_slice($scored, 0, $limit);
}
