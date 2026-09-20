<?php
/**
 * Admin statistics.
 */


/**
 * 获取管理员统计数据
 * @return array 统计数据
 */
function getAdminStats()
{
    $conn = dbConnect();
    $stats = ['pending' => 0, 'verified' => 0, 'disabled' => 0, 'dynamics' => 0, 'reports' => 0];
    $result = $conn->query("SELECT `status`, COUNT(*) AS total FROM `user` WHERE `userType` = 's' GROUP BY `status`");
    while ($row = $result->fetch_assoc()) {
        if ($row['status'] === 'N') {
            $stats['pending'] = (int)$row['total'];
        } elseif ($row['status'] === 'V') {
            $stats['verified'] = (int)$row['total'];
        } elseif ($row['status'] === 'U') {
            $stats['disabled'] = (int)$row['total'];
        }
    }
    $result->free();
    $result = $conn->query("SELECT COUNT(*) AS total FROM `dynamic` WHERE `status` = 'normal'");
    if ($row = $result->fetch_assoc()) {
        $stats['dynamics'] = (int)$row['total'];
    }
    $result->free();
    $result = $conn->query("SELECT COUNT(*) AS total FROM `report` WHERE `status` = 'pending'");
    if ($row = $result->fetch_assoc()) {
        $stats['reports'] = (int)$row['total'];
    }
    $result->free();
    $conn->close();
    return $stats;
}

/**
 * 近 N 天活跃数据
 * @param int $days
 * @return array
 */
function getActivityStats($days = 7)
{
    $days = max(3, min(30, (int)$days));
    $conn = dbConnect();
    $labels = [];
    $dynamics = [];
    $comments = [];
    $registrations = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime('-' . $i . ' days'));
        $labels[] = date('m-d', strtotime($date));
        $row = $conn->query("SELECT COUNT(*) AS c FROM `dynamic` WHERE DATE(`createTime`) = '" . $date . "'")->fetch_assoc();
        $dynamics[] = (int)$row['c'];
        $row = $conn->query("SELECT COUNT(*) AS c FROM `comment` WHERE DATE(`createTime`) = '" . $date . "'")->fetch_assoc();
        $comments[] = (int)$row['c'];
        $row = $conn->query("SELECT COUNT(*) AS c FROM `user` WHERE DATE(`createTime`) = '" . $date . "' AND `userType` = 's'")->fetch_assoc();
        $registrations[] = (int)$row['c'];
    }
    $conn->close();
    return [
        'labels' => $labels,
        'dynamics' => $dynamics,
        'comments' => $comments,
        'registrations' => $registrations,
        'max' => max(1, max(array_merge($dynamics, $comments, $registrations)))
    ];
}
