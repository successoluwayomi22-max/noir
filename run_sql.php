<?php
require 'c:/xamp/htdocs/noir/bootstrap.php';
$db = Noir\Core\Container::get('db');
$migrations = [
    '032_community_interactions.sql'
];

foreach ($migrations as $migration) {
    $sql = file_get_contents('c:/xamp/htdocs/noir/database/migrations/' . $migration);
    $db->exec($sql);
}

echo "Done.";
