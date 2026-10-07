<?php

declare(strict_types=1);

namespace Noir\App\Services;

use PDO;

/**
 * Enterprise Authentication Rate Limiter & Brute-Force Prevention Service.
 *
 * Protects login and registration endpoints against credential stuffing,
 * dictionary attacks, and automated bot registration.
 */
final class SecurityRateLimiter
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_SECONDS = 900; // 15 minutes
    private const MAX_REGISTER_HOURLY = 5;
    private const REGISTER_WINDOW_SECONDS = 3600; // 1 hour

    public function __construct(private readonly PDO $db) {}

    /**
     * Checks if a login attempt is allowed for given IP and email/username.
     *
     * @return array{allowed: bool, wait_seconds: int, message?: string}
     */
    public function checkLogin(string $ip, string $identifier): array
    {
        $now = time();
        $ipKey = hash('sha256', 'login_ip_' . trim($ip));
        $userKey = hash('sha256', 'login_user_' . strtolower(trim($identifier)));

        // Check if either IP or username is locked out
        $stmt = $this->db->prepare(
            "SELECT attempts, locked_until, UNIX_TIMESTAMP(locked_until) as lock_ts
             FROM auth_rate_limits
             WHERE key_hash IN (?, ?) AND locked_until IS NOT NULL AND locked_until > NOW()
             ORDER BY locked_until DESC LIMIT 1"
        );
        $stmt->execute([$ipKey, $userKey]);
        $locked = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($locked) {
            $lockTs = (int)$locked['lock_ts'];
            $waitSeconds = max(1, $lockTs - $now);
            $minutes = ceil($waitSeconds / 60);
            return [
                'allowed' => false,
                'wait_seconds' => $waitSeconds,
                'message' => "Too many failed attempts. For your security, access is temporarily locked. Please try again in {$minutes} minute" . ($minutes > 1 ? 's' : '') . ".",
            ];
        }

        return ['allowed' => true, 'wait_seconds' => 0];
    }

    /**
     * Records a failed login attempt and applies progressive lockout if threshold reached.
     */
    public function recordLoginFailure(string $ip, string $identifier): void
    {
        $keys = [
            hash('sha256', 'login_ip_' . trim($ip)) => ['action' => 'login_ip', 'id' => trim($ip)],
            hash('sha256', 'login_user_' . strtolower(trim($identifier))) => ['action' => 'login_user', 'id' => strtolower(trim($identifier))],
        ];

        foreach ($keys as $keyHash => $meta) {
            $stmt = $this->db->prepare("SELECT id, attempts, last_attempt_at FROM auth_rate_limits WHERE key_hash = ? LIMIT 1");
            $stmt->execute([$keyHash]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $attempts = (int)$row['attempts'] + 1;
                $lockedUntil = null;
                if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
                    $lockedUntil = date('Y-m-d H:i:s', time() + self::LOGIN_LOCKOUT_SECONDS);
                }
                $update = $this->db->prepare("UPDATE auth_rate_limits SET attempts = ?, locked_until = ?, last_attempt_at = NOW() WHERE id = ?");
                $update->execute([$attempts, $lockedUntil, (int)$row['id']]);
            } else {
                $insert = $this->db->prepare(
                    "INSERT INTO auth_rate_limits (key_hash, action, identifier, attempts, locked_until)
                     VALUES (?, ?, ?, 1, NULL)"
                );
                $insert->execute([$keyHash, $meta['action'], $meta['id']]);
            }
        }
    }

    /**
     * Clears failed attempt counters upon successful login.
     */
    public function recordLoginSuccess(string $ip, string $identifier): void
    {
        $ipKey = hash('sha256', 'login_ip_' . trim($ip));
        $userKey = hash('sha256', 'login_user_' . strtolower(trim($identifier)));

        $stmt = $this->db->prepare("DELETE FROM auth_rate_limits WHERE key_hash IN (?, ?)");
        $stmt->execute([$ipKey, $userKey]);
    }

    /**
     * Checks if registration attempt is within safe limits for given IP.
     */
    public function checkRegister(string $ip): array
    {
        $now = time();
        $ipKey = hash('sha256', 'register_ip_' . trim($ip));

        $stmt = $this->db->prepare(
            "SELECT attempts, locked_until, UNIX_TIMESTAMP(locked_until) as lock_ts, UNIX_TIMESTAMP(last_attempt_at) as last_ts
             FROM auth_rate_limits
             WHERE key_hash = ? LIMIT 1"
        );
        $stmt->execute([$ipKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $lastTs = (int)$row['last_ts'];
            // If previous attempts were outside the 1 hour window, reset
            if ($now - $lastTs > self::REGISTER_WINDOW_SECONDS) {
                $del = $this->db->prepare("DELETE FROM auth_rate_limits WHERE key_hash = ?");
                $del->execute([$ipKey]);
                return ['allowed' => true, 'wait_seconds' => 0];
            }

            if ((int)$row['attempts'] >= self::MAX_REGISTER_HOURLY) {
                $waitSeconds = max(1, self::REGISTER_WINDOW_SECONDS - ($now - $lastTs));
                $minutes = ceil($waitSeconds / 60);
                return [
                    'allowed' => false,
                    'wait_seconds' => $waitSeconds,
                    'message' => "Too many accounts created from this network. Please wait {$minutes} minutes before registering another account.",
                ];
            }
        }

        return ['allowed' => true, 'wait_seconds' => 0];
    }

    /**
     * Records a registration attempt.
     */
    public function recordRegisterAttempt(string $ip): void
    {
        $ipKey = hash('sha256', 'register_ip_' . trim($ip));
        $stmt = $this->db->prepare("SELECT id, attempts FROM auth_rate_limits WHERE key_hash = ? LIMIT 1");
        $stmt->execute([$ipKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $upd = $this->db->prepare("UPDATE auth_rate_limits SET attempts = attempts + 1, last_attempt_at = NOW() WHERE id = ?");
            $upd->execute([(int)$row['id']]);
        } else {
            $ins = $this->db->prepare("INSERT INTO auth_rate_limits (key_hash, action, identifier, attempts) VALUES (?, 'register_ip', ?, 1)");
            $ins->execute([$ipKey, trim($ip)]);
        }
    }
}
