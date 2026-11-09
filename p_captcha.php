<?php
require_once __DIR__ . '/p_dbInfo.php';
require_once __DIR__ . '/lib/autoload.php';

secureSessionStart();

if (APP_ENV === 'development' && isset($_GET['reveal'])) {
    $code = captchaGenerate(4);
    $_SESSION['captcha_answer'] = $code;
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['code' => $code]);
    exit;
}

$code = captchaGenerate(4);
$_SESSION['captcha_answer'] = $code;
captchaRender($code);