<?php
require_once __DIR__ . '/../lib/manageDB.php';
requireAdminLogin();

$date = isset($_GET['date']) ? date('Y-m-d', strtotime($_GET['date'])) : date('Y-m-d');
$report = getDailyOperationsReport($date);
$labels = dailyReportLabels();
$rows = [];
foreach ($labels as $key => $label) {
    $rows[] = ['metric' => $label, 'value' => $report[$key]];
}
csvResponse('daily-report-' . $date . '.csv', ['指标', '数值'], $rows);