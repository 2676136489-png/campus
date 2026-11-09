<?php
/**
 * AI 助手后端：代理 Dify chat-messages API，为学生端聊天页服务。
 *
 * 鉴权：仅登录学生可用（管理员用官方气泡，不经过这里）。
 * 会话记忆：conversation_id 由前端保存并随请求带回，Dify 侧天然续聊。
 * 配置（.env）：DIFY_API_KEY（Dify 控制台 → API 访问 → 生成，学生版应用）、
 *             DIFY_BASE_URL（默认 https://api.dify.ai/v1，以 Dify 控制台「访问 API」页显示的 Endpoint 为准）。
 */
require_once __DIR__ . '/lib/bootstrap.php';
secureSessionStart();
require_once __DIR__ . '/p_manageDB.php';

$student = requireStudentLogin();

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'send') {
    if (!verifyCsrf()) {
        aiChatJson(['ok' => false, 'error' => 'bad_csrf'], 403);
    }
    aiChatSend($student);
}
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'history') {
    aiChatHistory($student);
}
aiChatJson(['ok' => false, 'error' => 'unknown_action'], 400);

function aiChatJson($payload, $code = 200)
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function aiChatConfig()
{
    $key = trim(envValue('DIFY_API_KEY', ''));
    $base = rtrim(trim(envValue('DIFY_BASE_URL', 'https://api.dify.ai/v1')), '/');
    if ($key === '' || $base === '') {
        return null;
    }
    return [$key, $base];
}

/**
 * POST 调 Dify 并聚合流式响应（Agent 应用只支持 streaming，不支持 blocking）。
 * SSE 事件：agent_message/message 的 answer 为增量文本，拼接即完整回答；
 * message_end 带 conversation_id；error 事件视为失败。
 * @return array [httpStatus, ['ok'=>bool,'answer'=>string,'conversationId'=>string]]
 */
function aiChatStreamCall($url, $key, $body)
{
    $answer = '';
    $conversationId = '';
    $buffer = '';
    $sawEnd = false;
    $status = 0;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
            'Accept: text/event-stream',
        ],
        CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$answer, &$conversationId, &$buffer, &$sawEnd) {
            $buffer .= $chunk;
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);
                if ($line === '' || strpos($line, 'data:') !== 0) {
                    continue;
                }
                $payload = json_decode(trim(substr($line, 5)), true);
                if (!is_array($payload)) {
                    continue;
                }
                $ev = (string)($payload['event'] ?? '');
                if ($ev === 'agent_message' || $ev === 'message') {
                    $answer .= (string)($payload['answer'] ?? '');
                    if ($conversationId === '' && !empty($payload['conversation_id'])) {
                        $conversationId = (string)$payload['conversation_id'];
                    }
                } elseif ($ev === 'message_end') {
                    $sawEnd = true;
                    if (!empty($payload['conversation_id'])) {
                        $conversationId = (string)$payload['conversation_id'];
                    }
                } elseif ($ev === 'error') {
                    return -1;
                }
            }
            return strlen($chunk);
        },
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_UNICODE));
    curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $errno = curl_errno($ch);
    curl_close($ch);

    if ($errno !== 0 || ($status >= 400 || (!$sawEnd && $answer === ''))) {
        return [502, ['ok' => false, 'error' => 'ai_unreachable']];
    }
    return [200, ['ok' => true, 'answer' => $answer, 'conversationId' => $conversationId]];
}

/**
 * GET 类普通 JSON 接口（历史消息等，非流式）。
 * @return array [httpStatus, array]
 */
function aiChatJsonCall($method, $url, $key)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $key,
            'Accept: application/json',
        ],
    ]);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $errno = curl_errno($ch);
    curl_close($ch);
    if ($raw === false || $errno !== 0) {
        return [502, ['ok' => false, 'error' => 'ai_unreachable']];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return [502, ['ok' => false, 'error' => 'ai_bad_response']];
    }
    return [$status, $data];
}

/**
 * 剥离推理模型（DeepSeek-R1 类）的 <think> 思维链，只留面向用户的正文。
 */
function aiChatStripThinking($text)
{
    $text = preg_replace('/<think>.*?<\/think>/is', '', (string)$text);
    return trim((string)$text);
}

/**
 * 实体链接化兜底：回答中出现的站内学生姓名自动转为【同学|pk|姓名】引用卡片标记。
 * Agent 的输出格式受提示词影响可能不稳定，这里在后端保证卡片一定渲染。
 * 已有标记先占位保护，避免双重包裹。
 */
function aiChatLinkEntities($text)
{
    $text = (string)$text;

    // 1. 占位保护 Agent 自己输出的标记
    $marks = [];
    $text = preg_replace_callback('/【(?:同学|动态)\|(\d+)\|([^】]{1,40})】/u', function ($m) use (&$marks) {
        $marks[] = $m[0];
        return "\x00M" . (count($marks) - 1) . "\x00";
    }, $text);

    // 2. 拉取站内学生姓名 → pk 映射（长名优先，避免子串误配）
    $conn = dbConnect();
    $r = $conn->query("SELECT s.pk, s.name FROM student s INNER JOIN user u ON u.pk = s.pk WHERE u.userType = 's' AND u.status = 'V'");
    $map = [];
    while ($row = $r->fetch_assoc()) {
        $map[$row['name']] = (int)$row['pk'];
    }
    $conn->close();
    if ($map) {
        uksort($map, function ($a, $b) {
            return mb_strlen($b) - mb_strlen($a);
        });
        $names = implode('|', array_map(function ($n) {
            return preg_quote($n, '/');
        }, array_keys($map)));
        $re = '/\*\*(' . $names . ')\*\*|(' . $names . ')/u';
        $text = preg_replace_callback($re, function ($m) use ($map) {
            $name = ($m[1] ?? '') !== '' ? $m[1] : $m[2];
            return '【同学|' . $map[$name] . '|' . $name . '】';
        }, $text);
    }

    // 3. 恢复 Agent 原生标记
    $text = preg_replace_callback('/\x00M(\d+)\x00/', function ($m) use ($marks) {
        return $marks[(int)$m[1]];
    }, $text);
    return $text;
}

function aiChatSend(array $student)
{
    if (!aiChatConfig()) {
        aiChatJson(['ok' => false, 'error' => 'ai_not_configured'], 503);
    }
    [$key, $base] = aiChatConfig();

    $question = trim((string)($_POST['question'] ?? ''));
    if ($question === '' || mb_strlen($question) > 500) {
        aiChatJson(['ok' => false, 'error' => 'bad_question'], 400);
    }
    $conversationId = preg_match('/^[A-Za-z0-9-]{8,64}$/', (string)($_POST['conversationId'] ?? ''))
        ? (string)$_POST['conversationId'] : '';

    [$status, $data] = aiChatStreamCall($base . '/chat-messages', $key, [
        'inputs' => new stdClass(),
        'query' => $question,
        'response_mode' => 'streaming',
        'conversation_id' => $conversationId,
        'user' => 'stu-' . $student['userName'],
    ]);
    if ($status !== 200 || empty($data['answer'])) {
        aiChatJson(['ok' => false, 'error' => 'ai_upstream_error'], 502);
    }
    aiChatJson([
        'ok' => true,
        'answer' => aiChatLinkEntities(aiChatStripThinking((string)$data['answer'])),
        'conversationId' => (string)($data['conversationId'] ?? ''),
    ]);
}

function aiChatHistory(array $student)
{
    if (!aiChatConfig()) {
        aiChatJson(['ok' => false, 'error' => 'ai_not_configured'], 503);
    }
    [$key, $base] = aiChatConfig();

    $conversationId = preg_match('/^[A-Za-z0-9-]{8,64}$/', (string)($_GET['conversationId'] ?? ''))
        ? (string)$_GET['conversationId'] : '';
    if ($conversationId === '') {
        aiChatJson(['ok' => true, 'messages' => []]);
    }
    $url = $base . '/messages?' . http_build_query([
        'conversation_id' => $conversationId,
        'user' => 'stu-' . $student['userName'],
        'limit' => 30,
    ]);
    [$status, $data] = aiChatJsonCall('GET', $url, $key);
    if ($status !== 200 || !isset($data['data']) || !is_array($data['data'])) {
        // 会话不存在（过期/清空）时让前端从新会话开始，而不是报错
        aiChatJson(['ok' => true, 'messages' => []]);
    }
    $messages = [];
    foreach ($data['data'] as $m) {
        if (!empty($m['query'])) {
            $messages[] = ['role' => 'user', 'text' => (string)$m['query']];
        }
        if (!empty($m['answer'])) {
            $messages[] = ['role' => 'assistant', 'text' => aiChatLinkEntities(aiChatStripThinking((string)$m['answer']))];
        }
    }
    aiChatJson(['ok' => true, 'messages' => $messages]);
}
