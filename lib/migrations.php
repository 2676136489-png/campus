<?php
/**
 * Versioned SQL migrations.
 */

/**
 * @return string[]
 */
function migrationFiles()
{
    $dir = __DIR__ . '/../migrations';
    $files = glob($dir . '/*.sql');
    if ($files === false) {
        return [];
    }
    sort($files);
    return $files;
}

/**
 * 迁移文件里定义的最大版本号（纯文件扫描，不连数据库）。
 * 用于在每次请求时快速判断「结构是否已就绪」，避免无谓地跑完整迁移流程。
 *
 * @return int 没有任何迁移文件时返回 0
 */
function latestMigrationVersion()
{
    $max = 0;
    foreach (migrationFiles() as $file) {
        if (preg_match('/^(\d+)_/', basename($file), $m)) {
            $max = max($max, (int)$m[1]);
        }
    }
    return $max;
}

/**
 * Apply pending migrations and record them in schema_migrations.
 */
function runMigrations()
{
    $server = dbConnect(false);
    $server->query("CREATE DATABASE IF NOT EXISTS `" . DBNAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $server->close();

    $conn = dbConnect(true);
    $conn->query("CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `version` int unsigned NOT NULL,
        `name` varchar(255) NOT NULL,
        `appliedAt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`version`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $applied = [];
    $result = $conn->query("SELECT `version` FROM `schema_migrations`");
    while ($row = $result->fetch_assoc()) {
        $applied[(int)$row['version']] = true;
    }
    $result->free();

    foreach (migrationFiles() as $file) {
        $base = basename($file);
        if (!preg_match('/^(\d+)_/', $base, $m)) {
            continue;
        }
        $version = (int)$m[1];
        if (isset($applied[$version])) {
            continue;
        }
        $sql = (string)file_get_contents($file);
        $statements = array_filter(array_map('trim', preg_split('/;\s*/', $sql)));
        foreach ($statements as $statement) {
            if ($statement !== '') {
                $conn->query($statement);
            }
        }
        $stmt = $conn->prepare("INSERT INTO `schema_migrations` (`version`, `name`) VALUES (?, ?)");
        $stmt->bind_param('is', $version, $base);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close();
}
