<?php
declare(strict_types=1);
namespace Noir\App\Middleware;
use Noir\Core\Container;
use Noir\Core\Request;
final class FinanceMiddleware
{
    public function handle(Request $request): void
    {
        $base = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');
        $auth = Container::get('auth');
        if (!$auth->check()) { header('Location: ' . ($base ?: '') . '/login'); exit; }
        $userId = (int)($auth->user()['user_id'] ?? $auth->user()['id'] ?? 0);
        $q = Container::get('db')->prepare('SELECT account_type, status FROM users WHERE id=? LIMIT 1');
        $q->execute([$userId]);
        $record = $q->fetch();
        if (($record['account_type'] ?? null) === 'customer') { header('Location: ' . ($base ?: '') . '/customer'); exit; }
        if (($record['status'] ?? '') === 'suspended' || ($record['status'] ?? '') === 'inactive') {
            http_response_code(403);
            echo 'Your staff credentials are currently suspended or inactive. Please contact your system administrator.';
            exit;
        }

        // Dynamically refresh current roles from user_roles table
        $rolesQuery = Container::get('db')->prepare('SELECT r.slug FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?');
        $rolesQuery->execute([$userId]);
        $currentRoles = $rolesQuery->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        $_SESSION['user']['roles'] = $currentRoles;

        if (!in_array('finance-manager', $currentRoles, true) && !in_array('super-admin', $currentRoles, true) && !$auth->can('*')) {
            $target = Container::get('role_redirect')->pathFor($currentRoles);
            header('Location: ' . ($base ?: '') . $target);
            exit;
        }
    }
}
