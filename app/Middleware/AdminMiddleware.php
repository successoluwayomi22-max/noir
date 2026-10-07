<?php
declare(strict_types=1);

namespace Noir\App\Middleware;

use Noir\Core\Container;
use Noir\Core\Request;

final class AdminMiddleware
{
    public function handle(Request $request): void
    {
        $base = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');
        $auth = Container::get('auth');
        if (!$auth->check()) { header('Location: ' . ($base ?: '') . '/login'); exit; }
        $user = $auth->user();
        $userId = (int) ($user['user_id'] ?? $user['id'] ?? 0);
        if ($userId) {
            $accountQuery=Container::get('db')->prepare('SELECT account_type FROM users WHERE id=? LIMIT 1');$accountQuery->execute([$userId]);
            if($accountQuery->fetchColumn()==='customer'){header('Location: ' . ($base ?: '') . '/customer');exit;}
            $rolesQuery = Container::get('db')->prepare('SELECT r.slug FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?');
            $rolesQuery->execute([$userId]);
            $_SESSION['user']['roles'] = $rolesQuery->fetchAll(\PDO::FETCH_COLUMN);
        }
        $adminRoles = config('roles.admin_roles', []);
        if (!$auth->can('*') && !RoleMiddleware::allows($adminRoles)) {
            $target = Container::get('role_redirect')->pathFor($_SESSION['user']['roles'] ?? []);
            header('Location: ' . ($base ?: '') . $target);
            exit;
        }
    }
}
