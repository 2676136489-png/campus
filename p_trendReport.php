<?php
require_once __DIR__ . '/p_manageDB.php';
require_once __DIR__ . '/p_layout.php';

requireAdminLogin();
$trend = getTrendReport(30);

renderHead('30 天趋势');
renderSiteHeader([
    'home' => 'p_adminAuth.php',
    'nav' => [
        ['label' => '后台总览', 'href' => 'p_adminAuth.php'],
        ['label' => '运营日报', 'href' => 'p_dailyReport.php'],
        ['label' => '30 天趋势', 'href' => 'p_trendReport.php', 'active' => true],
        ['label' => '学生入口', 'href' => 'p_loginStu.php'],
    ],
    'actions' => '<a class="btn btn-ghost btn-sm" href="p_adminAuth.php?logout=1">' . icon('logout') . '退出</a>',
]);
?>
<div class="container">
    <div class="page-head">
        <div>
            <h1 class="page-title">30 天运营趋势</h1>
            <p class="page-sub">查看注册、动态、评论、私信与活跃学生的每日走势。</p>
        </div>
        <a class="btn btn-soft" href="p_exportTrendReport.php"><?=icon('download')?>导出 CSV</a>
    </div>

    <div class="card card-pad">
        <div class="activity-chart trend-chart">
            <?php foreach ($trend['labels'] as $i => $label): ?>
                <div class="activity-col">
                    <div class="activity-bars">
                        <span class="bar-dynamic" style="height: <?=h(round($trend['registrations'][$i] / $trend['max'] * 100))?>%" title="注册"></span>
                        <span class="bar-comment" style="height: <?=h(round($trend['dynamics'][$i] / $trend['max'] * 100))?>%" title="动态"></span>
                        <span class="bar-register" style="height: <?=h(round($trend['comments'][$i] / $trend['max'] * 100))?>%" title="评论"></span>
                        <span class="bar-message" style="height: <?=h(round($trend['messages'][$i] / $trend['max'] * 100))?>%" title="私信"></span>
                    </div>
                    <div class="activity-label"><?=h($label)?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="activity-legend">
            <span><i class="legend-dot dot-dynamic"></i>注册</span>
            <span><i class="legend-dot dot-comment"></i>动态</span>
            <span><i class="legend-dot dot-register"></i>评论</span>
            <span><i class="legend-dot dot-message"></i>私信</span>
        </div>
    </div>

    <div class="card card-pad mt-24">
        <div class="table-wrap">
            <table>
                <thead><tr><th>日期</th><th>注册</th><th>动态</th><th>评论</th><th>私信</th><th>活跃学生</th></tr></thead>
                <tbody>
                    <?php foreach ($trend['labels'] as $i => $label): ?>
                        <tr>
                            <td><?=h($label)?></td>
                            <td><?=h($trend['registrations'][$i])?></td>
                            <td><?=h($trend['dynamics'][$i])?></td>
                            <td><?=h($trend['comments'][$i])?></td>
                            <td><?=h($trend['messages'][$i])?></td>
                            <td><?=h($trend['active'][$i])?></td>
                        </tr>
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