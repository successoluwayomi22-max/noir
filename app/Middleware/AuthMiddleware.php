<?php
declare(strict_types=1);
namespace Noir\App\Middleware;
use Noir\Core\Container; use Noir\Core\Request;
final class AuthMiddleware {
    public function handle(Request $request): void {
        if(!Container::get('auth')->check()){
            $base = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');
            header('Location: ' . ($base ?: '') . '/login');
            exit;
        }
    }
}
