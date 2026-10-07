<?php
declare(strict_types=1);

namespace Noir\App\Services;

use PDO;

final class PaymentService
{
    public function __construct(private readonly PDO $db) {}

    public function createPendingOrder(array $cart, array $customer, int $userId, string $currency, string $provider): array
    {
        if (!$cart) throw new \RuntimeException('Your cart is empty.');
        $this->db->beginTransaction();
        try {
            $items = []; $total = 0.0;
            $rate = match (strtoupper($currency)) {
                'USD' => 0.00065,
                'EUR' => 0.00060,
                'GBP' => 0.00052,
                'CAD' => 0.00088,
                'AUD' => 0.00098,
                'GHS' => 0.0098,
                'KES' => 0.084,
                'ZAR' => 0.012,
                default => 1.0,
            };
            $productQuery = $this->db->prepare("SELECT id,name,price,featured_image,quantity FROM products WHERE id=? AND status='published' FOR UPDATE");
            foreach ($cart as $line) {
                $productQuery->execute([(int)($line['id'] ?? 0)]); $product = $productQuery->fetch();
                $quantity = max(1, min(99, (int)($line['quantity'] ?? 1)));
                if (!$product || (int)$product['quantity'] < $quantity) throw new \RuntimeException('One or more items in your selection are no longer available in the requested quantity.');
                $convertedPrice = round((float)$product['price'] * $rate, 2);
                $items[] = ['product' => $product, 'quantity' => $quantity, 'unit_price' => $convertedPrice];
                $total += $convertedPrice * $quantity;
            }
            $total = round($total, 2);
            $number = 'ORD-' . strtoupper(bin2hex(random_bytes(5)));
            $order = $this->db->prepare('INSERT INTO orders(order_number,user_id,status,payment_status,total,currency) VALUES(?,?,\'pending\',\'pending\',?,?)');
            $order->execute([$number, $userId ?: null, $total, $currency]); $orderId = (int)$this->db->lastInsertId();
            $item = $this->db->prepare('INSERT INTO order_items(order_id,product_id,product_name,quantity,unit_price,image_url) VALUES(?,?,?,?,?,?)');
            foreach ($items as $line) $item->execute([$orderId, $line['product']['id'], $line['product']['name'], $line['quantity'], $line['unit_price'], $line['product']['featured_image']]);
            $reference = 'nrc_' . bin2hex(random_bytes(12));
            $payment = $this->db->prepare('INSERT INTO payment_transactions(order_id,provider,provider_reference,status,amount,currency,gateway_payload) VALUES(?,?,?,\'initialized\',?,?,?)');
            $payment->execute([$orderId, $provider, $reference, $total, $currency, json_encode(['customer' => $customer], JSON_UNESCAPED_SLASHES)]);
            $this->db->commit();
            return ['order_id' => $orderId, 'order_number' => $number, 'reference' => $reference, 'total' => $total, 'currency' => $currency];
        } catch (\Throwable $exception) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $exception; }
    }

    public function initializePaystack(array $order, string $email, string $callbackUrl, string $secretKey): string
    {
        $result = $this->request('https://api.paystack.co/transaction/initialize', $secretKey, [
            'email' => $email, 'amount' => (int)round($order['total'] * 100), 'currency' => $order['currency'],
            'reference' => $order['reference'], 'callback_url' => $callbackUrl,
            'metadata' => ['order_number' => $order['order_number']],
        ]);
        if (empty($result['status']) || empty($result['data']['authorization_url'])) throw new \RuntimeException($result['message'] ?? 'Unable to start payment.');
        $saved = $this->db->prepare('SELECT gateway_payload FROM payment_transactions WHERE provider_reference=? LIMIT 1');
        $saved->execute([$order['reference']]);
        $savedPayload = json_decode((string)$saved->fetchColumn(), true);
        if (is_array($savedPayload) && isset($savedPayload['customer'])) $result['customer'] = $savedPayload['customer'];
        $this->db->prepare('UPDATE payment_transactions SET authorization_url=?,gateway_payload=? WHERE provider_reference=?')->execute([$result['data']['authorization_url'], json_encode($result, JSON_UNESCAPED_SLASHES), $order['reference']]);
        return (string)$result['data']['authorization_url'];
    }

    public function verifyPaystack(string $reference, string $secretKey): array
    {
        $result = $this->request('https://api.paystack.co/transaction/verify/' . rawurlencode($reference), $secretKey);
        
        $payment = $this->db->prepare('SELECT pt.*,o.order_number,o.total,o.currency,o.user_id FROM payment_transactions pt JOIN orders o ON o.id=pt.order_id WHERE pt.provider_reference=? AND pt.provider=\'paystack\' LIMIT 1');
        $payment->execute([$reference]);
        $row = $payment->fetch();
        if (!$row) {
            throw new \RuntimeException('Payment record was not found.');
        }
        // Paystack may retry webhooks; an already paid transaction is strictly idempotent and immutable.
        if (($row['status'] ?? '') === 'paid') {
            return ['success' => true, 'order_number' => $row['order_number']];
        }
        $successful = !empty($result['status']) && (($result['data']['status'] ?? '') === 'success') && (int)($result['data']['amount'] ?? 0) === (int)round((float)$row['amount'] * 100) && strtoupper((string)($result['data']['currency'] ?? '')) === strtoupper((string)$row['currency']);
        if ($successful) {
            $this->db->beginTransaction();
            try {
                // Preserve the checkout customer/shipping details alongside the
                // provider response so the order can display the address later.
                $existingPayload = json_decode((string)($row['gateway_payload'] ?? ''), true);
                if (!is_array($existingPayload)) $existingPayload = [];
                $result['customer'] = $existingPayload['customer'] ?? null;
                $this->db->prepare("UPDATE payment_transactions SET status='paid',paid_at=NOW(),gateway_payload=? WHERE id=?")->execute([json_encode($result, JSON_UNESCAPED_SLASHES), $row['id']]);
                $this->db->prepare("UPDATE orders SET payment_status='paid',status=CASE WHEN status='pending' THEN 'processing' ELSE status END WHERE id=?")->execute([$row['order_id']]);
                
                // Atomically decrement purchased product inventory
                $orderItemsStmt = $this->db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
                $orderItemsStmt->execute([$row['order_id']]);
                $purchasedItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);

                $deductStock = $this->db->prepare('UPDATE products SET quantity = GREATEST(0, quantity - ?) WHERE id = ?');
                foreach ($purchasedItems as $pItem) {
                    $pid = (int)($pItem['product_id'] ?? 0);
                    $pqty = (int)($pItem['quantity'] ?? 0);
                    if ($pid > 0 && $pqty > 0) {
                        $deductStock->execute([$pqty, $pid]);
                    }
                }
                
                // Fetch order items & FCM token for async notification dispatch
                $orderItems = $this->db->prepare('SELECT product_name, quantity, unit_price FROM order_items WHERE order_id=?');
                $orderItems->execute([$row['order_id']]);
                $items = $orderItems->fetchAll(\PDO::FETCH_ASSOC);

                $userQ = $this->db->prepare('SELECT u.fcm_token FROM users u JOIN orders o ON o.user_id = u.id WHERE o.id = ?');
                $userQ->execute([$row['order_id']]);
                $fcmToken = (string)($userQ->fetchColumn() ?: '');

                // Dispatch to async queue for sub-20ms instant customer checkout response
                \Noir\App\Services\QueueService::dispatch('SendOrderConfirmation', [
                    'email' => (string)($result['customer']['email'] ?? ''),
                    'order_number' => (string)($row['order_number'] ?? ''),
                    'total' => (float)($row['total'] ?? 0),
                    'currency' => (string)($row['currency'] ?? 'NGN'),
                    'items' => $items,
                    'fcm_token' => $fcmToken,
                ]);

                $this->db->commit();
            } catch (\Throwable $exception) { if ($this->db->inTransaction()) $this->db->rollBack(); throw $exception; }
            // Log the successful order placement in the activity feed
            try {
                $userId = (int)($row['user_id'] ?? 0);
                $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
                $this->db->prepare(
                    'INSERT INTO activity_logs (user_id,actor_name,event,subject_type,subject_id,subject_label,ip_address,metadata,platform,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())'
                )->execute([
                    $userId ?: null,
                    null, // resolved later via JOIN if needed
                    'order.placed',
                    'order',
                    (int)$row['order_id'],
                    'Order #' . $row['order_number'],
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    json_encode(['order_number' => $row['order_number'], 'total' => $row['total'], 'currency' => $row['currency']], JSON_UNESCAPED_SLASHES),
                    'web',
                    $ua ?: null,
                ]);
            } catch (\Throwable) {}
            return ['success' => true, 'order_number' => $row['order_number']];
        }
        $failedPayload = json_decode((string)($row['gateway_payload'] ?? ''), true);
        if (is_array($failedPayload) && isset($failedPayload['customer'])) $result['customer'] = $failedPayload['customer'];
        $this->db->prepare("UPDATE payment_transactions SET status='failed',gateway_payload=? WHERE id=?")->execute([json_encode($result, JSON_UNESCAPED_SLASHES), $row['id']]);
        $this->db->prepare("UPDATE orders SET payment_status='failed' WHERE id=?")->execute([$row['order_id']]);
        // Log the payment failure
        try {
            $userId = (int)($row['user_id'] ?? 0);
            $ua = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
            $this->db->prepare(
                'INSERT INTO activity_logs (user_id,event,subject_type,subject_id,subject_label,ip_address,metadata,platform,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())'
            )->execute([
                $userId ?: null,
                'payment.failed',
                'order',
                (int)$row['order_id'],
                'Order #' . $row['order_number'],
                $_SERVER['REMOTE_ADDR'] ?? null,
                json_encode(['order_number' => $row['order_number'], 'reference' => $reference], JSON_UNESCAPED_SLASHES),
                'web',
                $ua ?: null,
            ]);
        } catch (\Throwable) {}
        return ['success' => false, 'order_number' => $row['order_number']];
    }

    public function recoverablePayment(string $reference): ?array
    {
        $payment = $this->db->prepare("SELECT pt.provider_reference,pt.status,pt.amount,pt.currency,o.order_number,o.payment_status,o.status order_status FROM payment_transactions pt JOIN orders o ON o.id=pt.order_id WHERE pt.provider_reference=? AND pt.provider='paystack' LIMIT 1");
        $payment->execute([$reference]);
        $row = $payment->fetch();
        if (!$row || in_array((string)$row['status'], ['paid', 'refunded'], true) || (string)$row['payment_status'] === 'paid') {
            return null;
        }
        return $row;
    }

    public function retryPaystack(string $reference, string $callbackUrl, string $secretKey): string
    {
        $payment = $this->db->prepare("SELECT pt.*,o.order_number,o.payment_status,o.status order_status FROM payment_transactions pt JOIN orders o ON o.id=pt.order_id WHERE pt.provider_reference=? AND pt.provider='paystack' LIMIT 1");
        $payment->execute([$reference]);
        $row = $payment->fetch();
        if (!$row || in_array((string)$row['status'], ['paid', 'refunded'], true) || (string)$row['payment_status'] === 'paid') {
            throw new \RuntimeException('This payment can no longer be retried.');
        }
        $payload = json_decode((string)($row['gateway_payload'] ?? ''), true);
        $customer = is_array($payload) && isset($payload['customer']) && is_array($payload['customer']) ? $payload['customer'] : [];
        $email = filter_var((string)($customer['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            throw new \RuntimeException('The saved checkout email is missing.');
        }
        $newReference = 'nrc_' . bin2hex(random_bytes(12));
        $this->db->prepare("INSERT INTO payment_transactions(order_id,provider,provider_reference,status,amount,currency,gateway_payload) VALUES(?,?,?,'initialized',?,?,?)")
            ->execute([(int)$row['order_id'], 'paystack', $newReference, $row['amount'], $row['currency'], json_encode(['customer' => $customer, 'retry_of' => $reference], JSON_UNESCAPED_SLASHES)]);
        $this->db->prepare("UPDATE orders SET payment_status='pending',status=CASE WHEN status='pending' THEN status ELSE 'pending' END WHERE id=?")
            ->execute([(int)$row['order_id']]);
        return $this->initializePaystack([
            'order_id' => (int)$row['order_id'],
            'order_number' => $row['order_number'],
            'reference' => $newReference,
            'total' => (float)$row['amount'],
            'currency' => (string)$row['currency'],
        ], (string)$email, $callbackUrl, $secretKey);
    }

    private function request(string $url, string $secretKey, ?array $payload = null): array
    {
        if (!function_exists('curl_init')) throw new \RuntimeException('cURL is required to contact the payment provider.');
        $curl = curl_init($url); $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey, 'Content-Type: application/json']];
        if ($payload !== null) $options += [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES)];
        curl_setopt_array($curl, $options); $body = curl_exec($curl); $error = curl_error($curl); $code = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE); curl_close($curl);
        if ($body === false || $error !== '') throw new \RuntimeException('Payment provider connection failed.');
        $decoded = json_decode((string)$body, true); if (!is_array($decoded) || $code >= 500) throw new \RuntimeException('Payment provider is unavailable.');
        return $decoded;
    }
}
