<?php
declare(strict_types=1);

namespace Noir\Core;

final class Auth
{
    public static function user(): ?array
    {
        if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
            return null;
        }

        $userId = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? 0);
        if ($userId > 0 && (!isset($_SESSION['user']['_synced_at']) || time() - (int)$_SESSION['user']['_synced_at'] > 5)) {
            try {
                $db = Container::get('db');
                if ($db instanceof \PDO) {
                    $q = $db->prepare('SELECT id, name, email, phone, role, account_type, profile_image, avatar, is_active FROM users WHERE id = ? LIMIT 1');
                    $q->execute([$userId]);
                    $row = $q->fetch(\PDO::FETCH_ASSOC);
                    if ($row) {
                        $_SESSION['user'] = array_merge($_SESSION['user'], $row);
                        $_SESSION['user']['_synced_at'] = time();
                    }
                }
            } catch (\Throwable) {}
        }

        return $_SESSION['user'];
    }

    public static function id(): ?int
    {
        $user = self::user();
        if (!$user) {
            return null;
        }
        $id = $user['id'] ?? $user['user_id'] ?? null;
        return $id !== null ? (int)$id : null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user'] = $user;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        return in_array('*', $user['permissions'] ?? [], true) || in_array($permission, $user['permissions'] ?? [], true);
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (method_exists(self::class, $name)) {
            return self::$name(...$arguments);
        }
        return null;
    }

    public static function __callStatic(string $name, array $arguments): mixed
    {
        if (method_exists(self::class, $name)) {
            return self::$name(...$arguments);
        }
        return null;
    }
}
