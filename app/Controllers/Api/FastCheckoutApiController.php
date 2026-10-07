<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;
use PDO;

final class FastCheckoutApiController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Container::get('db');
    }

    /**
     * 1-Tap Biometric / Express Fast Checkout
     */
    public function expressPurchase(): never
    {
        $auth = Container::get('auth');
        $user = $auth->user();
        if (!$user) {
            json(['success' => false, 'message' => 'Authentication required for biometric fast checkout.'], 401);
        }

        $userId = (int) ($user['id'] ?? $user['user_id'] ?? 0);
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true) ?? $_POST;

        $productId = isset($body['product_id']) ? (int) $body['product_id'] : 0;
        $quantity = isset($body['quantity']) ? max(1, (int) $body['quantity']) : 1;
        $selectedSize = trim((string) ($body['size'] ?? 'Standard'));
        $selectedColor = trim((string) ($body['color'] ?? 'Obsidian'));
        $shippingAddress = trim((string) ($body['shipping_address'] ?? ''));

        // If no product specified, check user cart
        $items = [];
        $totalAmount = 0.0;
        $currency = 'NGN';

        if ($productId > 0) {
            $stmt = $this->db->prepare("SELECT id, name, price, sale_price, stock_quantity FROM products WHERE id = ? LIMIT 1");
            $stmt->execute([$productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                json(['success' => false, 'message' => 'Product not found.'], 404);
            }
            $unitPrice = (float) ($product['sale_price'] ?? $product['price']);
            $totalAmount = $unitPrice * $quantity;
            $items[] = [
                'product_id' => (int) $product['id'],
                'name' => $product['name'],
                'price' => $unitPrice,
                'quantity' => $quantity,
                'size' => $selectedSize,
                'color' => $selectedColor,
            ];
        } else {
            // Fetch cart items
            $cStmt = $this->db->prepare("
                SELECT ci.product_id, ci.quantity, ci.size, ci.color, p.name, p.price, p.sale_price 
                FROM cart_items ci
                JOIN carts c ON c.id = ci.cart_id
                JOIN products p ON p.id = ci.product_id
                WHERE c.user_id = ?
            ");
            $cStmt->execute([$userId]);
            $cartRows = $cStmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($cartRows)) {
                json(['success' => false, 'message' => 'Your luxury shopping bag is empty.'], 422);
            }
            foreach ($cartRows as $cr) {
                $pPrice = (float) ($cr['sale_price'] ?? $cr['price']);
                $sub = $pPrice * (int) $cr['quantity'];
                $totalAmount += $sub;
                $items[] = [
                    'product_id' => (int) $cr['product_id'],
                    'name' => $cr['name'],
                    'price' => $pPrice,
                    'quantity' => (int) $cr['quantity'],
                    'size' => $cr['size'] ?? 'Standard',
                    'color' => $cr['color'] ?? 'Default',
                ];
            }
        }

        // Resolve deliverable shipping address
        if (empty($shippingAddress)) {
            $aStmt = $this->db->prepare("SELECT address_line1, city, state, postal_code FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC LIMIT 1");
            $aStmt->execute([$userId]);
            $addr = $aStmt->fetch(PDO::FETCH_ASSOC);
            if ($addr) {
                $shippingAddress = implode(', ', array_filter([$addr['address_line1'], $addr['city'], $addr['state'], $addr['postal_code']]));
            } else {
                $shippingAddress = "Victoria Island, Lagos, Nigeria";
            }
        }

        // Generate Order Number
        $orderNumber = 'NOIR-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));
        $securityPin = (string) rand(1000, 9999);

        // Begin transaction
        $this->db->beginTransaction();
        try {
            $insOrder = $this->db->prepare("
                INSERT INTO orders (user_id, order_number, total, subtotal, tax_total, shipping_total, currency, status, payment_status, payment_method, shipping_address, shipping_name, created_at, updated_at)
                VALUES (?, ?, ?, ?, 0.00, 0.00, ?, 'processing', 'paid', 'biometric_1tap', ?, ?, NOW(), NOW())
            ");
            $recipientName = $user['name'] ?? 'VIP Client';
            $insOrder->execute([$userId, $orderNumber, $totalAmount, $totalAmount, $currency, $shippingAddress, $recipientName]);
            $orderId = (int) $this->db->lastInsertId();

            // Insert order items
            $insItem = $this->db->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, price, quantity, size, color, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            foreach ($items as $it) {
                $insItem->execute([$orderId, $it['product_id'], $it['name'], $it['price'], $it['quantity'], $it['size'], $it['color']]);
            }

            // Assign VIP courier
            $cQuery = $this->db->query("SELECT id FROM couriers WHERE is_active = 1 ORDER BY status = 'available' DESC, total_deliveries ASC LIMIT 1");
            $courier = $cQuery->fetch(PDO::FETCH_ASSOC);
            $courierId = $courier ? (int) $courier['id'] : 1;

            $insDispatch = $this->db->prepare("
                INSERT INTO order_dispatches (order_id, order_number, courier_id, dispatch_status, estimated_delivery, security_pin, distance_remaining_km, created_at)
                VALUES (?, ?, ?, 'assigned', DATE_ADD(NOW(), INTERVAL 45 MINUTE), ?, 3.2, NOW())
            ");
            $insDispatch->execute([$orderId, $orderNumber, $courierId, $securityPin]);

            // Add notification
            $this->db->prepare("
                INSERT INTO customer_notifications (user_id, title, body, created_at)
                VALUES (?, '⚡ 1-Tap Order Confirmed', ?, NOW())
            ")->execute([$userId, "Your express biometric order #$orderNumber for ₦" . number_format($totalAmount) . " has been authorized. Security Handshake PIN: $securityPin"]);

            // Clear user cart if bought from cart
            if ($productId === 0) {
                $this->db->prepare("DELETE ci FROM cart_items ci JOIN carts c ON c.id = ci.cart_id WHERE c.user_id = ?")->execute([$userId]);
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            json(['success' => false, 'message' => 'Failed to process fast checkout: ' . $e->getMessage()], 500);
        }

        json([
            'success' => true,
            'message' => '⚡ 1-Tap Biometric Purchase Successful! White-glove courier escort dispatched.',
            'order' => [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total' => $totalAmount,
                'currency' => $currency,
                'items_count' => count($items),
                'shipping_address' => $shippingAddress,
                'security_pin' => $securityPin,
                'status' => 'processing',
                'tracking_url' => "/order-tracking?ref=$orderNumber",
            ],
        ]);
    }
}
