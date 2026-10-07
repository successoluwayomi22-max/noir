<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap.php';

$dir = BASE_PATH . '/storage/backups'; if (!is_dir($dir)) mkdir($dir, 0700, true);
$lock = fopen($dir . '/.backup.lock', 'x'); if ($lock === false) { fwrite(STDERR, "Backup already running.\n"); exit(1); }
try {
    $cfg = Noir\Core\Container::get('config')['database']; $stamp=date('Y-m-d_His'); $tmp=$dir.'/noir-'.$stamp.'.sql'; $out=$tmp.'.gz';
    $dump = 'mysqldump';
    foreach ([BASE_PATH . '/../mysql/bin/mysqldump.exe', 'C:/xamp/mysql/bin/mysqldump.exe', 'C:/xampp/mysql/bin/mysqldump.exe'] as $candidate) {
        if (is_file($candidate)) { $dump = $candidate; break; }
    }
    $command=escapeshellarg($dump).' --single-transaction --routines --triggers -h '.escapeshellarg((string)$cfg['host']).' -P '.escapeshellarg((string)$cfg['port']).' -u '.escapeshellarg((string)$cfg['user']).' --password='.escapeshellarg((string)$cfg['password']).' '.escapeshellarg((string)$cfg['name']).' > '.escapeshellarg($tmp);
    exec($command, $lines, $code); if ($code !== 0 || !is_file($tmp) || filesize($tmp) < 100) throw new RuntimeException('mysqldump failed.');
    $in=fopen($tmp,'rb'); $gz=gzopen($out,'wb9'); while(!feof($in)) gzwrite($gz,(string)fread($in,1048576)); fclose($in); gzclose($gz); unlink($tmp);
    file_put_contents(BASE_PATH.'/storage/logs/backup.log', '['.date('Y-m-d H:i:s')."] Created $out\n", FILE_APPEND|LOCK_EX); echo "Created $out\n";
} catch (Throwable $e) { if (isset($tmp) && is_file($tmp)) unlink($tmp); file_put_contents(BASE_PATH.'/storage/logs/backup.log','['.date('Y-m-d H:i:s').'] FAILED: '.$e->getMessage()."\n",FILE_APPEND|LOCK_EX); fclose($lock); unlink($dir.'/.backup.lock'); fwrite(STDERR,$e->getMessage()."\n"); exit(1); }
fclose($lock); unlink($dir.'/.backup.lock');
