<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use Noir\Core\Container;

final class OpsPreflight
{
    private int $pass = 0;
    private int $warn = 0;
    private int $fail = 0;

    public function pass(string $message): void
    {
        $this->pass++;
        echo "PASS  {$message}\n";
    }

    public function warn(string $message): void
    {
        $this->warn++;
        echo "WARN  {$message}\n";
    }

    public function fail(string $message): void
    {
        $this->fail++;
        echo "FAIL  {$message}\n";
    }

    public function summary(): int
    {
        echo "\n{$this->pass} passed, {$this->warn} warnings, {$this->fail} failures\n";
        return $this->fail === 0 ? 0 : 1;
    }
}

function env_value(string $key, mixed $default = ''): mixed
{
    $value = $_ENV[$key] ?? getenv($key);
    return $value === false || $value === null ? $default : $value;
}

function table_exists(PDO $db, string $table): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function route_status(string $url): int
{
    $headers = @get_headers($url, true);
    if (!$headers || !isset($headers[0]) || !preg_match('/\s(\d{3})\s/', (string)$headers[0], $match)) {
        return 0;
    }

    return (int)$match[1];
}

function mysqldump_path(): string
{
    $path = trim((string)`where mysqldump 2>NUL`);
    if ($path !== '') {
        return strtok($path, "\r\n") ?: $path;
    }

    foreach ([BASE_PATH . '/../mysql/bin/mysqldump.exe', 'C:/xamp/mysql/bin/mysqldump.exe', 'C:/xampp/mysql/bin/mysqldump.exe'] as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    return '';
}

function scanner_command_status(string $scanner): int
{
    $tmp = BASE_PATH . '/storage/validation/preflight-scan.txt';
    $dir = dirname($tmp);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    file_put_contents($tmp, "Noir scanner preflight\n");
    $command = str_contains($scanner, '{file}')
        ? str_replace('{file}', escapeshellarg($tmp), $scanner)
        : $scanner . ' ' . escapeshellarg($tmp);
    exec($command, $output, $code);
    @unlink($tmp);

    return (int)$code;
}

$check = new OpsPreflight();
$config = Container::get('config');
$db = Container::get('db');
$baseCandidates = array_values(array_unique(array_filter([
    trim((string)env_value('PREFLIGHT_BASE_URL', '')),
    rtrim((string)config('url', ''), '/'),
    'http://localhost/noir/public',
])));
$baseUrl = '';
foreach ($baseCandidates as $candidate) {
    if (route_status($candidate . '/store') === 200) {
        $baseUrl = rtrim($candidate, '/');
        break;
    }
}
if ($baseUrl === '') {
    $baseUrl = rtrim((string)($baseCandidates[0] ?? 'http://localhost/noir/public'), '/');
}

$appKey = (string)config('key', '');
strlen($appKey) >= 32
    ? $check->pass('APP_KEY is at least 32 characters.')
    : $check->fail('APP_KEY is missing or shorter than 32 characters.');

config('debug', false) === false
    ? $check->pass('APP_DEBUG resolves to false.')
    : $check->warn('APP_DEBUG is enabled; this is acceptable locally but not production-ready.');

config('session.secure', false) === true
    ? $check->pass('SESSION_SECURE is enabled.')
    : $check->warn('SESSION_SECURE is disabled; enable it on HTTPS production.');

config('payments.default_provider') === 'paystack'
    ? $check->pass('Default payment provider is Paystack.')
    : $check->fail('Default payment provider is not Paystack.');

trim((string)config('payments.paystack.secret_key', '')) !== ''
    ? $check->pass('Paystack secret key is configured.')
    : $check->fail('Paystack secret key is missing.');

trim((string)config('payments.paystack.public_key', '')) !== ''
    ? $check->pass('Paystack public key is configured.')
    : $check->warn('Paystack public key is missing; current server-side redirect checkout uses the secret key, but add the public key before inline checkout or public Paystack integrations.');

trim((string)config('mail.brevo_api_key', '')) !== ''
    ? $check->pass('Brevo API key is configured.')
    : $check->fail('Brevo API key is missing.');

config('mail.enabled', false) === true
    ? $check->pass('Transactional mail is enabled.')
    : $check->warn('MAIL_ENABLED is false; delivery-provider proof is blocked until enabled in the target environment.');

trim((string)config('google.client_id', '')) !== '' && trim((string)config('google.client_secret', '')) !== ''
    ? $check->pass('Google OAuth client id and secret are configured.')
    : $check->warn('Google OAuth credentials are incomplete.');

foreach (['payment_transactions', 'upload_scan_queue', 'analytics_events', 'security_alerts', 'jobs', 'failed_jobs'] as $table) {
    table_exists($db, $table)
        ? $check->pass("Required operations table exists: {$table}.")
        : $check->fail("Required operations table is missing: {$table}.");
}

is_file(BASE_PATH . '/bin/worker.php') && is_file(BASE_PATH . '/crons/process-queue.php')
    ? $check->pass('Background queue worker daemon and cron scripts are available.')
    : $check->fail('Queue worker script(s) missing.');

table_exists($db, 'schema_migrations')
    ? $check->pass('schema_migrations table exists.')
    : $check->warn('schema_migrations table is missing; baseline the current schema before production migration tracking.');

$scannerCommand = trim((string)env_value('MALWARE_SCANNER_COMMAND', ''));
if ($scannerCommand === '') {
    $check->warn('MALWARE_SCANNER_COMMAND is missing; upload malware scanning is not production-complete.');
} elseif (scanner_command_status($scannerCommand) === 0) {
    $check->pass('MALWARE_SCANNER_COMMAND is configured and executable.');
} else {
    $check->fail('MALWARE_SCANNER_COMMAND is configured but did not scan a safe preflight file successfully.');
}

is_file(BASE_PATH . '/public/uploads/.htaccess')
    ? $check->pass('Uploads execution-deny .htaccess exists.')
    : $check->fail('Uploads execution-deny .htaccess is missing.');

is_dir(BASE_PATH . '/storage/backups')
    ? $check->pass('Backup directory exists.')
    : $check->warn('Backup directory does not exist yet; the backup cron will create it on first run.');

mysqldump_path() !== ''
    ? $check->pass('mysqldump is available for backup cron.')
    : $check->warn('mysqldump is not available; backup cron may fail unless the host PATH is configured.');

foreach ([
    '/store' => [200],
    '/cart' => [200],
    '/login' => [200],
    '/checkout' => [200, 302, 303],
] as $path => $allowed) {
    $status = route_status($baseUrl . $path);
    in_array($status, $allowed, true)
        ? $check->pass("Route {$path} returned expected HTTP {$status}.")
        : $check->fail("Route {$path} returned unexpected HTTP {$status}.");
}

exit($check->summary());
