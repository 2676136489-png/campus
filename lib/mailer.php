<?php
/**
 * 系统邮件发送：登录 OTP 与找回密码共用。
 *
 * 通道由 .env 的 MAIL_TRANSPORT 决定：
 *  - smtp：真实发信，走 465 端口隐式 SSL（云服务器普遍封 25 端口，587 STARTTLS 不做）；
 *  - log ：写入 var/logs/mail.log，本地开发与联调用；
 *  - none：不发信、返回失败。生产默认值，宁可诚实失败也不静默吞掉验证码。
 *
 * 注意：日志必须放 var/logs/（站点根目录的 .log 会被 Web 直接下载，泄露验证码）。
 */

/**
 * 当前邮件通道是否可用（凭据齐不齐）。
 * @return bool
 */
function mailTransportReady()
{
    if (MAIL_TRANSPORT === 'log') {
        return true;
    }
    if (MAIL_TRANSPORT === 'smtp') {
        return SMTP_HOST !== ''
            && SMTP_USERNAME !== ''
            && SMTP_PASSWORD !== ''
            && SMTP_SENDER !== '';
    }
    return false;
}

/**
 * 登录邮箱验证码总开关：显式开关与通道可用必须同时满足。
 * 生产环境只要不配置 LOGIN_EMAIL_OTP_ENABLED / MAIL_TRANSPORT，登录流程保持原样。
 * @return bool
 */
function loginEmailOtpEnabled()
{
    return LOGIN_EMAIL_OTP_ENABLED && mailTransportReady();
}

/**
 * 统一发信入口。
 * @param string $to 收件人邮箱
 * @param string $subject 主题
 * @param string $body 纯文本正文
 * @return bool
 */
function sendSystemEmail($to, $subject, $body)
{
    $to = trim((string)$to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (MAIL_TRANSPORT === 'smtp') {
        return smtpSend($to, $subject, $body);
    }
    if (MAIL_TRANSPORT === 'log') {
        return mailLogWrite($to, $subject, $body);
    }
    return false;
}

/**
 * 把邮件内容追加到 var/logs/mail.log。
 * @return bool
 */
function mailLogWrite($to, $subject, $body)
{
    $dir = dirname(__DIR__) . '/var/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    $line = '[' . date('Y-m-d H:i:s') . '] to=' . $to . ' subject=' . $subject . PHP_EOL
        . $body . PHP_EOL . str_repeat('-', 48) . PHP_EOL;
    return @file_put_contents($dir . '/mail.log', $line, FILE_APPEND) !== false;
}

/**
 * 最小 SMTP 客户端：465 隐式 SSL + AUTH LOGIN，纯文本、单收件人。
 * 只覆盖本项目需要的场景，不引入第三方依赖。
 * @return bool
 */
function smtpSend($to, $subject, $body)
{
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen('ssl://' . SMTP_HOST, SMTP_PORT, $errno, $errstr, 10);
    if (!$socket) {
        return false;
    }
    stream_set_timeout($socket, 10);

    $fail = function () use ($socket) {
        fclose($socket);
        return false;
    };

    // 读取一条 SMTP 应答：按 "XXX "（第 4 位是空格）判断多行应答是否结束
    $read = function () use ($socket) {
        $data = '';
        while (($line = fgets($socket, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return trim($data);
    };
    $cmd = function (string $command) use ($socket, $read) {
        fwrite($socket, $command . "\r\n");
        return $read();
    };

    if (strpos($read(), '220') !== 0) {
        return $fail();
    }
    // EHLO 域名用发件人邮箱的域名段，部分服务商（163/126 等）会校验
    $senderDomain = substr(strrchr(SMTP_SENDER, '@'), 1);
    if ($senderDomain === '' || $senderDomain === false) {
        $senderDomain = 'localhost';
    }
    if (strpos($cmd('EHLO ' . $senderDomain), '250') !== 0) {
        return $fail();
    }
    if (strpos($cmd('AUTH LOGIN'), '334') !== 0) {
        return $fail();
    }
    if (strpos($cmd(base64_encode(SMTP_USERNAME)), '334') !== 0) {
        return $fail();
    }
    if (strpos($cmd(base64_encode(SMTP_PASSWORD)), '235') !== 0) {
        return $fail();
    }
    if (strpos($cmd('MAIL FROM:<' . SMTP_SENDER . '>'), '250') !== 0) {
        return $fail();
    }
    if (strpos($cmd('RCPT TO:<' . $to . '>'), '250') !== 0) {
        return $fail();
    }
    if (strpos($cmd('DATA'), '354') !== 0) {
        return $fail();
    }

    $headers = 'From: =?UTF-8?B?' . base64_encode('校园圈子') . '?= <' . SMTP_SENDER . '>' . "\r\n"
        . 'To: <' . $to . '>' . "\r\n"
        . 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=' . "\r\n"
        . 'Date: ' . date('r') . "\r\n"
        . 'MIME-Version: 1.0' . "\r\n"
        . 'Content-Type: text/plain; charset=UTF-8' . "\r\n"
        . 'Content-Transfer-Encoding: base64';
    $payload = $headers . "\r\n\r\n" . chunk_split(base64_encode($body));
    // SMTP 规范：DATA 段里行首的点需要双写转义，单独一行 "." 表示结束
    $payload = preg_replace('/^\./m', '..', $payload);
    fwrite($socket, $payload . "\r\n.\r\n");
    if (strpos($read(), '250') !== 0) {
        return $fail();
    }
    $cmd('QUIT');
    fclose($socket);
    return true;
}
