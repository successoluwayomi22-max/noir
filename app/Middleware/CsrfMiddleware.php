<?php
declare(strict_types=1);

namespace Noir\App\Middleware;

use Noir\Core\Request;

final class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        // Logout should never be blocked by an expired session CSRF token
        if ($request->path === '/logout' || $request->path === '/api/v1/auth/logout') {
            return;
        }

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $request->input('_csrf');
        if (!hash_equals($_SESSION['_csrf'] ?? '', (string)$token)) {
            $isJson = str_starts_with($request->path, '/api/') 
                || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
                || !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

            if ($isJson) {
                json(['error' => 'Invalid or expired CSRF token. Please refresh the page.'], 419);
            }

            // For standard browser requests whose session expired, redirect gracefully
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
            $loginPath = str_starts_with($request->path, '/admin') ? ($base . '/admin/login') : ($base . '/login');
            
            http_response_code(419);
            header('Location: ' . $loginPath . '?error=' . urlencode('Your session expired. Please sign in again.'));
            exit;
        }
    }
}
