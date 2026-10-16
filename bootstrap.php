<?php
declare(strict_types=1);

define('BASE_PATH', __DIR__);
define('APP_PATH', BASE_PATH . '/app');

require BASE_PATH . '/core/helpers.php';
require BASE_PATH . '/core/Autoloader.php';
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
header('X-XSS-Protection: 1; mode=block');
header('X-Permitted-Cross-Domain-Policies: none');
ob_start('normalize_legacy_encoding');

// Load local development variables without overriding values supplied by the server.
$environmentFile = BASE_PATH . '/.env';
if (is_readable($environmentFile)) {
    foreach (file($environmentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2); $key = trim($key); $value = trim($value);
        if ($key === '' || getenv($key) !== false) continue;
        if (strlen($value) >= 2 && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) $value = substr($value, 1, -1);
        $_ENV[$key] = $value; putenv($key . '=' . $value);
    }
}

Noir\Core\Autoloader::register();
Noir\Core\ExceptionHandler::register();

$config = require BASE_PATH . '/config/app.php';

// Generate cryptographic CSP nonce for this request lifecycle
if (!defined('CSP_NONCE')) {
    define('CSP_NONCE', base64_encode(random_bytes(16)));
}
if (!function_exists('csp_nonce')) {
    function csp_nonce(): string {
        return CSP_NONCE;
    }
}

$host = (string)($_SERVER['HTTP_HOST'] ?? '');
$cleanHost = explode(':', $host)[0];
$isPrivateIp = preg_match('/^(127\.|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $cleanHost) === 1;
$isLocal = str_starts_with($cleanHost, 'localhost') || str_ends_with($cleanHost, '.test') || str_ends_with($cleanHost, '.local') || $isPrivateIp;
$isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
    || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on')
    || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

if ($isHttps) {
    $_SERVER['HTTPS'] = 'on';
}
if ($isHttps && !headers_sent()) {
    header_remove('Strict-Transport-Security');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload', true);
}

// Redirect cleartext HTTP requests to HTTPS when running on public domains / tunnels
if (!$isHttps && php_sapi_name() !== 'cli' && !$isLocal && $host !== '') {
    header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

if ($config['env'] === 'production') {
    if (strlen((string)$config['key']) < 32) {
        throw new RuntimeException('APP_KEY must be at least 32 characters in production.');
    }
    // Never allow an accidental production deployment to render debug output.
    $config['debug'] = false;
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}
date_default_timezone_set($config['timezone']);

$sessionSecure = $isHttps || (bool)($config['session']['secure'] ?? false);

session_name($config['session']['name']);
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $sessionSecure,
    'httponly' => true,
    'samesite' => 'Lax',
]);
// Cluster Session Handler: Automatically leverage Redis in multi-server environments
if (extension_loaded('redis') && !empty($_ENV['REDIS_HOST'])) {
    ini_set('session.save_handler', 'redis');
    $redisAuth = !empty($_ENV['REDIS_PASSWORD']) ? '?auth=' . rawurlencode((string)$_ENV['REDIS_PASSWORD']) : '';
    $redisPort = (int)($_ENV['REDIS_PORT'] ?? 6379);
    ini_set('session.save_path', 'tcp://' . $_ENV['REDIS_HOST'] . ':' . $redisPort . $redisAuth);
} else {
    // Keep local sessions inside the project so the app also runs when the
    // machine-wide PHP session directory is missing or not writable.
    $sessionPath = BASE_PATH . '/storage/sessions';
    if (!is_dir($sessionPath)) {
        @mkdir($sessionPath, 0755, true);
    }
    if (is_dir($sessionPath) && is_writable($sessionPath)) {
        ini_set('session.save_path', $sessionPath);
    }
}
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

Noir\Core\Container::set('config', $config);
$db = Noir\Core\Database::connection($config['database']);
Noir\Core\Container::set('db', $db);
Noir\Core\Container::set('auth', new Noir\Core\Auth());
Noir\Core\Container::set('rbac', new Noir\Core\Rbac($db));
Noir\Core\Container::set('authentication', new Noir\App\Services\AuthenticationService($db));
Noir\Core\Container::set('role_redirect', new Noir\App\Services\RoleRedirectService());

// Domain Repositories
Noir\Core\Container::set('product_repo', new Noir\App\Repositories\ProductRepository($db));
Noir\Core\Container::set('staff_repo', new Noir\App\Repositories\StaffRepository($db));
Noir\Core\Container::set('report_repo', new Noir\App\Repositories\ReportRepository($db));
Noir\Core\Container::set('setting_repo', new Noir\App\Repositories\SettingRepository($db));
Noir\Core\Container::set('order_repo', new Noir\App\Repositories\OrderRepository($db));
Noir\Core\Container::set('customer_repo', new Noir\App\Repositories\CustomerRepository($db));

Noir\Core\Container::get('authentication')->restoreRememberedSession();
