<?php

declare(strict_types=1);

namespace Noir\App\Repositories;

use PDO;
use Noir\App\Contracts\SettingRepositoryInterface;

final class SettingRepository implements SettingRepositoryInterface
{
    private static ?array $cache = null;

    public function __construct(private readonly PDO $db) {}

    /**
     * Load all settings with type casting and in-memory memoization.
     */
    public function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        try {
            $rows = $this->db->query('SELECT setting_key, setting_value, setting_type FROM settings')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $settings = [];
            foreach ($rows as $row) {
                $type = $row['setting_type'] ?? 'string';
                $val = $row['setting_value'];
                $settings[$row['setting_key']] = match ($type) {
                    'boolean' => $val === '1' || $val === 'true',
                    'integer' => (int)$val,
                    'float' => (float)$val,
                    'json' => json_decode((string)$val, true) ?? [],
                    default => (string)$val,
                };
            }
            self::$cache = $settings;
            return self::$cache;
        } catch (\Throwable) {
            return [];
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        return $all[$key] ?? $default;
    }

    public function set(string $key, mixed $value, string $type = 'string'): bool
    {
        $stringValue = match ($type) {
            'boolean' => $value ? '1' : '0',
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string)$value,
        };

        $stmt = $this->db->prepare("
            INSERT INTO settings (setting_key, setting_value, setting_type) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type)
        ");
        $success = $stmt->execute([$key, $stringValue, $type]);

        // Invalidate cache
        self::$cache = null;

        return $success;
    }

    public function setMany(array $keyValues): bool
    {
        $this->db->beginTransaction();
        try {
            foreach ($keyValues as $key => $spec) {
                if (is_array($spec) && isset($spec['value'])) {
                    $this->set((string)$key, $spec['value'], (string)($spec['type'] ?? 'string'));
                } else {
                    $this->set((string)$key, $spec);
                }
            }
            $this->db->commit();
            self::$cache = null;
            return true;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    public function remove(string $key): bool
    {
        $stmt = $this->db->prepare("DELETE FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        self::$cache = null;
        return $stmt->rowCount() > 0;
    }

    public function brandTokens(): array
    {
        return [
            'primary' => (string)$this->get('brand_primary', '#687158'),
            'dark' => (string)$this->get('brand_dark', '#252820'),
            'accent' => (string)$this->get('brand_accent', '#929b80'),
            'paper' => (string)$this->get('brand_paper', '#f7f7f2'),
        ];
    }
}
