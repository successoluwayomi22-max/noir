<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\App\Traits\ManagesProfileImage;
use Noir\Core\Container;
use Noir\Core\Request;

final class DeliveryController
{
    use ManagesProfileImage;

    public function updateStatus(Request $request): never
    {
        $orderNumber = trim((string)$request->input('order_number'));
        $status = trim((string)$request->input('status'));
        $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        if ($orderNumber === '' || !in_array($status, $allowed, true)) {
            http_response_code(422);
            exit('Invalid order status.');
        }

        $db = Container::get('db');
        $q = $db->prepare('SELECT id, user_id, status FROM orders WHERE order_number = ? LIMIT 1');
        $q->execute([$orderNumber]);
        $order = $q->fetch();

        if (!$order) {
            http_response_code(404);
            exit('Order not found.');
        }

        $db->prepare('UPDATE orders SET status = ? WHERE id = ?')
            ->execute([$status, (int)$order['id']]);

        $actor = Container::get('auth')->user();

        $db->prepare('INSERT INTO activity_logs (user_id, event, subject_type, subject_id, metadata) VALUES (?, ?, ?, ?, ?)')
            ->execute([
                (int)($actor['id'] ?? $actor['user_id'] ?? 0),
                'order.status_changed',
                'order',
                (int)$order['id'],
                json_encode(['from' => $order['status'], 'to' => $status, 'order_number' => $orderNumber], JSON_UNESCAPED_SLASHES)
            ]);

        if (!empty($order['user_id'])) {
            $db->prepare('INSERT INTO customer_notifications (user_id, title, body) VALUES (?, ?, ?)')
                ->execute([
                    (int)$order['user_id'],
                    'Order update',
                    'Your order ' . $orderNumber . ' is now ' . str_replace('_', ' ', $status) . '.'
                ]);
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        header('Location: ' . ($base ?: '') . '/delivery/dashboard?saved=1', true, 303);
        exit;
    }

    public function updateProfile(Request $request): never
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $this->handleProfileUpload($request, ($base ?: '') . '/delivery/dashboard');
    }

    public function dashboard(): never
    {
        $db = Container::get('db');
        $stats = [
            'pending' => 0,
            'processing' => 0,
            'shipped' => 0,
            'delivered' => 0
        ];

        $q = $db->query("SELECT status, COUNT(*) total FROM orders GROUP BY status");
        foreach ($q->fetchAll() as $row) {
            if (array_key_exists($row['status'], $stats)) {
                $stats[$row['status']] = (int)$row['total'];
            }
        }

        $orders = $db->query(
            "SELECT o.order_number, o.status, o.payment_status, o.total, o.currency, o.created_at, COALESCE(u.name, 'Guest customer') customer_name 
             FROM orders o 
             LEFT JOIN users u ON u.id = o.user_id 
             ORDER BY o.created_at DESC 
             LIMIT 20"
        )->fetchAll();

        $userId = (int)(Container::get('auth')->user()['user_id'] ?? 0);

        $nq = $db->prepare(
            "SELECT title, body, read_at, DATE_FORMAT(created_at, '%d %b %Y %H:%i') time 
             FROM customer_notifications 
             WHERE user_id = ? 
             ORDER BY id DESC 
             LIMIT 8"
        );
        $nq->execute([$userId]);
        $notifications = $nq->fetchAll();

        $saved = isset($_GET['saved']);
        require BASE_PATH . '/app/Views/delivery/dashboard.php';
        exit;
    }
}
