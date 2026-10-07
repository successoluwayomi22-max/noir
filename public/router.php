<?php
declare(strict_types=1);

$uri = urldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$docRoot = __DIR__;
$filePath = realpath($docRoot . $uri);

// Security: Ensure requested file is strictly inside public docroot
if ($filePath !== false && str_starts_with($filePath, $docRoot) && is_file($filePath)) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $staticTypes = [
        'css'         => 'text/css; charset=UTF-8',
        'js'          => 'application/javascript; charset=UTF-8',
        'json'        => 'application/json; charset=UTF-8',
        'webmanifest' => 'application/manifest+json; charset=UTF-8',
        'png'         => 'image/png',
        'jpg'         => 'image/jpeg',
        'jpeg'        => 'image/jpeg',
        'webp'        => 'image/webp',
        'gif'         => 'image/gif',
        'svg'         => 'image/svg+xml',
        'ico'         => 'image/x-icon',
        'woff'        => 'font/woff',
        'woff2'       => 'font/woff2',
        'ttf'         => 'font/ttf',
        'txt'         => 'text/plain; charset=UTF-8',
        'xml'         => 'application/xml; charset=UTF-8',
    ];

    if (isset($staticTypes[$ext])) {
        // Fast 304 Not Modified support
        $mtime = filemtime($filePath);
        $etag = sprintf('"%x-%x"', $mtime, filesize($filePath));
        header('ETag: ' . $etag);
        header('Cache-Control: public, max-age=31536000, immutable');
        header('Content-Type: ' . $staticTypes[$ext]);
        header('X-Content-Type-Options: nosniff');

        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
            http_response_code(304);
            exit;
        }

        readfile($filePath);
        exit;
    }

    return false;
}

require __DIR__ . '/index.php';
