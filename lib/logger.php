<?php

/**
 * 统一应用日志：写入 var/logs/app.log。
 * @param string $level debug|info|warning|error
 * @param string $message 日志内容
 * @param array $context 结构化上下文
 */
function writeAppLog($level, $message, array $context = [])
{
    $dir = __DIR__ . '/../var/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    $line = date('Y-m-d H:i:s')
        . ' [' . strtoupper($level) . '] '
        . $message
        . ($context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '')
        . PHP_EOL;
    @file_put_contents($dir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

/**
 * 捕获 PHP 致命错误并写入日志。
 */
function logFatalErrors()
{
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        writeAppLog('error', $error['message'], [
            'file' => $error['file'],
            'line' => $error['line'],
        ]);
    }
}
