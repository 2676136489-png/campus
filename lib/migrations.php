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
