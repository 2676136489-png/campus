<?php
require_once __DIR__ . '/../p_manageDB.php';

$conn = dbConnect();
$conn->begin_transaction();
try {
    $tables = array(
        'dynamic_photo',
        'comment',
        '`like`',
        'notification',
        'notification_pref',
        'follow',
        'block',
        'message',
        'conversation_setting',
        'privacy',
        'report',
        'feedback',
        'password_reset',
        'login_attempt',
        'action_log',
        'audit_log',
        'announcement',
        'sensitive_word',
        'dynamic',
        'student'
    );
    foreach ($tables as $table) {
        $conn->query('DELETE FROM ' . $table);
    }
    $stmt = $conn->prepare("DELETE FROM `user` WHERE `userType` != 'a'");
    $stmt->execute();
    $stmt->close();

    $hash = password_hash('Admin@123456', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE `user` SET `password` = ? WHERE `userName` = 'admin' AND `userType` = 'a'");
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $stmt->close();
    $conn->query("UPDATE `user` SET `totp_enabled` = 0, `totp_secret` = NULL WHERE `userType` = 'a'");

    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    fwrite(STDERR, 'E2E reset failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
$conn->close();
echo "E2E data reset complete.\n";