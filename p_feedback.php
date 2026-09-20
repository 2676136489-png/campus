<?php
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

$student = requireStudentLogin();
$error = '';
$success = '';

if (isset($_POST['submitFeedback'])) {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } else {
        $image = '';
        if (!empty($_FILES['image']['name'])) {
            $image = uploadImageFile('image', 'feedback', 20 * 1024 * 1024, false);
            if (strpos($image, 'uploads/') !== 0) {
                $error = '图片上传失败：' . $image;
            }
        }
        if ($error === '') {
            $result = createFeedback((int)$student['pk'], $_POST['type'] ?? 'other', $_POST['content'] ?? '', $image);
            if ($result === true) {
                $success = '反馈已提交，管理员会尽快处理。';
            } else {
                deleteUploadedFile($image);
                $error = $result;
            }
        }
    }
}

$page = getMyFeedbackPage((int)$student['pk'], max(1, (int)($_GET['page'] ?? 1)), 10);
$types = ['bug' => '问题反馈', 'suggestion' => '建议', 'complaint' => '投诉', 'other' => '其他'];
$statusLabels = ['open' => '待处理', 'processing' => '处理中', 'closed' => '已关闭'];

renderHead('意见反馈');
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
<div class="container narrow" data-paginate="feedback">
    <div class="page-head">
        <div>
            <h1 class="page-title">意见反馈</h1>
            <p class="page-sub">遇到问题或想提建议，都可以告诉我们。</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?=icon('check')?><div><?=h($success)?></div></div>
    <?php endif; ?>

    <div class="card card-pad mb-24">
        <form method="POST" enctype="multipart/form-data">
            <?=csrfField()?>
            <div class="form-field">
                <label class="form-label" for="type">反馈类型</label>
                <select class="input input-select" name="type" id="type">
                    <?php foreach ($types as $key => $label): ?>
                        <option value="<?=h($key)?>"><?=h($label)?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label class="form-label" for="content">反馈内容</label>
                <textarea name="content" id="content" required maxlength="1000" placeholder="请描述你遇到的问题或建议"></textarea>
            </div>
            <div class="form-field">
                <label class="form-label" for="image">图片附件（可选）</label>
                <input class="input" type="file" name="image" id="image" accept="image/jpeg,image/png,image/gif" data-max-size="20971520" data-max-dim="1280">
                <div class="form-hint">可选，JPG、PNG 或 GIF，最大 20MB。</div>
            </div>
            <button type="submit" name="submitFeedback" class="btn"><?=icon('send')?>提交反馈</button>
        </form>
    </div>

    <div class="card card-pad">
        <div class="section-head">
            <h2 class="section-title">我的反馈</h2>
            <span class="badge badge-neutral">共 <?=h($page['total'])?> 条</span>
        </div>
        <?php if (empty($page['items'])): ?>
            <p class="muted">暂无反馈记录。</p>
        <?php else: ?>
            <div class="feedback-list">
                <?php foreach ($page['items'] as $item): ?>
                    <div class="feedback-item">
                        <div class="feedback-head">
                            <span class="tag"><?=h($types[$item['type']] ?? $item['type'])?></span>
                            <span class="badge <?=$item['status'] === 'closed' ? 'badge-neutral' : ($item['status'] === 'processing' ? 'badge-warning' : 'badge-info')?>"><?=h($statusLabels[$item['status']] ?? $item['status'])?></span>
                        </div>
                        <div class="feedback-content"><?=nl2br(h($item['content']))?></div>
                        <?php if (!empty($item['image']) && uploadFileExists($item['image'])): ?>
                            <img class="feedback-image js-lightbox" src="<?=h(thumbnailUrl($item['image'], 'md'))?>" alt="反馈图片" loading="lazy">
                        <?php endif; ?>
                        <div class="muted small-text"><?=h($item['createTime'])?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="list-meta list-meta-bottom">
                <?=paginationLinks('p_feedback.php', $page['page'], $page['totalPages'])?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php renderSiteFooter(); ?>