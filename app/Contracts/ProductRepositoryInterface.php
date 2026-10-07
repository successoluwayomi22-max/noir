<?php

declare(strict_types=1);

namespace Noir\App\Contracts;

interface ProductRepositoryInterface
{
    public function paginate(array $filters, int $page = 1, int $perPage = 12): array;
    public function stats(): array;
    public function find(int $id): ?array;
    public function categories(): array;
    public function brands(): array;
    public function collections(): array;
    public function images(int $productId): array;
    public function create(array $data): int;
    public function update(int $id, array $data): bool;
    public function duplicate(int $id): ?int;
    public function delete(int $id): bool;
    public function updateStatus(int $id, string $status): bool;
    public function exportRows(): array;
    public function uniqueSlug(string $name, ?int $ignoreId = null): string;
}
