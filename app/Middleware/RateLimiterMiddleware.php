<?php

declare(strict_types=1);

namespace Noir\App\Middleware;

use Noir\App\Services\CacheService;
use Noir\Core\Container;
use Noir\Core\Request;
use PDO;

final class RateLimiterMiddleware
{
    private const DEFAULT_WINDOW = 60;

    public function handle(Request $request): void
    {
        $ip = (string)($_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '127.0.0.1');

        if (str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $db = null;
        try {
            $db = Container::get('db');
        } catch (\Throwable) {}

        $now = time();

        // 1. Check if this IP is already blocked in the database
        if ($db instanceof PDO) {
            try {
                $checkStmt = $db->prepare('SELECT id, reason, blocked_until, is_permanent FROM blocked_ips WHERE ip_address = ? LIMIT 1');
                $checkStmt->execute([$ip]);
                $blocked = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($blocked) {
                    $isExpired = !$blocked['is_permanent'] && strtotime((string)$blocked['blocked_until']) <= $now;
                    $isLoopback = in_array($ip, ['127.0.0.1', '::1', 'localhost'], true);
                    $isAdminRoute = str_contains($request->path, '/admin');

                    if ($isLoopback || ($isAdminRoute && !empty($_SESSION['user']['account_type']) && in_array($_SESSION['user']['account_type'], ['staff', 'admin'], true))) {
                        if ($isLoopback) {
                            $db->prepare("DELETE FROM blocked_ips WHERE ip_address IN ('127.0.0.1', '::1', 'localhost')")->execute();
                        }
                    } elseif (!$isExpired) {
                        http_response_code(403);
                        if (str_starts_with($request->path, '/api/') || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
                            json([
                                'error' => 'Forbidden',
                                'message' => 'Access Denied: Your IP address (' . $ip . ') has been blocked due to excessive requests.',
                                'blocked' => true,
                                'reason' => $blocked['reason'],
                                'blocked_until' => $blocked['blocked_until'],
                            ], 403);
                        }
                        require BASE_PATH . '/app/Views/errors/403.php';
                        exit;
                    } else {
                        // Lift expired block
                        $db->prepare('DELETE FROM blocked_ips WHERE id = ?')->execute([(int)$blocked['id']]);
                    }
                }
            } catch (\Throwable) {}
        }

        // 2. Determine Endpoint Tier & Immunity
        $user = null;
        try {
            $user = \Noir\Core\Auth::user();
        } catch (\Throwable) {}

        $userRole = (string)($user['role'] ?? '');
        $isAdmin = in_array($userRole, ['admin', 'super_admin', 'staff', 'manager'], true);

        // Admins are completely immune to automated rate limit bans
        if ($isAdmin) {
            return;
        }

        $limits = $this->resolveTierLimits($request);
        $maxAttempts = $limits['max_attempts'];
        $autoBlockThreshold = $limits['auto_block_threshold'];
        $windowSeconds = $limits['window_seconds'];
        $tierName = $limits['tier'];

        // Differentiate bucket key: for logged-in customers use user ID to protect shared Wi-Fi
        $userId = (int)($user['id'] ?? $user['user_id'] ?? 0);
        $identity = ($userId > 0) ? 'usr_' . $userId : 'ip_' . md5($ip);

        $bucketKey = 'rl:' . $identity . ':' . $tierName;
        $bucket = CacheService::increment($bucketKey, $windowSeconds);

        // 3. Evaluate limits and auto-block if threshold exceeded
        if ($bucket['count'] > $maxAttempts) {
            $retryAfter = max(1, (int)$bucket['reset_at'] - $now);
            header('Retry-After: ' . $retryAfter);

            // Auto-block IP in database only if abusive flood reaches autoBlockThreshold
            if ($db instanceof PDO && $bucket['count'] >= $autoBlockThreshold) {
                $blockDurationSeconds = ($bucket['count'] >= ($autoBlockThreshold * 2)) ? 86400 * 7 : 3600; // 1 hr, or 7 days for massive floods
                $blockedUntil = date('Y-m-d H:i:s', $now + $blockDurationSeconds);
                $reason = 'Rate limit exceeded: ' . $bucket['count'] . ' requests on ' . $request->path . ' (' . $tierName . ')';

                try {
                    $insertBlock = $db->prepare(
                        'INSERT INTO blocked_ips (ip_address, reason, request_count, route, blocked_until)
                         VALUES (?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE request_count = ?, route = ?, blocked_until = ?'
                    );
                    $insertBlock->execute([
                        $ip,
                        $reason,
                        $bucket['count'],
                        $request->path,
                        $blockedUntil,
                        $bucket['count'],
                        $request->path,
                        $blockedUntil,
                    ]);

                    // Send alert to security_alerts table
                    $db->prepare(
                        'INSERT INTO security_alerts (severity, event, subject_type, status, metadata)
                         VALUES (?, ?, ?, ?, ?)'
                    )->execute([
                        'critical',
                        'security.ip_blocked',
                        'ip',
                        'open',
                        json_encode([
                            'ip' => $ip,
                            'route' => $request->path,
                            'attempts' => $bucket['count'],
                            'reason' => $reason,
                            'tier' => $tierName,
                            'blocked_until' => $blockedUntil,
                            'user_agent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                        ], JSON_UNESCAPED_SLASHES),
                    ]);

                    // Send alert to activity_logs table for immediate Live System Logs visibility
                    $db->prepare(
                        'INSERT INTO activity_logs (event, actor_name, subject_type, subject_label, ip_address, platform, metadata)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    )->execute([
                        'security.ip_blocked',
                        'Firewall Shield',
                        'security',
                        'Auto-Blocked IP: ' . $ip . ' (Flood on ' . $request->path . ')',
                        $ip,
                        str_starts_with($request->path, '/api/') ? 'mobile' : 'web',
                        json_encode([
                            'attempts' => $bucket['count'],
                            'route' => $request->path,
                            'tier' => $tierName,
                            'blocked_until' => $blockedUntil,
                        ], JSON_UNESCAPED_SLASHES),
                    ]);
                } catch (\Throwable) {}
            }

            if (str_starts_with($request->path, '/api/') || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
                json([
                    'error' => 'Too many requests.',
                    'message' => 'Too many attempts. Please wait ' . $retryAfter . ' seconds before trying again.',
                    'retry_after' => $retryAfter,
                ], 429);
            }

            http_response_code(429);
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            if (str_contains($request->path, 'checkout')) {
                $redirectPath = '/checkout';
            } elseif (str_starts_with($request->path, '/admin')) {
                $redirectPath = '/admin/login';
            } else {
                $redirectPath = '/login';
            }

            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['error'] = 'Too many attempts. Please wait ' . $retryAfter . ' seconds before trying again.';
            }

            header('Location: ' . ($base ?: '') . $redirectPath . '?error=rate_limit');
            exit;
        }
    }

    /**
     * Resolve tier limits dynamically based on route sensitivity.
     */
    private function resolveTierLimits(Request $request): array
    {
        $path = strtolower($request->path);

        // Sensitive Payment Processing Endpoints (Anti Card-Testing)
        if (
            str_contains($path, '/checkout/pay') ||
            str_contains($path, '/checkout/retry')
        ) {
            return [
                'tier' => 'payment_attempt',
                'max_attempts' => 5,
                'auto_block_threshold' => 8,
                'window_seconds' => self::DEFAULT_WINDOW,
            ];
        }

        // Sensitive Authentication Endpoints
        if (
            str_contains($path, 'login') ||
            str_contains($path, 'register') ||
            str_contains($path, 'forgot-password') ||
            str_contains($path, 'reset-password')
        ) {
            return [
                'tier' => 'auth',
                'max_attempts' => 6,
                'auto_block_threshold' => 8,
                'window_seconds' => self::DEFAULT_WINDOW,
            ];
        }

        // Cart, Wishlist, Checkout Endpoints (allow rapid clicking)
        if (
            str_contains($path, 'cart') ||
            str_contains($path, 'wishlist') ||
            str_contains($path, 'checkout')
        ) {
            return [
                'tier' => 'cart_checkout',
                'max_attempts' => 60,
                'auto_block_threshold' => 90,
                'window_seconds' => self::DEFAULT_WINDOW,
            ];
        }

        // General Browsing / Store Catalogue (generous allowance)
        return [
            'tier' => 'general_browsing',
            'max_attempts' => 120,
            'auto_block_threshold' => 180,
            'window_seconds' => self::DEFAULT_WINDOW,
        ];
    }
}
