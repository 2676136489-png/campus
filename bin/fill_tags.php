<?php
/**
 * 为尚未设置标签的学生账号随机生成 3 个兴趣标签。
 * 已有标签的账号不会修改。
 */
require_once __DIR__ . '/../lib/manageDB.php';

$pool = [
    '摄影', '篮球', '音乐', '阅读', '编程', '跑步', '绘画', '电影',
    '旅行', '美食', '游戏', '动漫', '羽毛球', '乒乓球', '健身', '舞蹈',
    '桌游', '辩论', '志愿', '乐器', '骑行', '滑板', '天文', '心理学',
    '外语', '写作', '咖啡', '汉服', '二次元', '电竞'
];

$conn = dbConnect();
$result = $conn->query("SELECT `pk` FROM `student` WHERE `tags` = '' OR `tags` IS NULL");
$ids = [];
while ($row = $result->fetch_assoc()) {
    $ids[] = (int)$row['pk'];
}
$result->free();

$updated = 0;
foreach ($ids as $pk) {
    $keys = array_rand($pool, 3);
    $tags = [$pool[$keys[0]], $pool[$keys[1]], $pool[$keys[2]]];
    if (updateStuTags($pk, implode(',', $tags))) {
        $updated++;
    }
}
$conn->close();
echo "Filled tags for {$updated} students.\n";