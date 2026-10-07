<?php
declare(strict_types=1);

use Noir\App\Controllers\AuthController;
use Noir\App\Controllers\AdminController;
use Noir\App\Controllers\AiAssistantController;
use Noir\App\Controllers\VendorController;
use Noir\App\Controllers\CustomerController;
use Noir\App\Controllers\StaffController;
use Noir\App\Controllers\StoreController;
use Noir\App\Controllers\CheckoutController;
use Noir\App\Controllers\ContactController;
use Noir\App\Controllers\DeliveryController;
use Noir\App\Controllers\FinanceController;
use Noir\App\Middleware\CsrfMiddleware;
use Noir\App\Middleware\AdminMiddleware;
use Noir\App\Middleware\VendorMiddleware;
use Noir\App\Middleware\GuestMiddleware;
use Noir\App\Middleware\CustomerMiddleware;
use Noir\App\Middleware\DeliveryMiddleware;
use Noir\App\Middleware\FinanceMiddleware;
use Noir\App\Middleware\RateLimiterMiddleware;
use Noir\App\Controllers\HealthController;

$auth = AuthController::class;
$admin = AdminController::class;
$customer = CustomerController::class;
$staff = StaffController::class;

// System High-Availability Health Check
$router->get('/health', [HealthController::class, 'check']);
$router->get('/', [StoreController::class, 'index']);
$router->get('/favicon.ico', function() {
    $logo = (string) store_setting('store_logo');
    $file = ($logo !== '' && file_exists(BASE_PATH . '/public' . $logo))
        ? BASE_PATH . '/public' . $logo
        : BASE_PATH . '/public/assets/icons/noir.svg';
    if (file_exists($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => 'image/x-icon'
        };
        header('Content-Type: ' . $mime);
        header('Cache-Control: public, max-age=86400');
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
});
$router->get('/llms.txt', function() {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
        ? 'https://' : 'http://';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost:8088');
    $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $baseUrl = $scheme . $host . $publicBase;
    $storeName = store_setting('store_name', 'Noir & Co.');

    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');

    echo "# {$storeName}\n\n";
    echo "> Considered luxury design for the everyday atelier. Modern luxury apparel, accessories, and curated home collections.\n\n";
    echo "Noir & Co. is a luxury retail and atelier brand creating elevated wardrobe essentials, crafted accessories, and refined homeware. Sourced responsibly and designed to endure.\n\n";
    echo "## Documentation & Storefront\n";
    echo "- [Storefront]({$baseUrl}/): Homepage featuring new arrivals, editorial drops, and curated categories\n";
    echo "- [Catalogue]({$baseUrl}/store): Complete shopping catalogue with live filtering, stock status, and pricing\n";
    echo "- [Collections]({$baseUrl}/store#collections): Limited atelier releases, capsule edits, and seasonal series\n";
    echo "- [Search]({$baseUrl}/search): Real-time product search across all collections and categories\n";
    echo "- [Shopping Bag]({$baseUrl}/cart): Customer shopping bag and order review\n";
    echo "- [Mobile Application]({$baseUrl}/download/app): Direct download for the Noir native iOS and Android application\n\n";
    echo "## Client Care & Policies\n";
    echo "- [About the Maison]({$baseUrl}/about): Brand philosophy, ethical sourcing, and master craftsmanship\n";
    echo "- [Frequently Asked Questions]({$baseUrl}/faq): Answers regarding orders, international delivery, and sizing\n";
    echo "- [Track Order]({$baseUrl}/track-order): Live courier dispatch tracking and order status\n";
    echo "- [Size Guide]({$baseUrl}/size-guide): Garment measurements, fit recommendations, and international conversion charts\n";
    echo "- [Returns & Exchanges]({$baseUrl}/returns): Returns policy, exchanges, and refund guidelines\n";
    echo "- [Shipping Policy]({$baseUrl}/shipping): Worldwide insured courier dispatch timelines and duties\n";
    echo "- [Terms of Service]({$baseUrl}/terms): Conditions of sale, client rights, and atelier policies\n";
    echo "- [Privacy Policy]({$baseUrl}/privacy): Data protection, cookie governance, and privacy policy\n";
    echo "- [Client Concierge]({$baseUrl}/contact): Direct contact channels and client support inquiries\n\n";
    echo "## Optional\n";
    echo "- [Full Text Information]({$baseUrl}/llms-full.txt): Comprehensive brand, product specifications, and policy text for language models\n";
    exit;
});
$router->get('/llms-full.txt', function() {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
        ? 'https://' : 'http://';
    $host = (string)($_SERVER['HTTP_HOST'] ?? 'localhost:8088');
    $publicBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $baseUrl = $scheme . $host . $publicBase;
    $storeName = store_setting('store_name', 'Noir & Co.');

    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: public, max-age=3600');
    header('X-Content-Type-Options: nosniff');

    echo "# {$storeName} — Full Atelier Documentation\n\n";
    echo "> Complete product specifications, brand ethos, and operational policies for LLM agents and indexing systems.\n\n";
    echo "## Brand Summary\n";
    echo "Noir & Co. is a luxury direct-to-consumer and retail atelier offering premium menswear, womenswear, accessories, and bespoke living artifacts.\n\n";
    echo "## Key Links\n";
    echo "- Storefront: {$baseUrl}/\n";
    echo "- Complete Catalogue: {$baseUrl}/store\n";
    echo "- Mobile App: {$baseUrl}/download/app\n";
    echo "- Customer Care: {$baseUrl}/contact\n";
    echo "- Order Tracking: {$baseUrl}/track-order\n";
    exit;
});
$router->get('/store', [StoreController::class, 'shop']);
$router->get('/cart', [StoreController::class, 'cart']);
$router->post('/cart/remove', [StoreController::class, 'removeFromCart'], [CsrfMiddleware::class]);
$router->post('/store/ajax/{action}', [StoreController::class, 'ajax'], [CsrfMiddleware::class]);
$router->get('/ajax/cart/count', [StoreController::class, 'cartCount']);
$router->post('/ajax/wishlist/toggle', [StoreController::class, 'toggleWishlist']);
$router->post('/store/ajax/wishlist-toggle', [StoreController::class, 'toggleWishlist']);
$router->get('/checkout', [CheckoutController::class, 'checkout']);
$router->post('/checkout/pay', [CheckoutController::class, 'startPayment'], [RateLimiterMiddleware::class, CsrfMiddleware::class]);
$router->post('/checkout/retry', [CheckoutController::class, 'retryPayment'], [RateLimiterMiddleware::class, CsrfMiddleware::class]);
$router->get('/payments/paystack/callback', [CheckoutController::class, 'paystackCallback']);
$router->post('/payments/paystack/webhook', [CheckoutController::class, 'paystackWebhook'], [RateLimiterMiddleware::class]);
$router->get('/categories/{slug}', [StoreController::class, 'category']);
$router->get('/collections/{slug}', [StoreController::class, 'collection']);
$router->get('/store/product/{slug}', [StoreController::class, 'show']);
$router->get('/about', [StoreController::class, 'info']);
$router->get('/contact', [StoreController::class, 'info']);
$router->post('/contact', [ContactController::class, 'contactSubmit'], [CsrfMiddleware::class]);
$router->post('/newsletter/subscribe', [ContactController::class, 'newsletterSubscribe'], [CsrfMiddleware::class]);
$router->post('/analytics/storefront', [ContactController::class, 'storefrontAnalytics'], [CsrfMiddleware::class]);
$router->get('/faq', [StoreController::class, 'info']);
$router->get('/journal', [StoreController::class, 'info']);
$router->get('/runway', [StoreController::class, 'runway']);
$router->get('/returns', [StoreController::class, 'returnsPolicy']);
$router->get('/refund-policy', [StoreController::class, 'returnsPolicy']);
$router->get('/shipping', [StoreController::class, 'shippingPolicy']);
$router->get('/shipping-policy', [StoreController::class, 'shippingPolicy']);
$router->get('/terms', [StoreController::class, 'terms']);
$router->get('/terms-of-service', [StoreController::class, 'terms']);
$router->get('/privacy', [StoreController::class, 'privacy']);
$router->get('/privacy-policy', [StoreController::class, 'privacy']);
$router->get('/gift-cards', [StoreController::class, 'giftCards']);
$router->get('/track-order', [StoreController::class, 'trackOrder']);
$router->post('/track-order', [StoreController::class, 'trackOrder'], [CsrfMiddleware::class]);
$router->get('/search', [StoreController::class, 'search']);
$router->post('/set-language', [StoreController::class, 'setLanguage'], [CsrfMiddleware::class]);
$router->post('/set-currency', [StoreController::class, 'setCurrency'], [CsrfMiddleware::class]);
$router->get('/set-currency', [StoreController::class, 'setCurrency']);
$router->get('/collections', [StoreController::class, 'collections']);
$router->get('/careers', [StoreController::class, 'careers']);
$router->get('/wishlist', [StoreController::class, 'wishlist']);
$router->get('/size-guide', [StoreController::class, 'sizeGuide']);
$router->get('/download/app', [StoreController::class, 'downloadApp']);
$router->get('/login', [$auth, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [$auth, 'loginForm'], [GuestMiddleware::class, RateLimiterMiddleware::class, CsrfMiddleware::class]);
$router->post('/guest', [$auth, 'guest'], [GuestMiddleware::class, CsrfMiddleware::class]);
// Keep the conventional admin sign-in URL aligned with the shared auth flow.
$router->get('/admin/login', [$auth, 'showLogin'], [GuestMiddleware::class]);
$router->post('/admin/login', [$auth, 'loginForm'], [GuestMiddleware::class, RateLimiterMiddleware::class, CsrfMiddleware::class]);
$router->post('/logout', [$auth, 'logoutForm'], [CsrfMiddleware::class]);
$router->get('/forgot-password', [$auth, 'showForgotPassword']);
$router->post('/forgot-password', [$auth, 'requestPasswordReset'], [RateLimiterMiddleware::class, CsrfMiddleware::class]);
$router->get('/reset-password', [$auth, 'showResetPassword']);
$router->post('/reset-password', [$auth, 'resetPassword'], [RateLimiterMiddleware::class, CsrfMiddleware::class]);
$router->get('/register', [$auth, 'showRegister'], [GuestMiddleware::class]);
$router->post('/register', [$auth, 'register'], [GuestMiddleware::class, RateLimiterMiddleware::class, CsrfMiddleware::class]);
$router->get('/auth/google', [$auth, 'googleRedirect'], [GuestMiddleware::class]);
$router->get('/auth/google/callback', [$auth, 'googleCallback'], [GuestMiddleware::class]);
$router->get('/careers/apply', [$staff, 'apply']);
$router->get('/careers/application-submitted', [$staff, 'submitted']);
$router->post('/careers/apply', [$staff, 'submit'], [CsrfMiddleware::class]);
$router->get('/careers/status', [$staff, 'statusCheck']);
$router->post('/careers/status', [$staff, 'statusCheck']);
$router->get('/admin', [AdminController::class, 'dashboard'], [AdminMiddleware::class]);
$router->get('/admin/dashboard', [AdminController::class, 'dashboard'], [AdminMiddleware::class]);
$router->get('/admin/reports', [AdminController::class, 'reports'], [AdminMiddleware::class]);
$router->get('/admin/reports/demo', [AdminController::class, 'demoReports'], [AdminMiddleware::class]);
$router->get('/admin/products', [AdminController::class, 'products'], [AdminMiddleware::class]);
$router->get('/admin/fleet', [\Noir\App\Controllers\AdminFleetController::class, 'dashboard'], [AdminMiddleware::class]);
$router->post('/admin/fleet/assign', [\Noir\App\Controllers\AdminFleetController::class, 'assignCourier'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/admin/fleet/add-driver', [\Noir\App\Controllers\AdminFleetController::class, 'addCourier'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/admin/fleet/delete-driver', [\Noir\App\Controllers\AdminFleetController::class, 'deleteCourier'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/store-catalog', [AdminController::class, 'storeCatalog'], [AdminMiddleware::class]);
$router->post('/admin/store-catalog/action', [AdminController::class, 'storeCatalogAction'], [AdminMiddleware::class, CsrfMiddleware::class]);

$router->get('/admin/video-commerce', [AdminController::class, 'videoCommerce'], [AdminMiddleware::class]);
$router->post('/admin/video-commerce/action', [AdminController::class, 'videoCommerceAction'], [AdminMiddleware::class, CsrfMiddleware::class]);

$router->get('/admin/community-feed', [AdminController::class, 'communityFeed'], [AdminMiddleware::class]);
$router->post('/admin/community-feed/action', [AdminController::class, 'communityFeedAction'], [AdminMiddleware::class, CsrfMiddleware::class]);

$router->get('/admin/notifications', [\Noir\App\Controllers\AdminNotificationController::class, 'index'], [AdminMiddleware::class]);
$router->post('/admin/notifications/send', [\Noir\App\Controllers\AdminNotificationController::class, 'send'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/admin/notifications/delete', [\Noir\App\Controllers\AdminNotificationController::class, 'delete'], [AdminMiddleware::class, CsrfMiddleware::class]);

$router->get('/admin/ai-intelligence', [\Noir\App\Controllers\AdminAiIntelligenceController::class, 'dashboard'], [AdminMiddleware::class]);
$router->get('/admin/ai-intelligence/conversation', [\Noir\App\Controllers\AdminAiIntelligenceController::class, 'conversationDetails'], [AdminMiddleware::class]);
$router->post('/admin/ai-intelligence/demand-status', [\Noir\App\Controllers\AdminAiIntelligenceController::class, 'updateDemandStatus'], [AdminMiddleware::class]);
$router->post('/admin/ai-intelligence/save-knowledge', [\Noir\App\Controllers\AdminAiIntelligenceController::class, 'saveKnowledge'], [AdminMiddleware::class]);
$router->post('/admin/ai-intelligence/delete-knowledge', [\Noir\App\Controllers\AdminAiIntelligenceController::class, 'deleteKnowledge'], [AdminMiddleware::class]);
$router->post('/admin/ai-intelligence/save-weights', [\Noir\App\Controllers\AdminAiIntelligenceController::class, 'saveWeights'], [AdminMiddleware::class]);
$router->get('/admin/ai-intelligence/export', [\Noir\App\Controllers\AdminAiIntelligenceController::class, 'exportDemands'], [AdminMiddleware::class]);
$router->post('/api/v1/assistant/feedback', [AiAssistantController::class, 'feedback']);

$router->get('/admin/products/export', [AdminController::class, 'exportProducts'], [AdminMiddleware::class]);
$router->get('/admin/products/{id}', [AdminController::class, 'editProduct'], [AdminMiddleware::class]);
$router->post('/admin/products', [AdminController::class, 'storeProduct'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/admin/products/status', [AdminController::class, 'productStatus'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/admin/products/duplicate', [AdminController::class, 'duplicateProduct'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/admin/products/delete', [AdminController::class, 'deleteProduct'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/admin/products/{id}', [AdminController::class, 'updateProduct'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/security', [AdminController::class, 'security'], [AdminMiddleware::class]);
$router->post('/admin/security/unblock-ip', [AdminController::class, 'unblockIp'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/admin/security/block-ip', [AdminController::class, 'manualBlockIp'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/vendor', [VendorController::class, 'dashboard'], [VendorMiddleware::class]);
$router->get('/vendor/dashboard', [VendorController::class, 'dashboard'], [VendorMiddleware::class]);
$router->post('/vendor/settings/profile', [VendorController::class, 'updateProfile'], [VendorMiddleware::class, CsrfMiddleware::class]);
$router->get('/delivery', [DeliveryController::class, 'dashboard'], [DeliveryMiddleware::class]);
$router->get('/delivery/dashboard', [DeliveryController::class, 'dashboard'], [DeliveryMiddleware::class]);
$router->post('/delivery/order-status', [DeliveryController::class, 'updateStatus'], [DeliveryMiddleware::class, CsrfMiddleware::class]);
$router->post('/delivery/settings/profile', [DeliveryController::class, 'updateProfile'], [DeliveryMiddleware::class, CsrfMiddleware::class]);
$router->get('/finance', [FinanceController::class, 'dashboard'], [FinanceMiddleware::class]);
$router->get('/finance/dashboard', [FinanceController::class, 'dashboard'], [FinanceMiddleware::class]);
$router->get('/finance/analytics', [FinanceController::class, 'analytics'], [FinanceMiddleware::class]);
$router->get('/finance/ledger', [FinanceController::class, 'ledger'], [FinanceMiddleware::class]);
$router->get('/finance/operations', [FinanceController::class, 'operations'], [FinanceMiddleware::class]);
$router->get('/finance/settings', [FinanceController::class, 'settings'], [FinanceMiddleware::class]);
$router->get('/finance/transaction/{id}', [FinanceController::class, 'transactionDetail'], [FinanceMiddleware::class]);
$router->get('/finance/printer-status', [FinanceController::class, 'printerStatus'], [FinanceMiddleware::class]);
$router->get('/finance/export', [FinanceController::class, 'export'], [FinanceMiddleware::class]);
$router->post('/finance/settings/profile', [FinanceController::class, 'updateProfile'], [FinanceMiddleware::class, CsrfMiddleware::class]);
$router->get('/dashboard', [$customer, 'dashboard'], [CustomerMiddleware::class]);
$router->get('/customer', [$customer, 'dashboard'], [CustomerMiddleware::class]);
$router->post('/customer/fcm-token', [$customer, 'updateFcmToken'], [CustomerMiddleware::class, CsrfMiddleware::class]);
$router->get('/customer/orders', [$customer, 'orders'], [CustomerMiddleware::class]);
$router->get('/customer/orders/{number}', [$customer, 'orderDetails'], [CustomerMiddleware::class]);
$router->get('/customer/wishlist', [$customer, 'wishlist'], [CustomerMiddleware::class]);
$router->get('/customer/saved-items', [$customer, 'wishlist'], [CustomerMiddleware::class]);
$router->get('/customer/coupons', [$customer, 'coupons'], [CustomerMiddleware::class]);
$router->get('/customer/addresses', [$customer, 'addresses'], [CustomerMiddleware::class]);
$router->get('/customer/payments', [$customer, 'payments'], [CustomerMiddleware::class]);
$router->get('/customer/reviews', [$customer, 'reviews'], [CustomerMiddleware::class]);
$router->get('/customer/support', [$customer, 'support'], [CustomerMiddleware::class]);
$router->get('/customer/notifications', [$customer, 'notifications'], [CustomerMiddleware::class]);
$router->get('/customer/settings', [$customer, 'settings'], [CustomerMiddleware::class]);
$router->post('/customer/ajax/{action}', [$customer, 'ajax'], [CustomerMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/roles', [$admin, 'roles'], [AdminMiddleware::class]);
$router->get('/admin/roles/users', [$admin, 'roleUsers'], [AdminMiddleware::class]);
$router->get('/admin/roles/staff/{id}', [$admin, 'staffDetails'], [AdminMiddleware::class]);
$router->post('/admin/roles/staff/action', [$admin, 'staffAction'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/admin/roles/staff/message', [$admin, 'staffMessage'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/roles/permissions/{slug}', [$admin, 'rolePermissions'], [AdminMiddleware::class]);
$router->post('/admin/roles/assign', [$admin, 'assignUserRole'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/customers', [$admin, 'customers'], [AdminMiddleware::class]);
$router->get('/admin/customers/list', [$admin, 'customerList'], [AdminMiddleware::class]);
$router->get('/admin/customers/{id}', [$admin, 'customerDetails'], [AdminMiddleware::class]);
$router->post('/admin/customers/action', [$admin, 'customerAction'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/customers/export', [$admin, 'exportCustomers'], [AdminMiddleware::class]);
$router->get('/admin/orders', [$admin, 'orders'], [AdminMiddleware::class]);
$router->get('/admin/orders/list', [$admin, 'orderList'], [AdminMiddleware::class]);
$router->get('/admin/orders/export', [$admin, 'exportOrders'], [AdminMiddleware::class]);
$router->get('/admin/orders/{id}', [$admin, 'orderDetails'], [AdminMiddleware::class]);
$router->post('/admin/orders/status', [$admin, 'updateOrderStatus'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/messages', [$admin, 'messages'], [AdminMiddleware::class]);
$router->get('/admin/staff-applications', [$staff, 'applications'], [AdminMiddleware::class]);
$router->get('/admin/staff-applications/{id}/resume', [$staff, 'resume'], [AdminMiddleware::class]);
$router->post('/admin/staff-applications/approve', [$staff, 'approve'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/admin/staff-applications/reject', [$staff, 'reject'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/settings', [$admin, 'settings'], [AdminMiddleware::class]);
$router->post('/admin/settings', [$admin, 'updateSettings'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/system-logs', [$admin, 'systemLogs'], [AdminMiddleware::class]);
$router->post('/admin/system-logs/clear', [$admin, 'clearSystemLogs'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/admin/system-logs/diagnose', [$admin, 'diagnoseLog'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/telemetry', [$admin, 'telemetry'], [AdminMiddleware::class]);
$router->post('/admin/telemetry/self-heal', [$admin, 'selfHealTelemetry'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/telemetry/export', [$admin, 'exportTelemetryReport'], [AdminMiddleware::class]);
$router->get('/admin/heap', [$admin, 'heap'], [AdminMiddleware::class]);
$router->post('/admin/heap/gc', [$admin, 'heapGc'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/reports/export', [$admin, 'exportReports'], [AdminMiddleware::class]);
$router->post('/admin/settings/test-webhook', [$admin, 'testWebhook'], [AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/api/v1/auth/csrf', [AuthController::class, 'csrf']);
$router->post('/api/v1/assistant/chat', [AiAssistantController::class, 'chat'], [CsrfMiddleware::class]);
$router->post('/api/v1/auth/login', [AuthController::class, 'login'], [RateLimiterMiddleware::class, CsrfMiddleware::class]);
$router->post('/api/v1/auth/logout', [AuthController::class, 'logout'], [CsrfMiddleware::class]);
$router->get('/api/v1/auth/me', [AuthController::class, 'me']);
$router->get('/robots.txt', function () {
    header('Content-Type: text/plain; charset=UTF-8');
    echo "User-agent: *\nAllow: /\nDisallow: /admin/\nDisallow: /customer/\nDisallow: /vendor/\nDisallow: /delivery/\nDisallow: /finance/\nDisallow: /api/\n";
    exit;
});
