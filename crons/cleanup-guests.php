<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require dirname(__DIR__) . '/bootstrap.php';

$db = \Noir\Core\Container::get('db');
$stmt = $db->prepare("
    DELETE u
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    WHERE u.is_guest = 1
      AND u.guest_promoted_at IS NULL
      AND u.guest_expires_at < NOW()
      AND o.id IS NULL
    LIMIT 250
");
$stmt->execute();

echo 'Expired guest accounts removed: ' . $stmt->rowCount() . PHP_EOL;
