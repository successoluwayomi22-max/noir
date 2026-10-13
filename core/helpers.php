<?php
declare(strict_types=1);

// ---------------------------------------------------------------------------
// Split helper modules — loaded here so all callers continue to work with a
// single require of this file (nothing outside needs to change).
// ---------------------------------------------------------------------------
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/branding.php';
require_once __DIR__ . '/media.php';
require_once __DIR__ . '/i18n.php';

// ---------------------------------------------------------------------------
// Defensive fallback: ensure csp_nonce() is always available, even when
// the exception handler renders before bootstrap.php defines CSP_NONCE.
// ---------------------------------------------------------------------------
if (!defined('CSP_NONCE')) {
    define('CSP_NONCE', base64_encode(random_bytes(16)));
}
if (!function_exists('csp_nonce')) {
    function csp_nonce(): string {
        return CSP_NONCE;
    }
}

// ---------------------------------------------------------------------------
// Core utilities
// ---------------------------------------------------------------------------

/**
 * Reads a dot-separated key from the application config array.
 * Returns $default when the key does not exist.
 */
function config(?string $key = null, mixed $default = null): mixed
{
    try {
        $config = Noir\Core\Container::get('config') ?? [];
    } catch (\Throwable) {
        $config = [];
    }

    if ($key === null) {
        return $config;
    }

    foreach (explode('.', $key) as $segment) {
        $config = is_array($config) ? ($config[$segment] ?? $default) : $default;
    }

    return $config;
}

/**
 * Fetches a single value from the `settings` table, caching all rows in a
 * static variable so the table is only queried once per request.
 */
function store_setting(string $key, string $default = ''): string
{
    static $settings = null;

    if ($settings === null) {
        $settings = [];
        try {
            $db = \Noir\Core\Container::get('db');
            if ($db) {
                $settings = $db->query('SELECT setting_key,setting_value FROM settings')
                    ->fetchAll(\PDO::FETCH_KEY_PAIR);
            }
        } catch (\Throwable) {}
    }

    return (string) ($settings[$key] ?? $default);
}

/**
 * Sends a JSON response and terminates the request.
 */
function json(array $payload, int $status = 200): never
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Returns (and lazily creates) the CSRF token for the current session.
 */
function csrf_token(): string
{
    return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
}

/**
 * HTML-encodes a value for safe output inside HTML attributes and text nodes.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Converts a human-readable label into a URL-safe lowercase slug.
 */
function catalogue_slug(string $value): string
{
    return strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', trim($value)), '-'));
}

/**
 * Returns the currently active currency code (e.g. NGN, USD, EUR, GBP).
 */
function active_currency(): string
{
    return strtoupper((string)($_SESSION['currency'] ?? $_COOKIE['noir_currency'] ?? store_setting('currency', 'NGN')));
}

/**
 * Returns the active currency symbol based on the configured currency setting.
 */
function currency_symbol(?string $currency = null): string
{
    $c = strtoupper(trim((string)($currency ?: active_currency())));
    return match ($c) {
        'USD', '$' => '$',
        'EUR', '€' => '€',
        'GBP', '£' => '£',
        'CAD'      => 'CA$',
        'AUD'      => 'AU$',
        'JPY', '¥' => '¥',
        'GHS'      => 'GH₵',
        'KES'      => 'KSh ',
        'ZAR'      => 'R ',
        default    => '₦',
    };
}

/**
 * Fetches real-time exchange rates with automatic local caching.
 */
function get_exchange_rate(?string $currency = null): float
{
    $active = strtoupper(trim((string)($currency ?: active_currency())));
    if ($active === 'NGN' || $active === '') {
        return 1.0;
    }

    static $memoryRates = null;
    if ($memoryRates === null) {
        $cacheFile = BASE_PATH . '/storage/cache/exchange_rates.json';
        $fresh = false;

        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 21600) { // 6 hours cache
            $data = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($data) && !empty($data['rates'])) {
                $memoryRates = $data['rates'];
                $fresh = true;
            }
        }

        if (!$fresh) {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 2,
                    'ignore_errors' => true,
                    'header' => "User-Agent: NoirStorefront/2.0\r\n"
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);
            $raw = @file_get_contents('https://open.er-api.com/v6/latest/NGN', false, $ctx);
            if ($raw) {
                $parsed = json_decode($raw, true);
                if (is_array($parsed) && isset($parsed['rates']) && is_array($parsed['rates'])) {
                    $memoryRates = $parsed['rates'];
                    if (!is_dir(BASE_PATH . '/storage/cache')) {
                        @mkdir(BASE_PATH . '/storage/cache', 0777, true);
                    }
                    @file_put_contents($cacheFile, $raw);
                }
            }
        }
    }

    if (isset($memoryRates[$active]) && is_numeric($memoryRates[$active]) && (float)$memoryRates[$active] > 0) {
        return (float)$memoryRates[$active];
    }

    // Accurate fallback baseline
    return match ($active) {
        'USD' => 0.00075,
        'EUR' => 0.00063,
        'GBP' => 0.00054,
        'CAD' => 0.00100,
        'AUD' => 0.00102,
        'GHS' => 0.00827,
        'KES' => 0.0966,
        'ZAR' => 0.0118,
        default => 1.0,
    };
}

/**
 * Formats a monetary amount using the active currency symbol and real-time conversion.
 */
function format_currency(mixed $amount, ?string $currency = null): string
{
    $val = (float)($amount ?? 0);
    $active = strtoupper(trim((string)($currency ?: active_currency())));
    $rate = get_exchange_rate($active);
    $converted = $val * $rate;
    $sym = currency_symbol($active);

    if ($active === 'NGN') {
        $decimals = fmod($val, 1.0) == 0.0 ? 0 : 2;
        return $sym . number_format($converted, $decimals);
    }

    return $sym . number_format($converted, 2);
}

/**
 * Returns a styled <select> form for changing the storefront currency.
 */
function currency_switcher_html(string $publicPath = ''): string
{
    $currencies = [
        'NGN' => 'NGN - Nigerian Naira (₦)',
        'USD' => 'USD - US Dollar ($)',
        'EUR' => 'EUR - Euro (€)',
        'GBP' => 'GBP - British Pound (£)',
    ];
    $current = active_currency();

    $opts = '';
    foreach ($currencies as $code => $label) {
        $selected = $code === $current ? ' selected' : '';
        $opts .= '<option value="' . e($code) . '"' . $selected . '>' . e($label) . '</option>';
    }

    return '<form method="post" action="' . e($publicPath) . '/set-currency" class="currency-switcher-form" style="display:inline">'
        . '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'
        . '<select id="footer-currency-select" name="currency" onchange="this.form.submit()" aria-label="Select currency">'
        . $opts
        . '</select>'
        . '</form>';
}

/**
 * Output-buffer callback that fixes legacy Windows-1252 / double-encoded
 * UTF-8 characters and applies editorial copy improvements to rendered HTML.
 */
function normalize_legacy_encoding(string $output): string
{
    $map = [
        hex2bin('c3a2e282ace284a2') => hex2bin('e28099'),
        hex2bin('c3a2e280a0e28094') => hex2bin('e28697'),
        hex2bin('c3a2e28090')       => hex2bin('e28690'),
        hex2bin('c3a2e28093')       => hex2bin('e28093'),
        hex2bin('c3a2e28094')       => hex2bin('e28094'),
        hex2bin('c3a2e2809a')       => hex2bin('e282a6'),
        hex2bin('c3a2e282ace2809c') => hex2bin('e28093'),
        hex2bin('c382c2a9')         => hex2bin('c2a9'),
        hex2bin('c382c2b7')         => hex2bin('c2b7'),
        hex2bin('c383c297')         => hex2bin('c397'),
        hex2bin('c383e28094')       => hex2bin('c397'),
    ];

    $output = str_replace(array_keys($map), array_values($map), $output);

    // Dynamic Multi-Language Page Translation Pass
    $locale = function_exists('current_locale') ? current_locale() : 'en';
    if ($locale !== 'en' && function_exists('_load_translations')) {
        $trans = _load_translations($locale);
        if (!empty($trans) && is_array($trans)) {
            // Sort keys by length descending to replace longer phrases first
            uksort($trans, static fn($a, $b) => strlen((string)$b) <=> strlen((string)$a));
            foreach ($trans as $enKey => $translatedVal) {
                if (is_string($translatedVal) && $translatedVal !== '' && is_string($enKey) && $enKey !== $translatedVal) {
                    if (preg_match('/^[a-zA-Z0-9_-]+$/', $enKey)) {
                        $pattern = '/\b' . preg_quote($enKey, '/') . '\b/u';
                        $output = preg_replace($pattern, $translatedVal, $output) ?? $output;
                    } else {
                        $output = str_replace($enKey, $translatedVal, $output);
                    }
                }
            }
        }
    } else {
        // Editorial layer for default English interface
        $copy = [
            'Everything, considered.'                                          => 'Everything you need, considered.',
            'The latest edit'                                                  => 'Made for the moments that matter',
            'Find your point of view.'                                         => 'Find the pieces that feel like you.',
            'Build your uniform.'                                              => 'Build a wardrobe with staying power.',
            'Explore the collection by form, function, and feeling.'           => 'Shop by the forms, functions, and feelings that shape your day.',
            'Here is what is happening with your account.'                     => 'Your latest orders, rewards, and account updates—beautifully in one place.',
            'View all orders'                                                  => 'See your full order history',
            'Recent orders'                                                    => 'Your latest orders',
            'View history'                                                     => 'See all activity',
            'No orders yet.'                                                   => 'Your next considered purchase will appear here.',
            'Need your attention'                                              => 'Worth a quick look',
            'Keep collecting'                                                  => 'Every purchase adds value',
            'Your rewards'                                                     => 'A little something back',
            'Recommended for you'                                              => 'Selected for your next ritual',
            'Notifications'                                                    => 'Important updates',
            'Stay up to date with your orders, rewards, and account activity.' => 'The important updates, right when they matter.',
            'You are all caught up'                                            => 'You are beautifully up to date',
            'New account and order updates will appear here.'                  => 'When something matters, you will find it here first.',
            'Here is what is happening across '                                => 'A sharper view of ',
            'Commerce catalogue'                                               => 'Your catalogue, at a glance',
            'A clear view of everything you sell, from first draft to last order.' => 'Keep every piece moving from first idea to final checkout.',
            'Your product workspace'                                           => 'Make every product count',
            'Manage products'                                                  => 'Shape the catalogue',
            'Open reports'                                                     => 'See what is working',
            'Monitor commerce, customers, staff, products, and system activity.' => 'Turn daily activity into confident decisions.',
            'Revenue trend'                                                    => 'Revenue momentum',
            'No orders were recorded in this period.'                          => 'No orders landed in this period—try another range to uncover the story.',
            'No products found'                                                => 'Nothing matches this search yet',
            'Try changing your filters or create your first product.'          => 'Adjust your filters or bring your first product to life.',
            'Product saved successfully.'                                      => 'Product saved—your catalogue is ready for the next customer.',
            'No activity was recorded in this period.'                         => 'No activity appeared in this period.',
            'Considered design for the everyday.'                              => 'Thoughtful design for the way you live.',
        ];

        if (stripos($output, '<html') !== false || stripos($output, '<body') !== false) {
            $output = str_replace(array_keys($copy), array_values($copy), $output);
        }
    }

    try {
        $primary   = store_setting('brand_primary_color', '#687158');
        $dark      = store_setting('brand_dark_color', '#252820');
        $accent    = store_setting('brand_accent_color', '#b9c1a4');
        $paper     = store_setting('brand_paper_color', '#f7f7f2');
        $storeName = strtoupper(store_setting('store_name', 'Noir & Co.'));
        $nameCss   = str_replace(['\\', '"'], ['\\\\', '\\"'], $storeName);

        foreach ([$primary, $dark, $accent, $paper] as $colour) {
            if (!preg_match('/^#[0-9a-f]{6}$/i', $colour)) {
                throw new RuntimeException('Invalid palette colour');
            }
        }

        if (stripos($output, '<head') !== false) {
            $paletteCss = '<style id="store-palette-vars">'
                . ':root{--olive:' . $primary . ';--deep:' . $dark . ';--accent:' . $accent
                . ';--brand-paper:' . $paper . ';--brand-name:"' . $nameCss . '";--soft:' . $paper . '}'
                . ' .admin-sidebar{background:var(--deep)!important}'
                . '.side-link.active,.admin-page .button{background:var(--olive)!important}'
                . '</style>';

            $output = preg_replace('/(<\/head>)/i', $paletteCss . '$1', $output, 1) ?? $output;
        }
    } catch (Throwable) {}

    return $output;
}

/**
 * Renders the Google reCAPTCHA script tag if reCAPTCHA is configured.
 */
function recaptcha_head(): string
{
    $service = new \Noir\App\Services\RecaptchaService();
    if (!$service->isEnabled()) {
        return '';
    }
    $nonce = function_exists('csp_nonce') ? ' nonce="' . e(csp_nonce()) . '"' : '';
    $siteKey = $service->siteKey();
    if ($service->version() === 'v3') {
        return '<script src="https://www.google.com/recaptcha/api.js?render=' . urlencode($siteKey) . '" async defer' . $nonce . '></script>';
    }
    return '<script src="https://www.google.com/recaptcha/api.js" async defer' . $nonce . '></script>';
}

/**
 * Renders the reCAPTCHA widget HTML container if reCAPTCHA is configured.
 */
function recaptcha_field(string $action = 'submit'): string
{
    $service = new \Noir\App\Services\RecaptchaService();
    if (!$service->isEnabled()) {
        return '';
    }
    $siteKey = $service->siteKey();
    if ($service->version() === 'v3') {
        $nonce = function_exists('csp_nonce') ? ' nonce="' . e(csp_nonce()) . '"' : '';
        $jsSiteKey = json_encode($siteKey);
        $jsAction = json_encode($action);
        return '<input type="hidden" name="g-recaptcha-response" class="g-recaptcha-response" value="" data-ready="false">' .
            '<script' . $nonce . '>
            (function() {
                var siteKey = ' . $jsSiteKey . ';
                var actionName = ' . $jsAction . ';

                function obtainToken(callback) {
                    if (window.grecaptcha && window.grecaptcha.ready) {
                        try {
                            window.grecaptcha.ready(function() {
                                window.grecaptcha.execute(siteKey, {action: actionName}).then(function(token) {
                                    document.querySelectorAll("input.g-recaptcha-response").forEach(function(el) {
                                        el.value = token;
                                        el.setAttribute("data-ready", "true");
                                    });
                                    if (typeof callback === "function") callback(token);
                                }).catch(function(err) {
                                    console.warn("reCAPTCHA execute failed:", err);
                                    if (typeof callback === "function") callback("");
                                });
                            });
                        } catch (e) {
                            console.warn("reCAPTCHA invocation error:", e);
                            if (typeof callback === "function") callback("");
                        }
                    } else {
                        if (typeof callback === "function") callback("");
                    }
                }

                // Poll until Google grecaptcha library is ready
                var pollTries = 0;
                var pollInterval = setInterval(function() {
                    pollTries++;
                    if (window.grecaptcha && window.grecaptcha.ready) {
                        clearInterval(pollInterval);
                        obtainToken();
                    } else if (pollTries > 60) {
                        clearInterval(pollInterval);
                    }
                }, 200);

                // Refresh token every 90 seconds (Google v3 tokens expire after 2 minutes)
                setInterval(function() {
                    obtainToken();
                }, 90000);

                // Intercept form submission to guarantee token presence
                document.addEventListener("submit", function(event) {
                    var form = event.target;
                    if (!form || typeof form.querySelector !== "function") return;
                    var input = form.querySelector("input.g-recaptcha-response");
                    if (!input) return;

                    // If a valid token was already pre-fetched, submit immediately
                    if (input.value && input.getAttribute("data-ready") === "true") {
                        return;
                    }

                    // Otherwise intercept and fetch a fresh token before submitting
                    event.preventDefault();
                    var submitBtn = form.querySelector("button[type=submit], input[type=submit]");
                    if (submitBtn) submitBtn.disabled = true;

                    obtainToken(function(token) {
                        input.value = token;
                        input.setAttribute("data-ready", "true");
                        if (submitBtn) submitBtn.disabled = false;
                        HTMLFormElement.prototype.submit.call(form);
                    });
                }, true);
            })();
            </script>';
    }
    return '<div class="recaptcha-wrapper" style="margin: 16px 0;"><div class="g-recaptcha" data-sitekey="' . e($siteKey) . '"></div></div>';
}

/**
 * Helper to verify a reCAPTCHA token against Google.
 */
function recaptcha_verify(?string $token = null, ?string $ip = null): bool
{
    $token = $token ?? (string)($_POST['g-recaptcha-response'] ?? '');
    $ip = $ip ?? (string)($_SERVER['REMOTE_ADDR'] ?? '');
    return (new \Noir\App\Services\RecaptchaService())->verify($token, $ip);
}

