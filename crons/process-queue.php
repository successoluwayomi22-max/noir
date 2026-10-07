<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/bootstrap.php';

use Noir\App\Services\QueueService;

$logDir = BASE_PATH . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}

$lockFile = $logDir . '/.queue-cron.lock';
$lockHandle = @fopen($lockFile, 'c+');

if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Queue worker cron is already running. Skipping." . PHP_EOL;
    exit(0);
}

$queue = 'default';
$maxJobs = 50;

// Parse CLI flags
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--queue=')) {
        $queue = substr($arg, 8);
    } elseif (str_starts_with($arg, '--max-jobs=')) {
        $maxJobs = max(1, (int)substr($arg, 11));
    }
}

$logPath = $logDir . '/queue.log';
$startTime = microtime(true);
$processed = 0;
$failed = 0;

echo "[" . date('Y-m-d H:i:s') . "] Starting Noir Queue Cron (Queue: {$queue}, Max Jobs: {$maxJobs})..." . PHP_EOL;

while ($processed + $failed < $maxJobs) {
    $job = QueueService::reserveNext($queue);
    if (!$job) {
        break; // No more available jobs
    }

    $jobId = (int)$job['id'];
    $jobClass = (string)($job['job_class'] ?? 'unknown');

    try {
        QueueService::handle($job);
        QueueService::complete($jobId);
        $processed++;
        $msg = "[" . date('Y-m-d H:i:s') . "] COMPLETED job #{$jobId} [{$jobClass}]" . PHP_EOL;
        echo $msg;
        @file_put_contents($logPath, $msg, FILE_APPEND | LOCK_EX);
    } catch (\Throwable $e) {
        $failed++;
        QueueService::fail($job, $e);
        $errMsg = "[" . date('Y-m-d H:i:s') . "] FAILED job #{$jobId} [{$jobClass}]: " . $e->getMessage() . PHP_EOL;
        echo $errMsg;
        @file_put_contents($logPath, $errMsg, FILE_APPEND | LOCK_EX);
    }
}

$duration = round(microtime(true) - $startTime, 3);
$summary = "[" . date('Y-m-d H:i:s') . "] Queue cron finished in {$duration}s. Processed: {$processed}, Failed: {$failed}." . PHP_EOL;
echo $summary;
@file_put_contents($logPath, $summary, FILE_APPEND | LOCK_EX);

flock($lockHandle, LOCK_UN);
fclose($lockHandle);
@unlink($lockFile);
exit(0);
