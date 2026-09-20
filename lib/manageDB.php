<?php
require_once __DIR__ . '/dbInfo.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/autoload.php';

// 页面加载时自动执行数据库初始化
ensureSchema();
