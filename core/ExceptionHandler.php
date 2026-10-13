<?php

declare(strict_types=1);

namespace Noir\Core;

use Throwable;

final class ExceptionHandler
{
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline): bool {
            if (!(error_reporting() & $errno)) {
                return false;
            }
            // Only throw on fatal/recoverable user errors; log non-fatal warnings and notices without terminating the request
            if ($errno & (E_USER_ERROR | E_RECOVERABLE_ERROR)) {
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
            error_log(sprintf('[PHP Non-Fatal %d] %s in %s:%d', $errno, $errstr, $errfile, $errline));
            return true;
        });

        set_exception_handler([self::class, 'handleException']);
    }

    public static function handleException(Throwable $e): void
    {
        $traceId = substr(bin2hex(random_bytes(8)), 0, 12);
        self::logStructuredError($e, $traceId);

        $isDebug = false;
        try {
            $isDebug = (bool)(Container::get('config')['debug'] ?? false);
        } catch (\Throwable) {}

        $isJson = str_starts_with($_SERVER['REQUEST_URI'] ?? '', '/api/')
            || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');

        if (!headers_sent()) {
            http_response_code(500);
        }

        if ($isJson) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Internal Server Error',
                'trace_id' => $traceId,
                'message' => $isDebug ? $e->getMessage() : 'An unexpected error occurred. Please quote reference ID ' . $traceId,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }

        if (!$isDebug) {
            // Try the styled error view first; if it throws (e.g. DB down
            // breaking store_brand_style), fall through to the safe inline HTML.
            $errorFile = BASE_PATH . '/app/Views/errors/500.php';
            if (file_exists($errorFile)) {
                try {
                    // Clean any partial output that may have been buffered
                    while (ob_get_level() > 0) {
                        @ob_end_clean();
                    }
                    require $errorFile;
                    exit;
                } catch (\Throwable $viewError) {
                    // Log the secondary failure so it can be diagnosed
                    error_log('[ExceptionHandler] 500 view failed: ' . $viewError->getMessage());
                    while (ob_get_level() > 0) {
                        @ob_end_clean();
                    }
                    if (!headers_sent()) {
                        http_response_code(500);
                    }
                }
            }

            echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><title>System Error — Noir &amp; Co.</title><style>body{font-family:system-ui,sans-serif;background:#f7f7f2;color:#252820;display:grid;place-items:center;height:100vh;margin:0}.card{background:#fff;border:1px solid #e5e5dd;padding:2.5rem;border-radius:1.5rem;max-width:440px;box-shadow:0 8px 30px rgba(0,0,0,.04);text-align:center}code{background:#f0f1ec;padding:3px 8px;border-radius:6px;font-size:12px}</style></head><body><div class="card"><h1 style="font-size:1.5rem;margin-bottom:0.5rem">Something went wrong</h1><p style="font-size:0.9rem;color:#77796f;margin-bottom:1.5rem">Our atelier engineers have been notified. Please quote this reference code when contacting support:</p><code>REF-' . htmlspecialchars($traceId) . '</code></div></body></html>';
            exit;
        }

        // Detailed local debug view
        echo '<pre style="background:#1e1e1e;color:#f1f1f1;padding:20px;border-radius:12px;overflow-x:auto;font-size:13px;line-height:1.5">';
        echo '<strong>[FATAL ERROR / TRACE ' . htmlspecialchars($traceId) . ']</strong><br>';
        echo htmlspecialchars(get_class($e) . ': ' . $e->getMessage()) . '<br>';
        echo '<strong>File:</strong> ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '<br><br>';
        echo '<strong>Stack Trace:</strong><br>' . htmlspecialchars($e->getTraceAsString());
        echo '</pre>';
        exit;
    }

    private static function logStructuredError(Throwable $e, string $traceId): void
    {
        $logDir = BASE_PATH . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . '/app.log';
        $entry = [
            'timestamp' => date('c'),
            'trace_id' => $traceId,
            'client_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];

        @file_put_contents(
            $logFile,
            json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
