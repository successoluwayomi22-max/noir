<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

if (!defined('NOIR_TRACE_ID')) {
    define('NOIR_TRACE_ID', 'trc_' . bin2hex(random_bytes(6)));
}
header_remove('X-Powered-By');
header('X-Trace-Id: ' . NOIR_TRACE_ID);
\Noir\App\Services\ProfilerService::start();

register_shutdown_function(static function () {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!empty($uri) && !str_starts_with((string)$uri, '/assets/')) {
        $realMb = round(memory_get_usage(true) / 1024 / 1024, 2);
        $peakMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
        \Noir\App\Services\HeapProfilerService::recordRouteMemory($method, (string)$uri, $realMb, $peakMb);
    }
});

$router = new Noir\Core\Router();
require BASE_PATH . '/routes/web.php';
if (file_exists(BASE_PATH . '/routes/api.php')) {
    require BASE_PATH . '/routes/api.php';
}
// Anti-Ad Protection: Block browser ad auctions, topic tracking, and interest cohorts
header('Permissions-Policy: interest-cohort=(), browsing-topics=(), run-ad-auction=(), join-ad-interest-group=(), camera=(), microphone=(), geolocation=(), payment=(self "https://js.paystack.co")');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-XSS-Protection: 0');
header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
header('Cross-Origin-Resource-Policy: cross-origin');


// Enable CORS for Flutter Web / external clients
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
