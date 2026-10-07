<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Noir\App\Services\QueueService;

$once = in_array('--once', $argv, true);
$queue = 'default';

echo "[" . date('Y-m-d H:i:s') . "] Noir Queue Worker started (Queue: $queue)" . PHP_EOL;

$jobsProcessed = 0;

while (true) {
    $job = QueueService::reserveNext($queue);

    if ($job) {
        $jobId = (int)$job['id'];
        $jobClass = (string)$job['job_class'];
        echo "[" . date('Y-m-d H:i:s') . "] Processing job #$jobId [$jobClass]..." . PHP_EOL;

        try {
            QueueService::handle($job);
            QueueService::complete($jobId);
            echo "[" . date('Y-m-d H:i:s') . "] Completed job #$jobId." . PHP_EOL;
            $jobsProcessed++;
        } catch (\Throwable $e) {
            echo "[" . date('Y-m-d H:i:s') . "] Failed job #$jobId: " . $e->getMessage() . PHP_EOL;
            QueueService::fail($job, $e);
        }
    } else {
        if ($once) {
            echo "[" . date('Y-m-d H:i:s') . "] No more jobs in queue. Worker exiting (--once)." . PHP_EOL;
            break;
        }
        // Sleep 1 second before checking for new jobs to prevent CPU spin
        sleep(1);
    }
}
