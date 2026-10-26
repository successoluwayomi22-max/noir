<?php

declare(strict_types=1);

namespace Noir\App\Controllers;

use Noir\App\Repositories\StoreRepository;
use Noir\App\Services\CartService;
use Noir\Core\Auth;
use Noir\Core\Container;
use Noir\Core\Request;
use Noir\Core\ViewData;

/**
 * Handles the customer-facing storefront: home, shop catalogue, product detail,
 * cart, and static info pages.
 *
 * Checkout, payments, contact form, newsletter, and analytics have been moved
 * to CheckoutController and ContactController respectively.
 */
final class StoreController
{
    // -----------------------------------------------------------------------
    // Public actions
    // -----------------------------------------------------------------------

    public function index(Request $request): never
    {
        $db   = Container::get('db');
        $repo = new StoreRepository($db);

        $this->syncSessionAccountType($db);

        $search  = trim((string) $request->input('search'));
        $sort    = (string) $request->input('sort', 'newest');
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 12;
        $where   = ["p.status='published'"];
        $params  = [];

        if ($search !== '') {
            $term    = '%' . $search . '%';
            $where[] = '(p.name LIKE ? OR p.brand LIKE ? OR p.category LIKE ? OR p.sku LIKE ?)';
            $params  = [$term, $term, $term, $term];
        }

        foreach (['category', 'brand', 'gender', 'material', 'season'] as $field) {
            $value = trim((string) $request->input($field));
            if ($value !== '') {
                $where[]  = "p.$field=?";
                $params[] = $value;
            }
        }

        $collection = (int) $request->input('collection', 0);
        if ($collection > 0) {
            $where[]  = 'EXISTS (SELECT 1 FROM product_collections pc WHERE pc.product_id=p.id AND pc.collection_id=?)';
            $params[] = $collection;
        }

        $whereSql = ' WHERE ' . implode(' AND ', $where);
        $count    = $db->prepare("SELECT COUNT(*) FROM products p$whereSql");
        $count->execute($params);

        $total  = (int) $count->fetchColumn();
        $pages  = max(1, (int) ceil($total / $perPage));
        $page   = min($page, $pages);
        $offset = ($page - 1) * $perPage;
        $order  = $repo->sortOrder($sort);

        $catalog = $repo->products($whereSql, $params, "$order LIMIT $perPage OFFSET $offset");

        $this->redirectSingleSearchResult($search, $total, $catalog);

        $supportData   = $repo->homeSupportData();
        $productGroups = $repo->homeProductSections();
        $pagination    = ['page' => $page, 'pages' => $pages, 'total' => $total];

        ViewData::render(BASE_PATH . '/app/Views/home.php', array_merge(
            $supportData,
            $productGroups,
            [
                'catalog'    => $catalog,
                'pagination' => $pagination,
                'search'     => $search,
                'sort'       => $sort,
            ],
        ));
    }

    public function shop(Request $request): never
    {
        $db   = Container::get('db');
        $repo = new StoreRepository($db);

        $data = array_merge(
            $repo->catalogData($request),
            $repo->sectionProducts(),
            $repo->branding(),
        );

        ViewData::render(BASE_PATH . '/app/Views/store/shop.php', $data);
    }

    public function category(Request $request): never
    {
        $db   = Container::get('db');
        $repo = new StoreRepository($db);
        $slug = trim((string) ($request->params['slug'] ?? ''));

        $query = $db->prepare('SELECT * FROM categories WHERE slug=? AND is_active=1 LIMIT 1');
        $query->execute([$slug]);
        $category = $query->fetch();

        if (!$category) {
            http_response_code(404);
            exit('Category not found');
        }

        $data = array_merge(
            $repo->catalogData($request, (int) $category['id']),
            $repo->branding(),
            [
                'pageTitle'        => (string) $category['name'],
                'pageDescription'  => $category['description'] ?: 'Explore the considered pieces in this edit.',
                'categoryHero'     => catalogue_image_for(['name' => (string) $category['name']]),
                'contextUrl'       => '/categories/' . $category['slug'],
                'catalogueOnly'    => true,
                'showCatalogueHero' => true,
            ],
        );

        ViewData::render(BASE_PATH . '/app/Views/store/shop.php', $data);
    }

    public function collection(Request $request): never
    {
        $db   = Container::get('db');
        $repo = new StoreRepository($db);
        $slug = trim((string) ($request->params['slug'] ?? ''));

        $query = $db->prepare('SELECT * FROM collections WHERE slug=? LIMIT 1');
        $query->execute([$slug]);
        $collection = $query->fetch();

        if (!$collection) {
            http_response_code(404);
            exit('Collection not found');
        }

        $isBestSellers = $collection['slug'] === 'best-sellers';

        $data = array_merge(
            $repo->catalogData(
                $request,
                null,
                $isBestSellers ? null : (int) $collection['id'],
                $isBestSellers ? 'popular' : null,
            ),
            $repo->branding(),
            [
                'pageTitle'         => (string) $collection['name'],
                'pageDescription'   => $collection['description'] ?: 'A considered edit from Noir & Co.',
                'collectionHero'    => collection_image_for((string) $collection['name']),
                'contextUrl'        => '/collections/' . $collection['slug'],
                'catalogueOnly'     => true,
                'showCatalogueHero' => true,
            ],
        );

        ViewData::render(BASE_PATH . '/app/Views/store/shop.php', $data);
    }

    public function show(Request $request): never
    {
        $db   = Container::get('db');
        $repo = new StoreRepository($db);
        $slug = trim((string) (
            $request->params['slug']
            ?? $request->params['id']
            ?? $request->input('slug')
            ?? $request->input('id')
            ?? ''
        ));

        $q = $db->prepare(
            "SELECT p.*,COALESCE(sales.total_sold,0) total_sold
             FROM products p
             LEFT JOIN (
                 SELECT product_id,SUM(quantity) total_sold
                 FROM order_items GROUP BY product_id
             ) sales ON sales.product_id=p.id
             WHERE (p.slug=? OR p.id=?) AND p.status='published' LIMIT 1",
        );
        $q->execute([$slug, ctype_digit($slug) ? (int) $slug : 0]);
        $product = $q->fetch();

        if (!$product) {
            http_response_code(404);
            exit('Product not found');
        }

        $id = (int) $product['id'];

        // Record view and update recently-viewed session list.
        $db->prepare('UPDATE products SET view_count=view_count+1 WHERE id=?')->execute([$id]);
        $recentlyViewedIds = array_values(array_unique(
            array_merge([$id], array_map('intval', $_SESSION['recently_viewed_products'] ?? [])),
        ));
        $_SESSION['recently_viewed_products'] = array_slice($recentlyViewedIds, 0, 8);

        // Images and variants.
        $imagesQuery = $db->prepare('SELECT * FROM product_images WHERE product_id=? ORDER BY is_featured DESC,sort_order,id');
        $imagesQuery->execute([$id]);
        $images = $imagesQuery->fetchAll();

        $variantsQuery = $db->prepare('SELECT * FROM product_variants WHERE product_id=? ORDER BY id');
        $variantsQuery->execute([$id]);
        $variants = $variantsQuery->fetchAll();

        // Review summary and recent rows.
        $reviewQuery = $db->prepare(
            "SELECT COUNT(*) review_count,COALESCE(AVG(rating),0) review_average
             FROM customer_reviews WHERE product_id=? AND status='published'",
        );
        $reviewQuery->execute([$id]);
        $reviews = $reviewQuery->fetch() ?: ['review_count' => 0, 'review_average' => 0];

        $reviewRowsQuery = $db->prepare(
            "SELECT rating,review_text,created_at FROM customer_reviews
             WHERE product_id=? AND status='published' ORDER BY created_at DESC LIMIT 3",
        );
        $reviewRowsQuery->execute([$id]);
        $reviewRows = $reviewRowsQuery->fetchAll();

        // Recently viewed (excluding the current product).
        $recentIds      = array_values(array_filter($recentlyViewedIds, static fn (int $rid): bool => $rid !== $id));
        $recentlyViewed = [];

        if ($recentIds) {
            $placeholders   = implode(',', array_fill(0, count($recentIds), '?'));
            $recentlyViewed = array_slice(
                $repo->products(
                    " WHERE p.status='published' AND p.id IN ($placeholders)",
                    array_merge($recentIds, $recentIds),
                    "FIELD(p.id,$placeholders) LIMIT 4",
                ),
                0,
                4,
            );
        }

        // Related products.
        $related = $repo->products(
            " WHERE p.status='published' AND p.id<>? AND (p.category=? OR p.brand=?)",
            [$id, (string) $product['category'], (string) $product['brand']],
            'p.updated_at DESC LIMIT 4',
        );

        $isWishlisted = false;
        $currentUserId = Auth::id();
        if ($currentUserId !== null && $currentUserId > 0) {
            $wStmt = $db->prepare('SELECT 1 FROM customer_wishlist WHERE user_id = ? AND product_id = ? LIMIT 1');
            $wStmt->execute([$currentUserId, $id]);
            $isWishlisted = (bool)$wStmt->fetchColumn();
        }

        ViewData::render(BASE_PATH . '/app/Views/store/product.php', [
            'product'       => $product,
            'images'        => $images,
            'variants'      => $variants,
            'reviews'       => $reviews,
            'reviewRows'    => $reviewRows,
            'recentlyViewed' => $recentlyViewed,
            'related'       => $related,
            'isWishlisted'  => $isWishlisted,
        ]);
    }

    public function cartCount(): never
    {
        $db = Container::get('db');
        $userId = Auth::id();
        if ($userId > 0 && $db instanceof \PDO) {
            $cart = CartService::syncSessionCart($db, $userId);
        } else {
            $cart = $_SESSION['cart'] ?? [];
        }
        $count = array_sum(array_map(static fn(array $item): int => (int)($item['quantity'] ?? 0), $cart));
        json(['success' => true, 'count' => $count]);
    }

    public function cart(): never
    {
        $db         = Container::get('db');
        $publicPath = $this->publicPath();
        $cart       = CartService::syncSessionCart($db, Auth::id());
        $storeName  = store_setting('store_name', 'Noir & Co.');

        ViewData::render(BASE_PATH . '/app/Views/store/cart.php', [
            'publicPath' => $publicPath,
            'cart'       => $cart,
            'storeName'  => $storeName,
        ]);
    }

    public function removeFromCart(Request $request): never
    {
        $id = (int) $request->input('id');
        $db = Container::get('db');

        if ($id > 0) {
            CartService::removeItem($db, Auth::id(), $id);
            $_SESSION['cart_notice'] = 'Product removed from your cart.';
        }

        header('Location: ' . $this->publicPath() . '/cart', true, 303);
        exit;
    }

    public function ajax(Request $request): never
    {
        $action = (string) ($request->params['action'] ?? '');
        $id     = (int) $request->input('id');
        $db     = Container::get('db');

        if ($action === 'cart-add') {
            $q = $db->prepare("SELECT id,name,price,featured_image,quantity FROM products WHERE id=? AND status='published' LIMIT 1");
            $q->execute([$id]);
            $product = $q->fetch();

            if (!$product) {
                json(['success' => false, 'message' => 'This product is no longer available.'], 404);
            }

            $quantity  = max(1, min(99, (int) $request->input('quantity', 1)));
            $cart      = CartService::syncSessionCart($db, Auth::id());
            $key       = (string) $id;
            $existing  = (int) ($cart[$key]['quantity'] ?? 0);
            $available = (int) $product['quantity'];

            if ($available > 0 && $existing + $quantity > $available) {
                json(['success' => false, 'message' => 'You cannot add more than the available stock.'], 422);
            }

            CartService::addItem($db, Auth::id(), $id, $quantity, $product);
            $currentCart = $_SESSION['cart'] ?? [];
            $count = array_sum(array_map(static fn (array $item): int => (int) $item['quantity'], $currentCart));

            json(['success' => true, 'message' => 'Added to cart.', 'count' => $count]);
        }

        if ($action === 'wishlist-toggle') {
            $this->toggleWishlist($request);
        }

        json(['success' => false, 'message' => 'Unknown action.'], 400);
        exit;
    }

    public function toggleWishlist(Request $request): never
    {
        $userId = Auth::id();
        if ($userId === null || $userId <= 0) {
            json(['success' => false, 'error' => 'Please sign in to save pieces to your collection.'], 401);
        }

        $productId = (int)($request->input('product_id') ?: $request->input('id', 0));
        if ($productId <= 0) {
            json(['success' => false, 'error' => 'Invalid product ID.'], 400);
        }

        $db = Container::get('db');
        $check = $db->prepare('SELECT 1 FROM customer_wishlist WHERE user_id = ? AND product_id = ? LIMIT 1');
        $check->execute([$userId, $productId]);
        $exists = (bool)$check->fetchColumn();

        if ($exists) {
            $db->prepare('DELETE FROM customer_wishlist WHERE user_id = ? AND product_id = ?')->execute([$userId, $productId]);
            $isSaved = false;
            $msg = 'Piece removed from your wishlist.';
        } else {
            $db->prepare('INSERT IGNORE INTO customer_wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())')->execute([$userId, $productId]);
            $isSaved = true;
            $msg = 'Piece saved to your wishlist.';
        }

        json([
            'success' => true,
            'is_saved' => $isSaved,
            'product_id' => $productId,
            'message' => $msg
        ]);
    }

    public function runway(Request $request): never
    {
        $db = Container::get('db');
        $this->syncSessionAccountType($db);
        
        $products = [];
        try {
            $stmt = $db->query("SELECT * FROM products WHERE status = 'published' ORDER BY created_at DESC LIMIT 4");
            $products = $stmt->fetchAll();
        } catch (\Throwable $t) {}

        ViewData::render(BASE_PATH . '/app/Views/store/runway.php', [
            'products' => $products
        ]);
        exit;
    }

    public function info(Request $request): never
    {
        $uriPath = trim((string)parse_url($request->path ?? $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
        $slug    = basename($uriPath);
        $page    = (string) ($request->params['page'] ?? $slug ?: 'about');
        if (in_array($page, ['refund-policy', 'returns'], true)) {
            $page = 'returns';
        } elseif (in_array($page, ['shipping-policy', 'shipping'], true)) {
            $page = 'shipping';
        }
        $allowed = ['about', 'contact', 'faq', 'journal', 'returns', 'shipping'];

        if (!in_array($page, $allowed, true)) {
            http_response_code(404);
            exit('Page not found');
        }

        $titles = [
            'about'    => 'Our story',
            'contact'  => 'Contact us',
            'faq'      => 'Frequently asked questions',
            'journal'  => 'Journal',
            'returns'  => 'Returns & refunds',
            'shipping' => 'Shipping & delivery policy',
        ];

        $descriptions = [
            'about'    => 'Considered objects, thoughtful sourcing, and a slower approach to everyday design.',
            'contact'  => 'Questions about an order, a product, or working with us? We are here to help.',
            'faq'      => 'Helpful answers about orders, delivery, returns, and your account.',
            'journal'  => 'Notes on materials, rituals, and the considered life.',
            'returns'  => 'A clear, considerate process if something is not quite right.',
            'shipping' => 'Transparent shipping rates, courier partners, dispatch timelines, and delivery guarantees.',
        ];

        $publicPath = $this->publicPath();
        $storeName  = store_setting('store_name', 'Noir & Co.');

        $viewData = [
            'page'        => $page,
            'activePage'  => $page,
            'publicPath'  => $publicPath,
            'storeName'   => $storeName,
            'title'       => $titles[$page] ?? '',
            'description' => $descriptions[$page] ?? '',
        ];

        if ($page === 'journal') {
            ViewData::render(BASE_PATH . '/app/Views/store/journal.php', $viewData);
        }

        ViewData::render(BASE_PATH . '/app/Views/store/info.php', $viewData);
    }

    public function terms(): never
    {
        ViewData::render(BASE_PATH . '/app/Views/store/terms.php', [
            'page'       => 'terms',
            'publicPath' => $this->publicPath(),
            'storeName'  => store_setting('store_name', 'Noir & Co.'),
            'storeEmail' => store_setting('store_email', 'legal@noirandco.com'),
        ]);
    }

    public function privacy(): never
    {
        ViewData::render(BASE_PATH . '/app/Views/store/privacy.php', [
            'page'       => 'privacy',
            'publicPath' => $this->publicPath(),
            'storeName'  => store_setting('store_name', 'Noir & Co.'),
            'storeEmail' => store_setting('store_email', 'privacy@noirandco.com'),
        ]);
    }

    public function returnsPolicy(Request $request): never
    {
        $this->info($request);
    }

    public function shippingPolicy(Request $request): never
    {
        $this->info($request);
    }

    public function trackOrder(Request $request): never
    {
        $db          = Container::get('db');
        $orderNumber = trim((string) $request->input('order_number'));
        $email       = strtolower(trim((string) $request->input('email')));
        $result      = null;
        $error       = null;

        if ($orderNumber !== '' && $email !== '') {
            $stmt = $db->prepare(
                "SELECT order_number, status, total, currency, name, city, created_at
                 FROM orders WHERE order_number=? AND LOWER(email)=? LIMIT 1"
            );
            $stmt->execute([$orderNumber, $email]);
            $result = $stmt->fetch() ?: null;
            if (!$result) {
                $error = 'No order found matching those details. Please double-check your order number and email.';
            }
        }

        ViewData::render(BASE_PATH . '/app/Views/store/track-order.php', [
            'publicPath'  => $this->publicPath(),
            'storeName'   => store_setting('store_name', 'Noir & Co.'),
            'result'      => $result,
            'error'       => $error,
            'orderNumber' => $orderNumber,
            'email'       => $email,
        ]);
    }

    public function search(Request $request): never
    {
        $db      = Container::get('db');
        $repo    = new StoreRepository($db);
        $query   = trim((string) $request->input('q', $request->input('search', '')));
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 16;
        $products = [];
        $total    = 0;
        $pages    = 1;

        if ($query !== '') {
            $term   = '%' . $query . '%';
            $where  = "p.status='published' AND (p.name LIKE ? OR p.brand LIKE ? OR p.category LIKE ? OR p.sku LIKE ?)";
            $params = [$term, $term, $term, $term];
            $offset = ($page - 1) * $perPage;

            $cStmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE $where");
            $cStmt->execute($params);
            $total = (int) $cStmt->fetchColumn();
            $pages = (int) ceil($total / $perPage);

            $pStmt = $db->prepare(
                "SELECT p.*, c.name as category_name
                 FROM products p
                 LEFT JOIN categories c ON c.id=p.category_id
                 WHERE $where ORDER BY p.name ASC LIMIT $perPage OFFSET $offset"
            );
            $pStmt->execute($params);
            $products = $pStmt->fetchAll();
            $products = $repo->attachVariantSummaries($products);
        }

        ViewData::render(BASE_PATH . '/app/Views/store/search.php', [
            'publicPath' => $this->publicPath(),
            'storeName'  => store_setting('store_name', 'Noir & Co.'),
            'query'      => $query,
            'products'   => $products,
            'total'      => $total,
            'page'       => $page,
            'pages'      => $pages,
        ]);
    }

    public function sizeGuide(): never
    {
        ViewData::render(BASE_PATH . '/app/Views/store/size-guide.php', [
            'publicPath' => $this->publicPath(),
            'storeName'  => store_setting('store_name', 'Noir & Co.'),
        ]);
    }

    public function collections(): never
    {
        ViewData::render(BASE_PATH . '/app/Views/store/collections.php', [
            'publicPath' => $this->publicPath(),
            'storeName'  => store_setting('store_name', 'Noir & Co.'),
        ]);
    }

    public function careers(): never
    {
        ViewData::render(BASE_PATH . '/app/Views/store/careers.php', [
            'publicPath' => $this->publicPath(),
            'storeName'  => store_setting('store_name', 'Noir & Co.'),
        ]);
    }

    public function wishlist(): never
    {
        $db = Container::get('db');
        $userId = \Noir\Core\Auth::id();
        $items = [];

        if ($userId > 0 && $db instanceof \PDO) {
            $stmt = $db->prepare(
                "SELECT p.id, p.public_id, p.name, p.slug, p.price, p.compare_at_price, 
                        p.featured_image as image, p.quantity, p.category, p.brand, w.created_at as saved_at
                 FROM customer_wishlist w
                 JOIN products p ON p.id = w.product_id
                 WHERE w.user_id = ? AND p.status = 'published'
                 ORDER BY w.created_at DESC"
            );
            $stmt->execute([$userId]);
            $items = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        }

        ViewData::render(BASE_PATH . '/app/Views/store/wishlist.php', [
            'publicPath' => $this->publicPath(),
            'storeName'  => store_setting('store_name', 'Noir & Co.'),
            'items'      => $items,
            'isLoggedIn' => $userId > 0,
        ]);
    }

    public function giftCards(Request $request): never
    {
        $db = Container::get('db');
        $this->syncSessionAccountType($db);
        $cards = $db->query("SELECT * FROM gift_cards WHERE status = 'active' ORDER BY initial_amount ASC LIMIT 10")->fetchAll(\PDO::FETCH_ASSOC);
        require BASE_PATH . '/app/Views/store/gift-cards.php';
        exit;
    }

    /**
     * POST /set-language
     * Saves the chosen locale to session + cookie and redirects.
     */
    public function setLanguage(Request $request): never
    {
        $supported = ['en', 'fr', 'es', 'de', 'it', 'ar', 'yo', 'ha', 'ig', 'vi'];
        $locale    = (string) ($request->input('locale') ?? $_POST['locale'] ?? $_GET['locale'] ?? 'en');

        if (in_array($locale, $supported, true)) {
            $_SESSION['locale'] = $locale;
            setcookie('noir_locale', $locale, [
                'expires'  => time() + (86400 * 365),
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? ($this->publicPath() . '/');
        header('Location: ' . $referer, true, 303);
        exit;
    }

    /**
     * POST /set-currency
     * Saves the chosen currency to session + cookie and redirects.
     */
    public function setCurrency(Request $request): never
    {
        $supported = ['NGN', 'USD', 'EUR', 'GBP', 'CAD', 'AUD', 'GHS', 'KES', 'ZAR'];
        $currency  = strtoupper((string) ($request->input('currency') ?? $_POST['currency'] ?? $_GET['currency'] ?? 'NGN'));

        if (in_array($currency, $supported, true)) {
            $_SESSION['currency'] = $currency;
            setcookie('noir_currency', $currency, [
                'expires'  => time() + (86400 * 365),
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? ($this->publicPath() . '/');
        header('Location: ' . $referer, true, 303);
        exit;
    }

    public function downloadApp(): never
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isIos = (bool)preg_match('/iPhone|iPad|iPod/i', $userAgent);
        $forcedPlatform = strtolower(trim((string)($_GET['platform'] ?? '')));
        $directDownload = isset($_GET['direct']) || isset($_GET['download']) || $forcedPlatform === 'android';

        // Direct users on iOS or explicitly requesting the mobile view to the companion hub
        if (($isIos || isset($_GET['view']) || $forcedPlatform === 'ios') && !$directDownload) {
            ViewData::render(BASE_PATH . '/app/Views/store/app-download.php', [
                'publicPath' => $this->publicPath(),
                'storeName'  => store_setting('store_name', 'Noir & Co.'),
                'isIos'      => $isIos || $forcedPlatform === 'ios',
            ]);
            exit;
        }

        // Check for external direct download URL (e.g. GitHub Releases, Google Drive, Cloudflare R2)
        $externalUrl = config('app_download_url') 
            ?: store_setting('app_download_url') 
            ?: store_setting('mobile_app_apk_url')
            ?: (getenv('APP_DOWNLOAD_URL') ?: (getenv('APP_URL_DOWNLOAD') ?: ($_ENV['APP_DOWNLOAD_URL'] ?? ($_ENV['APP_URL_DOWNLOAD'] ?? ''))));

        if (!empty($externalUrl)) {
            header('Location: ' . $externalUrl, true, 302);
            exit;
        }

        $apkPath = BASE_PATH . '/public/downloads/noir-app.apk';
        if (!file_exists($apkPath)) {
            $debugPath = BASE_PATH . '/mobile/build/app/outputs/flutter-apk/app-debug.apk';
            if (file_exists($debugPath)) {
                $apkPath = $debugPath;
            } else {
                http_response_code(404);
                ViewData::render(BASE_PATH . '/app/Views/store/app-download.php', [
                    'publicPath' => $this->publicPath(),
                    'storeName'  => store_setting('store_name', 'Noir & Co.'),
                    'isIos'      => $isIos,
                    'errorMessage' => 'Noir Mobile App package is currently being prepared for direct download. Please install as PWA or check back shortly.',
                ]);
                exit;
            }
        }

        while (ob_get_level()) {
            @ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.android.package-archive');
        header('Content-Disposition: attachment; filename="noir-app.apk"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . (string)filesize($apkPath));

        $fp = fopen($apkPath, 'rb');
        if ($fp !== false) {
            fpassthru($fp);
            fclose($fp);
        } else {
            readfile($apkPath);
        }
        exit;
    }

    // -----------------------------------------------------------------------
    // Private helpers
    // -----------------------------------------------------------------------

    private function syncSessionAccountType(\PDO $db): void
    {
        if (empty($_SESSION['user']['user_id'])) {
            return;
        }

        $accountQuery = $db->prepare('SELECT account_type FROM users WHERE id=?');
        $accountQuery->execute([(int) $_SESSION['user']['user_id']]);
        $_SESSION['user']['account_type'] = $accountQuery->fetchColumn() ?: null;
    }

    private function redirectSingleSearchResult(string $search, int $total, array $catalog): void
    {
        if ($search === '' || $total !== 1 || empty($catalog)) {
            return;
        }

        header(
            'Location: ' . $this->publicPath() . '/store/product/' . rawurlencode((string) ($catalog[0]['slug'] ?: $catalog[0]['id'])),
            true,
            303,
        );
        exit;
    }

    private function publicPath(): string
    {
        return rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    }
}
