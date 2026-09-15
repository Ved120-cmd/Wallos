<?php

require_once __DIR__ . '/database.php';

try {
    $db = new WallosDatabase();
} catch (Throwable $exception) {
    die('Connection to the PostgreSQL database failed: ' . $exception->getMessage());
}

?>