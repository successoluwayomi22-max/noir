<?php
declare(strict_types=1);

namespace Noir\Core;

final class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler, array $middleware = []): void { $this->add('GET', $path, $handler, $middleware); }
    public function post(string $path, callable|array $handler, array $middleware = []): void { $this->add('POST', $path, $handler, $middleware); }
    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void
    {
        $pattern = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', rtrim($path, '/') ?: '/') . '$#';
        $this->routes[] = compact('method', 'pattern', 'handler', 'middleware', 'path');
    }

    public function dispatch(string $method, string $uri): mixed
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        $clientIp = (string)($_SERVER['HTTP_CF_CONNECTING_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '127.0.0.1');
        if (str_contains($clientIp, ',')) {
            $clientIp = trim(explode(',', $clientIp)[0]);
        }

        try {
            $db = Container::get('db');
            if ($db instanceof \PDO) {
                $blockCheck = $db->prepare('SELECT id, reason, blocked_until, is_permanent FROM blocked_ips WHERE ip_address = ? LIMIT 1');
                $blockCheck->execute([$clientIp]);
                $blocked = $blockCheck->fetch(\PDO::FETCH_ASSOC);
                if ($blocked) {
                    $isExpired = !$blocked['is_permanent'] && strtotime((string)$blocked['blocked_until']) <= time();
                    // Loopback localhost should never lock out local administrators
                    $isLoopback = in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
                    $isAdminRoute = str_contains($path, '/admin');

                    if ($isLoopback || ($isAdminRoute && !empty($_SESSION['user']['account_type']) && in_array($_SESSION['user']['account_type'], ['staff', 'admin'], true))) {
                        if ($isLoopback) {
                            $db->prepare("DELETE FROM blocked_ips WHERE ip_address IN ('127.0.0.1', '::1', 'localhost')")->execute();
                        }
                    } elseif (!$isExpired) {
                        http_response_code(403);
                        if (str_starts_with($path, '/api/') || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode([
                                'error' => 'Forbidden',
                                'message' => 'Access Denied: Your IP (' . $clientIp . ') has been blocked by the security system.',
                                'blocked' => true,
                                'reason' => $blocked['reason'],
                                'blocked_until' => $blocked['blocked_until'],
                            ]);
                            exit;
                        }
                        if (file_exists(BASE_PATH . '/app/Views/errors/403.php')) {
                            require BASE_PATH . '/app/Views/errors/403.php';
                            exit;
                        }
                        echo 'Access Denied: Your IP has been blocked.';
                        exit;
                    } else {
                        $db->prepare('DELETE FROM blocked_ips WHERE id = ?')->execute([(int)$blocked['id']]);
                    }
                }
            }
        } catch (\Throwable) {}
        // Support both a public/ document root and a project subdirectory such
        // as http://localhost/noir/public/ without changing route definitions.
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
        $scriptDirectory = str_replace('\\', '/', dirname($scriptName));
        $baseDirectories = [$scriptName, $scriptDirectory];
        if (basename($scriptDirectory) === 'public') {
            $baseDirectories[] = dirname($scriptDirectory);
        }
        foreach ($baseDirectories as $baseDirectory) {
            if ($baseDirectory !== '/' && str_starts_with($path, $baseDirectory)) {
                $path = substr($path, strlen($baseDirectory)) ?: '/';
                break;
            }
        }
        // When the development server is started from the project root, or
        // when XAMPP exposes the repository as /noir, users commonly open
        // /public/ directly. Treat that document-root marker as internal.
        if ($path === '/public' || str_starts_with($path, '/public/')) {
            $path = substr($path, strlen('/public')) ?: '/';
        }
        // Apache/PHP setups can append the front controller more than once
        // when index.php is included in the browser URL. It is not part of
        // the application route, so normalize it before matching.
        while ($path === '/index.php' || str_starts_with($path, '/index.php/')) {
            $path = substr($path, strlen('/index.php')) ?: '/';
        }
        $path = '/' . ltrim($path, '/');
        $upperMethod = strtoupper($method);
        foreach ($this->routes as $route) {
            $isMethodMatch = ($route['method'] === $upperMethod) || ($upperMethod === 'HEAD' && $route['method'] === 'GET');
            if (!$isMethodMatch || !preg_match($route['pattern'], rtrim($path, '/') ?: '/', $matches)) continue;
            $request = new Request($method, $path, array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));
            (new \Noir\App\Middleware\SecurityHeadersMiddleware())->handle($request);
            foreach ($route['middleware'] as $middleware) { (new $middleware())->handle($request); }
            try {
                return is_array($route['handler']) ? (new $route['handler'][0]())->{$route['handler'][1]}($request) : ($route['handler'])($request);
            } catch (\Throwable $e) {
                error_log('Route dispatch error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
                http_response_code(500);
                if (str_starts_with($path, '/api/') || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
                    header('Content-Type: application/json');
                    echo json_encode(['error' => 'Internal server error', 'message' => 'An unexpected interruption occurred. Please try again.']);
                    exit;
                }
                if (file_exists(BASE_PATH . '/app/Views/errors/500.php')) {
                    require BASE_PATH . '/app/Views/errors/500.php';
                    exit;
                }
                throw $e;
            }
        }
        http_response_code(404);
        if (str_starts_with($path, '/api/') || str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')) {
            return json(['error' => 'Not found'], 404);
        }
        require BASE_PATH . '/app/Views/errors/404.php';
        return null;
    }
}
