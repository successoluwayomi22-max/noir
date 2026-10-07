<?php

declare(strict_types=1);

namespace Noir\App\Services;

final class CacheService
{
    private static ?string $driver = null;
    private static ?\Redis $redisInstance = null;
    private static string $cachePath = BASE_PATH . '/storage/cache/shards';

    /**
     * Get or initialize cache driver ('redis' or 'sharded_file').
     */
    public static function getDriver(): string
    {
        if (self::$driver !== null) {
            return self::$driver;
        }

        // 1. Check if Redis is configured and extension available
        if (extension_loaded('redis')) {
            try {
                $host = (string)($_ENV['REDIS_HOST'] ?? '127.0.0.1');
                $port = (int)($_ENV['REDIS_PORT'] ?? 6379);
                $timeout = 0.5; // fast failover

                $redis = new \Redis();
                if (@$redis->connect($host, $port, $timeout)) {
                    if (!empty($_ENV['REDIS_PASSWORD'])) {
                        $redis->auth((string)$_ENV['REDIS_PASSWORD']);
                    }
                    self::$redisInstance = $redis;
                    self::$driver = 'redis';
                    return 'redis';
                }
            } catch (\Throwable) {}
        }

        // 2. Fallback to sharded file system
        if (!is_dir(self::$cachePath)) {
            @mkdir(self::$cachePath, 0755, true);
        }

        // Probabilistic garbage collection (1% chance per request)
        if (mt_rand(1, 100) === 1) {
            self::collectGarbage();
        }

        self::$driver = 'sharded_file';
        return 'sharded_file';
    }

    /**
     * Retrieve item from cache.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (self::getDriver() === 'redis' && self::$redisInstance) {
            try {
                $val = self::$redisInstance->get('nrc:' . $key);
                return ($val === false) ? $default : json_decode($val, true);
            } catch (\Throwable) {}
        }

        $file = self::resolveShardPath($key);
        if (!is_file($file)) {
            return $default;
        }

        $content = @file_get_contents($file);
        if ($content === false) {
            return $default;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['expires_at'])) {
            @unlink($file);
            return $default;
        }

        if (time() > (int)$data['expires_at']) {
            @unlink($file);
            return $default;
        }

        return $data['value'] ?? $default;
    }

    /**
     * Store item in cache with TTL in seconds.
     */
    public static function set(string $key, mixed $value, int $ttlSeconds = 60): bool
    {
        if (self::getDriver() === 'redis' && self::$redisInstance) {
            try {
                return (bool)self::$redisInstance->setex('nrc:' . $key, max(1, $ttlSeconds), json_encode($value, JSON_UNESCAPED_SLASHES));
            } catch (\Throwable) {}
        }

        $file = self::resolveShardPath($key);
        $payload = [
            'expires_at' => time() + max(1, $ttlSeconds),
            'value' => $value,
        ];

        return (bool)@file_put_contents($file, json_encode($payload, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    /**
     * Atomic increment helper for rate-limiting.
     * Returns the incremented counter value.
     */
    public static function increment(string $key, int $windowSeconds = 60): array
    {
        if (self::getDriver() === 'redis' && self::$redisInstance) {
            try {
                $rKey = 'nrc:' . $key;
                $count = (int)self::$redisInstance->incr($rKey);
                if ($count === 1) {
                    self::$redisInstance->expire($rKey, $windowSeconds);
                }
                $ttl = (int)self::$redisInstance->ttl($rKey);
                return [
                    'count' => $count,
                    'reset_at' => time() + max(1, $ttl),
                ];
            } catch (\Throwable) {}
        }

        $file = self::resolveShardPath($key);
        $now = time();
        $bucket = null;

        if (is_file($file)) {
            $raw = @file_get_contents($file);
            if ($raw !== false) {
                $bucket = json_decode($raw, true);
            }
        }

        if (!is_array($bucket) || ($bucket['reset_at'] ?? 0) <= $now) {
            $bucket = [
                'count' => 1,
                'reset_at' => $now + $windowSeconds,
                'expires_at' => $now + $windowSeconds,
            ];
        } else {
            $bucket['count'] = (int)($bucket['count'] ?? 0) + 1;
            $bucket['expires_at'] = $bucket['reset_at'];
        }

        @file_put_contents($file, json_encode($bucket, JSON_UNESCAPED_SLASHES), LOCK_EX);

        return [
            'count' => $bucket['count'],
            'reset_at' => $bucket['reset_at'],
        ];
    }

    /**
     * Delete an item from cache.
     */
    public static function delete(string $key): bool
    {
        if (self::getDriver() === 'redis' && self::$redisInstance) {
            try {
                return (bool)self::$redisInstance->del('nrc:' . $key);
            } catch (\Throwable) {}
        }

        $file = self::resolveShardPath($key);
        if (is_file($file)) {
            return @unlink($file);
        }

        return true;
    }

    /**
     * Resolve path sharded by the first 2 characters of md5 hash.
     * Prevents thousands of files from piling up in a single directory.
     */
    private static function resolveShardPath(string $key): string
    {
        $hash = md5($key);
        $shard = substr($hash, 0, 2);
        $dir = self::$cachePath . '/' . $shard;

        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        return $dir . '/' . $hash . '.json';
    }

    /**
     * Background cleanup of expired cache shards.
     */
    private static function collectGarbage(): void
    {
        if (!is_dir(self::$cachePath)) return;

        $now = time();
        $subdirs = glob(self::$cachePath . '/*', GLOB_ONLYDIR) ?: [];
        foreach ($subdirs as $dir) {
            $files = glob($dir . '/*.json') ?: [];
            foreach ($files as $f) {
                if (filemtime($f) < ($now - 7200)) { // older than 2 hours
                    @unlink($f);
                }
            }
        }
    }
}
