<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap.php';

$db = Noir\Core\Container::get('db');
$exists = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
$exists->execute(['analytics_events']);
if (!$exists->fetchColumn()) { fwrite(STDERR, "analytics_events table is not available. Run migrations first.\n"); exit(1); }

$days = max(30, min(730, (int)($_ENV['ANALYTICS_RETENTION_DAYS'] ?? getenv('ANALYTICS_RETENTION_DAYS') ?: 180)));
$stmt = $db->prepare('DELETE FROM analytics_events WHERE created_at < DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)');
$stmt->execute();
$deleted = $stmt->rowCount();
$logDir = BASE_PATH . '/storage/logs';
if (!is_dir($logDir)) mkdir($logDir, 0775, true);
$message = '[' . date('Y-m-d H:i:s') . '] analytics_retention_days=' . $days . ', deleted=' . $deleted . "\n";
file_put_contents($logDir . '/analytics-maintenance.log', $message, FILE_APPEND | LOCK_EX);
echo $message;
