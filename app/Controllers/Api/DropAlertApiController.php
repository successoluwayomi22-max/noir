<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use Noir\Core\Request;
use PDO;

final class DropAlertApiController
{
    /**
     * Get all active and upcoming drop alerts / flash sales
     */
    public function getDropAlerts(Request $req): never
    {
        $db = Container::get('db');
        $userId = $req->params['auth_user_id'] ?? ($_SESSION['user_id'] ?? 0);

        $stmt = $db->prepare("
            SELECT d.*, 
                   IF(s.id IS NOT NULL, 1, 0) AS is_subscribed
            FROM drop_alerts d
            LEFT JOIN drop_alert_subscriptions s 
                ON d.id = s.drop_alert_id AND s.user_id = :user_id
            WHERE d.status IN ('upcoming', 'live')
            ORDER BY 
                CASE d.status 
                    WHEN 'live' THEN 1 
                    WHEN 'upcoming' THEN 2 
                    ELSE 3 
                END,
                d.starts_at ASC
        ");
        $stmt->execute(['user_id' => $userId ?: 0]);
        $drops = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $now = time();
        $formatted = array_map(function ($drop) use ($now) {
            $startTime = strtotime($drop['starts_at']);
            $endTime = !empty($drop['ends_at']) ? strtotime($drop['ends_at']) : null;

            $status = $drop['status'];
            if ($now >= $startTime && (!$endTime || $now < $endTime)) {
                $status = 'live';
            } elseif ($endTime && $now >= $endTime) {
                $status = 'ended';
            }

            return [
                'id' => (int)$drop['id'],
                'title' => $drop['title'],
                'subtitle' => $drop['subtitle'] ?? '',
                'description' => $drop['description'] ?? '',
                'image_url' => $drop['image_url'] ?? '',
                'type' => $drop['type'],
                'discount_percentage' => (int)($drop['discount_percentage'] ?? 0),
                'min_vip_tier' => $drop['min_vip_tier'],
                'starts_at' => $drop['starts_at'],
                'ends_at' => $drop['ends_at'],
                'starts_in_seconds' => max(0, $startTime - $now),
                'ends_in_seconds' => $endTime ? max(0, $endTime - $now) : 0,
                'stock_limit' => $drop['stock_limit'] ? (int)$drop['stock_limit'] : null,
                'status' => $status,
                'is_subscribed' => (bool)$drop['is_subscribed'],
            ];
        }, $drops);

        json([
            'success' => true,
            'drops' => $formatted,
            'server_time' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Subscribe / Unsubscribe to a drop alert
     */
    public function toggleSubscription(Request $req, mixed $id = null): never
    {
        $db = Container::get('db');
        $userId = $req->params['auth_user_id'] ?? ($_SESSION['user_id'] ?? 0);
        if (!$userId) {
            json(['success' => false, 'error' => 'Authentication required to subscribe to drop alerts.'], 401);
        }

        $dropId = (int)($id ?? $req->input('drop_alert_id', 0));
        if ($dropId <= 0) {
            json(['success' => false, 'error' => 'Valid drop alert ID is required.'], 400);
        }

        // Check if already subscribed
        $check = $db->prepare("SELECT id FROM drop_alert_subscriptions WHERE user_id = :uid AND drop_alert_id = :did");
        $check->execute(['uid' => $userId, 'did' => $dropId]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $del = $db->prepare("DELETE FROM drop_alert_subscriptions WHERE id = :id");
            $del->execute(['id' => $existing['id']]);

            json([
                'success' => true,
                'subscribed' => false,
                'message' => 'Alert reminder removed.',
            ]);
        }

        $ins = $db->prepare("INSERT INTO drop_alert_subscriptions (user_id, drop_alert_id) VALUES (:uid, :did)");
        $ins->execute(['uid' => $userId, 'did' => $dropId]);

        // Record a notification in customer_notifications
        $dropStmt = $db->prepare("SELECT title FROM drop_alerts WHERE id = :id");
        $dropStmt->execute(['id' => $dropId]);
        $drop = $dropStmt->fetch(PDO::FETCH_ASSOC);

        if ($drop) {
            $notif = $db->prepare("
                INSERT INTO customer_notifications (user_id, title, body, type)
                VALUES (:uid, :title, :body, 'vip_drop')
            ");
            $notif->execute([
                'uid' => $userId,
                'title' => 'VIP Drop Alert Set: ' . $drop['title'],
                'body' => 'You are on the VIP priority access list. We will notify you the instant this drop goes live.',
            ]);
        }

        json([
            'success' => true,
            'subscribed' => true,
            'message' => 'VIP Alert confirmed! You will receive priority notification when the drop goes live.',
        ]);
    }

    /**
     * Get user notifications with badges
     */
    public function getNotifications(Request $req): never
    {
        $db = Container::get('db');
        $userId = $req->params['auth_user_id'] ?? ($_SESSION['user_id'] ?? 0);
        if (!$userId) {
            json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $stmt = $db->prepare("
            SELECT id, title, body, type, read_at, created_at
            FROM customer_notifications
            WHERE user_id = :uid
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute(['uid' => $userId]);
        $notifs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $unreadCount = 0;
        foreach ($notifs as $n) {
            if (empty($n['read_at'])) {
                $unreadCount++;
            }
        }

        json([
            'success' => true,
            'unread_count' => $unreadCount,
            'notifications' => $notifs,
        ]);
    }

    /**
     * Mark notifications as read
     */
    public function markAsRead(Request $req): never
    {
        $db = Container::get('db');
        $userId = $req->params['auth_user_id'] ?? ($_SESSION['user_id'] ?? 0);
        if (!$userId) {
            json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $id = $req->input('id');
        if ($id) {
            $stmt = $db->prepare("UPDATE customer_notifications SET read_at = NOW() WHERE id = :id AND user_id = :uid");
            $stmt->execute(['id' => $id, 'uid' => $userId]);
        } else {
            $stmt = $db->prepare("UPDATE customer_notifications SET read_at = NOW() WHERE user_id = :uid AND read_at IS NULL");
            $stmt->execute(['uid' => $userId]);
        }

        json(['success' => true, 'message' => 'Notifications updated']);
    }

    /**
     * Register FCM Device Token
     */
    public function registerDeviceToken(Request $req): never
    {
        $db = Container::get('db');
        $userId = $req->params['auth_user_id'] ?? ($_SESSION['user_id'] ?? 0);
        $token = trim((string)$req->input('fcm_token', ''));

        if (!$token) {
            json(['success' => false, 'error' => 'FCM token required'], 400);
        }

        if ($userId) {
            $stmt = $db->prepare("UPDATE users SET fcm_token = :token WHERE id = :uid");
            $stmt->execute(['token' => $token, 'uid' => $userId]);
        }

        json(['success' => true, 'message' => 'Device token registered successfully']);
    }
}
