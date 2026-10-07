<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use Noir\Core\Request;

final class OrderApiController
{
    public function index(Request $request): never
    {
        $user  = \Noir\Core\Auth::user() ?? [];
        $userId = (int)($user['user_id'] ?? $user['id'] ?? 0);

        if ($userId === 0) {
            json(['error' => 'Unauthorized'], 401);
        }

        $db   = Container::get('db');
        $page = max(1, (int)$request->input('page', 1));
        $per  = 15;
        $off  = ($page - 1) * $per;

        $total = (int)$db->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?')
                         ->execute([$userId]) ? $db->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?')
                         ->execute([$userId]) : 0;

        $stmt = $db->prepare(
            "SELECT id, order_number, status, total, currency, created_at
             FROM orders
             WHERE user_id=?
             ORDER BY id DESC
             LIMIT $per OFFSET $off"
        );
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();

        // count properly
        $cStmt = $db->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?');
        $cStmt->execute([$userId]);
        $totalCount = (int)$cStmt->fetchColumn();

        json([
            'orders'     => $orders,
            'pagination' => [
                'page'  => $page,
                'pages' => (int)ceil($totalCount / $per),
                'total' => $totalCount,
            ],
        ]);
    }

    public function show(Request $request): never
    {
        $user  = \Noir\Core\Auth::user() ?? [];
        $userId = (int)($user['user_id'] ?? $user['id'] ?? 0);

        if ($userId === 0) {
            json(['error' => 'Unauthorized'], 401);
        }

        $orderNumber = trim((string)($request->params['number'] ?? ''));

        if ($orderNumber === '') {
            json(['error' => 'Order number required'], 400);
        }

        $db   = Container::get('db');
        $stmt = $db->prepare(
            "SELECT id, order_number, status, total, currency, name, email,
                    address, city, state, created_at, notes
             FROM orders
             WHERE order_number=? AND user_id=?
             LIMIT 1"
        );
        $stmt->execute([$orderNumber, $userId]);
        $order = $stmt->fetch();

        if (!$order) {
            json(['error' => 'Order not found'], 404);
        }

        $itemStmt = $db->prepare(
            "SELECT oi.product_id, oi.name, oi.price, oi.quantity,
                    p.featured_image
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id=?"
        );
        $itemStmt->execute([$order['id']]);
        $items = $itemStmt->fetchAll();

        json(['order' => $order, 'items' => $items]);
    }

    public function track(Request $request): never
    {
        $orderNumber = trim((string)$request->input('order_number'));
        $email       = strtolower(trim((string)$request->input('email')));

        if ($orderNumber === '' || $email === '') {
            json(['error' => 'Order number and email are required'], 400);
        }

        $db   = Container::get('db');
        $stmt = $db->prepare(
            "SELECT order_number, status, total, currency, name, city, created_at
             FROM orders
             WHERE order_number=? AND LOWER(email)=?
             LIMIT 1"
        );
        $stmt->execute([$orderNumber, $email]);
        $order = $stmt->fetch();

        if (!$order) {
            json(['error' => 'No order found matching those details'], 404);
        }

        json(['order' => $order]);
    }
}
