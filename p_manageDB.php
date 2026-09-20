<?php
require_once __DIR__ . '/p_dbInfo.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/autoload.php';

// 页面加载时自动执行数据库初始化
ensureSchema();
