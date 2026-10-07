<?php
declare(strict_types=1);

namespace Noir\App\Services;

final class RoleRedirectService
{
    public function pathFor(array $roles): string
    {
        $redirects = config('roles.redirects', []);
        foreach ($roles as $role) if (isset($redirects[$role])) return $redirects[$role];
        return (string)config('roles.default_redirect', '/dashboard');
    }
}
