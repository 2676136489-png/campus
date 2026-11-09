<?php
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

requireAdminLogin();
$date = isset($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : date('Y-m-d');
$report = getDailyOperationsReport($date);
$labels = dailyReportLabels();
$highlight = ['newRegistrations', 'verifiedStudents', 'newDynamics', 'newMessages', 'activeStudents', 'pendingReports'];

renderHead('运营数据日报');
renderSiteHeader([
    'home' => 'p_adminAuth.php',
    'nav' => [
        ['label' => '后台总览', 'href' => 'p_adminAuth.php'],
        ['label' => '运营日报', 'href' => 'p_dailyReport.php', 'active' => true],
        ['label' => '公告管理', 'href' => 'p_announcements.php'],
        ['label' => '学生入口', 'href' => 'p_loginStu.php'],
    ],
    'actions' => '<a class="btn btn-ghost btn-sm" href="p_adminAuth.php?logout=1">' . icon('logout') . '退出</a>',
]);
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">运营数据日报</h1>
            <p class="page-sub">按天查看注册、内容、互动与举报等核心运营指标。</p>
        </div>
        <form class="inline-date" method="GET" action="p_dailyReport.php">
            <input class="input" type="date" name="date" value="<?=h($date)?>" required>
            <button class="btn" type="submit">查看</button>
            <a class="btn btn-soft" href="p_exportDailyReport.php?date=<?=h($date)?>"><?=icon('download')?>导出 CSV</a>
        </form>
    </div>

    <div class="stats-grid">
        <?php foreach ($highlight as $key): ?>
            <div class="stat-card">
                <span class="stat-icon"><?=icon('chart')?></span>
                <span><span class="stat-value"><?=h($report[$key])?></span><span class="stat-label"><?=h($labels[$key])?></span></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="card card-pad mt-24">
        <div class="section-head">
            <h2 class="section-title"><?=h($date)?> 完整指标</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>指标</th><th>数值</th></tr></thead>
                <tbody>
                    <?php foreach ($labels as $key => $label): ?>
                        <tr><td><?=h($label)?></td><td><strong><?=h($report[$key])?></strong></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php renderSiteFooter([
    'links' => [
        ['label' => '后台总览', 'href' => 'p_adminAuth.php'],
        ['label' => '学生登录', 'href' => 'p_loginStu.php'],
    ],
]); ?>