<?php
// 安全配置：防止Session劫持
require_once __DIR__ . '/lib/bootstrap.php';
secureSessionStart();
sendSecurityHeaders();
require_once __DIR__ . '/p_manageDB.php';

// 验证请求方式和CSRF令牌
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verifyCsrf()) {
    $_SESSION['reg_error'] = 'Invalid form token. Please try again.';
    header('Location: ' . tabUrl('p_registerStu.php'));
    exit;
}

if (!verifyCaptcha($_POST['captcha'] ?? '')) {
    $_SESSION['reg_error'] = '验证码错误，请重试。';
    header('Location: ' . tabUrl('p_registerStu.php'));
    exit;
}

// 同一IP注册过于频繁时限制
if (isLoginRateLimited('register', clientIp())) {
    $_SESSION['reg_error'] = '注册尝试次数过多，请 15 分钟后再试。';
    header('Location: ' . tabUrl('p_registerStu.php'));
    exit;
}

// 获取表单字段数据
$fields = ['userName', 'pwd', 'name', 'gender', 'birth_date', 'college', 'grade', 'major', 'stuNo', 'phone', 'email', 'QQ'];
$data = [];
foreach ($fields as $field) {
    $data[$field] = isset($_POST[$field]) ? trim($_POST[$field]) : '';
}

// 上传头像（最大2MB）
$valid = validateStudentFields($data, true);
if ($valid !== true) {
    $_SESSION['reg_error'] = $valid;
    header('Location: ' . tabUrl('p_registerStu.php'));
    exit;
}

$avatar = uploadImageFile('avatar', '', 20 * 1024 * 1024, true);
if (strpos($avatar, 'uploads/') !== 0) {
    $_SESSION['reg_error'] = '头像上传失败：' . $avatar;
    header('Location: ' . tabUrl('p_registerStu.php'));
    exit;
}

// 上传学生证照片（最大20MB）
$studentCard = uploadImageFile('student_card', '', 20 * 1024 * 1024, true);
if (strpos($studentCard, 'uploads/') !== 0) {
    // 学生证上传失败，删除已上传的头像
    deleteUploadedFile($avatar);
    $_SESSION['reg_error'] = '学生证照片上传失败：' . $studentCard;
    header('Location: ' . tabUrl('p_registerStu.php'));
    exit;
}

// 调用注册函数（事务操作：同时插入user和student表）
$result = regStuUser(
    $data['userName'],
    $data['pwd'],
    $data['name'],
    $data['gender'],
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

if ($result === true) {
    $newUser = getUserByUserName($data['userName']);
    $newPk = $newUser ? (int)$newUser['pk'] : 0;
    $previousUser = $_SESSION['userName'] ?? '';
    if ($previousUser !== '' && $previousUser !== $data['userName']) {
        $rotatedData = $_SESSION;
        $rotatedData['userName'] = $data['userName'];
        if ($newPk > 0) {
            $rotatedData['auth_token'] = createActiveSessionToken($newPk);
        }
        $newTab = rotateTabSession($rotatedData);
        header('Location: p_welcomeStu.php?tab=' . rawurlencode($newTab) . '&rotate=1');
        exit;
    }
    $_SESSION['userName'] = $data['userName'];
    if ($newPk > 0) {
        issueActiveSessionToken($newPk);
    }
    header('Location: ' . tabUrl('p_welcomeStu.php'));
    exit;
}

// 注册失败：删除已上传的文件，保存错误信息并记录一次失败尝试
deleteUploadedFile($avatar);
deleteUploadedFile($studentCard);
recordLoginAttempt('register', clientIp(), false);
$_SESSION['reg_error'] = $result;
header('Location: ' . tabUrl('p_registerStu.php'));
exit;
?>
