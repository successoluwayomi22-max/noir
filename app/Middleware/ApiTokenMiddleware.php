<?php
declare(strict_types=1);

namespace Noir\App\Middleware;

use Noir\Core\Container;
use Noir\Core\Request;
use Noir\Core\Jwt;

final class ApiTokenMiddleware
{
    public function handle(Request $request): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] 
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] 
            ?? '';

        if (empty($authHeader) && function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }
        if (empty($authHeader) && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        }

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            self::unauthorized();
        }

        $token = substr($authHeader, 7);
        $payload = Jwt::decode($token, (string)config('key'));

        if (!$payload || empty($payload['user_id'])) {
            self::unauthorized();
        }

        $db = Container::get('db');
        $userId = (int)$payload['user_id'];
        
        $query = $db->prepare('SELECT id,public_id,username,name,email,account_type,status,email_verified_at,profile_image,loyalty_points,vip_tier FROM users WHERE id=? LIMIT 1');
        $query->execute([$userId]);
        $user = $query->fetch();

        if (!$user || $user['status'] === 'suspended' || $user['status'] === 'deleted') {
            self::unauthorized();
        }

        $rolesQuery = $db->prepare('SELECT r.id,r.name,r.slug FROM roles r JOIN user_roles ur ON ur.role_id=r.id WHERE ur.user_id=? ORDER BY r.is_system DESC,r.id');
        $rolesQuery->execute([$userId]);
        $roles = $rolesQuery->fetchAll();
        $accountType = (string)($user['account_type'] ?? 'customer');
        if (empty($roles)) {
            $roles = [['id' => 0, 'name' => ucfirst($accountType), 'slug' => $accountType]];
        } elseif ($accountType === 'customer' && !in_array('customer', array_column($roles, 'slug'), true)) {
            $roles = array_merge([['id' => 0, 'name' => 'Customer', 'slug' => 'customer']], $roles);
        } elseif (in_array($accountType, ['admin', 'staff', 'super-admin'], true) && !in_array($accountType, array_column($roles, 'slug'), true)) {
            $roles = array_merge([['id' => 0, 'name' => ucfirst($accountType), 'slug' => $accountType]], $roles);
        }

        $permissionQuery = $db->prepare('SELECT DISTINCT p.slug FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id JOIN user_roles ur ON ur.role_id=rp.role_id WHERE ur.user_id=? AND rp.effect="allow"');
        $permissionQuery->execute([$userId]);
        $primaryRole = $roles[0];
        $profileImage = !empty($user['profile_image']) ? (string)$user['profile_image'] : (string)(store_setting('admin_profile_image_' . $userId) ?: store_setting('admin_profile_image_0'));

        $_SESSION['user'] = [
            'user_id' => $userId,
            'id' => $userId,
            'public_id' => $user['public_id'],
            'username' => $user['username'],
            'role_id' => (int)$primaryRole['id'],
            'role_name' => $primaryRole['name'],
            'roles' => array_column($roles, 'slug'),
            'account_type' => $user['account_type'] ?? null,
            'display_name' => $user['name'],
            'name' => $user['name'],
            'email' => $user['email'],
            'avatar' => $profileImage ?: null,
            'profile_image' => $profileImage ?: null,
            'permissions' => $permissionQuery->fetchAll(\PDO::FETCH_COLUMN),
            'is_guest' => false,
            'loyalty_points' => (int)($user['loyalty_points'] ?? 0),
            'vip_tier' => $user['vip_tier'] ?? 'Member'
        ];
    }

    private static function unauthorized(): never
    {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}
