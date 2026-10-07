<?php

declare(strict_types=1);

namespace Noir\App\Services;

use Noir\Core\Container;
use PDO;
use Throwable;

final class QueueService
{
    /**
     * Push a new job onto the queue.
     */
    public static function dispatch(string $jobClass, array $payload = [], int $delaySeconds = 0, string $queue = 'default'): int
    {
        $db = Container::get('db');
        if (!($db instanceof PDO)) {
            return 0;
        }

        $availableAt = date('Y-m-d H:i:s', time() + max(0, $delaySeconds));
        $stmt = $db->prepare(
            'INSERT INTO jobs (queue, job_class, payload, available_at, created_at)
             VALUES (?, ?, ?, ?, NOW())'
        );

        $stmt->execute([
            $queue,
            $jobClass,
            json_encode($payload, JSON_UNESCAPED_SLASHES),
            $availableAt,
        ]);

        return (int)$db->lastInsertId();
    }

    /**
     * Reserve the next available job for processing (atomic lock).
     */
    public static function reserveNext(string $queue = 'default'): ?array
    {
        $db = Container::get('db');
        if (!($db instanceof PDO)) {
            return null;
        }

        $db->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');
            // Select one available job and hold a row lock
            $stmt = $db->prepare(
                'SELECT * FROM jobs 
                 WHERE queue = ? 
                   AND available_at <= ? 
                   AND (reserved_at IS NULL OR reserved_at < DATE_SUB(?, INTERVAL 300 SECOND))
                 ORDER BY id ASC 
                 LIMIT 1 
                 FOR UPDATE'
            );
            $stmt->execute([$queue, $now, $now]);
            $job = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($job) {
                // Mark reserved
                $update = $db->prepare('UPDATE jobs SET reserved_at = ?, attempts = attempts + 1 WHERE id = ?');
                $update->execute([$now, $job['id']]);
                $db->commit();
                $job['payload'] = json_decode((string)$job['payload'], true) ?: [];
                return $job;
            }

            $db->commit();
            return null;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return null;
        }
    }

    /**
     * Delete a successfully completed job from the queue.
     */
    public static function complete(int $jobId): bool
    {
        $db = Container::get('db');
        if (!($db instanceof PDO)) {
            return false;
        }

        $stmt = $db->prepare('DELETE FROM jobs WHERE id = ?');
        return $stmt->execute([$jobId]);
    }

    /**
     * Record a failed job and remove it from the active queue.
     */
    public static function fail(array $job, Throwable $e): void
    {
        $db = Container::get('db');
        if (!($db instanceof PDO)) {
            return;
        }

        try {
            $db->prepare(
                'INSERT INTO failed_jobs (queue, job_class, payload, exception, failed_at)
                 VALUES (?, ?, ?, ?, NOW())'
            )->execute([
                $job['queue'] ?? 'default',
                $job['job_class'] ?? 'unknown',
                is_string($job['payload']) ? $job['payload'] : json_encode($job['payload'] ?? []),
                $e->getMessage() . "\n" . $e->getTraceAsString(),
            ]);

            $db->prepare('DELETE FROM jobs WHERE id = ?')->execute([$job['id']]);
        } catch (Throwable) {}
    }

    /**
     * Process a single job by instantiating the handler or executing the known routine.
     */
    public static function handle(array $job): void
    {
        $jobClass = (string)($job['job_class'] ?? '');
        $payload = is_array($job['payload']) ? $job['payload'] : (json_decode((string)$job['payload'], true) ?: []);

        switch ($jobClass) {
            case 'SendOrderConfirmation':
                self::processOrderConfirmation($payload);
                break;
            default:
                if (class_exists($jobClass)) {
                    $handler = new $jobClass();
                    if (method_exists($handler, 'handle')) {
                        $handler->handle($payload);
                    }
                }
                break;
        }
    }

    /**
     * Background handler for Klaviyo tracking and Firebase push notifications.
     */
    private static function processOrderConfirmation(array $payload): void
    {
        $email = (string)($payload['email'] ?? '');
        $orderNumber = (string)($payload['order_number'] ?? '');
        $total = (float)($payload['total'] ?? 0.0);
        $currency = (string)($payload['currency'] ?? 'NGN');
        $items = (array)($payload['items'] ?? []);
        $fcmToken = (string)($payload['fcm_token'] ?? '');

        // 1. Dispatch Transactional Order Confirmation Email (Brevo)
        try {
            if (!empty($email)) {
                $mailer = new \Noir\Core\Mailer();
                $orderData = [
                    'order_number' => $orderNumber,
                    'total' => $total,
                    'currency' => $currency,
                    'subtotal' => $total,
                    'payment_status' => 'Paid',
                ];
                $mailer->sendOrderConfirmation($email, (string)($payload['customer_name'] ?? 'Valued Customer'), $orderData, $items);
            }
        } catch (Throwable $e) {
            error_log('Queue worker Mailer error: ' . $e->getMessage());
        }

        // 2. Dispatch Klaviyo tracking
        try {
            $klaviyo = new KlaviyoService();
            if ($klaviyo->isConfigured() && !empty($email)) {
                $klaviyo->trackPlacedOrder($email, $orderNumber, $total, $currency, $items);
            }
        } catch (Throwable $e) {
            error_log('Queue worker Klaviyo error: ' . $e->getMessage());
        }

        // 3. Dispatch FCM Push Notification
        try {
            if (!empty($fcmToken)) {
                $fcm = new FirebaseService();
                $fcm->sendPushNotification(
                    $fcmToken,
                    'Payment Successful!',
                    "Your order #{$orderNumber} has been confirmed.",
                    ['order_number' => $orderNumber]
                );
            }
        } catch (Throwable $e) {
            error_log('Queue worker FCM error: ' . $e->getMessage());
        }
    }
}
