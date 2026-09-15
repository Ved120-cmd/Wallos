<?php
// This migration adds a "activated" column to the subscriptions table and sets all values to true.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'subscriptions' AND column_name = 'inactive'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE subscriptions ADD COLUMN inactive INTEGER DEFAULT false');
    $db->exec('UPDATE subscriptions SET inactive = false');
}
