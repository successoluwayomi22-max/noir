<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Noir\Core\Container;
use Noir\Core\Mailer;

echo "[" . date('Y-m-d H:i:s') . "] Starting Abandoned Cart Recovery processor...\n";

try {
    $db = Container::get('db');
    if (!$db instanceof \PDO) {
        echo "Database connection failed.\n";
        exit(1);
    }

    // 1. Identify users with cart items modified > 1 hour ago who haven't completed checkout
    $sql = "
        SELECT DISTINCT c.user_id, u.name, u.email, MAX(c.updated_at) as last_cart_activity
        FROM customer_cart_items c
        JOIN users u ON u.id = c.user_id
        WHERE c.updated_at <= (NOW() - INTERVAL 1 HOUR)
          AND c.updated_at >= (NOW() - INTERVAL 7 DAY)
          AND NOT EXISTS (
              SELECT 1 FROM orders o 
              WHERE o.user_id = c.user_id 
                AND o.created_at >= c.updated_at
          )
          AND NOT EXISTS (
              SELECT 1 FROM activity_logs a 
              WHERE a.user_id = c.user_id 
                AND a.event = 'mail.abandoned_cart' 
                AND a.created_at >= (NOW() - INTERVAL 24 HOUR)
          )
        GROUP BY c.user_id, u.name, u.email
    ";

    $stmt = $db->query($sql);
    $abandonedUsers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

    echo "Found " . count($abandonedUsers) . " abandoned cart(s) eligible for reminder.\n";

    $mailer = new Mailer();
    $sentCount = 0;
    $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $cartUrl = (config('app.url', 'http://localhost:8000')) . '/store';

    foreach ($abandonedUsers as $user) {
        $userId = (int)$user['user_id'];
        $userEmail = (string)$user['email'];
        $userName = (string)($user['name'] ?: 'Valued Customer');

        if (empty($userEmail)) continue;

        // Fetch user's cart items
        $itemStmt = $db->prepare("
            SELECT p.id, p.name, p.price, p.featured_image, c.quantity
            FROM customer_cart_items c
            JOIN products p ON p.id = c.product_id
            WHERE c.user_id = ?
        ");
        $itemStmt->execute([$userId]);
        $items = $itemStmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($items)) continue;

        $total = 0.0;
        foreach ($items as $item) {
            $total += (float)($item['price'] ?? 0) * (int)($item['quantity'] ?? 1);
        }

        $currency = 'NGN';

        echo "Dispatching abandoned cart reminder to {$userName} <{$userEmail}> (Subtotal: {$currency} " . number_format($total, 2) . ")...\n";
        
        $sent = $mailer->sendAbandonedCartReminder($userEmail, $userName, $items, $total, $currency, $cartUrl);

        if ($sent) {
            $sentCount++;
            // Log in activity logs to prevent duplicate sending within 24h
            try {
                $db->prepare("
                    INSERT INTO activity_logs (user_id, actor_name, event, subject_type, subject_id, subject_label, metadata, created_at)
                    VALUES (?, ?, 'mail.abandoned_cart', 'cart', ?, 'Forgotten Cart Reminder', ?, NOW())
                ")->execute([
                    $userId,
                    'System Cron',
                    $userId,
                    json_encode(['item_count' => count($items), 'total' => $total, 'email' => $userEmail], JSON_UNESCAPED_SLASHES)
                ]);
            } catch (\Throwable) {}
        }
    }

    echo "[" . date('Y-m-d H:i:s') . "] Abandoned cart recovery completed. {$sentCount} reminder(s) dispatched.\n";
} catch (\Throwable $e) {
    echo "Error in abandoned cart recovery: " . $e->getMessage() . "\n";
    exit(1);
}
