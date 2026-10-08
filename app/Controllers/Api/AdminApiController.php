<?php

declare(strict_types=1);

namespace Noir\App\Controllers\Api;

use Noir\Core\Container;

/**
 * Admin API controller for the mobile app.
 *
 * All endpoints require an authenticated user whose account_type is 'admin'
 * or 'staff', or who has an admin-level role (super-admin, admin, store-manager).
 */
final class AdminApiController
{
    /**
     * Verify the current API user is an admin.
     * Returns the user row on success, or sends a 403 JSON and exits.
     */
    private function requireAdmin(): array
    {
        $user = \Noir\Core\Auth::user();
        if (!$user) {
            json(['success' => false, 'error' => 'Unauthorized'], 401);
        }

        $accountType = $user['account_type'] ?? '';
        $isAdmin = in_array($accountType, ['admin', 'staff', 'super-admin', 'administrator'], true);
        $roles = $user['roles'] ?? [];
        $hasAdminRole = !empty(array_intersect($roles, ['super-admin', 'admin', 'administrator', 'store-manager', 'inventory-manager', 'staff']));

        if (!$isAdmin && !$hasAdminRole && !in_array('*', $roles, true)) {
            json(['success' => false, 'error' => 'Forbidden: admin access required'], 403);
        }

        return $user;
    }

    /**
     * GET /api/v1/mobile/admin/dashboard
     *
     * Returns stats, product summary, and recent orders.
     */
    public function dashboard(): never
    {
        $this->requireAdmin();
        $db = Container::get('db');

        $stats = [
            'customers' => (int)$db->query("SELECT COUNT(*) FROM users WHERE status='active' AND account_type='customer'")->fetchColumn(),
            'staff'     => (int)$db->query("SELECT COUNT(*) FROM users u WHERE u.status='active' AND u.account_type='staff' AND u.email<>'admin@noir.test' AND NOT EXISTS (SELECT 1 FROM user_roles ur JOIN roles r ON r.id=ur.role_id WHERE ur.user_id=u.id AND r.slug='super-admin')")->fetchColumn(),
            'products'  => (int)$db->query("SELECT COUNT(*) FROM products WHERE status='published'")->fetchColumn(),
            'orders'    => (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'revenue'   => (float)$db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ('cancelled','refunded')")->fetchColumn(),
        ];

        $productSummary = [
            'total'  => (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'active' => (int)$db->query("SELECT COUNT(*) FROM products WHERE status='published'")->fetchColumn(),
            'draft'  => (int)$db->query("SELECT COUNT(*) FROM products WHERE status='draft'")->fetchColumn(),
            'out'    => (int)$db->query('SELECT COUNT(*) FROM products WHERE quantity<=0')->fetchColumn(),
        ];

        $orders = $db->query('SELECT order_number, status, total, currency, created_at FROM orders ORDER BY id DESC LIMIT 10')->fetchAll();

        json([
            'success'        => true,
            'stats'          => $stats,
            'productSummary' => $productSummary,
            'orders'         => $orders,
        ]);
    }

    /**
     * GET /api/v1/mobile/admin/orders?page=1
     *
     * Returns paginated orders list for admin.
     */
    public function orders(\Noir\Core\Request $request): never
    {
        $this->requireAdmin();
        $db = Container::get('db');

        $page    = max(1, (int)$request->input('page', 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $total = (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));

        $q = $db->prepare("SELECT o.order_number, o.status, o.payment_status, o.total, o.currency, o.created_at, u.name as customer_name, u.email as customer_email FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.id DESC LIMIT $perPage OFFSET $offset");
        $q->execute();
        $orders = $q->fetchAll();

        json([
            'success'    => true,
            'orders'     => $orders,
            'pagination' => [
                'page'     => $page,
                'pages'    => $pages,
                'total'    => $total,
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * POST /api/v1/mobile/admin/orders/status
     *
     * Update an order's status.
     * Body: { "order_number": "NO-1234", "status": "processing" }
     */
    public function updateOrderStatus(\Noir\Core\Request $request): never
    {
        $admin = $this->requireAdmin();
        $db = Container::get('db');

        $orderNumber = trim((string)$request->input('order_number'));
        $newStatus   = trim((string)$request->input('status'));

        $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        if (!in_array($newStatus, $allowed, true)) {
            json(['success' => false, 'error' => 'Invalid status. Allowed: ' . implode(', ', $allowed)], 422);
        }

        $q = $db->prepare('SELECT id, status FROM orders WHERE order_number = ?');
        $q->execute([$orderNumber]);
        $order = $q->fetch();

        if (!$order) {
            json(['success' => false, 'error' => 'Order not found'], 404);
        }

        $db->prepare('UPDATE orders SET status = ?, updated_at = NOW() WHERE order_number = ?')
           ->execute([$newStatus, $orderNumber]);

        // Send Push Notification + Klaviyo event if status is shipped or delivered
        if (in_array($newStatus, ['shipped', 'delivered'], true)) {
            try {
                // Fetch customer email + FCM token together
                $userQ = $db->prepare('SELECT u.fcm_token, u.email FROM users u JOIN orders o ON o.user_id = u.id WHERE o.order_number = ?');
                $userQ->execute([$orderNumber]);
                $userRow = $userQ->fetch(\PDO::FETCH_ASSOC);

                if ($userRow) {
                    // Push notification
                    if (!empty($userRow['fcm_token'])) {
                        $fcm   = new \Noir\App\Services\FirebaseService();
                        $title = $newStatus === 'shipped' ? 'Order Shipped! 🚚' : 'Order Delivered! 🎉';
                        $body  = "Your order #$orderNumber has been $newStatus.";
                        $fcm->sendPushNotification((string)$userRow['fcm_token'], $title, $body, ['order_number' => $orderNumber]);
                    }

                    // Klaviyo event
                    $klaviyo = new \Noir\App\Services\KlaviyoService();
                    if ($klaviyo->isConfigured() && !empty($userRow['email'])) {
                        if ($newStatus === 'shipped') {
                            $klaviyo->trackOrderShipped((string)$userRow['email'], $orderNumber);
                        } else {
                            $klaviyo->trackOrderDelivered((string)$userRow['email'], $orderNumber);
                        }
                    }
                }
            } catch (\Throwable $e) {
                error_log('Notification / Klaviyo dispatch error: ' . $e->getMessage());
            }
        }

        // Log the action
        $db->prepare('INSERT INTO activity_logs (user_id, event, subject_type, subject_id, metadata) VALUES (?, ?, ?, ?, ?)')
           ->execute([
               $admin['id'] ?? null,
               'order.status_changed',
               'order',
               $order['id'],
               json_encode(['from' => $order['status'], 'to' => $newStatus], JSON_UNESCAPED_SLASHES),
           ]);

        json(['success' => true, 'message' => "Order $orderNumber status updated to $newStatus."]);
    }

    public function products(\Noir\Core\Request $request): never
    {
        $this->requireAdmin();
        $db = Container::get('db');
        
        $page    = max(1, (int)$request->input('page', 1));
        $perPage = 50;
        $offset  = ($page - 1) * $perPage;

        $total = (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();
        $pages = max(1, (int)ceil($total / $perPage));

        $q = $db->prepare("SELECT id, name, slug, price, quantity, status, featured_image, created_at FROM products ORDER BY id DESC LIMIT $perPage OFFSET $offset");
        $q->execute();
        $products = $q->fetchAll();

        $summary = [
            'total_products' => $total,
            'low_stock_count' => (int)$db->query('SELECT COUNT(*) FROM products WHERE quantity < 5 AND quantity > 0')->fetchColumn(),
            'out_of_stock_count' => (int)$db->query('SELECT COUNT(*) FROM products WHERE quantity <= 0')->fetchColumn(),
            'published_count' => (int)$db->query("SELECT COUNT(*) FROM products WHERE status = 'published'")->fetchColumn(),
            'total_inventory_value' => (float)$db->query('SELECT COALESCE(SUM(price * quantity), 0) FROM products')->fetchColumn(),
        ];

        json([
            'success'    => true,
            'products'   => $products,
            'summary'    => $summary,
            'pagination' => [
                'page'  => $page,
                'pages' => $pages,
                'total' => $total,
            ]
        ]);
    }

    public function createProduct(\Noir\Core\Request $request): never
    {
        $this->requireAdmin();
        $db = Container::get('db');
        
        $name = trim((string)$request->input('name'));
        $description = trim((string)$request->input('description', ''));
        $shortDesc = trim((string)$request->input('short_description', ''));
        $price = (float)$request->input('price');
        $quantity = (int)$request->input('quantity', 10);
        $status = trim((string)$request->input('status', 'published'));
        $category = trim((string)$request->input('category', $request->input('category_name', '')));
        $brand = trim((string)$request->input('brand', $request->input('brand_name', 'Noir & Co.')));
        $sku = trim((string)$request->input('sku', ''));
        $featuredImage = trim((string)$request->input('featured_image', 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?auto=format&fit=crop&q=80&w=800'));
        $videoUrl = trim((string)$request->input('video_url', ''));
        $slug = strtolower(trim((string)preg_replace('/[^A-Za-z0-9-]+/', '-', $name))) . '-' . substr(bin2hex(random_bytes(4)), 0, 6);
        $publicId = strtoupper(bin2hex(random_bytes(13)));

        if (empty($name)) {
            json(['success' => false, 'error' => 'Product name is required'], 400);
        }

        if (empty($sku)) {
            $sku = 'NOIR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        }

        $categoryId = null;
        if ($category !== '') {
            $catStmt = $db->prepare('SELECT id FROM categories WHERE name = ? OR slug = ? LIMIT 1');
            $catStmt->execute([$category, strtolower($category)]);
            $categoryId = $catStmt->fetchColumn() ?: null;
        }

        $q = $db->prepare('
            INSERT INTO products (public_id, name, slug, sku, category, category_id, brand, short_description, description, price, quantity, status, featured_image, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ');
        $q->execute([
            $publicId,
            $name,
            $slug,
            $sku,
            $category ?: 'Capsule Collection',
            $categoryId,
            $brand ?: 'Noir & Co.',
            $shortDesc ?: (mb_strlen($description) > 150 ? mb_substr(strip_tags($description), 0, 150) . '...' : $description),
            $description,
            $price,
            $quantity,
            $status,
            $featuredImage
        ]);
        $productId = (int)$db->lastInsertId();

        // Also insert into product_images so storefront image gallery and carousels display it
        try {
            $db->prepare('INSERT INTO product_images (product_id, path, is_featured, sort_order) VALUES (?, ?, 1, 0)')
               ->execute([$productId, $featuredImage]);
        } catch (\Throwable) {}

        // Auto publish engagement
        try {
            $caption = "✨ New Arrival: {$name} — " . ($description ? substr(strip_tags($description), 0, 120) : "Discover the latest luxury drop in our collection.") . " #Noir #Luxury #NewArrival";
            $db->prepare('INSERT INTO community_posts (user_handle, image_url, caption, products_count, is_active, created_at, updated_at) VALUES (?, ?, ?, 1, 1, NOW(), NOW())')
               ->execute(['@noir.official', $featuredImage, $caption]);

            if (empty($videoUrl)) {
                $videoUrl = 'https://assets.mixkit.co/videos/preview/mixkit-fashion-model-in-a-black-dress-40348-large.mp4';
            }
            $db->prepare("INSERT INTO video_commerce (title, user, likes_count, comments_count, products_count, url, status, created_at, updated_at) VALUES (?, ?, 0, 0, 1, ?, 'active', NOW(), NOW())")
               ->execute([$name, '@noir.official', $videoUrl]);
        } catch (\Throwable $e) {}
        
        json(['success' => true, 'message' => 'Product created and published to Community Feed & Video Commerce', 'product_id' => $productId]);
    }

    public function updateProduct(\Noir\Core\Request $request): never
    {
        $this->requireAdmin();
        $db = Container::get('db');
        $id = (int)($request->params['id'] ?? 0);
        
        $name = trim((string)$request->input('name'));
        $price = (float)$request->input('price');
        $quantity = (int)$request->input('quantity');
        $status = trim((string)$request->input('status'));
        $category = trim((string)$request->input('category', ''));
        $brand = trim((string)$request->input('brand', ''));
        $featuredImage = trim((string)$request->input('featured_image', ''));

        if (empty($name)) {
            json(['success' => false, 'error' => 'Product name is required'], 400);
        }

        $fields = ['name = ?', 'price = ?', 'quantity = ?', 'status = ?', 'updated_at = NOW()'];
        $params = [$name, $price, $quantity, $status];

        if ($category !== '') {
            $fields[] = 'category = ?';
            $params[] = $category;
        }
        if ($brand !== '') {
            $fields[] = 'brand = ?';
            $params[] = $brand;
        }
        if ($featuredImage !== '') {
            $fields[] = 'featured_image = ?';
            $params[] = $featuredImage;
        }

        $params[] = $id;
        $q = $db->prepare('UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?');
        $q->execute($params);
        
        json(['success' => true, 'message' => 'Product updated']);
    }

    public function deleteProduct(\Noir\Core\Request $request): never
    {
        $this->requireAdmin();
        $db = Container::get('db');
        $id = (int)($request->params['id'] ?? 0);
        
        $q = $db->prepare('DELETE FROM products WHERE id=?');
        $q->execute([$id]);
        
        json(['success' => true, 'message' => 'Product deleted']);
    }

    public function customers(\Noir\Core\Request $request): never
    {
        $this->requireAdmin();
        $db = Container::get('db');
        
        $q = $db->prepare("SELECT id, name, email, status, created_at FROM users WHERE account_type='customer' ORDER BY id DESC LIMIT 50");
        $q->execute();
        $customers = $q->fetchAll();

        json(['success' => true, 'customers' => $customers]);
    }

    public function reports(\Noir\Core\Request $request): never
    {
        $this->requireAdmin();
        $db = Container::get('db');
        
        // Simple mock report for now
        json([
            'success' => true, 
            'reports' => [
                'sales_today' => 120000,
                'visitors_today' => 340,
                'conversion_rate' => 2.4
            ]
        ]);
    }

    public function settings(\Noir\Core\Request $request): never
    {
        $this->requireAdmin();
        $db = Container::get('db');
        
        $q = $db->prepare("SELECT setting_key, setting_value FROM settings");
        $q->execute();
        $settings = $q->fetchAll();
        
        $mapped = [];
        foreach ($settings as $s) {
            $mapped[$s['setting_key']] = $s['setting_value'];
        }

        json(['success' => true, 'settings' => $mapped]);
    }

    public function updateSettings(\Noir\Core\Request $request): never
    {
        $this->requireAdmin();
        $db = Container::get('db');
        
        $keys = ['store_name', 'currency', 'support_email'];
        foreach ($keys as $k) {
            $val = $request->input($k);
            if ($val !== null) {
                $q = $db->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?');
                $q->execute([$val, $k]);
                // if not exists, insert
                if ($q->rowCount() === 0) {
                    $q2 = $db->prepare('INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)');
                    $q2->execute([$k, $val]);
                }
            }
        }
        
        json(['success' => true, 'message' => 'Settings updated']);
    }
}
