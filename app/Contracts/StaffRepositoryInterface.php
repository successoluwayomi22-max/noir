<?php

declare(strict_types=1);

namespace Noir\App\Contracts;

interface StaffRepositoryInterface
{
    public function roles(): array;
    public function stats(): array;
    public function staffUsers(array $filters, int $page = 1, int $perPage = 25): array;
    public function staffDetails(int $id): ?array;
    public function toggleStatus(int $id): ?string;
    public function assignRole(int $userId, int $roleId): bool;
}
