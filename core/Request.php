<?php
declare(strict_types=1);

namespace Noir\Core;

final class Request
{
    public function __construct(public readonly string $method, public readonly string $path, public readonly array $params = []) {}
    public function input(?string $key = null, mixed $default = null): mixed
    {
        $data = json_decode(file_get_contents('php://input') ?: '', true) ?: [];
        $data = array_merge($_GET, $_POST, $data);
        return $key === null ? $data : ($data[$key] ?? $default);
    }
}
