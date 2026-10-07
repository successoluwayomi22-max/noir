<?php

declare(strict_types=1);

namespace Noir\App\Repositories;

use PDO;
use Noir\App\Contracts\ReportRepositoryInterface;

final class ReportRepository implements ReportRepositoryInterface
{
    public function __construct(private readonly PDO $db) {}

    /**
     * Primary commerce KPI aggregation for the selected date range.
     */
    public function summary(string $from, string $toExclusive): array
    {
        $params = [$from, $toExclusive];

        // Orders & Revenue
        $statsStmt = $this->db->prepare("
            SELECT 
                COUNT(*) AS orders,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled', 'refunded') THEN total ELSE 0 END), 0) AS revenue,
                COALESCE(AVG(CASE WHEN status NOT IN ('cancelled', 'refunded') THEN total END), 0) AS average_order 
            FROM orders 
            WHERE created_at >= ? AND created_at < ?
        ");
        $statsStmt->execute($params);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC) ?: ['orders' => 0, 'revenue' => 0, 'average_order' => 0];

        // New Customers
        $custStmt = $this->db->prepare("
            SELECT COUNT(*) FROM users 
            WHERE account_type = 'customer' AND created_at >= ? AND created_at < ?
        ");
        $custStmt->execute($params);
        $newCustomers = (int)$custStmt->fetchColumn();

        // Active Staff & Published Products
        $activeStaff = (int)($this->db->query("
            SELECT COUNT(*) FROM users u 
            WHERE u.status = 'active' AND u.account_type = 'staff' AND u.email <> 'admin@noir.test' 
            AND NOT EXISTS (
                SELECT 1 FROM user_roles ur JOIN roles r ON r.id = ur.role_id 
                WHERE ur.user_id = u.id AND r.slug = 'super-admin'
            )
        ")->fetchColumn() ?: 0);

        $productCount = (int)($this->db->query("
            SELECT COUNT(*) FROM products WHERE status = 'published'
        ")->fetchColumn() ?: 0);

        return [
            'orders' => (int)($stats['orders'] ?? 0),
            'revenue' => (float)($stats['revenue'] ?? 0),
            'average_order' => (float)($stats['average_order'] ?? 0),
            'new_customers' => $newCustomers,
            'active_staff' => $activeStaff,
            'product_count' => $productCount,
        ];
    }

    public function orderStatuses(string $from, string $toExclusive): array
    {
        $stmt = $this->db->prepare("
            SELECT status, COUNT(*) AS total 
            FROM orders 
            WHERE created_at >= ? AND created_at < ? 
            GROUP BY status 
            ORDER BY total DESC
        ");
        $stmt->execute([$from, $toExclusive]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function dailySales(string $from, string $toExclusive): array
    {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) AS day,
                COUNT(*) AS orders,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled', 'refunded') THEN total ELSE 0 END), 0) AS revenue 
            FROM orders 
            WHERE created_at >= ? AND created_at < ? 
            GROUP BY DATE(created_at) 
            ORDER BY day
        ");
        $stmt->execute([$from, $toExclusive]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function applicationStatuses(string $from, string $toExclusive): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT status, COUNT(*) AS total 
                FROM staff_applications 
                WHERE created_at >= ? AND created_at < ? 
                GROUP BY status 
                ORDER BY total DESC
            ");
            $stmt->execute([$from, $toExclusive]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function recentActivity(string $from, string $toExclusive, int $limit = 12): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT a.event, a.subject_type, a.created_at, u.name AS user_name 
                FROM activity_logs a 
                LEFT JOIN users u ON u.id = a.user_id 
                WHERE a.created_at >= ? AND a.created_at < ? 
                ORDER BY a.id DESC 
                LIMIT " . (int)$limit . "
            ");
            $stmt->execute([$from, $toExclusive]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function roleBreakdown(): array
    {
        try {
            return $this->db->query("
                SELECT r.name, COUNT(DISTINCT ur.user_id) AS total 
                FROM user_roles ur 
                JOIN roles r ON r.id = ur.role_id 
                JOIN users u ON u.id = ur.user_id 
                WHERE u.account_type = 'staff' AND u.email <> 'admin@noir.test' AND r.slug <> 'super-admin' 
                GROUP BY r.id, r.name 
                ORDER BY total DESC
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function paymentRates(string $from, string $toExclusive): array
    {
        $stmt = $this->db->prepare("
            SELECT payment_status, COUNT(*) AS total 
            FROM orders 
            WHERE created_at >= ? AND created_at < ? 
            GROUP BY payment_status
        ");
        $stmt->execute([$from, $toExclusive]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function observabilitySummary(string $from, string $toExclusive): array
    {
        $fromTime = strtotime($from . ' 00:00:00');
        $toTime = strtotime($toExclusive . ' 00:00:00');
        $events = [
            'search_submitted' => 0,
            'product_viewed' => 0,
            'product_clicked' => 0,
            'cart_intent' => 0,
            'checkout_started' => 0,
            'checkout_success' => 0,
            'checkout_failure' => 0,
            'payment_retry_clicked' => 0,
            'performance_metric' => 0,
        ];
        $failedSearches = [];
        $topProducts = [];
        $performance = ['lcp' => null, 'page_load' => null, 'slow_interaction' => 0, 'cls' => null];
        $usedAnalyticsTable = false;

        try {
            $tableExists = (bool)$this->db->query("
                SELECT COUNT(*) FROM information_schema.tables 
                WHERE table_schema = DATABASE() AND table_name = 'analytics_events'
            ")->fetchColumn();

            if ($tableExists) {
                $usedAnalyticsTable = true;
                $q = $this->db->prepare("
                    SELECT event_name, COUNT(*) AS total 
                    FROM analytics_events 
                    WHERE created_at >= ? AND created_at < ? 
                    GROUP BY event_name
                ");
                $q->execute([$from . ' 00:00:00', $toExclusive . ' 00:00:00']);
                foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (isset($events[$row['event_name']])) {
                        $events[$row['event_name']] = (int)$row['total'];
                    }
                }

                $q = $this->db->prepare("
                    SELECT JSON_UNQUOTE(JSON_EXTRACT(payload, '$.query')) AS query_text, COUNT(*) AS total 
                    FROM analytics_events 
                    WHERE event_name = 'search_submitted' AND created_at >= ? AND created_at < ? 
                    GROUP BY query_text 
                    ORDER BY total DESC 
                    LIMIT 6
                ");
                $q->execute([$from . ' 00:00:00', $toExclusive . ' 00:00:00']);
                foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($row['query_text']) {
                        $failedSearches[$row['query_text']] = (int)$row['total'];
                    }
                }

                $q = $this->db->prepare("
                    SELECT JSON_UNQUOTE(JSON_EXTRACT(payload, '$.title')) AS product_title, COUNT(*) AS total 
                    FROM analytics_events 
                    WHERE event_name IN ('product_viewed', 'product_clicked') AND created_at >= ? AND created_at < ? 
                    GROUP BY product_title 
                    ORDER BY total DESC 
                    LIMIT 6
                ");
                $q->execute([$from . ' 00:00:00', $toExclusive . ' 00:00:00']);
                foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($row['product_title']) {
                        $topProducts[$row['product_title']] = (int)$row['total'];
                    }
                }

                $q = $this->db->prepare("
                    SELECT 
                        JSON_UNQUOTE(JSON_EXTRACT(payload, '$.metric')) AS metric,
                        AVG(CAST(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.value')) AS UNSIGNED)) AS avg_value,
                        MAX(CAST(JSON_UNQUOTE(JSON_EXTRACT(payload, '$.cls')) AS DECIMAL(8,4))) AS max_cls,
                        COUNT(*) AS total 
                    FROM analytics_events 
                    WHERE event_name = 'performance_metric' AND created_at >= ? AND created_at < ? 
                    GROUP BY metric
                ");
                $q->execute([$from . ' 00:00:00', $toExclusive . ' 00:00:00']);
                foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ($row['metric'] === 'lcp') {
                        $performance['lcp'] = (int)$row['avg_value'];
                    } elseif ($row['metric'] === 'page_load') {
                        $performance['page_load'] = (int)$row['avg_value'];
                        $performance['cls'] = $row['max_cls'];
                    } elseif ($row['metric'] === 'slow_interaction') {
                        $performance['slow_interaction'] = (int)$row['total'];
                    }
                }
            }
        } catch (\Throwable) {}

        arsort($failedSearches);
        arsort($topProducts);

        $logs = [
            'mail-delivery.log' => 0,
            'payment-failures.log' => 0,
            'payment-reconciliation.log' => 0,
            'reviews.log' => 0,
            'staff-approvals.log' => 0,
            'ai-assistant.log' => 0,
        ];

        foreach ($logs as $file => $count) {
            $logPath = BASE_PATH . '/storage/logs/' . $file;
            if (!is_file($logPath)) {
                continue;
            }
            $lines = file($logPath, FILE_IGNORE_NEW_LINES) ?: [];
            foreach ($lines as $line) {
                if (str_contains($line, '[')) {
                    preg_match('/\[(.*?)\]/', $line, $match);
                    $time = isset($match[1]) ? strtotime($match[1]) : false;
                    if ($time && ($time < $fromTime || $time >= $toTime)) {
                        continue;
                    }
                }
                if (stripos($line, 'fail') !== false || stripos($line, 'reject') !== false || stripos($line, 'error') !== false) {
                    $logs[$file]++;
                }
            }
        }

        return [
            'events' => $events,
            'searches' => array_slice($failedSearches, 0, 6, true),
            'products' => array_slice($topProducts, 0, 6, true),
            'logs' => $logs,
            'performance' => $performance,
        ];
    }
}
