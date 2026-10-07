<?php

declare(strict_types=1);

namespace Noir\App\Contracts;

interface SettingRepositoryInterface
{
    public function all(): array;
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value, string $type = 'string'): bool;
    public function setMany(array $keyValues): bool;
    public function remove(string $key): bool;
    public function brandTokens(): array;
}
