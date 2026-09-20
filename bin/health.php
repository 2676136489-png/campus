<?php
/**
 * Health check endpoint: returns JSON with app and database status.
 */

require_once __DIR__ . '/../p_manageDB.php';

header('Content-Type: application/json; charset=utf-8');
$payload = [
    'status' => 'ok',
    'time' => date('Y-m-d H:i:s'),
    'service' => 'campus-circle',
    'version' => '1.0.0',
    'database' => 'ok'
];
try {
    $conn = dbConnect();
    $conn->query('SELECT 1');
    $conn->close();
} catch (Throwable $e) {
    $payload['status'] = 'error';
    $payload['database'] = 'error';
    http_response_code(503);
}
echo json_encode($payload, JSON_UNESCAPED_UNICODE);