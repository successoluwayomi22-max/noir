<?php
declare(strict_types=1);

use Noir\App\Controllers\AiAssistantController;
use Noir\App\Controllers\Api\AddressApiController;
use Noir\App\Controllers\Api\AdminApiController;
use Noir\App\Controllers\Api\AiVisualSearchController;
use Noir\App\Controllers\Api\AuthApiController;
use Noir\App\Controllers\Api\CartApiController;
use Noir\App\Controllers\Api\CurrencyApiController;
use Noir\App\Controllers\Api\DropAlertApiController;
use Noir\App\Controllers\Api\FastCheckoutApiController;
use Noir\App\Controllers\Api\GiftCardApiController;
use Noir\App\Controllers\Api\LogisticsApiController;
use Noir\App\Controllers\Api\OrderApiController;
use Noir\App\Controllers\Api\ReviewApiController;
use Noir\App\Controllers\Api\StoreApiController;
use Noir\App\Middleware\ApiTokenMiddleware;
use Noir\App\Middleware\RateLimiterMiddleware;
use Noir\App\Controllers\HealthController;
use Noir\Core\Container;
use Noir\Core\Request;

// System High-Availability Health Check
$router->get('/api/v1/health', [HealthController::class, 'check']);

// Auth Routes
$router->post('/api/v1/mobile/auth/login', [AuthApiController::class, 'login'], [RateLimiterMiddleware::class]);
$router->post('/api/v1/mobile/auth/register', [AuthApiController::class, 'register'], [RateLimiterMiddleware::class]);
$router->post('/api/v1/mobile/auth/google', [AuthApiController::class, 'googleLogin']);
$router->get('/api/v1/mobile/auth/me', [AuthApiController::class, 'me'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/user/fcm-token', [AuthApiController::class, 'updateFcmToken'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/user/profile', [AuthApiController::class, 'updateProfile'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/user/profile-image', [AuthApiController::class, 'uploadProfileImage'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/user/profile-image/delete', [AuthApiController::class, 'deleteProfileImage'], [ApiTokenMiddleware::class]);

// Store Routes
$router->get('/api/v1/mobile/store/settings', [StoreApiController::class, 'settings']);
$router->get('/api/v1/mobile/store/products', [StoreApiController::class, 'products']);
$router->get('/api/v1/mobile/store/categories', [StoreApiController::class, 'categories']);
$router->get('/api/v1/mobile/store/products/{id}', [StoreApiController::class, 'productDetail']);
$router->get('/api/v1/mobile/store/video-commerce', [StoreApiController::class, 'videoCommerce']);
$router->get('/api/v1/mobile/store/community-feed', [StoreApiController::class, 'communityFeed']);
$router->post('/api/v1/mobile/store/community-feed/{id}/like', [StoreApiController::class, 'toggleLike'], [ApiTokenMiddleware::class]);
$router->get('/api/v1/mobile/store/community-feed/{id}/comments', [StoreApiController::class, 'getComments']);
$router->post('/api/v1/mobile/store/community-feed/{id}/comments', [StoreApiController::class, 'addComment'], [ApiTokenMiddleware::class]);

// Cart & Checkout Routes
$router->get('/api/v1/mobile/cart', [CartApiController::class, 'getCart'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/cart/add', [CartApiController::class, 'addToCart'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/cart/remove', [CartApiController::class, 'removeFromCart'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/cart/sync', [CartApiController::class, 'syncCart'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/cart/clear', [CartApiController::class, 'clearCart'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/checkout', [CartApiController::class, 'checkout']);

// Wishlist Routes (Cross-device persistence)
$router->get('/api/v1/mobile/wishlist', [StoreApiController::class, 'getWishlist'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/wishlist/toggle', [StoreApiController::class, 'toggleWishlist'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/wishlist/sync', [StoreApiController::class, 'syncWishlist'], [ApiTokenMiddleware::class]);

// Orders Routes
$router->get('/api/v1/mobile/orders', [OrderApiController::class, 'index'], [ApiTokenMiddleware::class]);
$router->get('/api/v1/mobile/orders/{number}', [OrderApiController::class, 'show'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/orders/track', [OrderApiController::class, 'track']);

// AI Assistant Route
$router->post('/api/v1/mobile/assistant/chat', [AiAssistantController::class, 'chat']);
$router->post('/api/v1/mobile/assistant/feedback', [AiAssistantController::class, 'feedback']);


// Admin API Routes (mobile)
$router->get('/api/v1/mobile/admin/dashboard', [AdminApiController::class, 'dashboard'], [ApiTokenMiddleware::class]);
$router->get('/api/v1/mobile/admin/orders', [AdminApiController::class, 'orders'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/admin/orders/status', [AdminApiController::class, 'updateOrderStatus'], [ApiTokenMiddleware::class]);

// Admin API Routes - Products
$router->get('/api/v1/mobile/admin/products', [AdminApiController::class, 'products'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/admin/products', [AdminApiController::class, 'createProduct'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/admin/products/{id}', [AdminApiController::class, 'updateProduct'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/admin/products/{id}/delete', [AdminApiController::class, 'deleteProduct'], [ApiTokenMiddleware::class]);

// Admin API Routes - Customers
$router->get('/api/v1/mobile/admin/customers', [AdminApiController::class, 'customers'], [ApiTokenMiddleware::class]);

// Admin API Routes - Reports & Settings
$router->get('/api/v1/mobile/admin/reports', [AdminApiController::class, 'reports'], [ApiTokenMiddleware::class]);
$router->get('/api/v1/mobile/admin/settings', [AdminApiController::class, 'settings'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/admin/settings', [AdminApiController::class, 'updateSettings'], [ApiTokenMiddleware::class]);

// New Mobile API routes for 11 Features
$router->get('/api/v1/mobile/products/{id}/reviews', [ReviewApiController::class, 'index'], []);
$router->post('/api/v1/mobile/products/{id}/reviews', [ReviewApiController::class, 'store'], [ApiTokenMiddleware::class]);
$router->get('/api/v1/mobile/addresses', [AddressApiController::class, 'index'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/addresses', [AddressApiController::class, 'store'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/addresses/{id}/delete', [AddressApiController::class, 'destroy'], [ApiTokenMiddleware::class]);

// Auth Mobile OTP Routes
$router->post('/api/v1/mobile/auth/forgot-password', [AuthApiController::class, 'forgotPassword']);
$router->post('/api/v1/mobile/auth/verify-otp', [AuthApiController::class, 'verifyOtp']);
$router->post('/api/v1/mobile/auth/reset-password', [AuthApiController::class, 'resetPassword']);
$router->post('/api/v1/mobile/auth/change-password', [AuthApiController::class, 'changePassword'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/auth/request-password-otp', [AuthApiController::class, 'requestPasswordOtp'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/auth/change-password-with-otp', [AuthApiController::class, 'changePasswordWithOtp'], [ApiTokenMiddleware::class]);

// Perks & Rewards
$router->get('/api/v1/mobile/profile/loyalty', [StoreApiController::class, 'getLoyalty'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/profile/loyalty/redeem', [StoreApiController::class, 'redeemLoyalty'], [ApiTokenMiddleware::class]);

// Gift Cards & Subscriptions
$router->post('/api/v1/mobile/store/gift-cards/buy', [StoreApiController::class, 'buyGiftCard']);
$router->post('/api/v1/mobile/store/gift-cards/check', [StoreApiController::class, 'checkGiftCard']);
$router->get('/api/v1/mobile/store/subscriptions', [StoreApiController::class, 'getSubscriptions']);
$router->post('/api/v1/mobile/store/subscriptions', [StoreApiController::class, 'createSubscription']);

// Discover & AI Stylist
$router->post('/api/v1/mobile/discover/ai-stylist', [StoreApiController::class, 'aiStylist']);
$router->post('/api/v1/mobile/discover/stylist/feedback', [StoreApiController::class, 'stylistFeedback']);
$router->get('/api/v1/mobile/discover/stylist/profile', [StoreApiController::class, 'getStylistProfile']);
$router->post('/api/v1/mobile/discover/stylist/profile', [StoreApiController::class, 'updateStylistProfile']);
$router->post('/api/v1/mobile/discover/stylist/save-outfit', [StoreApiController::class, 'saveOutfit']);
$router->post('/api/v1/mobile/discover/scan-authenticity', [StoreApiController::class, 'scanAuthenticity']);

// Coupons (Verified against coupons DB table)
$router->post('/api/v1/mobile/cart/coupon', function(Request $req) {
    $code = strtoupper(trim((string)$req->input('code')));
    if (empty($code)) {
        json(['success' => false, 'error' => 'Coupon code is required'], 400);
    }
    $db = Container::get('db');
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    if ($coupon) {
        json([
            'success' => true,
            'code' => $coupon['code'],
            'discount_type' => $coupon['discount_type'],
            'discount_value' => (float)$coupon['discount_value'],
            'description' => $coupon['description']
        ]);
    }
    json(['success' => false, 'error' => 'Invalid or expired coupon code'], 400);
});

// Real-Time Order Tracking & Logistics
$router->get('/api/v1/mobile/orders/{id}/tracking', [LogisticsApiController::class, 'getOrderTracking']);
$router->get('/api/v1/tracking/{id}', [LogisticsApiController::class, 'getOrderTracking']);
$router->post('/api/v1/logistics/dispatch', [LogisticsApiController::class, 'getOrderTracking']);

// Drop Alerts & Flash Sales Notifications
$router->get('/api/v1/mobile/drop-alerts', [DropAlertApiController::class, 'getDropAlerts'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/drop-alerts/{id}/notify-me', [DropAlertApiController::class, 'toggleSubscription'], [ApiTokenMiddleware::class]);
$router->get('/api/v1/mobile/notifications', [DropAlertApiController::class, 'getNotifications'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/notifications/read', [DropAlertApiController::class, 'markAsRead'], [ApiTokenMiddleware::class]);
$router->post('/api/v1/mobile/notifications/register-device', [DropAlertApiController::class, 'registerDeviceToken'], [ApiTokenMiddleware::class]);

// AI Visual Search ("Snap & Match")
$router->post('/api/v1/mobile/ai/visual-search', [AiVisualSearchController::class, 'search']);
$router->post('/ai/visual-search', [AiVisualSearchController::class, 'search']);

// 1-Tap Biometric & Express Fast Checkout
$router->post('/api/v1/mobile/fast-checkout', [FastCheckoutApiController::class, 'expressPurchase']);

// Digital VIP Luxury Gift Cards & Vouchers
$router->get('/api/v1/gift-cards/balance', [GiftCardApiController::class, 'checkBalance']);
$router->post('/api/v1/gift-cards/purchase', [GiftCardApiController::class, 'purchase']);
$router->post('/api/v1/gift-cards/apply', [GiftCardApiController::class, 'applyToOrder']);

// Multi-Currency & Real-Time Exchange Rates
$router->get('/api/v1/mobile/currencies', [CurrencyApiController::class, 'getCurrencies']);
$router->get('/api/v1/mobile/currencies/convert', [CurrencyApiController::class, 'convert']);
$router->post('/api/v1/mobile/currencies/convert', [CurrencyApiController::class, 'convert']);
