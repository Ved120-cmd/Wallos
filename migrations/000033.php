<?php
// This migration adds a "show_subscription_progress" column to the settings table and sets to false as default.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'settings' AND column_name = 'show_subscription_progress'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE settings ADD COLUMN show_subscription_progress INTEGER DEFAULT 0");
    $db->exec('UPDATE settings SET `show_subscription_progress` = 0');
}