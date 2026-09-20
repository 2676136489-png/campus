<?php
require_once __DIR__ . '/../lib/manageDB.php';
require_once __DIR__ . '/../lib/layout.php';

requireAdminLogin();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf()) {
        $error = 'Invalid form token. Please try again.';
    } elseif (isset($_POST['addWord'])) {
        $result = addSensitiveWord($_POST['word'] ?? '');
        if ($result === true) {
            $success = '敏感词已添加。';
        } else {
            $error = $result;
        }
    } elseif (isset($_POST['deleteWord'])) {
        deleteSensitiveWord($_POST['word'] ?? '');
        $success = '敏感词已删除。';
    }
}

$words = sensitiveWords();

renderHead('敏感词管理');
renderSiteHeader([
    'home' => 'p_adminAuth.php',
    'nav' => [
        ['label' => '后台总览', 'href' => 'p_adminAuth.php'],
        ['label' => '敏感词管理', 'href' => 'p_sensitiveWords.php', 'active' => true],
        ['label' => '学生入口', 'href' => 'p_loginStu.php'],
    ],
    'actions' => '<a class="btn btn-ghost btn-sm" href="p_adminAuth.php?logout=1">' . icon('logout') . '退出</a>',
]);
?>
<div class="container narrow">
    <div class="page-head">
        <div>
            <h1 class="page-title">敏感词管理</h1>
            <p class="page-sub">命中敏感词的动态、评论、私信会被系统拦截。</p>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?=icon('alert')?><div><?=h($error)?></div></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?=icon('check')?><div><?=h($success)?></div></div>
    <?php endif; ?>

    <div class="card card-pad mb-24">
        <div class="section-head">
            <h2 class="section-title">添加敏感词</h2>
        </div>
        <form method="POST">
            <?=csrfField()?>
            <div class="form-field">
                <input class="input" type="text" name="word" required maxlength="100" placeholder="输入要拦截的词">
            </div>
            <button type="submit" name="addWord" class="btn"><?=icon('plus')?>添加</button>
        </form>
    </div>

    <div class="card card-pad">
        <div class="section-head">
            <h2 class="section-title">当前词库</h2>
            <span class="badge badge-neutral">共 <?=count($words)?> 个</span>
        </div>
        <?php if (empty($words)): ?>
            <p class="muted">暂无敏感词。</p>
        <?php else: ?>
            <div class="word-list">
                <?php foreach ($words as $word): ?>
                    <div class="word-item">
                        <span><?=h($word)?></span>
                        <form method="POST">
                            <?=csrfField()?>
                            <input type="hidden" name="word" value="<?=h($word)?>">
                            <button type="submit" name="deleteWord" class="btn btn-danger-soft btn-sm"><?=icon('trash')?>删除</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php renderSiteFooter([
    'links' => [
        ['label' => '后台总览', 'href' => 'p_adminAuth.php'],
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
    ],
]); ?>