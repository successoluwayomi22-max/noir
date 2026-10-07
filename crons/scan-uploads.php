<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap.php';

$db = Noir\Core\Container::get('db');
$exists = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
$exists->execute(['upload_scan_queue']);
if (!$exists->fetchColumn()) { fwrite(STDERR, "upload_scan_queue table is not available. Run migrations first.\n"); exit(1); }

$scanner = trim((string)($_ENV['MALWARE_SCANNER_COMMAND'] ?? getenv('MALWARE_SCANNER_COMMAND') ?: ''));
$rows = $db->query("SELECT id,disk_path FROM upload_scan_queue WHERE status='pending' ORDER BY id LIMIT 50")->fetchAll();
foreach ($rows as $row) {
    $id = (int)$row['id'];
    $path = (string)$row['disk_path'];
    if (!is_file($path)) {
        $db->prepare("UPDATE upload_scan_queue SET status='failed',scanner=?,scan_result=?,scanned_at=NOW() WHERE id=?")->execute(['local', 'File was missing when scan ran.', $id]);
        continue;
    }
    if ($scanner === '') {
        $db->prepare("UPDATE upload_scan_queue SET status='failed',scanner=?,scan_result=?,scanned_at=NOW() WHERE id=?")->execute(['not-configured', 'Set MALWARE_SCANNER_COMMAND to enable production malware scanning.', $id]);
        continue;
    }
    $command = str_contains($scanner, '{file}')
        ? str_replace('{file}', escapeshellarg($path), $scanner)
        : $scanner . ' ' . escapeshellarg($path);
    exec($command, $output, $code);
    $status = $code === 0 ? 'clean' : 'flagged';
    $db->prepare('UPDATE upload_scan_queue SET status=?,scanner=?,scan_result=?,scanned_at=NOW() WHERE id=?')->execute([$status, $scanner, mb_substr(implode("\n", $output), 0, 4000), $id]);
}
echo '[' . date('Y-m-d H:i:s') . '] scanned=' . count($rows) . "\n";
