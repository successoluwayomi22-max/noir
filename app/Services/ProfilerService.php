<?php

declare(strict_types=1);

namespace Noir\App\Services;

final class ProfilerService
{
    private static float $startTime = 0.0;
    private static array $milestones = [];
    private static array $queries = [];

    public static function start(): void
    {
        self::$startTime = microtime(true);
        self::$milestones = [];
        self::$queries = [];
        self::checkpoint('Application Bootstrap');
    }

    public static function checkpoint(string $name): void
    {
        if (self::$startTime === 0.0) {
            self::$startTime = microtime(true);
        }
        $elapsed = round((microtime(true) - self::$startTime) * 1000, 3);
        self::$milestones[] = [
            'name' => $name,
            'time_ms' => $elapsed,
            'timestamp' => microtime(true)
        ];
    }

    public static function logQuery(string $sql, float $durationMs): void
    {
        self::$queries[] = [
            'sql' => $sql,
            'duration_ms' => round($durationMs, 3),
            'time' => date('H:i:s')
        ];
    }

    /**
     * Return waterfall breakdown of the current request.
     */
    public static function getWaterfall(): array
    {
        $waterfall = [];
        $count = count(self::$milestones);
        for ($i = 0; $i < $count; $i++) {
            $current = self::$milestones[$i];
            $duration = isset(self::$milestones[$i + 1])
                ? round(self::$milestones[$i + 1]['time_ms'] - $current['time_ms'], 3)
                : 0.25;
            $waterfall[] = [
                'stage' => $current['name'],
                'start_ms' => $current['time_ms'],
                'duration_ms' => max(0.05, $duration),
            ];
        }

        return [
            'trace_id' => defined('NOIR_TRACE_ID') ? NOIR_TRACE_ID : 'trc_' . substr(md5((string)microtime(true)), 0, 12),
            'total_duration_ms' => round((microtime(true) - (self::$startTime ?: microtime(true))) * 1000, 2),
            'stages' => $waterfall,
            'queries' => self::$queries
        ];
    }

    /**
     * Provide sample real-time simulated waterfall spans for the APM Waterfall view.
     */
    public static function getSampleTraces(): array
    {
        return [
            [
                'trace_id' => 'trc_' . substr(md5('checkout_flash'), 0, 10),
                'route' => 'POST /checkout/payment',
                'status' => 200,
                'total_ms' => 28.45,
                'client_ip' => '127.0.0.1',
                'stages' => [
                    ['name' => 'Middleware & CSP Security', 'duration_ms' => 0.42, 'color' => '#687158'],
                    ['name' => 'Session & Cart Resolution', 'duration_ms' => 1.15, 'color' => '#8e967a'],
                    ['name' => 'Inventory FOR UPDATE Lock', 'duration_ms' => 1.82, 'color' => '#d97706'],
                    ['name' => 'Paystack API Handshake', 'duration_ms' => 22.80, 'color' => '#2563eb'],
                    ['name' => 'Order Commit & Event Dispatch', 'duration_ms' => 2.26, 'color' => '#16a34a'],
                ]
            ],
            [
                'trace_id' => 'trc_' . substr(md5('catalog_explore'), 0, 10),
                'route' => 'GET /collections/all',
                'status' => 200,
                'total_ms' => 4.12,
                'client_ip' => '192.168.1.45',
                'stages' => [
                    ['name' => 'Routing & Middleware', 'duration_ms' => 0.38, 'color' => '#687158'],
                    ['name' => 'Database Catalog Index Scan', 'duration_ms' => 1.94, 'color' => '#d97706'],
                    ['name' => 'Blade/PHP View Render', 'duration_ms' => 1.55, 'color' => '#9333ea'],
                    ['name' => 'Output Flush & Response', 'duration_ms' => 0.25, 'color' => '#16a34a'],
                ]
            ],
            [
                'trace_id' => 'trc_' . substr(md5('health_probe'), 0, 10),
                'route' => 'GET /health',
                'status' => 200,
                'total_ms' => 1.48,
                'client_ip' => '127.0.0.1',
                'stages' => [
                    ['name' => 'Load Balancer Handshake', 'duration_ms' => 0.22, 'color' => '#687158'],
                    ['name' => 'MariaDB Atomic Ping', 'duration_ms' => 0.62, 'color' => '#d97706'],
                    ['name' => 'Storage & Cache Validation', 'duration_ms' => 0.41, 'color' => '#059669'],
                    ['name' => 'JSON Telemetry Serialization', 'duration_ms' => 0.23, 'color' => '#16a34a'],
                ]
            ]
        ];
    }
}
