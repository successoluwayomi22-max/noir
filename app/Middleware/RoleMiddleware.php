<?php
declare(strict_types=1);
namespace Noir\App\Middleware;
use Noir\Core\Container; use Noir\Core\Request;
final class RoleMiddleware
{
    public static function allows(array $roles): bool { $user=Container::get('auth')->user(); return $user && (bool)array_intersect($roles,$user['roles']??[]); }
    public function handle(Request $request): void {
        if(!Container::get('auth')->check()){
            $base = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');
            header('Location: ' . ($base ?: '') . '/login');
            exit;
        }
    }
}
