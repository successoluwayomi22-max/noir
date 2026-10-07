<?php
declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\App\Services\PaymentService;
use Noir\Core\Container;
use Noir\Core\Request;

final class CartApiController
{
    public function getCart(Request $request): never
    {
        $db = Container::get('db');
        $userId = (int)\Noir\Core\Auth::id();
        $items = $request->input('items', []);
        if (!is_array($items)) {
            $items = [];
        }

        // If user is authenticated, retrieve persistent cart from database
        if ($userId > 0) {
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS customer_cart_items (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id BIGINT UNSIGNED NOT NULL,
                    product_id BIGINT UNSIGNED NOT NULL,
                    quantity INT UNSIGNED NOT NULL DEFAULT 1,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_product (user_id, product_id)
                ) ENGINE=InnoDB;");

                // Merge client items into DB if provided
                if (!empty($items)) {
                    $ins = $db->prepare("INSERT INTO customer_cart_items (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = ?");
                    foreach ($items as $line) {
                        $pId = (int)($line['id'] ?? $line['product_id'] ?? 0);
                        $qty = max(1, (int)($line['quantity'] ?? 1));
                        if ($pId > 0) {
                            $ins->execute([$userId, $pId, $qty, $qty]);
                        }
                    }
                }

                // Load all items from DB
                $dbItemsStmt = $db->prepare("SELECT product_id as id, quantity FROM customer_cart_items WHERE user_id = ?");
                $dbItemsStmt->execute([$userId]);
                $dbItems = $dbItemsStmt->fetchAll(\PDO::FETCH_ASSOC);
                if (!empty($dbItems)) {
                    $items = $dbItems;
                }
            } catch (\Throwable $e) {
                error_log('Cart DB sync notice: ' . $e->getMessage());
            }
        }

        $hydrated = [];
        $total = 0.0;
        
        $productQuery = $db->prepare("SELECT id, name, price, featured_image, sku, quantity FROM products WHERE id=? AND status='published' LIMIT 1");
        
        foreach ($items as $line) {
            $productId = (int)($line['id'] ?? $line['product_id'] ?? 0);
            $qty = max(1, (int)($line['quantity'] ?? 1));
            
            $productQuery->execute([$productId]);
            $product = $productQuery->fetch();
            
            if ($product) {
                $subtotal = (float)$product['price'] * $qty;
                $total += $subtotal;
                $hydrated[] = [
                    'id' => (int)$product['id'],
                    'product_id' => (int)$product['id'],
                    'name' => $product['name'],
                    'price' => (float)$product['price'],
                    'featured_image' => $product['featured_image'],
                    'image' => $product['featured_image'],
                    'sku' => $product['sku'],
                    'stock_quantity' => (int)$product['quantity'],
                    'quantity' => $qty,
                    'subtotal' => $subtotal
                ];
            }
        }
        
        json([
            'success' => true,
            'items' => $hydrated,
            'total' => $total,
            'currency' => active_currency()
        ]);
    }

    public function addToCart(Request $request): never
    {
        $productId = (int)$request->input('product_id', $request->input('id', 0));
        $qty = max(1, (int)$request->input('quantity', 1));
        $userId = (int)\Noir\Core\Auth::id();

        $db = Container::get('db');
        $q = $db->prepare("SELECT id, name, price, quantity, featured_image FROM products WHERE id=? AND status='published' LIMIT 1");
        $q->execute([$productId]);
        $product = $q->fetch();

        if (!$product) {
            json(['error' => 'The selected piece is currently unavailable.'], 404);
        }

        if ((int)$product['quantity'] < $qty) {
            json(['error' => 'Only ' . $product['quantity'] . ' piece(s) remaining in stock.'], 400);
        }

        if ($userId > 0) {
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS customer_cart_items (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id BIGINT UNSIGNED NOT NULL,
                    product_id BIGINT UNSIGNED NOT NULL,
                    quantity INT UNSIGNED NOT NULL DEFAULT 1,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_product (user_id, product_id)
                ) ENGINE=InnoDB;");

                $stmt = $db->prepare("INSERT INTO customer_cart_items (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + ?");
                $stmt->execute([$userId, $productId, $qty, $qty]);
            } catch (\Throwable $e) {
                error_log('Failed to persist cart item: ' . $e->getMessage());
            }
        }

        json([
            'success' => true,
            'message' => 'Piece added to your bag.',
            'product' => [
                'id' => (int)$product['id'],
                'name' => $product['name'],
                'price' => (float)$product['price'],
                'image' => $product['featured_image'],
                'stock' => (int)$product['quantity']
            ]
        ]);
    }

    public function removeFromCart(Request $request): never
    {
        $productId = (int)$request->input('product_id', $request->input('id', 0));
        $userId = (int)\Noir\Core\Auth::id();

        if ($userId > 0 && $productId > 0) {
            try {
                $db = Container::get('db');
                $db->prepare("DELETE FROM customer_cart_items WHERE user_id = ? AND product_id = ?")->execute([$userId, $productId]);
            } catch (\Throwable $e) {}
        }

        json([
            'success' => true,
            'message' => 'Item removed from bag.'
        ]);
    }

    public function syncCart(Request $request): never
    {
        $userId = (int)\Noir\Core\Auth::id();
        $items = $request->input('items', []);
        $db = Container::get('db');

        if ($userId > 0 && is_array($items)) {
            try {
                $db->exec("CREATE TABLE IF NOT EXISTS customer_cart_items (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id BIGINT UNSIGNED NOT NULL,
                    product_id BIGINT UNSIGNED NOT NULL,
                    quantity INT UNSIGNED NOT NULL DEFAULT 1,
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uq_user_product (user_id, product_id)
                ) ENGINE=InnoDB;");

                $db->beginTransaction();
                $db->prepare("DELETE FROM customer_cart_items WHERE user_id = ?")->execute([$userId]);
                $ins = $db->prepare("INSERT INTO customer_cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
                foreach ($items as $line) {
                    $pId = (int)($line['id'] ?? $line['product_id'] ?? 0);
                    $qty = max(1, (int)($line['quantity'] ?? 1));
                    if ($pId > 0) {
                        $ins->execute([$userId, $pId, $qty]);
                    }
                }
                $db->commit();
            } catch (\Throwable $e) {
                if ($db->inTransaction()) $db->rollBack();
            }
        }

        json(['success' => true, 'message' => 'Cart synchronized successfully.']);
    }

    public function clearCart(Request $request): never
    {
        $userId = (int)\Noir\Core\Auth::id();
        if ($userId > 0) {
            try {
                $db = Container::get('db');
                $db->prepare("DELETE FROM customer_cart_items WHERE user_id = ?")->execute([$userId]);
            } catch (\Throwable $e) {}
        }
        json(['success' => true, 'message' => 'Cart cleared.']);
    }

    public function checkout(Request $request): never
    {
        $items = $request->input('items', []);
        $billing = $request->input('billing', []);
        if (!is_array($billing)) $billing = [];

        $name = trim((string)($request->input('name') ?: ($billing['name'] ?? '')));
        $emailRaw = trim((string)($request->input('email') ?: ($billing['email'] ?? '')));
        $email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL);
        $phone = trim((string)($request->input('phone') ?: ($billing['phone'] ?? '')));
        $address = trim((string)($request->input('address') ?: ($billing['address'] ?? '')));
        $city = trim((string)($request->input('city') ?: ($billing['city'] ?? '')));
        $state = trim((string)($request->input('state') ?: ($billing['state'] ?? '')));
        $currency = strtoupper((string)$request->input('currency', store_setting('currency', 'NGN')));
        $provider = (string)($request->input('payment_method') ?: ($billing['payment_method'] ?? 'paystack'));

        if (!$email || $name === '' || $address === '' || $city === '' || empty($items)) {
            json(['error' => 'Missing billing/shipping information or cart is empty.'], 400);
        }

        $db = Container::get('db');
        
        // Resolve user ID if authenticated via token
        $userId = 0;
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (empty($authHeader) && function_exists('getallheaders')) {
            $h = getallheaders();
            $authHeader = $h['Authorization'] ?? $h['authorization'] ?? '';
        }
        if (!empty($authHeader) && str_starts_with($authHeader, 'Bearer ')) {
            try {
                $payload = \Noir\Core\Jwt::decode(substr($authHeader, 7), (string)config('key'));
                if (!empty($payload['user_id'])) {
                    $userId = (int)$payload['user_id'];
                }
            } catch (\Throwable $_) {}
        }
        if ($userId === 0) {
            $userLookup = $db->prepare('SELECT id FROM users WHERE email=? LIMIT 1');
            $userLookup->execute([$email]);
            $foundId = $userLookup->fetchColumn();
            if ($foundId) $userId = (int)$foundId;
        }

        try {
            $service = new PaymentService($db);
            $order = $service->createPendingOrder(
                $items,
                ['name' => $name, 'email' => $email, 'phone' => $phone, 'address' => $address, 'city' => $city, 'state' => $state],
                $userId,
                $currency,
                $provider
            );

            // Handle wallet / store credit payments
            if ($provider === 'wallet') {
                $db->prepare("UPDATE orders SET payment_status='paid', status='processing', updated_at=NOW() WHERE id=?")->execute([$order['order_id']]);
                
                // Atomically decrement purchased product inventory
                $orderItemsStmt = $db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
                $orderItemsStmt->execute([$order['order_id']]);
                $purchasedItems = $orderItemsStmt->fetchAll(\PDO::FETCH_ASSOC);

                $deductStock = $db->prepare('UPDATE products SET quantity = GREATEST(0, quantity - ?) WHERE id = ?');
                foreach ($purchasedItems as $pItem) {
                    $pid = (int)($pItem['product_id'] ?? 0);
                    $pqty = (int)($pItem['quantity'] ?? 0);
                    if ($pid > 0 && $pqty > 0) {
                        $deductStock->execute([$pqty, $pid]);
                    }
                }

                json([
                    'success' => true,
                    'order_id' => $order['order_id'],
                    'order_number' => $order['order_number'],
                    'total' => $order['total'],
                    'currency' => $order['currency'],
                    'message' => 'Payment deducted from your verified Noir Wallet.',
                ]);
            }

            $secret = (string)config('payments.paystack.secret_key', '');
            if ($secret === '') {
                $secret = (string)(store_setting('paystack_secret_key', '') ?: ($_ENV['PAYSTACK_SECRET_KEY'] ?? ''));
            }
            if ($secret === '') {
                json(['error' => 'Paystack payment gateway is not configured in settings.'], 500);
            }

            $callbackUrl = (string)$request->input('callback_url', config('url') . '/payments/paystack/callback');
            $authUrl = $service->initializePaystack($order, $email, $callbackUrl, $secret);

            json([
                'success' => true,
                'order_id' => $order['order_id'],
                'order_number' => $order['order_number'],
                'reference' => $order['reference'],
                'total' => $order['total'],
                'currency' => $order['currency'],
                'authorization_url' => $authUrl
            ]);
        } catch (\Throwable $e) {
            json(['error' => $e->getMessage()], 500);
        }
    }
}
