<?php

declare(strict_types=1);

namespace Noir\App\Middleware;

use Noir\Core\Request;

final class SecurityHeadersMiddleware
{
    public function handle(Request $request): void
    {
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(self "https://js.paystack.co")');
        header('X-XSS-Protection: 0');
        header('X-Permitted-Cross-Domain-Policies: none');
        header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
        header('Cross-Origin-Resource-Policy: cross-origin');

        $nonce = defined('CSP_NONCE') ? CSP_NONCE : base64_encode(random_bytes(16));

        // Modern Content-Security-Policy (CSP) tailored for boutique storefront, admin suite & atelier workspaces
        $host = (string)($_SERVER['HTTP_HOST'] ?? '');
        $isLocal = str_starts_with($host, 'localhost') || str_starts_with($host, '127.0.0.1');
        $isHttps = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || (isset($_SERVER['HTTP_CF_VISITOR']) && str_contains($_SERVER['HTTP_CF_VISITOR'], 'https'))
            || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

        $csp = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-inline' https://www.google.com https://www.gstatic.com https://recaptcha.net https://www.recaptcha.net",
            "style-src 'self' 'unsafe-inline' https: data: blob:",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: https: blob: https://www.gstatic.com https://www.google.com",
            "media-src 'self' https: data: blob:",
            "frame-src 'self' https://js.paystack.co https://www.google.com https://recaptcha.google.com https://recaptcha.net https://www.recaptcha.net",
            "connect-src 'self' https://api.paystack.co https://maps.googleapis.com https://*.trycloudflare.com https://www.gstatic.com https://www.google.com https://recaptcha.net https://www.recaptcha.net wss: ws:",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self' https://accounts.google.com",
        ];
        if ($isHttps || !$isLocal) {
            $csp[] = "upgrade-insecure-requests";
        }
        header('Content-Security-Policy: ' . implode('; ', $csp));

        // Enforce HSTS with preload only for actual HTTPS environments (never on cleartext localhost)
        if ($isHttps) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
}
