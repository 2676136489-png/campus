<?php
/**
 * Campus Circle 唯一入口 / 路由转发层。
 *
 * 历史 URL（/p_loginStu.php、/api.php、/ai_api.php 等）在重构后已迁入
 * pages/ 与 api/ 目录，这里把它们原样映射回去，保证所有既有链接、书签、
 * 外部集成和前端 fetch 调用继续可用。
 *
 * 生产环境需要在 Web 服务器上把「文件不存在」的请求回退到本文件：
 *   Nginx : location ~ \.php$ { try_files $uri /index.php?$query_string; ... }
 *   Apache: 见 .htaccess
 * 详见 docs/deploy.md。
 */

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = is_string($requestPath) ? $requestPath : '/';

/**
 * PHP 内置开发服务器（php -S 127.0.0.1:8099 index.php）：
 * 已存在的静态文件交回给 PHP 自身处理，其余请求走路由。
 * 内部目录（pages/api/lib/...）不直接对外，统一由路由负责，与生产保持一致。
 */
$internalDir = '#^/(pages|api|lib|config|database|bin|scripts|tests|deploy|benchmark|var)/#';
if (
    PHP_SAPI === 'cli-server'
    && $requestPath !== '/'
    && is_file(__DIR__ . $requestPath)
    && preg_match($internalDir, $requestPath) !== 1
) {
    return false;
}

$name = basename($requestPath);
$isFlatPath = strpos(trim($requestPath, '/'), '/') === false;

// 首页：与重构前一致，跳转到学生登录页
if ($name === '' || $name === 'index.php') {
    header('Location: p_loginStu.php');
    exit;
}

/**
 * 入口白名单：仅映射 pages/ 与 api/ 下真实存在的 .php 文件。
 * 文件名先经过严格校验，避免路径穿越与任意文件包含。
 */
if ($isFlatPath && preg_match('/^[A-Za-z0-9_-]+\.php$/', $name) === 1) {
    foreach (['pages', 'api'] as $dir) {
        $target = __DIR__ . '/' . $dir . '/' . $name;
        if (is_file($target)) {
            require $target;
            exit;
        }
    }
}

http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 · 页面不存在</title>
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="container" style="padding:80px 0;text-align:center">
    <h1 style="font-size:clamp(48px,8vw,96px);margin:0;letter-spacing:-0.04em">404</h1>
    <p style="color:var(--text-muted,#6b6b6b);margin:12px 0 28px">没有找到这个页面，它可能已被移动或删除。</p>
    <a class="btn btn-primary" href="p_loginStu.php">返回首页</a>
</div>
</body>
</html>
