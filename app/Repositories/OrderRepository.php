<?php

declare(strict_types=1);

namespace Noir\App\Repositories;

use PDO;
use Noir\App\Contracts\OrderRepositoryInterface;

final class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(private readonly PDO $db) {}

    /**
     * Single-pass aggregated order metrics for the admin dashboard.
     */
    public function stats(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
                    COALESCE(SUM(CASE WHEN status IN ('processing', 'packed') THEN 1 ELSE 0 END), 0) AS processing,
                    COALESCE(SUM(CASE WHEN status IN ('delivered', 'completed') THEN 1 ELSE 0 END), 0) AS delivered,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total ELSE 0 END), 0) AS revenue_paid,
                    COALESCE(SUM(total), 0) AS revenue_all
                FROM orders
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $revenue = (float)($row['revenue_paid'] ?? 0);
            if ($revenue == 0.0) {
                $revenue = (float)($row['revenue_all'] ?? 0);
            }

            return [
                'total' => (int)($row['total'] ?? 0),
                'pending' => (int)($row['pending'] ?? 0),
                'processing' => (int)($row['processing'] ?? 0),
                'delivered' => (int)($row['delivered'] ?? 0),
                'revenue' => $revenue,
            ];
        } catch (\Throwable) {
            return [
                'total' => 0,
                'pending' => 0,
                'processing' => 0,
                'delivered' => 0,
                'revenue' => 0.0,
            ];
        }
    }

    public function paginate(array $filters, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $search = trim((string)($filters['search'] ?? ''));
        $status = trim((string)($filters['status'] ?? ''));
        $paymentStatus = trim((string)($filters['payment_status'] ?? ''));

        $where = ['1=1'];
        $params = [];

        if ($search !== '') {
            $where[] = '(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR o.shipping_address LIKE ?)';
            $term = '%' . $search . '%';
            $params = array_merge($params, [$term, $term, $term, $term]);
        }

        if ($status !== '') {
            $where[] = 'o.status = ?';
            $params[] = $status;
        }

        if ($paymentStatus !== '') {
            $where[] = 'o.payment_status = ?';
            $params[] = $paymentStatus;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("
            SELECT COUNT(*) FROM orders o 
            LEFT JOIN users u ON u.id = o.user_id 
            $whereSql
        ");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $query = $this->db->prepare("
            SELECT o.*, u.name AS customer_name, u.email AS customer_email 
            FROM orders o 
            LEFT JOIN users u ON u.id = o.user_id 
            $whereSql 
            ORDER BY o.id DESC 
            LIMIT $perPage OFFSET $offset
        ");
        $query->execute($params);
        $orders = $query->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'orders' => $orders,
            'pagination' => [
                'page' => $page,
                'pages' => $pages,
                'total' => $total,
                'per_page' => $perPage,
            ],
        ];
    }
}
