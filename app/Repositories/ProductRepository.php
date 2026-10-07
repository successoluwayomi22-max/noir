<?php

declare(strict_types=1);

namespace Noir\App\Repositories;

use PDO;
use Noir\App\Contracts\ProductRepositoryInterface;

final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(private readonly PDO $db) {}

    /**
     * Retrieve paginated products with filtering.
     */
    public function paginate(array $filters, int $page = 1, int $perPage = 12): array
    {
        $page = max(1, $page);
        $where = [];
        $params = [];

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(p.name LIKE ? OR p.slug LIKE ? OR p.sku LIKE ? OR p.category LIKE ? OR p.brand LIKE ?)';
            $term = '%' . $search . '%';
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        foreach (['status', 'category', 'brand'] as $field) {
            $value = trim((string)($filters[$field] ?? ''));
            if ($value !== '') {
                $where[] = "p.$field = ?";
                $params[] = $value;
            }
        }

        $stock = (string)($filters['stock'] ?? '');
        if ($stock === 'out') {
            $where[] = 'p.quantity <= 0';
        } elseif ($stock === 'low') {
            $where[] = 'p.quantity > 0 AND p.quantity <= p.low_stock_alert';
        } elseif ($stock === 'in') {
            $where[] = 'p.quantity > p.low_stock_alert';
        }

        if ((string)($filters['featured'] ?? '') === '1') {
            $where[] = 'p.featured = 1';
        }

        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

        // Total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM products p$whereSql");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $sort = (string)($filters['sort'] ?? 'newest');
        $order = match ($sort) {
            'name' => 'p.name ASC',
            'price_low' => 'p.price ASC',
            'price_high' => 'p.price DESC',
            'stock' => 'p.quantity ASC',
            default => 'p.updated_at DESC'
        };

        $query = $this->db->prepare("SELECT p.* FROM products p$whereSql ORDER BY $order LIMIT $perPage OFFSET $offset");
        $query->execute($params);
        $items = $query->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return [
            'items' => $items,
            'pagination' => [
                'page' => $page,
                'pages' => $pages,
                'total' => $total,
                'per_page' => $perPage,
            ],
        ];
    }

    /**
     * Single-pass aggregated product metrics for high performance.
     */
    public function stats(): array
    {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END), 0) AS active,
                COALESCE(SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END), 0) AS draft,
                COALESCE(SUM(CASE WHEN quantity <= 0 THEN 1 ELSE 0 END), 0) AS `out`,
                COALESCE(SUM(CASE WHEN quantity > 0 AND quantity <= low_stock_alert THEN 1 ELSE 0 END), 0) AS low,
                COALESCE(SUM(CASE WHEN featured = 1 THEN 1 ELSE 0 END), 0) AS featured
            FROM products
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int)($row['total'] ?? 0),
            'active' => (int)($row['active'] ?? 0),
            'draft' => (int)($row['draft'] ?? 0),
            'out' => (int)($row['out'] ?? 0),
            'low' => (int)($row['low'] ?? 0),
            'featured' => (int)($row['featured'] ?? 0),
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function categories(): array
    {
        return $this->db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function brands(): array
    {
        return $this->db->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand <> '' ORDER BY brand")->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function collections(): array
    {
        try {
            return $this->db->query('SELECT id, name FROM collections ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function images(int $productId): array
    {
        try {
            $stmt = $this->db->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC, id ASC');
            $stmt->execute([$productId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function create(array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO products (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $set = [];
        $values = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = ?";
            $values[] = $value;
        }
        $values[] = $id;
        $sql = sprintf('UPDATE products SET %s WHERE id = ?', implode(', ', $set));
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($values);
    }

    public function duplicate(int $id): ?int
    {
        $original = $this->find($id);
        if (!$original) {
            return null;
        }

        $original['name'] .= ' Copy';
        $original['slug'] = $this->uniqueSlug($original['name']);
        $original['public_id'] = strtoupper(bin2hex(random_bytes(13)));
        unset($original['id'], $original['created_at'], $original['updated_at']);

        return $this->create($original);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE products SET status = ?, updated_at = NOW() WHERE id = ?');
        return $stmt->execute([$status, $id]);
    }

    public function exportRows(): array
    {
        return $this->db->query(
            'SELECT name, sku, category, brand, quantity, cost_price, price, status, featured, updated_at FROM products ORDER BY id DESC'
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-')) ?: 'item';
        $slug = $base;
        $counter = 1;

        while (true) {
            $sql = 'SELECT COUNT(*) FROM products WHERE slug = ?';
            $params = [$slug];
            if ($ignoreId !== null) {
                $sql .= ' AND id <> ?';
                $params[] = $ignoreId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            if ((int)$stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $counter++;
        }
    }
}
