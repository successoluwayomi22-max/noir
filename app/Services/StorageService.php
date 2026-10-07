<?php

declare(strict_types=1);

namespace Noir\App\Services;

final class StorageService
{
    private static ?string $driver = null;

    /**
     * Get active storage driver ('local' or 's3').
     */
    public static function getDriver(): string
    {
        if (self::$driver !== null) {
            return self::$driver;
        }

        $configured = (string)($_ENV['STORAGE_DRIVER'] ?? 'local');
        if ($configured === 's3' && !empty($_ENV['AWS_BUCKET']) && !empty($_ENV['AWS_ACCESS_KEY_ID'])) {
            self::$driver = 's3';
        } else {
            self::$driver = 'local';
        }

        return self::$driver;
    }

    /**
     * Store an uploaded file.
     * Returns the web-accessible relative path or full cloud URL.
     */
    public static function store(string $tmpPath, string $folder, string $filename): string
    {
        $folder = trim($folder, '/');
        $filename = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $filename);

        // 1. Cloud S3 / Cloudflare R2 driver
        if (self::getDriver() === 's3') {
            $cloudUrl = self::uploadToS3($tmpPath, $folder . '/' . $filename);
            if ($cloudUrl !== null) {
                return $cloudUrl;
            }
        }

        // 2. Local disk fallback (public/uploads/...)
        $targetDir = BASE_PATH . '/public/uploads/' . $folder;
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $destPath = $targetDir . '/' . $filename;
        if (is_uploaded_file($tmpPath)) {
            @move_uploaded_file($tmpPath, $destPath);
        } else {
            @copy($tmpPath, $destPath);
        }

        return '/uploads/' . $folder . '/' . $filename;
    }

    /**
     * Generate the full public URL for a stored asset path.
     */
    public static function url(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        return ($base ?: '') . '/' . ltrim($path, '/');
    }

    /**
     * Delete an asset from storage.
     */
    public static function delete(string $path): bool
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            // Cloud asset
            return true;
        }

        $local = BASE_PATH . '/public/' . ltrim($path, '/');
        if (is_file($local)) {
            return @unlink($local);
        }

        return false;
    }

    /**
     * Upload stream to S3-compatible REST API.
     */
    private static function uploadToS3(string $tmpPath, string $key): ?string
    {
        $bucket = (string)($_ENV['AWS_BUCKET'] ?? '');
        $endpoint = (string)($_ENV['AWS_ENDPOINT'] ?? "https://{$bucket}.s3.amazonaws.com");
        $keyId = (string)($_ENV['AWS_ACCESS_KEY_ID'] ?? '');
        $secret = (string)($_ENV['AWS_SECRET_ACCESS_KEY'] ?? '');
        $region = (string)($_ENV['AWS_DEFAULT_REGION'] ?? 'us-east-1');

        if (!$bucket || !$keyId || !$secret || !is_file($tmpPath)) {
            return null;
        }

        $content = @file_get_contents($tmpPath);
        if ($content === false) {
            return null;
        }

        $mime = mime_content_type($tmpPath) ?: 'application/octet-stream';
        $host = parse_url($endpoint, PHP_URL_HOST) ?: "{$bucket}.s3.amazonaws.com";
        $url = rtrim($endpoint, '/') . '/' . ltrim($key, '/');

        // Simple PUT with AWS Signature V4 headers
        $date = gmdate('Ymd\THis\Z');
        $dateShort = gmdate('Ymd');
        $payloadHash = hash('sha256', $content);

        $headers = [
            'Host' => $host,
            'Content-Type' => $mime,
            'x-amz-date' => $date,
            'x-amz-content-sha256' => $payloadHash,
        ];

        // Authorization header signing
        $canonicalHeaders = '';
        $signedHeadersList = [];
        ksort($headers);
        foreach ($headers as $hKey => $hVal) {
            $hLower = strtolower($hKey);
            $canonicalHeaders .= $hLower . ':' . trim($hVal) . "\n";
            $signedHeadersList[] = $hLower;
        }
        $signedHeaders = implode(';', $signedHeadersList);

        $canonicalRequest = "PUT\n/" . ltrim($key, '/') . "\n\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . $payloadHash;
        $scope = "$dateShort/$region/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n$date\n$scope\n" . hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $dateShort, 'AWS4' . $secret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $headers['Authorization'] = "AWS4-HMAC-SHA256 Credential=$keyId/$scope, SignedHeaders=$signedHeaders, Signature=$signature";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $httpHeaders = [];
        foreach ($headers as $hk => $hv) {
            $httpHeaders[] = "$hk: $hv";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);

        $resp = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status >= 200 && $status < 300) {
            return $url;
        }

        return null;
    }
}
