<?php
// This migration adds a "hide_disabled" column to the settings table and sets to false as default.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'settings' AND column_name = 'hide_disabled'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE settings ADD COLUMN hide_disabled INTEGER DEFAULT 0");
    $db->exec('UPDATE settings SET `hide_disabled` = 0');
}