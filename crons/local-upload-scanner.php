<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$path = (string)($argv[1] ?? '');
if ($path === '' || !is_file($path)) {
    fwrite(STDERR, "File not found.\n");
    exit(2);
}

$blockedExtensions = [
    'bat', 'cmd', 'com', 'cpl', 'exe', 'hta', 'js', 'jse', 'msi', 'php', 'php3',
    'php4', 'php5', 'phtml', 'ps1', 'scr', 'sh', 'vbs', 'wsf',
];

$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
if (in_array($extension, $blockedExtensions, true)) {
    fwrite(STDOUT, "Flagged blocked executable/script extension: {$extension}\n");
    exit(1);
}

$handle = fopen($path, 'rb');
if ($handle === false) {
    fwrite(STDERR, "Unable to read file.\n");
    exit(2);
}

$bytes = (string)fread($handle, 8192);
fclose($handle);

$signatures = [
    '/<\?(?:php|=)?/i' => 'PHP code signature',
    '/<script\b/i' => 'embedded script tag',
    '/\b(eval|base64_decode|shell_exec|passthru|proc_open|popen|system)\s*\(/i' => 'dangerous code function',
    '/^MZ/' => 'Windows executable header',
    '/^\x7fELF/' => 'ELF executable header',
];

foreach ($signatures as $pattern => $label) {
    if (preg_match($pattern, $bytes)) {
        fwrite(STDOUT, "Flagged {$label}.\n");
        exit(1);
    }
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string)$finfo->file($path);
$allowedMimes = [
    'image/jpeg', 'image/png', 'image/webp', 'image/gif',
    'application/pdf',
    'text/plain',
];

if (!in_array($mime, $allowedMimes, true)) {
    fwrite(STDOUT, "Flagged unsupported MIME type: {$mime}\n");
    exit(1);
}

fwrite(STDOUT, "Clean baseline scan: {$mime}\n");
exit(0);
