<?php
declare(strict_types=1);

namespace Noir\Core;

use PDO;
use PDOException;

final class Database
{
    public static function connection(array $config): PDO
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 10,
        ];

        // Cloud MySQL providers (such as Aiven) enforce SSL transport
        $isLocalHost = in_array($config['host'], ['127.0.0.1', 'localhost', '::1'], true);
        if (!$isLocalHost || !empty($config['ssl'])) {
            if (defined('Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT') || defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }
            if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
                $caPaths = ['/etc/ssl/certs/ca-certificates.crt', '/etc/pki/tls/certs/ca-bundle.crt'];
                foreach ($caPaths as $ca) {
                    if (file_exists($ca)) {
                        $options[\PDO::MYSQL_ATTR_SSL_CA] = $ca;
                        break;
                    }
                }
            }
        }

        $hosts = [$config['host']];
        if (in_array($config['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
            $fallback = ($config['host'] === '127.0.0.1') ? 'localhost' : '127.0.0.1';
            $hosts[] = $fallback;
        }

        $lastException = null;

        foreach ($hosts as $host) {
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $config['port'], $config['name'], $config['charset']);
            try {
                return new PDO($dsn, $config['user'], $config['password'], $options);
            } catch (PDOException $e) {
                $lastException = $e;
            }
        }

        $isLocal = in_array($config['host'], ['127.0.0.1', 'localhost', '::1'], true);
        $isRefused = $lastException !== null && self::isConnectionRefused($lastException);

        if ($isLocal && $isRefused) {
            if (self::autoStartLocalMysqlDaemon()) {
                // Poll for connection readiness across both 127.0.0.1 and localhost for up to 6 seconds
                for ($attempt = 1; $attempt <= 12; $attempt++) {
                    usleep(500000); // 500ms
                    foreach ($hosts as $host) {
                        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $config['port'], $config['name'], $config['charset']);
                        try {
                            return new PDO($dsn, $config['user'], $config['password'], $options);
                        } catch (PDOException) {
                            // Continue polling until timeout
                        }
                    }
                }
            }
        }

        // Final retry on all candidates
        for ($retry = 0; $retry < 3; $retry++) {
            usleep(250000);
            foreach ($hosts as $host) {
                $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $config['port'], $config['name'], $config['charset']);
                try {
                    return new PDO($dsn, $config['user'], $config['password'], $options);
                } catch (PDOException $e) {
                    $lastException = $e;
                }
            }
        }

        $errorMsg = $lastException ? $lastException->getMessage() : 'Unknown database error';
        throw new \RuntimeException('Database connection failed: ' . $errorMsg, 0, $lastException);
    }

    private static function isConnectionRefused(PDOException $e): bool
    {
        $msg = $e->getMessage();
        $code = (int) $e->getCode();
        return $code === 2002
            || str_contains($msg, '2002')
            || str_contains($msg, 'actively refused')
            || str_contains($msg, 'target machine')
            || str_contains($msg, 'No such file or directory');
    }

    private static function autoStartLocalMysqlDaemon(): bool
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return false;
        }

        // Check for official XAMPP start scripts first
        $batCandidates = [
            'C:\\xamp\\mysql_start.bat',
            'C:\\xampp\\mysql_start.bat',
            dirname(PHP_BINARY) . '\\..\\mysql_start.bat',
        ];

        foreach ($batCandidates as $bat) {
            $realBat = realpath($bat);
            if ($realBat && file_exists($realBat)) {
                $cmd = sprintf('start /B "" cmd /c "%s"', $realBat);
                if (self::executeBackgroundCommand($cmd)) {
                    return true;
                }
            }
        }

        $candidates = [
            'C:\\xamp\\mysql\\bin\\mysqld.exe',
            'C:\\xampp\\mysql\\bin\\mysqld.exe',
            dirname(PHP_BINARY) . '\\..\\mysql\\bin\\mysqld.exe',
            dirname(PHP_BINARY) . '\\mysqld.exe',
        ];

        $mysqldPath = null;
        foreach ($candidates as $candidate) {
            $real = realpath($candidate);
            if ($real && file_exists($real)) {
                $mysqldPath = $real;
                break;
            }
        }

        if (!$mysqldPath) {
            return false;
        }

        $dir = dirname($mysqldPath);
        $iniCandidates = [
            $dir . '\\my.ini',
            $dir . '\\..\\my.ini',
            'C:\\xamp\\mysql\\bin\\my.ini',
            'C:\\xampp\\mysql\\bin\\my.ini',
        ];

        $iniPath = null;
        foreach ($iniCandidates as $ini) {
            $realIni = realpath($ini);
            if ($realIni && file_exists($realIni)) {
                $iniPath = $realIni;
                break;
            }
        }

        $cmd = $iniPath
            ? sprintf('start /B "" "%s" --defaults-file="%s" --standalone', $mysqldPath, $iniPath)
            : sprintf('start /B "" "%s" --standalone', $mysqldPath);

        return self::executeBackgroundCommand($cmd);
    }

    private static function executeBackgroundCommand(string $cmd): bool
    {
        try {
            if (function_exists('pclose') && function_exists('popen')) {
                $pipe = @popen($cmd, 'r');
                if ($pipe !== false) {
                    @pclose($pipe);
                    return true;
                }
            }
            if (function_exists('exec')) {
                @exec($cmd);
                return true;
            }
        } catch (\Throwable) {
            return false;
        }

        return false;
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
