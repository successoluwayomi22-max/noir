<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=dirname(__DIR__); $files=['mail-delivery.log','payment-failures.log','payment-reconciliation.log','backup.log','storefront-analytics.log','reviews.log','staff-approvals.log','ai-assistant.log']; $summary=[];
foreach($files as $file){$path=$root.'/storage/logs/'.$file;$count=0;if(is_file($path)){$lines=file($path,FILE_IGNORE_NEW_LINES)?:[];foreach($lines as $line)if(stripos($line,'fail')!==false||stripos($line,'reject')!==false||stripos($line,'error')!==false)$count++;}$summary[$file]=$count;}
$backupDir=$root.'/storage/backups';$latestBackup=0;if(is_dir($backupDir)){foreach(glob($backupDir.'/*.gz')?:[] as $backup)$latestBackup=max($latestBackup,(int)filemtime($backup));}
$summary['backup_age_hours']=$latestBackup>0?round((time()-$latestBackup)/3600,1):-1;
$summary['backup_missing']=$latestBackup===0||$latestBackup<strtotime('-26 hours')?1:0;
$message='['.date('Y-m-d H:i:s').'] '.implode(', ',array_map(static fn($k,$v)=>$k.'='.$v,array_keys($summary),$summary))."\n";file_put_contents($root.'/storage/logs/monitor.log',$message,FILE_APPEND|LOCK_EX);echo $message;
