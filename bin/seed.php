<?php
require_once __DIR__ . '/../p_manageDB.php';

if (getUserByUserName('demo01')) {
    echo "Seed data already exists.\n";
    exit(0);
}

$admin = getUserByUserName('admin');
$adminPk = $admin ? (int)$admin['pk'] : 0;

$students = [
    ['demo01', '林晓', 1, '2004-03-12', '计算机学院', '2023级', '软件工程', '20230101', '13800138001', 'demo01@example.com', '100001', '编程,篮球,摄影'],
    ['demo02', '陈思远', 0, '2004-07-08', '计算机学院', '2023级', '数据科学', '20230102', '13800138002', 'demo02@example.com', '100002', '机器学习,音乐,阅读'],
    ['demo03', '周雨桐', 1, '2005-01-20', '经济管理学院', '2024级', '金融学', '20240103', '13800138003', 'demo03@example.com', '100003', '摄影,旅行,美食'],
    ['demo04', '赵一鸣', 0, '2004-11-02', '外国语学院', '2023级', '英语', '20230104', '13800138004', 'demo04@example.com', '100004', '电影,羽毛球,写作'],
    ['demo05', '孙悦', 1, '2005-05-16', '艺术学院', '2024级', '视觉传达', '20240105', '13800138005', 'demo05@example.com', '100005', '设计,插画,摄影'],
    ['demo06', '吴浩然', 0, '2003-09-25', '计算机学院', '2022级', '网络工程', '20220106', '13800138006', 'demo06@example.com', '100006', '篮球,游戏,科技']
];

$pks = [];
foreach ($students as $i => $s) {
    $result = regStuUser($s[0], 'Demo@123456', $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], $s[8], $s[9], $s[10], '', '');
    if ($result !== true) {
        echo "Failed to create {$s[0]}: {$result}\n";
        exit(1);
    }
    $student = getCurrentStudent($s[0]);
    $pks[] = (int)$student['pk'];
    updateStuTags((int)$student['pk'], $s[11]);
    setUserStatus((int)$student['pk'], 'V', $adminPk, '演示数据');
    echo "Created {$s[0]}\n";
}

$post1 = publishDynamic($pks[0], '开学第一周，和室友在图书馆待到闭馆，学习真的会让人上瘾。', '学习,图书馆', '');
$post2 = publishDynamic($pks[1], '用校园数据做了个小实验：食堂窗口排队时间分布。周末整理成图发出来。', '数据,校园生活', '');
$post3 = publishDynamic($pks[2], '今天在操场拍到绝美晚霞，校园的傍晚永远值得期待。', '摄影,晚霞', '');

if ($post1 === true && $post2 === true && $post3 === true) {
    $feed = getDynamicsPage($pks[3], false, [], 1, 10);
    if (!empty($feed['items'])) {
        $d1 = (int)$feed['items'][0]['pk'];
        toggleLike($d1, $pks[3]);
        addComment($d1, $pks[3], '同感，图书馆的氛围真的很好。');
        toggleLike($d1, $pks[4]);
    }
}

echo "Seed data complete. Demo student password: Demo@123456\n";