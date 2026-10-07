<?php

declare(strict_types=1);

namespace Noir\App\Services;

use PDO;

final class CartService
{
    /**
     * Synchronize session cart with database cart for authenticated user.
     * Merges any guest cart items into the database, then hydrates $_SESSION['cart'].
     */
    public static function syncSessionCart(PDO $db, ?int $userId): array
    {
        if ($userId === null || $userId <= 0) {
            return $_SESSION['cart'] ?? [];
        }

        try {
            // 1. Ensure table exists
            $db->exec("CREATE TABLE IF NOT EXISTS customer_cart_items (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NOT NULL,
                quantity INT UNSIGNED NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_user_product (user_id, product_id)
            ) ENGINE=InnoDB;");

            // 2. If session cart has items, merge them into database
            if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                $upsert = $db->prepare("
                    INSERT INTO customer_cart_items (user_id, product_id, quantity)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE quantity = GREATEST(quantity, VALUES(quantity))
                ");
                foreach ($_SESSION['cart'] as $item) {
                    $pid = (int)($item['id'] ?? 0);
                    $qty = max(1, (int)($item['quantity'] ?? 1));
                    if ($pid > 0) {
                        $upsert->execute([$userId, $pid, $qty]);
                    }
                }
            }

            // 3. Hydrate session cart from database
            $stmt = $db->prepare("
                SELECT c.product_id as id, c.quantity, p.name, p.price, p.featured_image
                FROM customer_cart_items c
                JOIN products p ON p.id = c.product_id
                WHERE c.user_id = ? AND p.status = 'published'
                ORDER BY c.updated_at DESC
            ");
            $stmt->execute([$userId]);
            $dbItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $syncedCart = [];
            foreach ($dbItems as $line) {
                $key = (string)$line['id'];
                $syncedCart[$key] = [
                    'id' => (int)$line['id'],
                    'name' => (string)$line['name'],
                    'price' => (float)$line['price'],
                    'image' => (string)($line['featured_image'] ?? ''),
                    'quantity' => max(1, (int)$line['quantity']),
                ];
            }

            $_SESSION['cart'] = $syncedCart;
            return $syncedCart;
        } catch (\Throwable $e) {
            error_log('CartService sync notice: ' . $e->getMessage());
            return $_SESSION['cart'] ?? [];
        }
    }

    /**
     * Add product to cart and persist across session and database.
     */
    public static function addItem(PDO $db, ?int $userId, int $productId, int $quantity, array $product): void
    {
        $cart = $_SESSION['cart'] ?? [];
        $key = (string)$productId;
        $existingQty = (int)($cart[$key]['quantity'] ?? 0);
        $newQty = $existingQty + $quantity;

        $cart[$key] = [
            'id' => (int)$product['id'],
            'name' => (string)$product['name'],
            'price' => (float)$product['price'],
            'image' => (string)($product['featured_image'] ?? ''),
            'quantity' => $newQty,
        ];
        $_SESSION['cart'] = $cart;

        if ($userId !== null && $userId > 0) {
            try {
                $db->prepare("
                    INSERT INTO customer_cart_items (user_id, product_id, quantity)
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE quantity = ?
                ")->execute([$userId, $productId, $newQty, $newQty]);
            } catch (\Throwable $e) {
                error_log('CartService addItem notice: ' . $e->getMessage());
            }
        }
    }

    /**
     * Remove item from cart across session and database.
     */
    public static function removeItem(PDO $db, ?int $userId, int $productId): void
    {
        $key = (string)$productId;
        if (isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
        }

        if ($userId !== null && $userId > 0) {
            try {
                $db->prepare("DELETE FROM customer_cart_items WHERE user_id = ? AND product_id = ?")
                   ->execute([$userId, $productId]);
            } catch (\Throwable $e) {
                error_log('CartService removeItem notice: ' . $e->getMessage());
            }
        }
    }

    /**
     * Clear user cart across session and database.
     */
    public static function clearCart(PDO $db, ?int $userId): void
    {
        $_SESSION['cart'] = [];

        if ($userId !== null && $userId > 0) {
            try {
                $db->prepare("DELETE FROM customer_cart_items WHERE user_id = ?")
                   ->execute([$userId]);
            } catch (\Throwable $e) {
                error_log('CartService clearCart notice: ' . $e->getMessage());
            }
        }
    }
}
