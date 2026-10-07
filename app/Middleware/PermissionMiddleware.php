<?php
declare(strict_types=1);

namespace Noir\App\Middleware;

use Noir\Core\Container;
use Noir\Core\Request;
use Noir\Core\Rbac;

final class PermissionMiddleware
{
    public function __construct(private readonly string $permission = '') {}
    public function handle(Request $request): void
    {
        $user = Container::get('auth')->user();
        if (!$user || !Container::get('rbac')->allows((int)$user['id'], $request->path, $request->method, $this->permission)) json(['error' => 'Forbidden.'], 403);
    }
}
