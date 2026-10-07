<?php
// Only allow execution from localhost or CLI
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$isLocal = in_array($ip, ['127.0.0.1', '::1', 'localhost'], true) || (php_sapi_name() === 'cli');

if (!$isLocal) {
    http_response_code(403);
    echo "Forbidden: Diagnostic access restricted to local administration.";
    exit;
}

if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OPcache successfully reset.\n";
} else {
    echo "OPcache is not enabled or available.\n";
}
