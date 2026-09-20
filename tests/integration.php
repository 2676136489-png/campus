<?php
/**
 * Integration test against a disposable MySQL database.
 * Usage:
 *   DB_HOST=127.0.0.1 DB_NAME=circle_test DB_USER=root DB_PASS=123456 php tests/integration.php
 */

require_once __DIR__ . '/../lib/manageDB.php';

$tests = 0;
$failures = 0;

function it($name, $condition) {
    global $tests, $failures;
    $tests++;
    if ($condition) {
        echo "[PASS] " . $name . PHP_EOL;
    } else {
        $failures++;
        echo "[FAIL] " . $name . PHP_EOL;
    }
}

$suffix = substr(md5(uniqid('', true)), 0, 10);
$digits = substr(preg_replace('/\D/', '', $suffix) . '1234567890', 0, 8);
$user1 = 'stu_' . $suffix . 'a';
$user2 = 'stu_' . $suffix . 'b';
$stuNo1 = '20' . $digits;
$stuNo2 = '21' . $digits;
$phone1 = '13800' . substr($digits, 0, 6);
$phone2 = '13900' . substr($digits, 0, 6);

it('register student 1', regStuUser($user1, 'abc123', '测试学生甲', 1, '2004-01-01', '计算机学院', '2023级', '软件工程', $stuNo1, $phone1, 'a' . $suffix . '@example.com', '123456789', 'uploads/avatar_1.png', 'uploads/card_1.png') === true);
it('register student 2', regStuUser($user2, 'abc123', '测试学生乙', 0, '2005-02-02', '计算机学院', '2024级', '数据科学', $stuNo2, $phone2, 'b' . $suffix . '@example.com', '987654321', 'uploads/avatar_2.png', 'uploads/card_2.png') === true);

$student1 = getCurrentStudent($user1);
$student2 = getCurrentStudent($user2);
it('students are pending before review', $student1['status'] === 'N' && $student2['status'] === 'N');

$admin = getUserByUserName('admin');
it('admin account exists', $admin !== null && $admin['userType'] === 'a');
it('admin has super role', getAdminRole('admin') === 'super');
$opName = 'op_' . $suffix;
$createdAdmin = createAdminUser($opName, 'Op123456', 'operator', (int)$admin['pk']);
it('operator admin can be created', $createdAdmin === true);
it('operator role is stored', getAdminRole($opName) === 'operator');
$opLogin = checkAdminLogin($opName, 'Op123456');
it('operator admin can login', is_array($opLogin) && $opLogin['userType'] === 'a');
it('operator admin can be disabled', setAdminStatus($opName, 'U', (int)$admin['pk']) === true && is_string(checkAdminLogin($opName, 'Op123456')));
it('operator admin can be re-enabled', setAdminStatus($opName, 'V', (int)$admin['pk']) === true && is_array(checkAdminLogin($opName, 'Op123456')));
it('approve student 1 with audit', setUserStatus((int)$student1['pk'], 'V', (int)$admin['pk'], '资料无误') === true);
it('approve student 2 with audit', setUserStatus((int)$student2['pk'], 'V', (int)$admin['pk'], '资料无误') === true);

$student1 = getCurrentStudent($user1);
it('student 1 is verified', $student1['status'] === 'V');

$edit = updateStuInfo((int)$student1['pk'], [
    'name' => '测试学生甲改',
    'gender' => 1,
    'birth_date' => '2004-01-01',
    'college' => '计算机学院',
    'grade' => '2023级',
    'major' => '软件工程',
    'phone' => $phone1,
    'email' => 'a' . $suffix . '@example.com',
    'QQ' => '123456789'
], '', '');
it('profile edit returns ok with re-review flag', is_array($edit) && !empty($edit['ok']) && !empty($edit['needsReview']));
$student1 = getCurrentStudent($user1);
it('identity change resets status to pending', $student1['status'] === 'N');
it('re-approve after profile change', setUserStatus((int)$student1['pk'], 'V', (int)$admin['pk'], '重新审核通过') === true);

it('publish dynamic', publishDynamic((int)$student1['pk'], '第一条校园动态，欢迎交流。', '篮球,学习', 'uploads/demo.png') === true);
$feed = getDynamicsPage((int)$student2['pk'], false, ['tag' => '篮球'], 1, 10);
$topicPost = publishDynamic((int)$student2['pk'], '今天分享一个话题 #话题测试', '学习', '');
$topicFeed = getDynamicsPage((int)$student2['pk'], false, ['tag' => '话题测试'], 1, 5);
it('hashtag becomes searchable tag', $topicPost === true && $topicFeed['total'] >= 1);
it('dynamic page finds tagged post', $feed['total'] >= 1);
$rollbackDynPk = (int)$feed['items'][0]['pk'];
$delDynResult = deleteDynamic($rollbackDynPk, (int)$admin['pk'], true);
$conn = dbConnect();
$rbDyn = $conn->query("SELECT auditPk FROM `admin_rollback` WHERE targetType = 'dynamic' AND targetPk = " . $rollbackDynPk . " ORDER BY pk DESC LIMIT 1")->fetch_assoc();
$conn->close();
$dynRollbackOk = $delDynResult === true && $rbDyn && performRollback((int)$rbDyn['auditPk'], (int)$admin['pk']) === true;
it('admin deleted dynamic can be rolled back', $dynRollbackOk);
it('dynamic restored after rollback', getDynamicByPk($rollbackDynPk, (int)$student1['pk']) !== null);
$dynamic = $feed['items'][0];

$like = toggleLike((int)$dynamic['pk'], (int)$student2['pk']);
it('like succeeds and returns count', isset($like['ok']) && $like['ok'] && $like['liked'] === true && $like['likeCount'] === 1);
$comment = addComment((int)$dynamic['pk'], (int)$student2['pk'], '很棒的分享！');
$fav = toggleFavorite((int)$dynamic['pk'], (int)$student2['pk']);
it('favorite succeeds', isset($fav['ok']) && $fav['ok'] && $fav['favorited'] === true && $fav['favoriteCount'] === 1);
it('favorite appears in favorites feed', getDynamicsPage((int)$student2['pk'], false, ['favoriteUserPk' => (int)$student2['pk']], 1, 10)['total'] >= 1);
$unfav = toggleFavorite((int)$dynamic['pk'], (int)$student2['pk']);
$draftSaved = saveDynamicDraft((int)$student1['pk'], '草稿内容测试', '草稿,测试');
$draftRow = getDynamicDraft((int)$student1['pk']);
it('dynamic draft can be saved and loaded', $draftSaved === true && is_array($draftRow) && $draftRow['content'] === '草稿内容测试');
it('dynamic draft can be cleared', deleteDynamicDraft((int)$student1['pk']) === true && getDynamicDraft((int)$student1['pk']) === null);
it('unfavorite works', isset($unfav['ok']) && $unfav['ok'] && $unfav['favorited'] === false && $unfav['favoriteCount'] === 0);
it('comment succeeds', isset($comment['ok']) && $comment['ok'] && !empty($comment['comment']['content']));

$notifications = getNotifications((int)$student1['pk'], 50);
it('author receives like and comment notifications', count($notifications) >= 2);
it('unread count is at least 2', getUnreadNotificationCount((int)$student1['pk']) >= 2);
it('mark notifications read', markNotificationsRead((int)$student1['pk']) === true && getUnreadNotificationCount((int)$student1['pk']) === 0);

$user3 = 'stu_' . $suffix . 'c';
$stuNo3 = '22' . $digits;
$phone3 = '13700' . substr($digits, 0, 6);
it('register student 3', regStuUser($user3, 'abc123', '测试学生丙', 1, '2006-03-03', '计算机学院', '2024级', '网络安全', $stuNo3, $phone3, 'c' . $suffix . '@example.com', '555666777', 'uploads/avatar_3.png', 'uploads/card_3.png') === true);
$student3 = getCurrentStudent($user3);
it('approve student 3', setUserStatus((int)$student3['pk'], 'V', (int)$admin['pk'], '资料无误') === true);

$deniedDelete = deleteComment((int)$comment['comment']['pk'], (int)$student3['pk'], false);
it('non-privileged user cannot delete comment', isset($deniedDelete['ok']) && !$deniedDelete['ok']);

$authorDelete = deleteComment((int)$comment['comment']['pk'], (int)$student2['pk'], false);
it('comment author can delete own comment', isset($authorDelete['ok']) && $authorDelete['ok']);

$comment2 = addComment((int)$dynamic['pk'], (int)$student2['pk'], '第二条评论');
it('second comment succeeds', isset($comment2['ok']) && $comment2['ok']);
$ownerDelete = deleteComment((int)$comment2['comment']['pk'], (int)$student1['pk'], false);
it('dynamic owner can delete comments on own post', isset($ownerDelete['ok']) && $ownerDelete['ok']);

$comment3 = addComment((int)$dynamic['pk'], (int)$student2['pk'], '第三条评论');
it('third comment succeeds', isset($comment3['ok']) && $comment3['ok']);
$adminDelete = deleteComment((int)$comment3['comment']['pk'], (int)$admin['pk'], true);
it('admin can delete comments', isset($adminDelete['ok']) && $adminDelete['ok']);

$comment4 = addComment((int)$dynamic['pk'], (int)$student2['pk'], '第四条评论');
it('fourth comment succeeds', isset($comment4['ok']) && $comment4['ok']);
$commentsPage = getCommentsPage([], 1, 10);
$replyComment = addComment((int)$dynamic['pk'], (int)$student3['pk'], '回复第四评论', (int)$comment4['comment']['pk']);
it('comment reply succeeds', isset($replyComment['ok']) && $replyComment['ok'] && !empty($replyComment['comment']['parentPk']));
$replyList = getCommentsByDynamic((int)$dynamic['pk']);
$replyFound = false;
foreach ($replyList as $c) {
    if ((int)$c['pk'] === (int)$replyComment['comment']['pk'] && !empty($c['parentName'])) {
        $replyFound = true;
    }
}
it('comment reply loads parent name', $replyFound);
it('reply to missing comment is rejected', addComment((int)$dynamic['pk'], (int)$student3['pk'], '跨动态回复', 999999)['ok'] === false);
it('admin comments page returns data', $commentsPage['total'] >= 1 && $commentsPage['items'][0]['pk'] === (int)$comment4['comment']['pk']);
$delCommentResult = deleteComment((int)$comment4['comment']['pk'], (int)$admin['pk'], true);
$conn = dbConnect();
$rbComment = $conn->query("SELECT auditPk FROM `admin_rollback` WHERE targetType = 'comment' AND targetPk = " . (int)$comment4['comment']['pk'] . " ORDER BY pk DESC LIMIT 1")->fetch_assoc();
$conn->close();
$commentRollbackOk = isset($delCommentResult['ok']) && $delCommentResult['ok'] && $rbComment && performRollback((int)$rbComment['auditPk'], (int)$admin['pk']) === true;
$restoredComments = getCommentsByDynamic((int)$dynamic['pk']);
$commentRestored = false;
foreach ($restoredComments as $c) {
    if ((int)$c['pk'] === (int)$comment4['comment']['pk']) {
        $commentRestored = true;
    }
}
it('admin deleted comment can be rolled back', $commentRollbackOk);
it('comment restored after rollback', $commentRestored);

it('report dynamic can be created', createReport((int)$student3['pk'], 'dynamic', (int)$dynamic['pk'], '疑似广告内容') === true);
it('duplicate pending report is rejected', is_string(createReport((int)$student3['pk'], 'dynamic', (int)$dynamic['pk'], '重复举报')));
$reportsPage = getReportsPage('pending', 1, 10);
it('admin report queue lists pending report', $reportsPage['total'] >= 1 && $reportsPage['items'][0]['targetPk'] == $dynamic['pk']);
it('report can be dismissed', setReportStatus((int)$reportsPage['items'][0]['pk'], 'dismissed') === true);
it('report queue clears after dismissal', getReportsPage('pending', 1, 10)['total'] === 0);

it('dynamic owner can edit own post', updateDynamic((int)$dynamic['pk'], (int)$student1['pk'], '编辑后的动态内容', '学习,校园') === true);
it('non-owner cannot edit dynamic', updateDynamic((int)$dynamic['pk'], (int)$student3['pk'], '越权编辑', '') !== true);

$commentNotices = getNotificationsPage((int)$student1['pk'], 'comment', 1, 10);
it('notification page filters by comment type', $commentNotices['total'] >= 1);

$recommended = getRecommendedStudents((int)$student1['pk'], 4);
it('recommendations return same-college students', is_array($recommended) && count($recommended) >= 1);
it('student can be loaded by pk', getStudentByPk((int)$student1['pk']) !== null);

$follow = toggleFollow((int)$student2['pk'], (int)$student1['pk']);
it('follow works', isset($follow['ok']) && $follow['ok'] && $follow['following'] === true);
it('follow counts update', getFollowCounts((int)$student1['pk'])['followers'] === 1);
$unfollow = toggleFollow((int)$student2['pk'], (int)$student1['pk']);
it('unfollow works', isset($unfollow['ok']) && $unfollow['ok'] && $unfollow['following'] === false);

$prefs = getNotificationPrefs((int)$student1['pk']);
it('notification prefs default enabled', !empty($prefs['like']));
it('disable like notifications', updateNotificationPrefs((int)$student1['pk'], array_merge($prefs, ['like' => false])) === true);
it('disabled pref blocks notification', createNotification((int)$student1['pk'], 'like', '测试通知', '') === false);
updateNotificationPrefs((int)$student1['pk'], $prefs);

it('sensitive dynamic is blocked', publishDynamic((int)$student3['pk'], '这里有广告内容', '', '') !== true);
$sensitiveComment = addComment((int)$dynamic['pk'], (int)$student3['pk'], '这里有诈骗信息');
it('sensitive comment is blocked', isset($sensitiveComment['ok']) && $sensitiveComment['ok'] === false);

it('self-like is blocked', toggleLike((int)$dynamic['pk'], (int)$student1['pk'])['ok'] === false);
it('self-report is blocked', is_string(createReport((int)$student1['pk'], 'dynamic', (int)$dynamic['pk'], '自己举报自己')));
it('reserved username is blocked on register', regStuUser('admin', 'abc123', '保留名', 1, '2000-01-01', '测试学院', '2023级', '测试专业', '20990001', '13800138999', 'admin@example.com', '123456789', '', '') !== true);

it('block works', toggleBlock((int)$student3['pk'], (int)$student1['pk'])['ok'] === true && isBlocked((int)$student3['pk'], (int)$student1['pk']) === true);
it('blocked user cannot follow', toggleFollow((int)$student3['pk'], (int)$student1['pk'])['ok'] === false);
it('blocked user cannot message', sendMessage((int)$student3['pk'], (int)$student1['pk'], '你好')['ok'] === false);
it('unblock works', toggleBlock((int)$student3['pk'], (int)$student1['pk'])['ok'] === true && isBlocked((int)$student3['pk'], (int)$student1['pk']) === false);

$msg = sendMessage((int)$student2['pk'], (int)$student1['pk'], '周末一起打球吗？');
it('message can be sent', isset($msg['ok']) && $msg['ok']);
it('message unread count increases', getUnreadMessageCount((int)$student1['pk']) === 1);
$conversations = getConversations((int)$student1['pk']);
it('conversation list contains sender', count($conversations) >= 1);
$chatMessages = getMessagesBetween((int)$student1['pk'], (int)$student2['pk'], 50);
it('messages are read after opening chat', getUnreadMessageCount((int)$student1['pk']) === 0 && count($chatMessages) >= 1);

$recall = recallMessage((int)$student2['pk'], (int)$msg['message']['pk']);
it('sender can recall own message', isset($recall['ok']) && $recall['ok']);
$recalledMessages = getMessagesBetween((int)$student1['pk'], (int)$student2['pk'], 50);
it('recalled message is hidden in chat', $recalledMessages[0]['status'] === 'recalled');
$convAfterRecall = getConversations((int)$student1['pk']);
it('conversation preview shows recall', $convAfterRecall[0]['lastStatus'] === 'recalled');

$msg2 = sendMessage((int)$student2['pk'], (int)$student1['pk'], '不能被别人撤回');
it('recall other message is blocked', recallMessage((int)$student1['pk'], (int)$msg2['message']['pk'])['ok'] === false);

$msg3 = sendMessage((int)$student2['pk'], (int)$student1['pk'], '过期消息');
$conn = dbConnect();
$conn->query("UPDATE `message` SET `createTime` = DATE_SUB(NOW(), INTERVAL 3 MINUTE) WHERE pk = " . (int)$msg3['message']['pk']);
$conn->close();
it('expired message cannot be recalled', recallMessage((int)$student2['pk'], (int)$msg3['message']['pk'])['ok'] === false);

$olderMessages = getMessagesBefore((int)$student1['pk'], (int)$student2['pk'], (int)$msg2['message']['pk'], 10);
it('older messages can be loaded', count($olderMessages) >= 1);

$imgMsg = sendMessage((int)$student2['pk'], (int)$student1['pk'], '看看这张图', 'uploads/chat_demo.png');
it('image message can be sent', isset($imgMsg['ok']) && $imgMsg['ok'] && $imgMsg['message']['image'] === 'uploads/chat_demo.png');
$imgConv = getConversations((int)$student1['pk']);
it('conversation preview shows image placeholder', $imgConv[0]['lastImage'] === 'uploads/chat_demo.png' && $imgConv[0]['lastContent'] === '[图片]');
$imgChat = getMessagesBetween((int)$student1['pk'], (int)$student2['pk'], 50);
it('chat returns image field', $imgChat[count($imgChat) - 1]['image'] === 'uploads/chat_demo.png');

it('privacy can restrict messages to followed users', updatePrivacy((int)$student1['pk'], 'followed', true) === true && canMessage((int)$student2['pk'], (int)$student1['pk']) === false);
it('privacy blocks non-followed sender', sendMessage((int)$student2['pk'], (int)$student1['pk'], '隐私限制测试')['ok'] === false);
it('following unlocks private messages', toggleFollow((int)$student1['pk'], (int)$student2['pk'])['ok'] === true && canMessage((int)$student2['pk'], (int)$student1['pk']) === true);

it('conversation can be pinned', updateConversationSetting((int)$student1['pk'], (int)$student2['pk'], 'isPinned', true) === true);
$convSetting = getConversationSetting((int)$student1['pk'], (int)$student2['pk']);
it('conversation pin state is saved', $convSetting['isPinned'] === true);
it('conversation can be muted', updateConversationSetting((int)$student1['pk'], (int)$student2['pk'], 'isMuted', true) === true && getConversationSetting((int)$student1['pk'], (int)$student2['pk'])['isMuted'] === true);
$pinnedConversations = getConversations((int)$student1['pk']);
it('pinned conversation appears first', (int)$pinnedConversations[0]['isPinned'] === 1 && (int)$pinnedConversations[0]['isMuted'] === 1);

it('announcement can be created', createAnnouncement((int)$admin['pk'], '校园网络维护通知', '本周六凌晨维护。', true) === true);
it('published announcement is visible to students', count(getPublishedAnnouncements(3)) >= 1);
$announcementPage = getAnnouncementsPage(1, 10);
it('announcement admin page returns data', $announcementPage['total'] >= 1);
it('announcement can be updated', updateAnnouncement((int)$announcementPage['items'][0]['pk'], (int)$admin['pk'], '更新后的通知', '时间调整。', true) === true);
it('announcement can be deleted', deleteAnnouncement((int)$announcementPage['items'][0]['pk'], (int)$admin['pk']) === true);

$adminMessages = getAdminMessagesPage([], 1, 10);
it('admin message audit returns data', $adminMessages['total'] >= 1);
$auditMessagePk = (int)$adminMessages['items'][0]['pk'];
it('admin can delete audited message', deleteMessageByAdmin((int)$admin['pk'], $auditMessagePk) === true);
it('audited message is removed', getAdminMessagesPage([], 1, 10)['total'] === $adminMessages['total'] - 1);
$conn = dbConnect();
$rbMsg = $conn->query("SELECT auditPk FROM `admin_rollback` WHERE targetType = 'message' AND targetPk = " . $auditMessagePk . " ORDER BY pk DESC LIMIT 1")->fetch_assoc();
$conn->close();
$msgRollbackOk = $rbMsg && performRollback((int)$rbMsg['auditPk'], (int)$admin['pk']) === true;
it('admin deleted message can be rolled back', $msgRollbackOk);
it('message restored after rollback', getAdminMessagesPage([], 1, 10)['total'] === $adminMessages['total']);

$dailyReport = getDailyOperationsReport(date('Y-m-d'));
it('daily operations report has expected metrics', isset($dailyReport['newDynamics']) && isset($dailyReport['activeStudents']) && isset($dailyReport['pendingReports']));
$allReports = getReportsPage('all', 1, 10);
it('report export query supports all statuses', is_array($allReports['items']) && isset($allReports['total']));

it('feedback can be created', createFeedback((int)$student1['pk'], 'suggestion', '建议增加课程表功能。', 'uploads/feedback_demo.png') === true);
$myFeedback = getMyFeedbackPage((int)$student1['pk'], 1, 10);
it('student feedback list returns data', $myFeedback['total'] >= 1 && $myFeedback['items'][0]['image'] === 'uploads/feedback_demo.png');
$adminFeedback = getAdminFeedbackPage('open', 1, 10);
it('admin feedback queue returns data', $adminFeedback['total'] >= 1);
it('feedback status can be updated with notification', updateFeedbackStatus((int)$adminFeedback['items'][0]['pk'], 'closed', (int)$admin['pk']) === true);
$closedFeedback = getAdminFeedbackPage('closed', 1, 10);
it('closed feedback appears in closed queue', $closedFeedback['total'] >= 1);
$feedbackNotices = getNotifications((int)$student1['pk'], 50);
it('feedback status change notifies student', count(array_filter($feedbackNotices, function ($n) { return $n['type'] === 'feedback'; })) >= 1);

$trend = getTrendReport(7);
it('trend report has 7 days of series', count($trend['labels']) === 7 && count($trend['dynamics']) === 7 && count($trend['active']) === 7);

it('multi-photo dynamic can be published', publishDynamicWithPhotos((int)$student1['pk'], '多图动态测试', '测试', ['uploads/m1.png', 'uploads/m2.png']) === true);
$multiFeed = getDynamicsPage((int)$student2['pk'], false, ['keyword' => '多图动态测试'], 1, 10);
$multiItem = $multiFeed['items'][0] ?? [];
it('feed returns attached photos', !empty($multiItem['photos']) && count($multiItem['photos']) === 2);

for ($i = 0; $i < 10; $i++) {
    recordAction((int)$student3['pk'], 'comment');
}
it('comment rate limit triggers', enforceActionRateLimit((int)$student3['pk'], 'comment', 10, 60) !== true);

it('sensitive word can be added and removed', addSensitiveWord('测试违禁词') === true && filterSensitiveText('包含测试违禁词')['blocked'] === true && deleteSensitiveWord('测试违禁词') === true);

$activity = getActivityStats(7);
it('activity stats has 7 days', count($activity['labels']) === 7 && count($activity['dynamics']) === 7);
$auditPage = getAuditLogsPage(1, 20);
it('audit pagination works', $auditPage['total'] >= 1 && is_array($auditPage['items']));

$newName = 'stu_' . $suffix . '_new';
it('username can be changed', updateStuUserName((int)$student1['pk'], $newName) === true);
it('new username resolves to student', getCurrentStudent($newName) !== null);
it('old username no longer resolves', getCurrentStudent($user1) === null);
it('username availability detects duplicates', isUserNameAvailable($user2, (int)$student1['pk']) === false);

$resetPhone = requestPasswordReset($user2, 'phone');
it('phone reset code is issued', isset($resetPhone['ok']) && $resetPhone['ok'] && $resetPhone['devCode'] !== '');
it('phone reset completes with code', completePasswordReset($user2, 'phone', $resetPhone['devCode'], 'resetpass1') === true);
it('phone reset password works', verifyPasswordByPk((int)$student2['pk'], 'resetpass1') === true);

$resetEmail = requestPasswordReset($user2, 'email');
it('email reset code is issued', isset($resetEmail['ok']) && $resetEmail['ok'] && $resetEmail['devCode'] !== '');
it('wrong reset code is rejected', completePasswordReset($user2, 'email', '000000', 'resetpass2') === '验证码错误。');
it('email reset completes after wrong attempt', completePasswordReset($user2, 'email', $resetEmail['devCode'], 'resetpass2') === true);
it('email reset password works', verifyPasswordByPk((int)$student2['pk'], 'resetpass2') === true);

it('change student password', changeStudentPassword((int)$student1['pk'], 'abc123', 'newpass1') === true);
it('old password no longer works', !verifyPasswordByPk((int)$student1['pk'], 'abc123'));

$students = getStudentsPage('V', false, ['keyword' => '测试学生甲改'], 1, 10);
it('student search finds updated profile', $students['total'] >= 1);

$auditLogs = getAuditLogs(50);
it('audit log records admin review actions', count($auditLogs) >= 2);

recordLoginAttempt($user1, '203.0.113.10', false);
recordLoginAttempt($user1, '203.0.113.10', false);
recordLoginAttempt($user1, '203.0.113.10', false);
recordLoginAttempt($user1, '203.0.113.10', false);
recordLoginAttempt($user1, '203.0.113.10', false);
recordLoginAttempt($user1, '203.0.113.10', false);
recordLoginAttempt($user1, '203.0.113.10', false);
recordLoginAttempt($user1, '203.0.113.10', false);
recordLoginAttempt($user1, '203.0.113.10', false);
recordLoginAttempt($user1, '203.0.113.10', false);
recordLoginAttempt($user1, '203.0.113.10', false);
it('login rate limit triggers after repeated failures', isLoginRateLimited($user1, '203.0.113.10') === true);

$totpProvision = provisionAdminTotp('admin');
$totpSecret = is_array($totpProvision) ? $totpProvision['secret'] : '';
$totpCodeValue = totpCode($totpSecret);
$totpProvisionOk = is_array($totpProvision);
$totpVerifyOk = verifyTotp($totpSecret, $totpCodeValue);
$totpEnableOk = enableAdminTotp('admin');
$totpEnabledOk = isAdminTotpEnabled('admin');
$totpDisableOk = disableAdminTotp('admin');
$totpDisabledOk = !isAdminTotpEnabled('admin');
it('totp provision verify enable disable works', $totpProvisionOk && $totpVerifyOk && $totpEnableOk && $totpEnabledOk && $totpDisableOk && $totpDisabledOk);


$injectionUser = getCurrentStudent("' OR '1'='1");
it('SQL injection in username lookup is neutralized', $injectionUser === null);
$injectionSearch = getDynamicsPage((int)$student1['pk'], false, array('keyword' => "' OR '1'='1 --"), 1, 5);
it('SQL injection in search keyword is neutralized', is_array($injectionSearch) && isset($injectionSearch['total']));
echo PHP_EOL;
echo sprintf("%d integration tests, %d failures%s", $tests, $failures, PHP_EOL);
exit($failures > 0 ? 1 : 0);