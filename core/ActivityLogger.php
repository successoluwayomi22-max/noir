<?php

declare(strict_types=1);

namespace Noir\Core;

/**
 * ActivityLogger — write a structured event to the activity_logs table.
 *
 * Usage:
 *   ActivityLogger::log('order.placed', 'order', $orderId, "Order #{$num}");
 *   ActivityLogger::log('auth.login',   'user',  $userId,  $name, [], 'web');
 *   ActivityLogger::log('product.updated', 'product', $id, $name, ['field'=>'price'], 'mobile');
 */
final class ActivityLogger
{
    /**
     * Record an activity event.
     *
     * @param string $event        Dot-notation event name  e.g. 'auth.login'
     * @param string $subjectType  What was acted on        e.g. 'order', 'product', 'user'
     * @param int    $subjectId    ID of the subject
     * @param string $subjectLabel Human-readable label     e.g. "Order #NO-1042"
     * @param array  $metadata     Extra key-value context
     * @param string $platform     'web' | 'mobile' | 'system' | 'cron'
     */
    public static function log(
        string $event,
        string $subjectType  = '',
        int    $subjectId    = 0,
        string $subjectLabel = '',
        array  $metadata     = [],
        string $platform     = 'web'
    ): void {
        try {
            $db = Container::get('db');

            // Resolve current user from session (web) or from a JWT claim stored by the API layer
            $userId    = null;
            $actorName = null;

            if (isset($_SESSION['user'])) {
                $u         = $_SESSION['user'];
                $userId    = (int)($u['id'] ?? $u['user_id'] ?? 0) ?: null;
                $actorName = (string)($u['name'] ?? $u['email'] ?? '');
            } elseif (defined('CURRENT_USER_ID') && CURRENT_USER_ID) {
                $userId    = (int)CURRENT_USER_ID;
                $actorName = defined('CURRENT_USER_NAME') ? (string)CURRENT_USER_NAME : null;
            }

            $ip        = self::clientIp();
            $ua        = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
            $metaJson  = $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null;

            $db->prepare(
                'INSERT INTO activity_logs
                 (user_id, actor_name, event, subject_type, subject_id, subject_label,
                  ip_address, metadata, platform, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            )->execute([
                $userId,
                $actorName ?: null,
                $event,
                $subjectType ?: null,
                $subjectId ?: null,
                $subjectLabel ?: null,
                $ip,
                $metaJson,
                in_array($platform, ['web','mobile','system','cron'], true) ? $platform : 'web',
                $ua ?: null,
            ]);
        } catch (\Throwable) {
            // Never crash the application because of a logging failure
        }
    }

    /**
     * Convenience wrapper for mobile API actions.
     * Call from API controllers after resolving the JWT user.
     */
    public static function mobile(
        string $event,
        string $subjectType  = '',
        int    $subjectId    = 0,
        string $subjectLabel = '',
        array  $metadata     = []
    ): void {
        self::log($event, $subjectType, $subjectId, $subjectLabel, $metadata, 'mobile');
    }

    /**
     * Convenience wrapper for system/cron actions.
     */
    public static function system(
        string $event,
        string $subjectType  = '',
        int    $subjectId    = 0,
        string $subjectLabel = '',
        array  $metadata     = []
    ): void {
        self::log($event, $subjectType, $subjectId, $subjectLabel, $metadata, 'system');
    }

    // -------------------------------------------------------------------------

    private static function clientIp(): ?string
    {
        foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','REMOTE_ADDR'] as $key) {
            $val = $_SERVER[$key] ?? '';
            if ($val !== '') {
                return substr(explode(',', $val)[0], 0, 45);
            }
        }
        return null;
    }
}
