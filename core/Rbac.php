<?php
declare(strict_types=1);

namespace Noir\Core;

final class Rbac
{
    public function __construct(private readonly \PDO $db) {}

    public function permissionsFor(int $userId): array
    {
        $query = $this->db->prepare("SELECT DISTINCT p.slug FROM permissions p JOIN role_permissions rp ON rp.permission_id=p.id JOIN user_roles ur ON ur.role_id=rp.role_id WHERE ur.user_id=? AND rp.effect='allow'");
        $query->execute([$userId]);
        return $query->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function allows(int $userId, string $path, string $method, string $permission): bool
    {
        $permissions = $this->permissionsFor($userId);
        if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) return true;
        $query = $this->db->prepare('SELECT effect FROM access_rules WHERE is_active=1 AND (http_method IS NULL OR http_method=?) AND route_pattern=? AND permission_slug=? ORDER BY priority DESC LIMIT 1');
        $query->execute([strtoupper($method), $path, $permission]);
        return $query->fetchColumn() === 'allow';
    }
}
