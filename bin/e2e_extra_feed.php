<?php
require_once __DIR__ . '/../p_manageDB.php';

$conn = dbConnect();
$row = $conn->query("SELECT COUNT(*) AS total FROM `dynamic` WHERE `status` = 'normal'")->fetch_assoc();
$count = $row ? (int)$row['total'] : 0;
if ($count >= 12) {
    echo "Extra feed already seeded.\n";
    $conn->close();
    exit(0);
}

$pks = array();
$r = $conn->query("SELECT u.pk FROM `user` u INNER JOIN `student` s ON s.pk = u.pk WHERE u.userName IN ('demo01','demo02','demo03','demo04','demo05','demo06') AND u.status = 'V'");
while ($row = $r->fetch_assoc()) {
    $pks[] = (int)$row['pk'];
}
if (empty($pks)) {
    fwrite(STDERR, "Demo students missing.\n");
    exit(1);
}

$scenes = array(
    array('content' => '图书馆靠窗的位置永远先到先得，今天终于抢到了。', 'tags' => '学习,图书馆', 'photo' => 'uploads/dynamic_scene_library.jpg'),
    array('content' => '晚霞把操场染成了橘子汽水的颜色。', 'tags' => '摄影,晚霞', 'photo' => 'uploads/dynamic_scene_sunset_field.jpg'),
    array('content' => '食堂数据小实验：窗口排队时间分布，周末整理成图。', 'tags' => '数据,校园生活', 'photo' => 'uploads/dynamic_scene_data_canteen.jpg'),
    array('content' => '篮球赛最后一秒绝杀，全场都炸了。', 'tags' => '篮球,比赛', 'photo' => 'uploads/dynamic_scene_basketball.jpg'),
    array('content' => '设计课作业终于定稿，通宵值了。', 'tags' => '设计,作业', 'photo' => 'uploads/dynamic_scene_design_studio.jpg'),
    array('content' => '社团电影夜放映《海上钢琴师》，后排有人看哭了。', 'tags' => '社团,电影', 'photo' => 'uploads/dynamic_scene_community_movie.jpg'),
    array('content' => '在图书馆偶遇一只认真学习的猫，它比我还卷。', 'tags' => '图书馆,校园生活', 'photo' => 'uploads/dynamic_scene_library.jpg'),
    array('content' => '傍晚的风很凉，晚霞很短，但值得跑出去看。', 'tags' => '晚霞,摄影', 'photo' => 'uploads/dynamic_scene_sunset_field.jpg'),
    array('content' => '今天食堂新窗口的鸡排饭测评：分量足，排队值。', 'tags' => '食堂,美食', 'photo' => 'uploads/dynamic_scene_data_canteen.jpg'),
    array('content' => '院队训练结束，约好下周再战。', 'tags' => '篮球,训练', 'photo' => 'uploads/dynamic_scene_basketball.jpg'),
    array('content' => '设计展板从草图到成稿，记录一下过程。', 'tags' => '设计,创作', 'photo' => 'uploads/dynamic_scene_design_studio.jpg'),
    array('content' => '周末社团分享会，认识了好多有趣的人。', 'tags' => '社团,分享', 'photo' => 'uploads/dynamic_scene_community_movie.jpg')
);

$stmt = $conn->prepare("INSERT INTO `dynamic` (`userPk`, `content`, `tags`, `photo`, `status`, `createTime`) VALUES (?, ?, ?, ?, 'normal', DATE_SUB(NOW(), INTERVAL ? MINUTE))");
$photoStmt = $conn->prepare("INSERT INTO `dynamic_photo` (`dynamicPk`, `path`, `sort`) VALUES (?, ?, 1)");
$created = 0;
foreach ($scenes as $i => $scene) {
    $userPk = $pks[$i % count($pks)];
    $minutes = count($scenes) - $i;
    $stmt->bind_param('isssi', $userPk, $scene['content'], $scene['tags'], $scene['photo'], $minutes);
    $stmt->execute();
    $dynamicPk = $stmt->insert_id;
    $photoStmt->bind_param('is', $dynamicPk, $scene['photo']);
    $photoStmt->execute();
    $created++;
}
$stmt->close();
$photoStmt->close();
$conn->close();
echo "Extra feed seeded: {$created} dynamics.\n";