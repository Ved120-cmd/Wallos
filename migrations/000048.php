<?php
// This migration adds a "week_starts_sunday" column to the settings table and defaults it to false.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'settings' AND column_name = 'week_starts_sunday'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE settings ADD COLUMN week_starts_sunday INTEGER DEFAULT 0");
    $db->exec('UPDATE settings SET `week_starts_sunday` = 0');
}
