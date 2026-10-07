<?php
declare(strict_types=1);

// CLI-only migration runner. Never expose this file through the web server.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require dirname(__DIR__) . '/bootstrap.php';

$db = Noir\Core\Container::get('db');
$migrationDir = BASE_PATH . '/database/migrations';
$files = glob($migrationDir . '/*.sql') ?: [];
sort($files, SORT_STRING);
$applied = [];
$command = strtolower((string)($argv[1] ?? 'status'));
if (!in_array($command, ['status','dry-run','migrate','baseline'], true)) { fwrite(STDERR, "Usage: php database/migrate.php [status|dry-run|migrate|baseline]\n"); exit(2); }
$trackingExists = (bool)$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='schema_migrations'")->fetchColumn();
if ($trackingExists) foreach ($db->query('SELECT migration,checksum FROM schema_migrations ORDER BY migration')->fetchAll() as $row) $applied[$row['migration']] = $row['checksum'];
if (in_array($command, ['migrate','baseline'], true)) $db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) NOT NULL UNIQUE, checksum CHAR(64) NOT NULL, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
if ($command === 'baseline' && count($applied) === 0) {
    foreach ($files as $file) { $name=basename($file); $db->prepare('INSERT INTO schema_migrations (migration,checksum) VALUES (?,?)')->execute([$name,hash_file('sha256',$file)]); echo "baselined  $name\n"; }
    exit;
}
$pending = 0;
foreach ($files as $file) {
    $name = basename($file); $checksum = hash_file('sha256', $file);
    if (isset($applied[$name])) {
        if (!hash_equals((string)$applied[$name], $checksum)) { fwrite(STDERR, "ERROR: applied migration changed: $name\n"); exit(2); }
        if ($command === 'status') echo "applied  $name\n";
        continue;
    }
    $pending++;
    if ($command === 'status' || $command === 'dry-run') { echo "pending  $name\n"; continue; }
    $sql = trim((string)file_get_contents($file));
    if ($sql === '') continue;
    try {
        $db->beginTransaction();
        $db->exec($sql);
        $stmt = $db->prepare('INSERT INTO schema_migrations (migration,checksum) VALUES (?,?)');
        $stmt->execute([$name, $checksum]);
        $db->commit(); echo "migrated  $name\n";
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        fwrite(STDERR, "FAILED: $name - {$e->getMessage()}\n"); exit(1);
    }
}
if ($command === 'status' || $command === 'dry-run') echo $pending === 0 ? "No pending migrations.\n" : "$pending pending migration(s).\n";
elseif ($command !== 'migrate') { fwrite(STDERR, "Usage: php database/migrate.php [status|dry-run|migrate]\n"); exit(2); }
