<?php
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

$student = requireStudentLogin();
$error = '';
$success = '';

if (isset($_POST['editStuInfo'])) {
    $pwd = isset($_POST['pwd']) ? trim($_POST['pwd']) : '';

    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } elseif ($pwd === '' || !verifyPasswordByPk((int)$student['pk'], $pwd)) {
        $error = '密码验证失败，无法保存修改。';
    } else {
        $fieldError = validateStudentFields([
            'name' => trim($_POST['name'] ?? ''),
            'gender' => (string)($_POST['gender'] ?? ''),
            'birth_date' => trim($_POST['birth_date'] ?? ''),
            'college' => trim($_POST['college'] ?? ''),
            'grade' => trim($_POST['grade'] ?? ''),
            'major' => trim($_POST['major'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'QQ' => trim($_POST['QQ'] ?? ''),
            'stuNo' => '00000000',
            'userName' => 'user123',
            'pwd' => 'abc123'
        ], false);
        if ($fieldError !== true) {
            $error = $fieldError;
        }
        $newUserName = trim($_POST['userName'] ?? '');
        if ($error === '' && $newUserName !== $student['userName']) {
            if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $newUserName)) {
                $error = '用户名需为 3-30 位字母、数字或下划线。';
            } elseif (!isUserNameAvailable($newUserName, (int)$student['pk'])) {
                $error = '该用户名已被占用。';
            } else {
                $nameResult = updateStuUserName((int)$student['pk'], $newUserName);
                if ($nameResult !== true) {
                    $error = $nameResult;
                } else {
                    $_SESSION['userName'] = $newUserName;
                    $student = getCurrentStudent($newUserName);
                }
            }
        }

        $avatar = '';
        $studentCard = '';

        if ($error === '' && isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            $avatar = uploadImageFile('avatar', '', 20 * 1024 * 1024, false);
            if (strpos($avatar, 'uploads/') !== 0) {
                $error = '头像上传失败：' . $avatar;
            }
        }

        if ($error === '' && isset($_FILES['student_card']) && $_FILES['student_card']['error'] !== UPLOAD_ERR_NO_FILE) {
            $studentCard = uploadImageFile('student_card', '', 20 * 1024 * 1024, false);
            if (strpos($studentCard, 'uploads/') !== 0) {
                deleteUploadedFile($avatar);
                $error = '学生证照片上传失败：' . $studentCard;
            }
        }

        if ($error === '') {
            $result = updateStuInfo((int)$student['pk'], $_POST, $avatar, $studentCard);
            if (is_array($result) && !empty($result['ok'])) {
                if ($avatar !== '' && $student['avatar'] !== '') {
                    deleteUploadedFile($student['avatar']);
                }
                if ($studentCard !== '' && $student['student_card'] !== '') {
                    deleteUploadedFile($student['student_card']);
                }
                $success = !empty($result['needsReview'])
                    ? '资料已保存，实名信息需要管理员重新审核。'
                    : '个人资料已保存。';
                $student = getCurrentStudent($student['userName']);
            } else {
                deleteUploadedFile($avatar);
                deleteUploadedFile($studentCard);
                $error = $result;
            }
        }
    }
}

renderHead('修改个人资料');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => 'p_welcomeStu.php'],
        ['label' => '动态广场', 'href' => 'p_dynamics.php'],
        ['label' => '同学', 'href' => 'p_allStudents.php'],
    ],
    'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
    'composer' => ($student['status'] === 'V'),
]);
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1 class="page-title">修改个人资料</h1>
            <p class="page-sub">学号不可修改，其他资料保存前需要输入当前密码确认。</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?=icon('check')?><div><?=h($success)?></div></div>
    <?php endif; ?>

    <div class="card card-pad">
        <form method="POST" enctype="multipart/form-data">
            <?=csrfField()?>
            <div class="form-field">
                <label class="form-label" for="userName">账户名称</label>
                <input class="input" type="text" name="userName" id="userName" value="<?=h($student['userName'])?>" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_]+" autocomplete="username">
                <div class="form-hint">3-30 位字母、数字或下划线，修改后下次使用新名称登录。</div>
            </div>
            <div class="grid-2">
                <div class="form-field">
                    <label class="form-label" for="name">姓名</label>
                    <input class="input" type="text" name="name" id="name" value="<?=h($student['name'])?>" required maxlength="50">
                </div>
                <div class="form-field">
                    <span class="form-label">性别</span>
                    <div class="radio-pills">
                        <label class="radio-pill"><input type="radio" name="gender" value="0" <?=$student['gender'] == 0 ? 'checked' : ''?>><span>男</span></label>
                        <label class="radio-pill"><input type="radio" name="gender" value="1" <?=$student['gender'] == 1 ? 'checked' : ''?>><span>女</span></label>
                    </div>
                </div>
                <div class="form-field">
                    <label class="form-label" for="birth_date">出生年月</label>
                    <input class="input" type="date" name="birth_date" id="birth_date" value="<?=h($student['birth_date'])?>" required>
                </div>
                <div class="form-field">
                    <label class="form-label" for="stuNo">学号</label>
                    <input class="input" type="text" id="stuNo" value="<?=h($student['stuNo'])?>" disabled>
                    <div class="form-hint">学号用于实名认证，不支持修改。</div>
                </div>
                <div class="form-field">
                    <label class="form-label" for="college">学院</label>
                    <input class="input" type="text" name="college" id="college" value="<?=h($student['college'])?>" required maxlength="100">
                </div>
                <div class="form-field">
                    <label class="form-label" for="grade">年级</label>
                    <input class="input" type="text" name="grade" id="grade" value="<?=h($student['grade'])?>" required maxlength="20">
                </div>
                <div class="form-field">
                    <label class="form-label" for="major">专业</label>
                    <input class="input" type="text" name="major" id="major" value="<?=h($student['major'])?>" required maxlength="100">
                </div>
                <div class="form-field">
                    <label class="form-label" for="phone">手机号</label>
                    <input class="input" type="tel" name="phone" id="phone" value="<?=h($student['phone'])?>" required maxlength="13" pattern="[0-9]+">
                </div>
                <div class="form-field">
                    <label class="form-label" for="email">Email</label>
                    <input class="input" type="email" name="email" id="email" value="<?=h($student['email'])?>" required maxlength="100">
                </div>
                <div class="form-field">
                    <label class="form-label" for="QQ">QQ</label>
                    <input class="input" type="text" name="QQ" id="QQ" value="<?=h($student['QQ'])?>" required maxlength="20" pattern="[0-9]+">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-field">
                    <label class="form-label" for="avatar">更换头像</label>
                    <div class="dropzone">
                        <span class="dropzone-thumb <?=empty($student['avatar']) ? 'is-empty' : ''?>" id="avatarThumb">
                            <?=icon('user', 24)?>
                            <img class="thumb-img" src="<?=$student['avatar'] ? h(assetUrl($student['avatar'])) : ''?>" alt="头像预览">
                        </span>
                        <input type="file" name="avatar" id="avatar" accept="image/jpeg,image/png,image/gif" data-preview="#avatarThumb" data-max-size="20971520" data-max-dim="1280" data-clear-input="avatar">
                        <div class="dropzone-copy">
                            <strong>选择新头像</strong>
                            <span>JPG、PNG 或 GIF，最大 20MB，留空则保持不变</span>
                            <span class="file-meta"></span>
                            <button type="button" class="btn btn-ghost btn-sm clear-files" data-clear-input="avatar" hidden>清除选择</button>
                        </div>
                    </div>
                </div>
                <div class="form-field">
                    <label class="form-label" for="student_card">更换学生证照片</label>
                    <div class="dropzone">
                        <span class="dropzone-thumb <?=empty($student['student_card']) ? 'is-empty' : ''?>" id="cardThumb">
                            <?=icon('id', 24)?>
                            <img class="thumb-img" src="<?=$student['student_card'] ? h(assetUrl($student['student_card'])) : ''?>" alt="学生证照片预览">
                        </span>
                        <input type="file" name="student_card" id="student_card" accept="image/jpeg,image/png,image/gif" data-preview="#cardThumb" data-max-size="20971520" data-max-dim="1280" data-clear-input="student_card">
                        <div class="dropzone-copy">
                            <strong>选择新学生证照片</strong>
                            <span>JPG、PNG 或 GIF，最大 20MB，留空则保持不变</span>
                            <span class="file-meta"></span>
                            <button type="button" class="btn btn-ghost btn-sm clear-files" data-clear-input="student_card" hidden>清除选择</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-field">
                <label class="form-label" for="pwd">输入当前密码确认修改</label>
                <div class="password-wrap">
                    <input class="input" type="password" name="pwd" id="pwd" required autocomplete="current-password">
                    <button class="js-toggle-pwd toggle-pwd" type="button" data-target="pwd" aria-label="显示密码"><?=icon('eye')?></button>
                </div>
            </div>

            <div class="flex">
                <button type="submit" name="editStuInfo" class="btn"><?=icon('check')?>保存修改</button>
                <a class="btn btn-ghost" href="p_welcomeStu.php"><?=icon('arrow-left')?>返回学生中心</a>
            </div>
        </form>
    </div>
</div>
<?php renderSiteFooter(); ?>
