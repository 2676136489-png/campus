<?php
require_once __DIR__ . '/p_manageDB.php';

requireAdminLogin();
requireAdminRole('super');

$keyword = trim($_GET['q'] ?? '');
$page = getStudentsPage('V', false, ['keyword' => $keyword], 1, 1000);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="students-' . date('Ymd-His') . '.csv"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['用户名', '姓名', '性别', '学院', '年级', '专业', '学号', '手机号', '邮箱', '标签', '注册时间']);
foreach ($page['items'] as $stu) {
    fputcsv($out, [
        $stu['userName'],
        $stu['name'],
        genderText($stu['gender']),
        $stu['college'],
        $stu['grade'],
        $stu['major'],
        $stu['stuNo'],
        $stu['phone'],
        $stu['email'],
        $stu['tags'],
        $stu['createTime']
    ]);
}
fclose($out);
exit;