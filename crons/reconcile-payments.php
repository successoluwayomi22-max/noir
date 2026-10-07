<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap.php';
$db=Noir\Core\Container::get('db');
$q=$db->query("SELECT o.order_number,o.payment_status,pt.status transaction_status,pt.provider_reference FROM orders o JOIN payment_transactions pt ON pt.order_id=o.id WHERE (o.payment_status='paid' AND pt.status<>'paid') OR (o.payment_status<>'paid' AND pt.status='paid') ORDER BY o.id DESC");
$rows=$q->fetchAll(); $line='['.date('Y-m-d H:i:s').'] mismatches='.count($rows)."\n"; file_put_contents(BASE_PATH.'/storage/logs/payment-reconciliation.log',$line,FILE_APPEND|LOCK_EX); echo $line;
foreach($rows as $row) echo $row['order_number'].' '.$row['payment_status'].' / '.$row['transaction_status'].' '.$row['provider_reference']."\n";
