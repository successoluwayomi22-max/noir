<?php

declare(strict_types=1);

namespace Noir\App\Services;

final class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    private ?string $siteKey;
    private ?string $secretKey;

    public function __construct(?string $siteKey = null, ?string $secretKey = null)
    {
        $this->siteKey = $siteKey ?? (string) config('recaptcha.site_key', (string)($_ENV['RECAPTCHA_SITE_KEY'] ?? ''));
        $this->secretKey = $secretKey ?? (string) config('recaptcha.secret_key', (string)($_ENV['RECAPTCHA_SECRET_KEY'] ?? ''));
    }

    public function siteKey(): string
    {
        return trim((string) $this->siteKey);
    }

    public function secretKey(): string
    {
        return trim((string) $this->secretKey);
    }

    /**
     * reCAPTCHA is only considered enabled when both site_key and secret_key are configured.
     * This ensures local development and automated test suites without keys run without disruption.
     */
    public function isEnabled(): bool
    {
        return $this->siteKey() !== '' && $this->secretKey() !== '';
    }

    /**
     * Verifies the client response token against Google's verification API.
     * Returns true if reCAPTCHA is disabled, or if Google confirms success.
     */
    public function verify(?string $token, ?string $ip = null): bool
    {
        if (!$this->isEnabled()) {
            return true;
        }

        $token = trim((string) $token);
        if ($token === '') {
            return false;
        }

        $payload = [
            'secret'   => $this->secretKey(),
            'response' => $token,
        ];

        if ($ip !== null && $ip !== '') {
            $payload['remoteip'] = $ip;
        }

        return $this->postVerify($payload);
    }

    private function postVerify(array $payload): bool
    {
        $postData = http_build_query($payload);

        if (function_exists('curl_init')) {
            $ch = curl_init();
            $curlOptions = [
                CURLOPT_URL            => self::VERIFY_URL,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $postData,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Content-Length: ' . strlen($postData),
                ],
            ];

            if (file_exists('C:\\xamp\\apache\\bin\\curl-ca-bundle.crt')) {
                $curlOptions[CURLOPT_CAINFO] = 'C:\\xamp\\apache\\bin\\curl-ca-bundle.crt';
            }

            curl_setopt_array($ch, $curlOptions);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error !== '') {
                error_log('reCAPTCHA cURL error: ' . $error);
            }

            if ($response !== false && $httpCode === 200) {
                $data = json_decode((string) $response, true);
                return !empty($data['success']);
            }
        }

        // Fallback to stream context if cURL is unavailable
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: " . strlen($postData) . "\r\n",
                'content' => $postData,
                'timeout' => 8,
            ],
        ]);

        $response = @file_get_contents(self::VERIFY_URL, false, $context);
        if ($response !== false) {
            $data = json_decode($response, true);
            return !empty($data['success']);
        }

        return false;
    }
}
