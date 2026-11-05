<?php
require __DIR__ . '/../config/config.php';

header('Content-Type: text/plain');
$tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo 'Connected to database "' . DB_NAME . '" as "' . DB_USER . "\".\n";
echo 'Tables (' . count($tables) . '): ' . implode(', ', $tables) . "\n";
