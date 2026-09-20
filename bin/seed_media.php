<?php
require_once __DIR__ . '/../lib/manageDB.php';
$root = dirname(__DIR__);
$avatarSrcs = [
    'demo01' => 'assets/media/demo/avatars/demo01.jpg',
    'demo02' => 'assets/media/demo/avatars/demo02.jpg',
    'demo03' => 'assets/media/demo/avatars/demo03.jpg',
    'demo04' => 'assets/media/demo/avatars/demo04.jpg',
    'demo05' => 'assets/media/demo/avatars/demo05.jpg',
    'demo06' => 'assets/media/demo/avatars/demo06.jpg',
];
$avatarNames = [
    'demo01' => 'demo_avatar_01.jpg',
    'demo02' => 'demo_avatar_02.jpg',
    'demo03' => 'demo_avatar_03.jpg',
    'demo04' => 'demo_avatar_04.jpg',
    'demo05' => 'demo_avatar_05.jpg',
    'demo06' => 'demo_avatar_06.jpg',
];
$avatarDst = [];
foreach ($avatarNames as $user => $name) {
    $from = $root . '/' . $avatarSrcs[$user];
    $to = $root . '/uploads/' . $name;
    if (is_file($from)) {
        copy($from, $to);
        $avatarDst[$user] = 'uploads/' . $name;
    }
}
$scenes = [
    'library' => ['assets/media/demo/scenes/library.jpg', 'dynamic_scene_library.jpg'],
    'data-canteen' => ['assets/media/demo/scenes/data-canteen.jpg', 'dynamic_scene_data_canteen.jpg'],
    'sunset-field' => ['assets/media/demo/scenes/sunset-field.jpg', 'dynamic_scene_sunset_field.jpg'],
    'community-movie' => ['assets/media/demo/scenes/community-movie.jpg', 'dynamic_scene_community_movie.jpg'],
    'design-studio' => ['assets/media/demo/scenes/design-studio.jpg', 'dynamic_scene_design_studio.jpg'],
    'basketball' => ['assets/media/demo/scenes/basketball.jpg', 'dynamic_scene_basketball.jpg'],
];
$sceneMap = [];
foreach ($scenes as $key => $pair) {
    $from = $root . '/' . $pair[0];
    $to = $root . '/uploads/' . $pair[1];
    if (is_file($from) && copy($from, $to)) {
        $sceneMap[$key] = 'uploads/' . $pair[1];
    }
}
$conn = dbConnect();
foreach ($avatarDst as $userName => $avatarPath) {
    $student = getCurrentStudent($userName);
    if (!$student) {
        continue;
    }
    $stmt = $conn->prepare('UPDATE `student` SET `avatar` = ? WHERE `pk` = ?');
    $stmt->bind_param('si', $avatarPath, $student['pk']);
    $stmt->execute();
    $stmt->close();
    echo "Avatar assigned to {$userName} ({$student['name']})\n";
}
$res = $conn->query('SELECT pk, content FROM `dynamic` WHERE status = \'normal\' ORDER BY pk');
while ($row = $res->fetch_assoc()) {
    $content = $row['content'];
    $scene = null;
    if (mb_strpos($content, '图书馆', 0, 'UTF-8') !== false) {
        $scene = $sceneMap['library'] ?? '';
    } elseif (mb_strpos($content, '食堂', 0, 'UTF-8') !== false) {
        $scene = $sceneMap['data-canteen'] ?? '';
    } elseif (mb_strpos($content, '晚霞', 0, 'UTF-8') !== false || mb_strpos($content, '操场', 0, 'UTF-8') !== false) {
        $scene = $sceneMap['sunset-field'] ?? '';
    }
    if ($scene === null || $scene === '') {
        continue;
    }
    $stmt = $conn->prepare('UPDATE `dynamic` SET `photo` = ? WHERE `pk` = ?');
    $stmt->bind_param('si', $scene, $row['pk']);
    $stmt->execute();
    $stmt->close();
    $exists = $conn->prepare('SELECT COUNT(*) AS c FROM `dynamic_photo` WHERE `dynamicPk` = ?');
    $exists->bind_param('i', $row['pk']);
    $exists->execute();
    $count = (int)$exists->get_result()->fetch_assoc()['c'];
    $exists->close();
    if ($count === 0) {
        $ins = $conn->prepare('INSERT INTO `dynamic_photo` (`dynamicPk`, `path`, `sort`) VALUES (?, ?, 0)');
        $ins->bind_param('is', $row['pk'], $scene);
        $ins->execute();
        $ins->close();
    } else {
        $upd = $conn->prepare('UPDATE `dynamic_photo` SET `path` = ?, `sort` = 0 WHERE `dynamicPk` = ? ORDER BY pk LIMIT 1');
        $upd->bind_param('si', $scene, $row['pk']);
        $upd->execute();
        $upd->close();
    }
    echo "Scene assigned to dynamic {$row['pk']}\n";
}
$newPosts = [
    ['demo04', '周末在社团放映室看了一部老电影，散场后大家还在聊镜头语言。', '电影,社团', 'community-movie'],
    ['demo05', '给学院活动设计了一套主视觉，用克制的蓝色讲校园故事。', '设计,插画', 'design-studio'],
    ['demo06', '傍晚篮球场灯光亮起，今天的三分手感终于回来了。', '篮球,运动', 'basketball'],
];
foreach ($newPosts as $item) {
    $student = getCurrentStudent($item[0]);
    if (!$student || !isset($sceneMap[$item[3]]) || $sceneMap[$item[3]] === '') {
        continue;
    }
    $exists = $conn->prepare('SELECT pk FROM `dynamic` WHERE `userPk` = ? AND `content` = ? LIMIT 1');
    $exists->bind_param('is', $student['pk'], $item[1]);
    $exists->execute();
    $row = $exists->get_result()->fetch_assoc();
    $exists->close();
    $scene = $sceneMap[$item[3]];
    if ($row) {
        $stmt = $conn->prepare('UPDATE `dynamic` SET `photo` = ? WHERE `pk` = ?');
        $stmt->bind_param('si', $scene, $row['pk']);
        $stmt->execute();
        $stmt->close();
        $upd = $conn->prepare('UPDATE `dynamic_photo` SET `path` = ?, `sort` = 0 WHERE `dynamicPk` = ? ORDER BY pk LIMIT 1');
        $upd->bind_param('si', $scene, $row['pk']);
        $upd->execute();
        $upd->close();
        echo "New dynamic already exists for {$item[0]}, photo updated\n";
        continue;
    }
    $result = publishDynamicWithPhotos((int)$student['pk'], $item[1], $item[2], [$scene]);
    echo ($result === true ? "New dynamic created for {$item[0]}\n" : "New dynamic failed for {$item[0]}: {$result}\n");
}
$conn->close();
echo "Seed media complete.\n";
