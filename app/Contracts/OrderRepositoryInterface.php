<?php

declare(strict_types=1);

namespace Noir\App\Contracts;

interface OrderRepositoryInterface
{
    public function stats(): array;
    public function paginate(array $filters, int $page = 1, int $perPage = 20): array;
}
