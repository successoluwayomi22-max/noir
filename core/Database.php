<?php
declare(strict_types=1);

namespace Noir\Core;

use PDO;
use PDOException;

final class Database
{
    public static function connection(array $config): PDO
    {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $config['host'], $config['port'], $config['name'], $config['charset']);
        try {
            return new PDO($dsn, $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed.', 0, $e);
        }
    }

    public static function transaction(callable $callback): mixed
    {
        $db = Container::get('db');
        if (!$db instanceof PDO) {
            throw new \RuntimeException('Database instance not found in Container.');
        }

        if ($db->inTransaction()) {
            return $callback($db);
        }

        $db->beginTransaction();
        try {
            $result = $callback($db);
            $db->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
