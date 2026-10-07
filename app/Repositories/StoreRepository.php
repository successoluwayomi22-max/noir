<?php
declare(strict_types=1);

namespace Noir\App\Repositories;

use Noir\Core\Request;
use PDO;

/**
 * Encapsulates all product and catalogue queries used by the storefront.
 * Keeps StoreController free of raw SQL.
 */
final class StoreRepository
{
    public function __construct(private readonly PDO $db) {}

    // -----------------------------------------------------------------------
    // Catalogue / filtering
    // -----------------------------------------------------------------------

    /**
     * Builds and executes a paginated product query from the incoming request,
     * optionally scoped to a specific category or collection.
     *
     * @return array{
     *   catalog: array,
     *   collections: array,
     *   categories: array,
     *   brands: array,
     *   genders: array,
     *   hero: array,
     *   pagination: array{page: int, pages: int, total: int},
     *   sort: string,
     *   pageTitle: string,
     *   pageDescription: string,
     *   contextUrl: string,
     * }
     */
    public function catalogData(
        Request $request,
        ?int    $categoryId   = null,
        ?int    $collectionId = null,
        ?string $defaultSort  = null,
    ): array {
        $search           = trim((string) $request->input('search'));
        $requestedCategory = (int) $request->input('category');

        if (!$categoryId) {
            $categoryId = $requestedCategory ?: null;
        }

        $brandId = (int) $request->input('brand');
        $sort    = (string) $request->input('sort', $defaultSort ?: 'newest');
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 12;
        $where   = ["p.status='published'"];
        $params  = [];

        if ($categoryId) {
            $where[]  = 'p.category_id=?';
            $params[] = $categoryId;
        }

        if ($collectionId) {
            $where[]  = 'EXISTS (SELECT 1 FROM product_collections pc WHERE pc.product_id=p.id AND pc.collection_id=?)';
            $params[] = $collectionId;
        }

        if ($search !== '') {
            if (mb_strlen($search) >= 3) {
                $clean = trim(preg_replace('/[+\-><()~*\"@]+/', ' ', $search));
                if ($clean !== '') {
                    $where[] = 'MATCH(p.name, p.short_description, p.brand, p.category) AGAINST(? IN BOOLEAN MODE)';
                    $params[] = '*' . $clean . '*';
                } else {
                    $term = '%' . $search . '%';
                    $where[] = '(p.name LIKE ? OR p.short_description LIKE ? OR p.brand LIKE ? OR p.category LIKE ? OR p.sku LIKE ?)';
                    array_push($params, $term, $term, $term, $term, $term);
                }
            } else {
                $term = '%' . $search . '%';
                $where[] = '(p.name LIKE ? OR p.short_description LIKE ? OR p.brand LIKE ? OR p.category LIKE ? OR p.sku LIKE ?)';
                array_push($params, $term, $term, $term, $term, $term);
            }
        }

        if ($brandId) {
            $where[]  = 'p.brand_id=?';
            $params[] = $brandId;
        }

        foreach (['gender', 'material', 'season'] as $field) {
            $value = trim((string) $request->input($field));
            if ($value !== '') {
                $where[]  = "p.$field=?";
                $params[] = $value;
            }
        }

        if ($request->input('sale')) {
            $where[] = 'p.compare_at_price IS NOT NULL AND p.compare_at_price>p.price';
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $count    = $this->db->prepare("SELECT COUNT(*) FROM products p$whereSql");
        $count->execute($params);

        $total  = (int) $count->fetchColumn();
        $pages  = max(1, (int) ceil($total / $perPage));
        $page   = min($page, $pages);
        $order  = $this->sortOrder($sort);
        $offset = ($page - 1) * $perPage;

        $catalog = $this->products($whereSql, $params, "$order LIMIT $perPage OFFSET $offset");

        return [
            'catalog'     => $catalog,
            'collections' => $this->db->query(
                "SELECT c.*,COUNT(DISTINCT CASE WHEN p.status='published' THEN pc.product_id END) product_count
                 FROM collections c
                 LEFT JOIN product_collections pc ON pc.collection_id=c.id
                 LEFT JOIN products p ON p.id=pc.product_id
                 GROUP BY c.id ORDER BY c.updated_at DESC,c.id DESC",
            )->fetchAll(),
            'categories' => $this->db->query(
                "SELECT c.*,COUNT(p.id) product_count
                 FROM categories c
                 LEFT JOIN products p ON p.category_id=c.id AND p.status='published'
                 WHERE c.is_active=1 GROUP BY c.id ORDER BY c.name",
            )->fetchAll(),
            'brands' => $this->db->query(
                "SELECT b.*,COUNT(p.id) product_count
                 FROM brands b
                 LEFT JOIN products p ON p.brand_id=b.id AND p.status='published'
                 WHERE b.is_active=1 GROUP BY b.id ORDER BY b.name",
            )->fetchAll(),
            'genders' => $this->db->query(
                "SELECT DISTINCT gender FROM products
                 WHERE status='published' AND gender IS NOT NULL AND gender<>'' ORDER BY gender",
            )->fetchAll(PDO::FETCH_COLUMN),
            'hero' => $this->db->query(
                "SELECT name,featured_image,short_description FROM products
                 WHERE status='published' AND featured_image IS NOT NULL
                 ORDER BY featured DESC,created_at DESC LIMIT 1",
            )->fetch() ?: [],
            'pagination'      => ['page' => $page, 'pages' => $pages, 'total' => $total],
            'sort'            => $sort,
            'pageTitle'       => 'Shop the collection',
            'pageDescription' => 'A considered selection of modern pieces, shaped for everyday movement.',
            'contextUrl'      => '/store',
        ];
    }

    /**
     * Returns four curated product sections for the shop landing page without duplicating products.
     */
    public function sectionProducts(): array
    {
        $published = " WHERE p.status='published'";
        $excludeIds = [];

        $fetchUnique = function(string $extraWhere, array $params, string $order, int $limit = 4) use (&$excludeIds, $published): array {
            $where = $published . ($extraWhere !== '' ? ' ' . $extraWhere : '');
            if (!empty($excludeIds)) {
                $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
                $where .= " AND p.id NOT IN ($placeholders)";
                $params = array_merge($params, $excludeIds);
            }
            $products = $this->products($where, $params, "$order LIMIT $limit");
            foreach ($products as $p) {
                if (!empty($p['id'])) {
                    $excludeIds[] = (int) $p['id'];
                }
            }
            return $products;
        };

        return [
            'featuredProducts' => $fetchUnique('AND p.featured=1', [], 'p.updated_at DESC', 4),
            'newArrivals'      => $fetchUnique('', [], 'p.created_at DESC', 4),
            'bestSellers'      => $fetchUnique('', [], 'COALESCE(sales.total_sold,0) DESC,p.view_count DESC', 4),
            'saleProducts'     => $fetchUnique(
                'AND p.compare_at_price IS NOT NULL AND p.compare_at_price>p.price',
                [],
                'p.updated_at DESC',
                4,
            ),
        ];
    }

    /**
     * Returns the support data (collections, categories, brands, genders, reviews)
     * needed by the homepage sidebar and filtering panels.
     */
    public function homeSupportData(): array
    {
        return [
            'collections' => $this->db->query(
                "SELECT c.*,COUNT(pc.product_id) product_count
                 FROM collections c
                 LEFT JOIN product_collections pc ON pc.collection_id=c.id
                 LEFT JOIN products p ON p.id=pc.product_id AND p.status='published'
                 WHERE c.is_featured=1
                 GROUP BY c.id ORDER BY c.updated_at DESC,c.id DESC",
            )->fetchAll(),
            'categories' => $this->db->query(
                "SELECT category,COUNT(*) product_count FROM products
                 WHERE status='published' AND category IS NOT NULL AND category<>''
                 GROUP BY category ORDER BY category",
            )->fetchAll(),
            'brands' => $this->db->query(
                "SELECT brand,COUNT(*) product_count FROM products
                 WHERE status='published' AND brand IS NOT NULL AND brand<>''
                 GROUP BY brand ORDER BY brand",
            )->fetchAll(),
            'genders' => $this->db->query(
                "SELECT gender,COUNT(*) product_count FROM products
                 WHERE status='published' AND gender IS NOT NULL AND gender<>''
                 GROUP BY gender ORDER BY gender",
            )->fetchAll(),
            'reviews' => $this->db->query(
                "SELECT product_name,rating,review_text FROM customer_reviews
                 WHERE status='published' ORDER BY created_at DESC LIMIT 3",
            )->fetchAll(),
        ];
    }

    /**
     * Returns seven named product slices for the homepage sections without duplicating products.
     */
    public function homeProductSections(): array
    {
        $published = " WHERE p.status='published'";
        $excludeIds = [];

        $fetchUnique = function(string $extraWhere, array $params, string $order, int $limit = 4) use (&$excludeIds, $published): array {
            $where = $published . ($extraWhere !== '' ? ' ' . $extraWhere : '');
            if (!empty($excludeIds)) {
                $placeholders = implode(',', array_fill(0, count($excludeIds), '?'));
                $where .= " AND p.id NOT IN ($placeholders)";
                $params = array_merge($params, $excludeIds);
            }
            $products = $this->products($where, $params, "$order LIMIT $limit");
            foreach ($products as $p) {
                if (!empty($p['id'])) {
                    $excludeIds[] = (int) $p['id'];
                }
            }
            return $products;
        };

        return [
            'featured'    => $fetchUnique('AND p.featured=1', [], 'p.updated_at DESC', 4),
            'newArrivals' => $fetchUnique('', [], 'p.created_at DESC', 4),
            'trending'    => $fetchUnique('', [], 'p.view_count DESC,COALESCE(sales.total_sold,0) DESC', 4),
            'bestSellers' => $fetchUnique('', [], 'COALESCE(sales.total_sold,0) DESC,p.view_count DESC', 4),
            'saleItems'   => $fetchUnique(
                'AND p.compare_at_price IS NOT NULL AND p.compare_at_price>p.price',
                [],
                'p.updated_at DESC',
                4,
            ),
            'men'         => $fetchUnique("AND LOWER(p.gender)='men'",   [], 'p.created_at DESC', 4),
            'women'       => $fetchUnique("AND LOWER(p.gender)='women'", [], 'p.created_at DESC', 4),
            'excludedIds' => $excludeIds,
        ];
    }

    /**
     * Returns brand / palette settings for theming view output.
     */
    public function branding(): array
    {
        $rows = $this->db->query(
            "SELECT setting_key,setting_value FROM settings
             WHERE setting_key IN ('store_name','brand_primary_color','brand_dark_color','brand_accent_color')",
        )->fetchAll(PDO::FETCH_KEY_PAIR);

        return [
            'storeName'    => $rows['store_name']           ?? 'Noir & Co.',
            'brandPrimary' => $rows['brand_primary_color']  ?? '#687158',
            'brandDark'    => $rows['brand_dark_color']     ?? '#252820',
            'brandAccent'  => $rows['brand_accent_color']   ?? '#b9c1a4',
        ];
    }

    // -----------------------------------------------------------------------
    // Product fetching helpers
    // -----------------------------------------------------------------------

    /**
     * Executes a parameterised product SELECT with a sales sub-join and
     * attaches variant colour / size summaries to each row.
     *
     * @param array<int, mixed> $params
     */
    public function products(string $where, array $params, string $order): array
    {
        $needsSales = str_contains($order, 'sales.') || str_contains($where, 'sales.');
        if ($needsSales) {
            $sql = "SELECT p.*, COALESCE(sales.total_sold, 0) AS total_sold
                    FROM products p
                    LEFT JOIN (
                        SELECT product_id, SUM(quantity) AS total_sold
                        FROM order_items GROUP BY product_id
                    ) sales ON sales.product_id = p.id
                    $where ORDER BY $order";
        } else {
            $sql = "SELECT p.*, 0 AS total_sold
                    FROM products p
                    $where ORDER BY $order";
        }

        $q = $this->db->prepare($sql);
        $q->execute($params);

        return $this->attachVariantSummaries($q->fetchAll());
    }

    /**
     * Appends `colors` and `sizes` string fields to each product row by
     * loading variant attributes in a single batch query.
     *
     * @param  array<int, array<string, mixed>> $products
     * @return array<int, array<string, mixed>>
     */
    public function attachVariantSummaries(array $products): array
    {
        if (!$products) {
            return [];
        }

        $ids          = array_values(array_unique(array_map(static fn(array $p): int => (int) $p['id'], $products)));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $q = $this->db->prepare("SELECT product_id,attributes FROM product_variants WHERE product_id IN ($placeholders)");
        $q->execute($ids);

        $summaries = [];

        foreach ($q->fetchAll() as $variant) {
            $productId  = (int) $variant['product_id'];
            $attributes = json_decode((string) ($variant['attributes'] ?? ''), true);

            if (!is_array($attributes)) {
                continue;
            }

            foreach (['color' => 'colors', 'colour' => 'colors', 'size' => 'sizes'] as $key => $target) {
                $value = trim((string) ($attributes[$key] ?? ''));
                if ($value !== '') {
                    $summaries[$productId][$target][$value] = true;
                }
            }
        }

        foreach ($products as &$product) {
            $id = (int) $product['id'];
            $product['colors'] = isset($summaries[$id]['colors'])
                ? implode(', ', array_keys($summaries[$id]['colors']))
                : '';
            $product['sizes'] = isset($summaries[$id]['sizes'])
                ? implode(', ', array_keys($summaries[$id]['sizes']))
                : '';
        }

        unset($product);

        return $products;
    }

    // -----------------------------------------------------------------------
    // Sorting
    // -----------------------------------------------------------------------

    /**
     * Maps a sort key to its ORDER BY SQL fragment.
     */
    public function sortOrder(string $sort): string
    {
        return [
            'price_low'  => 'p.price ASC',
            'price_high' => 'p.price DESC',
            'popular'    => 'COALESCE(sales.total_sold,0) DESC,p.view_count DESC',
            'oldest'     => 'p.created_at ASC',
            'newest'     => 'p.created_at DESC',
        ][$sort] ?? 'p.created_at DESC';
    }
}
