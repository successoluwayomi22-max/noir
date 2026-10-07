<?php
declare(strict_types=1);

// XAMPP serves the repository directory from /noir while the application
// document root is /noir/public. Send direct project-root visits there.
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
header('Location: ' . ($base ?: '') . '/public/');
exit;
