<?php
/**
 * Pure helper functions that do not require a database connection.
 */

/**
 * HTML escape, prevents XSS.
 * @param mixed $value
 * @return string
 */
function h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/**
 * String length with multibyte support.
 * @param mixed $value
 * @return int
 */
function textLength($value)
{
    return function_exists('mb_strlen') ? mb_strlen((string)$value, 'UTF-8') : strlen((string)$value);
}

/**
 * Normalize comma-separated tags: dedupe, limit count and length.
 * @param string $tags
 * @return string
 */
function normalizeTags($tags)
{
    $tags = str_replace(['，', '、', ';', '；', ' '], ',', trim((string)$tags));
    $parts = array_filter(array_map('trim', explode(',', $tags)), function ($tag) {
        return $tag !== '';
    });
    $unique = [];
    foreach ($parts as $tag) {
        if (textLength($tag) > 20) {
            $tag = function_exists('mb_substr') ? mb_substr($tag, 0, 20, 'UTF-8') : substr($tag, 0, 20);
        }
        if (!in_array($tag, $unique, true)) {
            $unique[] = $tag;
        }
        if (count($unique) >= 10) {
            break;
        }
    }
    return implode(',', $unique);
}

/**
 * Validate student form fields.
 * @param array $data
 * @param bool $isRegister
 * @return true|string
 */
function validateStudentFields($data, $isRegister = true)
{
    if ($isRegister) {
        if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $data['userName'])) {
            return '用户名需为 3-30 位字母、数字或下划线。';
        }
        if (isReservedUserName($data['userName'])) {
            return '该用户名已被系统保留。';
        }
        if (strlen($data['pwd']) < 6 || !preg_match('/[A-Za-z]/', $data['pwd']) || !preg_match('/\d/', $data['pwd'])) {
            return '密码至少 6 位，并同时包含字母和数字。';
        }
        if (!preg_match('/^\d{6,20}$/', $data['stuNo'])) {
            return '学号需为 6-20 位数字。';
        }
    }
    if (trim($data['name']) === '') {
        return '姓名不能为空。';
    }
    if (!in_array((string)$data['gender'], ['0', '1'], true)) {
        return '请选择正确的性别。';
    }
    if (trim($data['birth_date']) === '' || strtotime($data['birth_date']) === false) {
        return '出生年月格式不正确。';
    }
    if (trim($data['college']) === '' || trim($data['grade']) === '' || trim($data['major']) === '') {
        return '学院、年级、专业不能为空。';
    }
    if (!preg_match('/^\d{11,13}$/', $data['phone'])) {
        return '手机号需为 11-13 位数字。';
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return 'Email 格式不正确。';
    }
    if (!preg_match('/^\d{5,20}$/', $data['QQ'])) {
        return 'QQ 号需为 5-20 位数字。';
    }
    return true;
}

/**
 * User status display text.
 * @param string $status
 * @return string
 */
function statusText($status)
{
    if ($status === 'V') {
        return '已通过';
    }
    if ($status === 'U') {
        return '已停用';
    }
    return '待审核';
}

/**
 * Gender display text.
 * @param int $gender
 * @return string
 */
function genderText($gender)
{
    return (string)$gender === '1' ? '女' : '男';
}

function mentionHtml($text)
{
    $safe = h($text);
    $safe = preg_replace('/@([A-Za-z0-9_]{3,30})/', '<span class="comment-mention">@$1</span>', $safe);
    return nl2br($safe);
}

/**
 * Build a safe asset URL, rejecting traversal.
 * @param string $path
 * @return string
 */
function assetUrl($path)
{
    $path = str_replace('\\', '/', ltrim(trim((string)$path), '/\\'));
    if ($path === '' || strpos($path, '..') !== false || strpos($path, 'uploads/') !== 0) {
        return '';
    }
    return $path;
}

/**
 * 脱敏展示手机号或邮箱
 * @param string $contact
 * @return string
 */
function maskContact($contact)
{
    $contact = trim((string)$contact);
    if ($contact === '') {
        return '';
    }
    if (preg_match('/^\d{6,}$/', $contact)) {
        $len = strlen($contact);
        if ($len <= 7) {
            return substr($contact, 0, 2) . '****' . substr($contact, -2);
        }
        return substr($contact, 0, 3) . '****' . substr($contact, -4);
    }
    $parts = explode('@', $contact, 2);
    $name = $parts[0];
    $domain = isset($parts[1]) ? $parts[1] : '';
    $visible = strlen($name) <= 2 ? $name : substr($name, 0, 2);
    return $visible . '***@' . $domain;
}

/**
 * Best-effort client IP detection.
 * @return string
 */
function clientIp()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP'] as $header) {
        if (!empty($_SERVER[$header])) {
            $parts = explode(',', $_SERVER[$header]);
            $candidate = trim($parts[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                $ip = $candidate;
                break;
            }
        }
    }
    return substr($ip, 0, 45);
}

/**
 * 是否系统保留用户名
 * @param string $name
 * @return bool
 */
function isReservedUserName($name)
{
    return in_array(strtolower(trim($name)), [
        'admin', 'root', 'system', 'campus', 'circle', 'support', 'service', 'administrator', '管理员'
    ], true);
}
/**
 * Bind optional prepared statement params without triggering PHPStan false positives.
 * @param mysqli_stmt $stmt
 * @param string $types
 * @param array $params
 * @return void
 */
function bindDynamicParams($stmt, $types, array $params)
{
    if ($types === '' || $params === []) {
        return;
    }
    $refs = [$types];
    foreach ($params as $key => $value) {
        $refs[$key + 1] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

/**
 * Extract #topic tags from dynamic content.
 * @param string $text
 * @return string[]
 */
function extractTopics($text)
{
    if (!preg_match_all('/#([\x{4e00}-\x{9fa5}A-Za-z0-9_]{1,20})/u', (string)$text, $matches)) {
        return [];
    }
    $topics = [];
    foreach ($matches[1] as $topic) {
        $topic = trim($topic);
        if ($topic !== '' && !in_array($topic, $topics, true)) {
            $topics[] = $topic;
        }
    }
    return $topics;
}

/**
 * Turn escaped dynamic content into clickable #topic links.
 * @param string $escapedHtml
 * @return string
 */
function topicHtml($escapedHtml)
{
    return preg_replace_callback('/#([\x{4e00}-\x{9fa5}A-Za-z0-9_]{1,20})/u', function ($m) {
        return '<a class="topic-link" href="p_dynamics.php?tag=' . rawurlencode($m[1]) . '">#' . $m[1] . '</a>';
    }, (string)$escapedHtml);
}
