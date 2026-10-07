<?php

declare(strict_types=1);

namespace Noir\App\Repositories;

use PDO;
use Noir\App\Contracts\StaffRepositoryInterface;

final class StaffRepository implements StaffRepositoryInterface
{
    public function __construct(private readonly PDO $db) {}

    /**
     * Retrieve non-customer/guest roles.
     */
    public function roles(): array
    {
        try {
            return $this->db->query("SELECT id, name, slug FROM roles WHERE slug NOT IN ('customer', 'guest') ORDER BY name")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Single-pass aggregated staff metrics.
     */
    public function stats(): array
    {
        try {
            $stmt = $this->db->query("
                SELECT 
                    COUNT(*) AS total,
                    COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) AS active,
                    COALESCE(SUM(CASE WHEN status IN ('suspended', 'inactive') THEN 1 ELSE 0 END), 0) AS suspended
                FROM users 
                WHERE account_type = 'staff'
            ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $rolesCount = (int)($this->db->query("SELECT COUNT(*) FROM roles WHERE slug NOT IN ('customer', 'guest')")->fetchColumn() ?: 0);

            return [
                'total' => (int)($row['total'] ?? 0),
                'active' => (int)($row['active'] ?? 0),
                'suspended' => (int)($row['suspended'] ?? 0),
                'roles_count' => $rolesCount,
            ];
        } catch (\Throwable) {
            return ['total' => 0, 'active' => 0, 'suspended' => 0, 'roles_count' => 0];
        }
    }

    /**
     * Paginated staff directory excluding protected super-admin accounts.
     */
    public function staffUsers(array $filters, int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $search = trim((string)($filters['search'] ?? ''));
        $where = [
            "u.account_type = 'staff'",
            "u.email <> 'admin@noir.test'",
            "NOT EXISTS (SELECT 1 FROM user_roles protected_ur JOIN roles protected_r ON protected_r.id = protected_ur.role_id WHERE protected_ur.user_id = u.id AND protected_r.slug = 'super-admin')"
        ];
        $params = [];

        if ($search !== '') {
            $where[] = '(u.email LIKE ? OR u.username LIKE ? OR u.name LIKE ? OR u.phone LIKE ? OR u.public_id LIKE ?)';
            $term = '%' . $search . '%';
            $params = [$term, $term, $term, $term, $term];
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users u$whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                u.id, u.public_id, u.username, u.name, u.email, u.phone, u.status, u.created_at,
                r.id AS role_id, r.name AS role_name, r.slug AS role_slug,
                MAX(r.slug = 'super-admin') AS is_super_admin 
            FROM users u 
            LEFT JOIN user_roles ur ON ur.user_id = u.id 
            LEFT JOIN roles r ON r.id = ur.role_id
            $whereSql 
            GROUP BY u.id 
            ORDER BY u.id DESC 
            LIMIT $perPage OFFSET $offset
        ";
        $query = $this->db->prepare($sql);
        $query->execute($params);
        $users = $query->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'pages' => $pages,
                'total' => $total,
                'per_page' => $perPage,
            ],
        ];
    }

    public function staffDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT 
                u.id, u.public_id, u.username, u.name, u.email, u.phone, u.status, u.created_at,
                GROUP_CONCAT(DISTINCT r.name ORDER BY r.name SEPARATOR ', ') AS roles,
                MAX(r.slug = 'super-admin') AS is_super_admin 
            FROM users u 
            LEFT JOIN user_roles ur ON ur.user_id = u.id 
            LEFT JOIN roles r ON r.id = ur.role_id 
            WHERE u.id = ? AND u.account_type = 'staff' 
            GROUP BY u.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function toggleStatus(int $id): ?string
    {
        $stmt = $this->db->prepare("SELECT status FROM users WHERE id = ? AND account_type = 'staff'");
        $stmt->execute([$id]);
        $status = $stmt->fetchColumn();
        if ($status === false) {
            return null;
        }

        $newStatus = ($status === 'active') ? 'suspended' : 'active';
        $update = $this->db->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?");
        $update->execute([$newStatus, $id]);

        return $newStatus;
    }

    public function assignRole(int $userId, int $roleId): bool
    {
        // Remove existing roles
        $del = $this->db->prepare("DELETE FROM user_roles WHERE user_id = ?");
        $del->execute([$userId]);

        // Assign new role
        $ins = $this->db->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
        return $ins->execute([$userId, $roleId]);
    }
}
