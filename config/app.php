<?php
declare(strict_types=1);

$env = static function (string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? getenv($key);
    return $value === false || $value === null ? $default : $value;
};
return [
    'name' => $env('APP_NAME', 'Noir & Co.'), 'env' => $env('APP_ENV', 'local'), 'key' => trim((string)$env('APP_KEY', '')), 'debug' => filter_var($env('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'timezone' => 'Africa/Lagos', 'url' => $env('APP_URL', 'http://localhost:8000'),
    'session' => ['name' => $env('SESSION_NAME', 'noir_session'), 'secure' => filter_var($env('SESSION_SECURE', false), FILTER_VALIDATE_BOOL)],
    'mail' => ['from_address' => $env('MAIL_FROM_ADDRESS', 'no-reply@noir.test'), 'from_name' => $env('MAIL_FROM_NAME', 'Noir & Co.'), 'contact_to' => $env('CONTACT_TO_EMAIL', ''), 'enabled' => filter_var($env('MAIL_ENABLED', false), FILTER_VALIDATE_BOOL), 'brevo_api_key' => trim((string)($env('BREVO_API_KEY') ?: $env('SMTP_KEY', ''))), 'smtp_key' => trim((string)$env('SMTP_KEY', ''))],
    'recaptcha' => [
        'version' => strtolower(trim((string)($env('RECAPTCHA_VERSION') ?: 'v3'))),
        'site_key' => trim((string)($env('RECAPTCHA_SITE_KEY') ?: $env('site') ?: $env('site_key') ?: $env('SITE_KEY') ?: $env('RECAPTCHA_SITE') ?: '6LfwneUtAAAAANk9NU-mJL-_iPn8ccx_msUUXvdU')),
        'secret_key' => trim((string)($env('RECAPTCHA_SECRET_KEY') ?: $env('secret') ?: $env('secret_key') ?: $env('SECRET_KEY') ?: $env('RECAPTCHA_SECRET') ?: '6LfwneUtAAAAAGalHqQjlpv7w7D-IIybIXiuh3Md')),
    ],
    'google' => [
        'client_id' => trim((string)($env('GOOGLE_OAUTH_CLIENT_ID') ?: $env('GOOGLE_CLIENT_ID') ?: $env('client_id') ?: $env('client id') ?: $env('CLIENT_ID') ?: '863818902619-u63j9hs0go519obr8njgmgg9asc03lej.apps.googleusercontent.com')),
        'client_secret' => trim((string)($env('GOOGLE_OAUTH_CLIENT_SECRET') ?: $env('GOOGLE_CLIENT_SECRET') ?: $env('client_secret') ?: $env('client secret') ?: $env('CLIENT_SECRET') ?: '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')),
        'redirect_uri' => trim((string)($env('GOOGLE_OAUTH_REDIRECT_URI') ?: $env('GOOGLE_REDIRECT_URI') ?: 'https://noir-73fy.onrender.com/auth/google/callback')),
    ],
    'ai' => [
        'enabled' => filter_var($env('AI_ASSISTANT_ENABLED', false), FILTER_VALIDATE_BOOL),
        'provider' => strtolower(trim((string)$env('AI_PROVIDER', 'openai'))),
        // AI_API_KEY, GEMINI_API_KEY, and OPENROUTER_API_KEY are supported for new integrations.
        // OPENAI_API_KEY remains a backwards-compatible fallback for existing installs.
        'api_key' => $env('AI_API_KEY', $env('GEMINI_API_KEY', $env('OPENROUTER_API_KEY', $env('OPENAI_API_KEY', '')))),
        'openai_api_key' => $env('OPENAI_API_KEY', ''),
        'base_url' => rtrim((string)$env('AI_API_BASE_URL', ''), '/'),
        'model' => $env('AI_MODEL', $env('OPENAI_MODEL', 'openai/gpt-4.1-mini')),
        'timeout' => max(5, min(60, (int)($env('AI_ASSISTANT_TIMEOUT', '') === '' ? 20 : $env('AI_ASSISTANT_TIMEOUT', 20)))),
        'max_requests_per_window' => max(1, min(100, (int)$env('AI_ASSISTANT_MAX_REQUESTS', 12))),
        'rate_limit_window' => max(60, min(3600, (int)$env('AI_ASSISTANT_RATE_LIMIT_WINDOW', 600))),
    ],
    'database' => (static function () use ($env): array {
        $dbConfig = [
            'host' => $env('DB_HOST', '127.0.0.1'),
            'port' => (int)$env('DB_PORT', 3306),
            'name' => $env('DB_DATABASE', 'noir'),
            'user' => $env('DB_USERNAME', 'root'),
            'password' => (string)$env('DB_PASSWORD', ''),
            'charset' => $env('DB_CHARSET', 'utf8mb4'),
            'ssl' => filter_var($env('DB_SSL', false), FILTER_VALIDATE_BOOL),
        ];
        $url = $env('DATABASE_URL') ?: $env('MYSQL_URL') ?: $env('AIVEN_DATABASE_URL') ?: $env('AIVEN_URL');
        if (!empty($url)) {
            $parsed = parse_url((string)$url);
            if ($parsed) {
                if (!empty($parsed['host'])) $dbConfig['host'] = $parsed['host'];
                if (!empty($parsed['port'])) $dbConfig['port'] = (int)$parsed['port'];
                if (!empty($parsed['user'])) $dbConfig['user'] = urldecode($parsed['user']);
                if (isset($parsed['pass'])) $dbConfig['password'] = urldecode($parsed['pass']);
                if (!empty($parsed['path'])) $dbConfig['name'] = ltrim($parsed['path'], '/');
                $dbConfig['ssl'] = true;
            }
        }
        return $dbConfig;
    })(),
    'app_download_url' => $env('APP_DOWNLOAD_URL', 'https://github.com/successoluwayomi22-max/noir/releases/download/v1.0.0/noir-app.apk'),
    'analytics' => ['google_measurement_id' => trim((string)$env('GOOGLE_ANALYTICS_ID', ''))],
    'payments' => [
        'default_provider' => $env('PAYMENT_DEFAULT_PROVIDER', 'paystack'),
        'currencies' => array_values(array_filter(array_map('trim', explode(',', (string)$env('PAYMENT_CURRENCIES', 'NGN,USD,GBP,EUR'))))),
        'paystack' => ['secret_key' => $env('PAYSTACK_SECRET_KEY', ''), 'public_key' => $env('PAYSTACK_PUBLIC_KEY', '')],
        'stripe' => ['secret_key' => $env('STRIPE_SECRET_KEY', ''), 'publishable_key' => $env('STRIPE_PUBLISHABLE_KEY', '')],
    ],
    'roles' => require BASE_PATH . '/config/roles.php',
];
