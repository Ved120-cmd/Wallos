<?php

// This migration adds a "api_key" column to the user table
// It also generates an API key for each user

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'user' AND column_name = 'api_key'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE user ADD COLUMN api_key TEXT');
}

$users = $db->query('SELECT * FROM user');
while ($user = $users->fetchArray(PDO::FETCH_ASSOC)) {
    if (empty($user['api_key'])) {
        $apiKey = bin2hex(random_bytes(32));
        $db->exec("UPDATE user SET api_key = '" . $apiKey . "' WHERE id = " . $user['id']);
    }
}
