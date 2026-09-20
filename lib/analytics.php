<?php
/**
 * Operations analytics.
 */

/**
 * 生成某天的运营数据日报
 * @param string $date Y-m-d
 * @return array
 */
function getDailyOperationsReport($date)
{
    $date = date('Y-m-d', strtotime($date ?: 'today'));
    $start = $date . ' 00:00:00';
    $end = $date . ' 23:59:59';

    $conn = dbConnect();
    $count = function ($sql) use ($conn) {
        $result = $conn->query($sql);
        if (!$result) {
            return 0;
        }
        $row = $result->fetch_assoc();
        $result->free();
        return (int)$row['c'];
    };

    $report = [
        'date' => $date,
        'newRegistrations' => $count("SELECT COUNT(*) AS c FROM `user` WHERE `userType` = 's' AND `createTime` BETWEEN '$start' AND '$end'"),
        'verifiedStudents' => $count("SELECT COUNT(*) AS c FROM `user` WHERE `userType` = 's' AND `status` = 'V' AND `createTime` BETWEEN '$start' AND '$end'"),
        'newDynamics' => $count("SELECT COUNT(*) AS c FROM `dynamic` WHERE `createTime` BETWEEN '$start' AND '$end'"),
        'newComments' => $count("SELECT COUNT(*) AS c FROM `comment` WHERE `createTime` BETWEEN '$start' AND '$end'"),
        'newLikes' => $count("SELECT COUNT(*) AS c FROM `like` WHERE `createTime` BETWEEN '$start' AND '$end'"),
        'newMessages' => $count("SELECT COUNT(*) AS c FROM `message` WHERE `createTime` BETWEEN '$start' AND '$end'"),
        'newFollows' => $count("SELECT COUNT(*) AS c FROM `follow` WHERE `createTime` BETWEEN '$start' AND '$end'"),
        'newReports' => $count("SELECT COUNT(*) AS c FROM `report` WHERE `createTime` BETWEEN '$start' AND '$end'"),
        'activeStudents' => $count("SELECT COUNT(DISTINCT `userPk`) AS c FROM (
            SELECT `userPk` FROM `dynamic` WHERE `createTime` BETWEEN '$start' AND '$end'
            UNION SELECT `userPk` FROM `comment` WHERE `createTime` BETWEEN '$start' AND '$end'
            UNION SELECT `userPk` FROM `like` WHERE `createTime` BETWEEN '$start' AND '$end'
            UNION SELECT `senderPk` AS userPk FROM `message` WHERE `createTime` BETWEEN '$start' AND '$end'
            UNION SELECT `userPk` FROM `action_log` WHERE `createTime` BETWEEN '$start' AND '$end'
        ) t"),
        'pendingReports' => $count("SELECT COUNT(*) AS c FROM `report` WHERE `status` = 'pending'"),
        'totalStudents' => $count("SELECT COUNT(*) AS c FROM `user` WHERE `userType` = 's'"),
        'totalDynamics' => $count("SELECT COUNT(*) AS c FROM `dynamic` WHERE `status` = 'normal'"),
        'totalMessages' => $count("SELECT COUNT(*) AS c FROM `message`"),
    ];
    $conn->close();
    return $report;
}

/**
 * 日报指标标签与顺序
 * @return array<string, string>
 */
function dailyReportLabels()
{
    return [
        'newRegistrations' => '新增注册',
        'verifiedStudents' => '新增认证',
        'newDynamics' => '新增动态',
        'newComments' => '新增评论',
        'newLikes' => '新增点赞',
        'newMessages' => '新增私信',
        'newFollows' => '新增关注',
        'newReports' => '新增举报',
        'activeStudents' => '活跃学生',
        'pendingReports' => '待处理举报',
        'totalStudents' => '累计学生',
        'totalDynamics' => '累计动态',
        'totalMessages' => '累计私信'
    ];
}

/**
 * 近 N 天运营趋势
 * @param int $days
 * @return array
 */
function getTrendReport($days = 30)
{
    $days = max(7, min(90, (int)$days));
    $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
    $conn = dbConnect();

    $map = function ($sql) use ($conn) {
        $result = $conn->query($sql);
        $data = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[$row['d']] = (int)$row['c'];
            }
            $result->free();
        }
        return $data;
    };

    $registrations = $map("SELECT DATE(createTime) AS d, COUNT(*) AS c FROM `user` WHERE userType='s' AND DATE(createTime) >= '$start' GROUP BY d");
    $dynamics = $map("SELECT DATE(createTime) AS d, COUNT(*) AS c FROM `dynamic` WHERE DATE(createTime) >= '$start' GROUP BY d");
    $comments = $map("SELECT DATE(createTime) AS d, COUNT(*) AS c FROM `comment` WHERE DATE(createTime) >= '$start' GROUP BY d");
    $messages = $map("SELECT DATE(createTime) AS d, COUNT(*) AS c FROM `message` WHERE DATE(createTime) >= '$start' GROUP BY d");

    $labels = [];
    $regSeries = [];
    $dynSeries = [];
    $comSeries = [];
    $msgSeries = [];
    $activeSeries = [];
    $max = 1;
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime('-' . $i . ' days'));
        $labels[] = date('m-d', strtotime($date));
        $reg = $registrations[$date] ?? 0;
        $dyn = $dynamics[$date] ?? 0;
        $com = $comments[$date] ?? 0;
        $msg = $messages[$date] ?? 0;
        $regSeries[] = $reg;
        $dynSeries[] = $dyn;
        $comSeries[] = $com;
        $msgSeries[] = $msg;
        $max = max($max, $reg, $dyn, $com, $msg);
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT userPk) AS c FROM (
            SELECT userPk FROM `dynamic` WHERE DATE(createTime) = ?
            UNION SELECT userPk FROM `comment` WHERE DATE(createTime) = ?
            UNION SELECT userPk FROM `like` WHERE DATE(createTime) = ?
            UNION SELECT senderPk AS userPk FROM `message` WHERE DATE(createTime) = ?
        ) t");
        $stmt->bind_param('ssss', $date, $date, $date, $date);
        $stmt->execute();
        $activeSeries[] = (int)$stmt->get_result()->fetch_assoc()['c'];
        $stmt->close();
        $max = max($max, $activeSeries[count($activeSeries) - 1]);
    }
    $conn->close();

    return [
        'labels' => $labels,
        'registrations' => $regSeries,
        'dynamics' => $dynSeries,
        'comments' => $comSeries,
        'messages' => $msgSeries,
        'active' => $activeSeries,
        'max' => max(1, $max)
    ];
}
