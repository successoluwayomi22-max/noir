<?php
require 'bootstrap.php';
$db = Noir\Core\Container::get('db');

$statements = [
    "ALTER TABLE activity_logs ADD COLUMN actor_name VARCHAR(120) NULL AFTER user_id",
    "ALTER TABLE activity_logs ADD COLUMN subject_label VARCHAR(255) NULL AFTER subject_id",
    "ALTER TABLE activity_logs ADD COLUMN platform ENUM('web','mobile','system','cron') NOT NULL DEFAULT 'web' AFTER metadata",
    "ALTER TABLE activity_logs ADD COLUMN user_agent VARCHAR(500) NULL AFTER platform",
    "ALTER TABLE activity_logs ADD INDEX idx_platform_created (platform, created_at)",
    "ALTER TABLE activity_logs ADD INDEX idx_event_created (event, created_at)",
];

foreach ($statements as $stmt) {
    try {
        $db->exec($stmt);
        echo "OK: " . substr($stmt, 0, 80) . "\n";
    } catch (Exception $e) {
        echo "SKIP: " . $e->getMessage() . "\n";
    }
}

$cols = $db->query('SHOW COLUMNS FROM activity_logs')->fetchAll(PDO::FETCH_ASSOC);
echo "\nFinal schema:\n";
foreach ($cols as $c) {
    echo "  " . $c['Field'] . " | " . $c['Type'] . "\n";
}
