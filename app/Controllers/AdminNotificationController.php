<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\Core\Container;
use Noir\Core\Request;
use Noir\App\Services\FirebaseService;

final class AdminNotificationController
{
    public function index(Request $request): never
    {
        $db = Container::get('db');

        // Audience Metrics
        $totalCustomers = 0;
        $appUsers = 0;
        $totalBroadcasts = 0;
        $webNotifsCount = 0;

        try {
            $custRow = $db->query("
                SELECT 
                    COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN fcm_token IS NOT NULL AND LENGTH(TRIM(fcm_token)) > 0 THEN 1 ELSE 0 END), 0) AS app_users
                FROM users 
                WHERE account_type = 'customer' AND status = 'active'
            ")->fetch(\PDO::FETCH_ASSOC);

            $totalCustomers = (int)($custRow['total'] ?? 0);
            $appUsers = (int)($custRow['app_users'] ?? 0);

            $bCount = $db->query("SELECT COUNT(*) FROM broadcast_notifications")->fetchColumn();
            $totalBroadcasts = (int)($bCount ?: 0);

            $nCount = $db->query("SELECT COUNT(*) FROM customer_notifications")->fetchColumn();
            $webNotifsCount = (int)($nCount ?: 0);
        } catch (\Throwable $e) {
            error_log('AdminNotificationController metric error: ' . $e->getMessage());
        }

        // Fetch customer list for targeted broadcast selector (limit 150)
        $customers = [];
        try {
            $stmt = $db->query("
                SELECT id, name, email, 
                       (CASE WHEN fcm_token IS NOT NULL AND LENGTH(TRIM(fcm_token)) > 0 THEN 1 ELSE 0 END) AS has_fcm
                FROM users 
                WHERE account_type = 'customer' AND status = 'active'
                ORDER BY name ASC 
                LIMIT 150
            ");
            $customers = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $customers = [];
        }

        // Fetch broadcast history
        $broadcasts = [];
        try {
            $stmt = $db->query("
                SELECT b.*, u.name AS sender_name
                FROM broadcast_notifications b
                LEFT JOIN users u ON b.created_by = u.id
                ORDER BY b.created_at DESC
                LIMIT 40
            ");
            $broadcasts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable) {
            $broadcasts = [];
        }

        require BASE_PATH . '/app/Views/admin/notifications.php';
        exit;
    }

    public function send(Request $request): never
    {
        $title = trim((string)$request->input('title', ''));
        $body = trim((string)$request->input('body', ''));
        $type = trim((string)$request->input('type', 'announcement'));
        $audience = trim((string)$request->input('target_audience', 'all_customers'));
        $channels = trim((string)$request->input('channels', 'both'));
        $specificCustomerId = (int)$request->input('specific_customer_id', 0);
        $actionUrl = trim((string)$request->input('action_url', '/store'));

        if ($title === '') {
            json(['success' => false, 'message' => 'Please provide a notification title.'], 422);
        }

        if ($body === '') {
            json(['success' => false, 'message' => 'Please provide the notification message body.'], 422);
        }

        $validTypes = ['announcement', 'drop', 'privilege', 'alert', 'system'];
        if (!in_array($type, $validTypes, true)) {
            $type = 'announcement';
        }

        $validChannels = ['both', 'web_only', 'push_only'];
        if (!in_array($channels, $validChannels, true)) {
            $channels = 'both';
        }

        $db = Container::get('db');

        // Resolve recipients
        $recipients = [];
        try {
            if ($audience === 'specific_customer') {
                if ($specificCustomerId <= 0) {
                    json(['success' => false, 'message' => 'Please select a customer recipient.'], 422);
                }
                $stmt = $db->prepare("SELECT id, name, email, fcm_token FROM users WHERE id = :id AND account_type = 'customer'");
                $stmt->execute(['id' => $specificCustomerId]);
                $recipients = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } elseif ($audience === 'app_users_only') {
                $stmt = $db->query("SELECT id, name, email, fcm_token FROM users WHERE account_type = 'customer' AND status = 'active' AND fcm_token IS NOT NULL AND LENGTH(TRIM(fcm_token)) > 0");
                $recipients = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                // All active customers
                $stmt = $db->query("SELECT id, name, email, fcm_token FROM users WHERE account_type = 'customer' AND status = 'active'");
                $recipients = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        } catch (\Throwable $e) {
            json(['success' => false, 'message' => 'Failed to resolve customer recipients: ' . $e->getMessage()], 500);
        }

        $recipientCount = count($recipients);
        if ($recipientCount === 0) {
            $msg = ($audience === 'app_users_only')
                ? 'No mobile app devices are currently paired with push notification tokens. Please select "All Customers" to dispatch directly to your clientele via Web Concierge.'
                : 'No active customers found for the selected audience criteria.';
            json(['success' => false, 'message' => $msg], 400);
        }

        $webDispatched = 0;
        $pushDispatched = 0;

        // If push_only was requested, but no recipient has an FCM token, deliver via Web Concierge as fallback
        if ($channels === 'push_only') {
            $hasAnyFcm = false;
            foreach ($recipients as $r) {
                if (!empty(trim((string)($r['fcm_token'] ?? '')))) {
                    $hasAnyFcm = true;
                    break;
                }
            }
            if (!$hasAnyFcm) {
                $channels = 'both';
            }
        }

        // 1. Dispatch Web / In-App Concierge Notification
        if ($channels === 'both' || $channels === 'web_only') {
            try {
                $insertStmt = $db->prepare("
                    INSERT INTO customer_notifications (user_id, title, body, type, created_at)
                    VALUES (:user_id, :title, :body, :type, NOW())
                ");
                foreach ($recipients as $r) {
                    $insertStmt->execute([
                        'user_id' => (int)$r['id'],
                        'title' => $title,
                        'body' => $body,
                        'type' => $type
                    ]);
                    $webDispatched++;
                }
            } catch (\Throwable $e) {
                error_log('AdminNotificationController web insert failed: ' . $e->getMessage());
            }
        }

        // 2. Dispatch Mobile Push Notification via FCM
        if ($channels === 'both' || $channels === 'push_only') {
            try {
                $firebase = new FirebaseService();
                foreach ($recipients as $r) {
                    $fcmToken = trim((string)($r['fcm_token'] ?? ''));
                    if ($fcmToken !== '') {
                        $sent = $firebase->sendPushNotification($fcmToken, $title, $body, [
                            'type' => $type,
                            'action_url' => $actionUrl,
                            'timestamp' => (string)time()
                        ]);
                        if ($sent) {
                            $pushDispatched++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                error_log('AdminNotificationController FCM dispatch exception: ' . $e->getMessage());
            }
        }

        // 3. Log broadcast in history
        $adminId = $_SESSION['user']['id'] ?? null;
        try {
            $logStmt = $db->prepare("
                INSERT INTO broadcast_notifications 
                (title, body, type, target_audience, channels, recipient_count, created_by, created_at)
                VALUES (:title, :body, :type, :target_audience, :channels, :recipient_count, :created_by, NOW())
            ");
            $logStmt->execute([
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'target_audience' => $audience,
                'channels' => $channels,
                'recipient_count' => $recipientCount,
                'created_by' => $adminId
            ]);
        } catch (\Throwable $e) {
            error_log('AdminNotificationController log error: ' . $e->getMessage());
        }

        if ($pushDispatched > 0) {
            $msg = "Broadcast successfully dispatched to {$recipientCount} clientele via Web Concierge ({$webDispatched}) & Mobile App Push ({$pushDispatched}).";
        } else {
            $msg = "Broadcast successfully dispatched to {$webDispatched} clientele via Web Concierge.";
        }

        json([
            'success' => true,
            'message' => $msg,
            'recipient_count' => $recipientCount,
            'web_count' => $webDispatched,
            'push_count' => $pushDispatched
        ]);
    }

    public function delete(Request $request): never
    {
        $id = (int)$request->input('id', 0);
        if ($id <= 0) {
            json(['success' => false, 'message' => 'Invalid broadcast ID.'], 400);
        }

        $db = Container::get('db');
        try {
            $stmt = $db->prepare("DELETE FROM broadcast_notifications WHERE id = :id");
            $stmt->execute(['id' => $id]);
            json(['success' => true, 'message' => 'Broadcast record deleted.']);
        } catch (\Throwable $e) {
            json(['success' => false, 'message' => 'Failed to delete record: ' . $e->getMessage()], 500);
        }
    }
}
