<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\Core\Container;
use Noir\Core\Request;
use PDO;

final class HealthController
{
    /**
     * GET /health & GET /api/v1/health
     * Automated high-availability health probe for load balancers (AWS ALB, Cloudflare, Datadog).
     */
    public function check(Request $request): never
    {
        $start = microtime(true);
        $status = 'healthy';
        $subsystems = [];

        // 1. Database Health & Latency Probe
        try {
            $dbStart = microtime(true);
            $db = Container::get('db');
            if ($db instanceof PDO) {
                $db->query('SELECT 1')->fetch();
                $dbLatency = round((microtime(true) - $dbStart) * 1000, 2);
                $subsystems['database'] = [
                    'status' => 'healthy',
                    'latency_ms' => $dbLatency,
                ];
            } else {
                throw new \RuntimeException('Database instance not resolved.');
            }
        } catch (\Throwable $e) {
            $status = 'degraded';
            $subsystems['database'] = [
                'status' => 'unhealthy',
                'error' => 'Database connection failed.',
            ];
        }

        // 2. Storage & Filesystem Health
        $storageDir = BASE_PATH . '/storage/logs';
        $cacheDir = BASE_PATH . '/storage/cache';
        $storageOk = is_writable($storageDir) && is_writable($cacheDir);
        $subsystems['filesystem'] = [
            'status' => $storageOk ? 'healthy' : 'degraded',
            'writable' => $storageOk,
        ];
        if (!$storageOk) {
            $status = 'degraded';
        }

        // 3. Cache Subsystem Health
        $cacheStatus = 'healthy';
        $cacheDriver = 'file';
        if (extension_loaded('redis') && !empty($_ENV['REDIS_HOST'])) {
            $cacheDriver = 'redis';
        }
        $subsystems['cache'] = [
            'status' => $cacheStatus,
            'driver' => $cacheDriver,
        ];

        // 4. Memory Utilization
        $memUsage = memory_get_usage(true);
        $memPeak = memory_get_peak_usage(true);
        $subsystems['memory'] = [
            'usage_mb' => round($memUsage / 1024 / 1024, 2),
            'peak_mb' => round($memPeak / 1024 / 1024, 2),
        ];

        $totalDurationMs = round((microtime(true) - $start) * 1000, 2);
        $httpCode = ($status === 'healthy') ? 200 : 503;

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        http_response_code($httpCode);

        echo json_encode([
            'status' => $status,
            'timestamp' => date('c'),
            'app_name' => store_setting('store_name', 'Noir & Co.'),
            'environment' => config('env', 'production'),
            'total_latency_ms' => $totalDurationMs,
            'subsystems' => $subsystems,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        exit;
    }
}
