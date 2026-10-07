<?php
declare(strict_types=1);

namespace Noir\App\Middleware;

use Noir\Core\Container;
use Noir\Core\Request;

final class VendorMiddleware
{
    public function handle(Request $request): void
    {
        $base = rtrim(str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']??'/')),'/');
        $user = Container::get('auth')->user();
        if (!$user) { header('Location: ' . ($base ?: '') . '/login'); exit; }
        $userId = (int)($user['user_id'] ?? $user['id'] ?? 0);
        $q = Container::get('db')->prepare('SELECT account_type, status FROM users WHERE id=? LIMIT 1');
        $q->execute([$userId]);
        $row = $q->fetch();
        if (($row['account_type'] ?? '') === 'customer') { header('Location: ' . ($base ?: '') . '/customer'); exit; }
        if (($row['status'] ?? '') === 'suspended' || ($row['status'] ?? '') === 'inactive') {
            http_response_code(403);
            echo 'Your staff credentials are currently suspended or inactive. Please contact your system administrator.';
            exit;
        }

        // Dynamically refresh current roles from user_roles table
        $rolesQuery = Container::get('db')->prepare('SELECT r.slug FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=?');
        $rolesQuery->execute([$userId]);
        $currentRoles = $rolesQuery->fetchAll(\PDO::FETCH_COLUMN) ?: [];
        $_SESSION['user']['roles'] = $currentRoles;

        if (!in_array('vendor', $currentRoles, true) && !in_array('super-admin', $currentRoles, true) && !Container::get('auth')->can('*')) {
            // Redirect to appropriate dashboard instead of raw forbidden
            $target = Container::get('role_redirect')->pathFor($currentRoles);
            header('Location: ' . ($base ?: '') . $target);
            exit;
        }
    }
}
