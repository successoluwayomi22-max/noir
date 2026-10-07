<?php
declare(strict_types=1);

namespace Noir\App\Repositories;

use PDO;

final class CustomerRepository
{
    public function __construct(private readonly PDO $db) {}

    // -----------------------------------------------------------------------
    // Profile
    // -----------------------------------------------------------------------

    public function customer(int $id): ?array
    {
        try {
            $q = $this->db->prepare(
                'SELECT id, name, email, username, phone, profile_image FROM users WHERE id = ?',
            );
            $q->execute([$id]);
            $customer = $q->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException) {
            $q = $this->db->prepare(
                'SELECT id, name, email, username, phone FROM users WHERE id = ?',
            );
            $q->execute([$id]);
            $customer = $q->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($customer) {
                $customer['profile_image'] = null;
            }
        }

        if ($customer) {
            $names             = array_filter(explode(' ', $customer['name'] ?? ''));
            $customer['initials'] = strtoupper(
                substr($names[0] ?? 'C', 0, 1) . substr($names[1] ?? '', 0, 1),
            );
        }

        return $customer;
    }

    public function stats(int $userId): array
    {
        $count = $this->db->prepare('SELECT COUNT(*) FROM orders WHERE user_id=?');
        $count->execute([$userId]);

        $pending = $this->db->prepare(
            "SELECT COUNT(*) FROM orders WHERE user_id=? AND status IN ('pending','processing')",
        );
        $pending->execute([$userId]);

        $delivered = $this->db->prepare(
            "SELECT COUNT(*) FROM orders WHERE user_id=? AND status='delivered'",
        );
        $delivered->execute([$userId]);

        $wish = $this->db->prepare('SELECT COUNT(*) FROM customer_wishlist WHERE user_id=?');
        $wish->execute([$userId]);

        $points = $this->db->prepare('SELECT reward_points FROM customer_wallets WHERE user_id=?');
        $points->execute([$userId]);

        return [
            'orders'    => (int) $count->fetchColumn(),
            'pending'   => (int) $pending->fetchColumn(),
            'delivered' => (int) $delivered->fetchColumn(),
            'wishlist'  => (int) $wish->fetchColumn(),
            'points'    => (int) ($points->fetchColumn() ?: 0),
        ];
    }

    public function setProfileImage(int $userId, ?string $path): void
    {
        try {
            $this->db->prepare('UPDATE users SET profile_image = ? WHERE id = ?')
                ->execute([$path, $userId]);
        } catch (\PDOException) {
            // Column may not yet exist — attempt to add it.
            try {
                $this->db->query('ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL');
                $this->db->prepare('UPDATE users SET profile_image = ? WHERE id = ?')
                    ->execute([$path, $userId]);
            } catch (\PDOException) {
                // Completely ignore.
            }
        }
    }

    // -----------------------------------------------------------------------
    // Orders
    // -----------------------------------------------------------------------

    public function orders(int $userId): array
    {
        $q = $this->db->prepare(
            "SELECT order_number number,
                    DATE_FORMAT(created_at,'%d %b %Y') date,
                    status,
                    payment_status payment,
                    total,
                    currency
             FROM orders
             WHERE user_id=?
             ORDER BY id DESC",
        );
        $q->execute([$userId]);

        return $q->fetchAll();
    }

    public function order(int $userId, string $number): ?array
    {
        $q = $this->db->prepare(
            "SELECT id, order_number number,
                    DATE_FORMAT(created_at,'%d %b %Y') date,
                    status, payment_status payment,
                    total, currency, user_id
             FROM orders
             WHERE user_id=? AND order_number=?
             LIMIT 1",
        );
        $q->execute([$userId, $number]);
        $o = $q->fetch();

        if (!$o) {
            return null;
        }

        // Resolve shipping address from default address or checkout payload.
        $a            = $this->defaultAddress($userId);
        $payloadQuery = $this->db->prepare(
            'SELECT gateway_payload FROM payment_transactions WHERE order_id=? ORDER BY id DESC LIMIT 1',
        );
        $payloadQuery->execute([(int) $o['id']]);
        $saved    = json_decode((string) $payloadQuery->fetchColumn(), true);
        $customer = is_array($saved['customer'] ?? null) ? $saved['customer'] : [];

        if ($customer) {
            $a = [
                'name'  => $customer['name']  ?? '',
                'line'  => $customer['address'] ?? '',
                'city'  => trim(($customer['city'] ?? '') . (!empty($customer['state']) ? ', ' . $customer['state'] : '')),
                'phone' => $customer['phone'] ?? '',
            ];
        }

        $items = $this->db->prepare(
            'SELECT product_name name, quantity, unit_price price, image_url FROM order_items WHERE order_id=?',
        );
        $items->execute([(int) $o['id']]);

        unset($o['id']);
        $o['shipping'] = $a;
        $o['billing']  = $a;
        $o['items']    = $items->fetchAll();

        return $o;
    }

    // -----------------------------------------------------------------------
    // Wishlist
    // -----------------------------------------------------------------------

    public function products(): array
    {
        return $this->db->query(
            "SELECT id, name, slug, price, featured_image, featured_image AS image, category, brand
             FROM products WHERE status='published' ORDER BY id DESC LIMIT 8",
        )->fetchAll();
    }

    public function wishlist(int $userId): array
    {
        try {
            $q = $this->db->prepare(
                "SELECT p.id, p.public_id, p.name, p.slug, p.price, p.compare_at_price, 
                        p.featured_image as image, p.quantity, p.category, p.brand, w.created_at as saved_at
                 FROM customer_wishlist w
                 JOIN products p ON p.id = w.product_id
                 WHERE w.user_id = ? AND p.status = 'published'
                 ORDER BY w.created_at DESC"
            );
            $q->execute([$userId]);
            return $q->fetchAll() ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function addToWishlist(int $userId, int $productId): bool
    {
        try {
            $q = $this->db->prepare(
                "INSERT INTO customer_wishlist (user_id, product_id, created_at) 
                 VALUES (?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE created_at = NOW()"
            );
            return $q->execute([$userId, $productId]);
        } catch (\Throwable) {
            return false;
        }
    }

    public function addWishlist(int $userId, int $productId): bool
    {
        return $this->addToWishlist($userId, $productId);
    }

    public function removeFromWishlist(int $userId, int $productId): bool
    {
        try {
            $q = $this->db->prepare(
                "DELETE FROM customer_wishlist WHERE user_id = ? AND product_id = ?"
            );
            return $q->execute([$userId, $productId]);
        } catch (\Throwable) {
            return false;
        }
    }

    public function removeWishlist(int $userId, int $productId): bool
    {
        return $this->removeFromWishlist($userId, $productId);
    }

    // -----------------------------------------------------------------------
    // Coupons
    // -----------------------------------------------------------------------

    public function coupons(): array
    {
        return $this->db->query(
            "SELECT code, description, discount_type, discount_value,
                    minimum_order_amount, expires_at
             FROM coupons
             WHERE status='active'
               AND (starts_at IS NULL OR starts_at<=NOW())
               AND (expires_at IS NULL OR expires_at>=NOW())
               AND (usage_limit IS NULL OR used_count<usage_limit)
             ORDER BY id DESC",
        )->fetchAll();
    }

    public function coupon(string $code): ?array
    {
        $q = $this->db->prepare(
            "SELECT code, description, discount_type, discount_value,
                    minimum_order_amount, expires_at
             FROM coupons
             WHERE code=?
               AND status='active'
               AND (starts_at IS NULL OR starts_at<=NOW())
               AND (expires_at IS NULL OR expires_at>=NOW())
               AND (usage_limit IS NULL OR used_count<usage_limit)
             LIMIT 1",
        );
        $q->execute([strtoupper(trim($code))]);
        $coupon = $q->fetch();

        return $coupon ?: null;
    }

    // -----------------------------------------------------------------------
    // Addresses
    // -----------------------------------------------------------------------

    public function addresses(int $userId): array
    {
        $q = $this->db->prepare(
            'SELECT id, label, recipient_name name, address_line line,
                    city, state, country, phone, is_default default_address
             FROM customer_addresses
             WHERE user_id=?
             ORDER BY is_default DESC, id DESC',
        );
        $q->execute([$userId]);

        return $q->fetchAll();
    }

    public function addAddress(int $userId, array $data): void
    {
        if (!empty($data['is_default'])) {
            $this->db->prepare('UPDATE customer_addresses SET is_default=0 WHERE user_id=?')
                ->execute([$userId]);
        }

        $this->db->prepare(
            'INSERT INTO customer_addresses
             (user_id, label, recipient_name, address_line, city, state, country, phone, is_default)
             VALUES (?,?,?,?,?,?,?,?,?)',
        )->execute([
            $userId,
            $data['label'],
            $data['recipient_name'],
            $data['address_line'],
            $data['city'],
            $data['state']   ?: null,
            $data['country'],
            $data['phone']   ?: null,
            !empty($data['is_default']) ? 1 : 0,
        ]);
    }

    public function deleteAddress(int $userId, int $addressId): void
    {
        $q = $this->db->prepare('DELETE FROM customer_addresses WHERE user_id=? AND id=?');
        $q->execute([$userId, $addressId]);
    }

    public function setDefaultAddress(int $userId, int $addressId): void
    {
        $this->db->beginTransaction();
        $this->db->prepare('UPDATE customer_addresses SET is_default=0 WHERE user_id=?')->execute([$userId]);
        $this->db->prepare('UPDATE customer_addresses SET is_default=1 WHERE user_id=? AND id=?')->execute([$userId, $addressId]);
        $this->db->commit();
    }

    // -----------------------------------------------------------------------
    // Payments
    // -----------------------------------------------------------------------

    public function payments(int $userId): array
    {
        $q = $this->db->prepare(
            "SELECT method_type type, last_four last4, expires_at expires, is_default
             FROM payment_methods
             WHERE user_id=? AND method_type<>'wallet'
             ORDER BY is_default DESC, id DESC",
        );
        $q->execute([$userId]);

        return $q->fetchAll();
    }

    public function addPaymentMethod(int $userId, string $type, string $lastFour, string $expires): void
    {
        $q = $this->db->prepare(
            'INSERT INTO payment_methods(user_id,method_type,last_four,expires_at,is_default) VALUES(?,?,?,?,?)',
        );
        $q->execute([$userId, $type, $lastFour, $expires, 0]);
    }

    public function transactions(int $userId): array
    {
        $q = $this->db->prepare(
            "SELECT DATE_FORMAT(created_at,'%d %b %Y') date,
                    description,
                    CONCAT(UCASE(LEFT(transaction_type,1)),LCASE(SUBSTRING(transaction_type,2))) type,
                    CASE WHEN transaction_type='credit' THEN amount ELSE -amount END amount
             FROM wallet_transactions
             WHERE user_id=?
             ORDER BY id DESC",
        );
        $q->execute([$userId]);

        return $q->fetchAll();
    }

    // -----------------------------------------------------------------------
    // Reviews & support
    // -----------------------------------------------------------------------

    public function reviews(int $userId): array
    {
        $q = $this->db->prepare(
            "SELECT product_name product, rating, review_text text,
                    DATE_FORMAT(created_at,'%d %b %Y') date
             FROM customer_reviews
             WHERE user_id=?
             ORDER BY id DESC",
        );
        $q->execute([$userId]);

        return $q->fetchAll();
    }

    public function addReview(int $userId, string $product, int $rating, string $text): void
    {
        $this->db->prepare(
            'INSERT INTO customer_reviews(user_id,product_name,rating,review_text,status) VALUES(?,?,?,?,?)',
        )->execute([$userId, $product, $rating, $text, 'pending']);
    }

    public function tickets(int $userId): array
    {
        $q = $this->db->prepare(
            "SELECT ticket_number id, subject,
                    CONCAT(UCASE(LEFT(status,1)),LCASE(SUBSTRING(status,2))) status,
                    DATE_FORMAT(created_at,'%d %b %Y') date
             FROM support_tickets
             WHERE user_id=?
             ORDER BY id DESC",
        );
        $q->execute([$userId]);

        return $q->fetchAll();
    }

    public function createTicket(int $userId, string $subject, string $message): string
    {
        $number = 'TKT-' . date('ymd') . '-' . random_int(1000, 9999);
        $q      = $this->db->prepare(
            'INSERT INTO support_tickets(ticket_number,user_id,subject,message) VALUES(?,?,?,?)',
        );
        $q->execute([$number, $userId, $subject, $message]);

        return $number;
    }

    // -----------------------------------------------------------------------
    // Notifications
    // -----------------------------------------------------------------------

    public function notifications(int $userId, int $limit = 5): array
    {
        $limit = max(1, min(100, $limit));
        $q     = $this->db->prepare(
            "SELECT id, title, COALESCE(body,'') body, type, read_at,
                    DATE_FORMAT(created_at,'%d %b %Y') time
             FROM customer_notifications
             WHERE user_id=?
             ORDER BY id DESC
             LIMIT $limit",
        );
        $q->execute([$userId]);

        return $q->fetchAll();
    }

    public function unreadNotifications(int $userId): int
    {
        $q = $this->db->prepare(
            'SELECT COUNT(*) FROM customer_notifications WHERE user_id=? AND read_at IS NULL',
        );
        $q->execute([$userId]);

        return (int) $q->fetchColumn();
    }

    public function markNotificationRead(int $userId, int $notificationId): void
    {
        $q = $this->db->prepare(
            'UPDATE customer_notifications SET read_at=COALESCE(read_at,NOW()) WHERE id=? AND user_id=?',
        );
        $q->execute([$notificationId, $userId]);
    }

    public function markAllNotificationsRead(int $userId): void
    {
        $q = $this->db->prepare(
            'UPDATE customer_notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL',
        );
        $q->execute([$userId]);
    }

    public function createNotification(int $userId, string $title, string $body, string $type = 'info'): void
    {
        $q = $this->db->prepare(
            'INSERT INTO customer_notifications(user_id,title,body,type) VALUES(?,?,?,?)',
        );
        $q->execute([$userId, $title, $body, $type]);
    }

    public function notificationPreferences(int $userId): array
    {
        $q = $this->db->prepare(
            'SELECT order_updates, account_alerts, promotions
             FROM customer_notification_preferences
             WHERE user_id=?
             LIMIT 1',
        );
        $q->execute([$userId]);
        $preferences = $q->fetch();

        return $preferences ?: ['order_updates' => 1, 'account_alerts' => 1, 'promotions' => 1];
    }

    public function saveNotificationPreferences(int $userId, array $preferences): void
    {
        try {
            $q = $this->db->prepare(
                'INSERT INTO customer_notification_preferences(user_id,order_updates,account_alerts,promotions)
                 VALUES(?,?,?,?)
                 ON DUPLICATE KEY UPDATE
                   order_updates=VALUES(order_updates),
                   account_alerts=VALUES(account_alerts),
                   promotions=VALUES(promotions)',
            );
            $q->execute([
                $userId,
                (int) !empty($preferences['order_updates']),
                (int) !empty($preferences['account_alerts']),
                (int) !empty($preferences['promotions']),
            ]);
        } catch (\PDOException) {
            try {
                $this->db->query(
                    'CREATE TABLE IF NOT EXISTS customer_notification_preferences (
                        user_id BIGINT UNSIGNED PRIMARY KEY,
                        order_updates TINYINT(1) DEFAULT 1,
                        account_alerts TINYINT(1) DEFAULT 1,
                        promotions TINYINT(1) DEFAULT 1,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    )',
                );
                $q = $this->db->prepare(
                    'INSERT INTO customer_notification_preferences(user_id,order_updates,account_alerts,promotions)
                     VALUES(?,?,?,?)
                     ON DUPLICATE KEY UPDATE
                       order_updates=VALUES(order_updates),
                       account_alerts=VALUES(account_alerts),
                       promotions=VALUES(promotions)',
                );
                $q->execute([
                    $userId,
                    (int) !empty($preferences['order_updates']),
                    (int) !empty($preferences['account_alerts']),
                    (int) !empty($preferences['promotions']),
                ]);
            } catch (\PDOException) {
                // Ignore — the table could not be created.
            }
        }
    }

    // -----------------------------------------------------------------------
    // Account / security
    // -----------------------------------------------------------------------

    public function updateProfile(int $userId, string $name, string $username, string $email, ?string $phone): array
    {
        $name = trim($name);
        $username = trim($username);
        $email = strtolower(trim($email));
        $phone = $phone ? trim($phone) : null;

        if ($name === '' || strlen($name) < 2) {
            return ['success' => false, 'message' => 'Please enter your full legal name.'];
        }

        if ($username === '' || strlen($username) < 3) {
            return ['success' => false, 'message' => 'Username must contain at least 3 characters.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Please provide a valid email address.'];
        }

        // Check if username is already taken
        $checkUser = $this->db->prepare('SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1');
        $checkUser->execute([$username, $userId]);
        if ($checkUser->fetch()) {
            return ['success' => false, 'message' => 'This username is already claimed by another member.'];
        }

        // Check if email is already taken
        $checkEmail = $this->db->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $checkEmail->execute([$email, $userId]);
        if ($checkEmail->fetch()) {
            return ['success' => false, 'message' => 'This email address is already associated with another account.'];
        }

        $stmt = $this->db->prepare('UPDATE users SET name = ?, username = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$name, $username, $email, $phone, $userId]);

        // Keep session updated
        if (!empty($_SESSION['user']) && (int)($_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? 0) === $userId) {
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['username'] = $username;
            $_SESSION['user']['email'] = $email;
            if ($phone !== null) {
                $_SESSION['user']['phone'] = $phone;
            }
        }

        return ['success' => true, 'message' => 'Your profile dossier has been updated successfully.'];
    }

    public function changePassword(int $userId, string $current, string $new): bool
    {
        $q = $this->db->prepare(
            "SELECT password_hash FROM users WHERE id=? AND status='active' LIMIT 1",
        );
        $q->execute([$userId]);
        $hash = $q->fetchColumn();

        if (!$hash || !password_verify($current, (string) $hash)) {
            return false;
        }

        $this->db->prepare('UPDATE users SET password_hash=?,updated_at=NOW() WHERE id=?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);

        return true;
    }

    // -----------------------------------------------------------------------
    // Cart helpers
    // -----------------------------------------------------------------------

    public function productForCart(int $productId): ?array
    {
        $q = $this->db->prepare(
            "SELECT id, name, price, quantity, featured_image
             FROM products WHERE id=? AND status='published' LIMIT 1",
        );
        $q->execute([$productId]);
        $product = $q->fetch();

        return $product ?: null;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function defaultAddress(int $userId): array
    {
        $rows = $this->addresses($userId);
        $a    = $rows[0] ?? ['name' => '', 'line' => '', 'city' => '', 'phone' => ''];

        return [
            'name'  => $a['name']  ?? '',
            'line'  => $a['line']  ?? '',
            'city'  => $a['city']  ?? '',
            'phone' => $a['phone'] ?? '',
        ];
    }
}
