<?php
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

$student = requireVerifiedStudent();
$draft = getDynamicDraft((int)$student['pk']);
$error = '';
$success = '';

if (isset($_POST['publishDynamic'])) {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    }

    $photos = [];
    if ($error === '' && !empty($_FILES['photos']['name'][0])) {
        $photos = uploadMultipleImages('photos', 'dynamics', 20 * 1024 * 1024, 3);
        if (is_string($photos)) {
            $error = '照片上传失败：' . $photos;
            $photos = [];
        }
    }

    if ($error === '') {
        $result = publishDynamicWithPhotos((int)$student['pk'], $_POST['content'] ?? '', $_POST['tags'] ?? '', $photos);
        if ($result === true) {
            $success = '动态发布成功。';
            deleteDynamicDraft((int)$student['pk']);
            $_POST = [];
        } else {
            foreach ($photos as $path) {
                deleteUploadedFile($path);
            }
            $error = $result;
        }
    }
}

renderHead('发布动态');
renderSiteHeader([
    'nav' => [
        ['label' => '学生中心', 'href' => 'p_welcomeStu.php'],
        ['label' => '动态广场', 'href' => 'p_dynamics.php', 'active' => true],
        ['label' => '同学', 'href' => 'p_allStudents.php'],
    ],
    'actions' => renderMessageBell($student['pk']) . renderNotificationBell($student['pk']) . '<a class="btn btn-ghost btn-sm" href="p_logoutStu.php">' . icon('logout') . '退出登录</a>',
    'composer' => ($student['status'] === 'V'),
]);
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1 class="page-title">发布动态</h1>
            <p class="page-sub">记录校园里值得分享的瞬间。</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?=icon('check')?><div><?=h($success)?></div></div>
    <?php endif; ?>


    <?php if ($draft && $success === ''): ?>
        <div class="alert alert-info" data-draft-banner>
            <?=icon('document')?><div>已恢复上次未发布的草稿，编辑内容会自动保存。</div>
        </div>
    <?php endif; ?>
    <div class="draft-status" data-draft-status hidden></div>
    <div class="card card-pad">
        <form method="POST" enctype="multipart/form-data" data-draft-form>
            <?=csrfField()?>
            <div class="form-field">
                <label class="form-label" for="content">
                    动态内容
                    <span class="label-count" data-count-for="content"></span>
                </label>
                <textarea name="content" id="content" required maxlength="1000" placeholder="分享你的校园生活..."><?=h($_POST['content'] ?? ($draft['content'] ?? ''))?></textarea>
            </div>
            <div class="form-field">
                <label class="form-label" for="tags">标签</label>
                <input class="input" type="text" name="tags" id="tags" value="<?=h($_POST['tags'] ?? ($draft['tags'] ?? ''))?>" maxlength="220" placeholder="多个标签用逗号分隔">
                <div class="form-hint">最多保留 10 个标签，每个不超过 20 字。</div>
            </div>
            <div class="form-field">
                <label class="form-label" for="photos">上传照片</label>
                <div class="dropzone">
                    <span class="dropzone-thumb is-empty" id="publishPhotoThumb"><?=icon('image', 24)?><img class="thumb-img" src="" alt="动态图片预览"></span>
                    <input type="file" name="photos[]" id="photos" accept="image/jpeg,image/png,image/gif" multiple data-preview="#publishPhotoThumb" data-max-size="20971520" data-max-count="3" data-max-dim="1600" data-preview-grid="#publishPhotoGrid" data-clear-input="photos">
                    <div class="dropzone-copy">
                        <strong>选择动态照片</strong>
                        <span>可选，最多 3 张，JPG、PNG 或 GIF，单张最大 20MB</span>
                        <span class="file-meta"></span>
                        <div class="photo-preview-grid" id="publishPhotoGrid" hidden></div>
                        <button type="button" class="btn btn-ghost btn-sm clear-files" data-clear-input="photos" hidden>清除选择</button>
                    </div>
                </div>
            </div>
            <div class="flex">
                <button type="submit" name="publishDynamic" class="btn"><?=icon('send')?>发布动态</button>
                <button type="button" class="btn btn-ghost" data-clear-draft><?=icon('trash')?>清空草稿</button>
                <a class="btn btn-ghost" href="p_dynamics.php"><?=icon('arrow-left')?>返回动态广场</a>
            </div>
        </form>
    </div>
</div>
<?php renderSiteFooter(); ?>
