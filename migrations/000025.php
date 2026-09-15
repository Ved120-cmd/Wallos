<?php
// This migration adds a "disabled_to_bottom" column to the settings table.
// This magration also adds a latest_version and update_notification columns to the admin table.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'settings' AND column_name = 'disabled_to_bottom'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE settings ADD COLUMN disabled_to_bottom INTEGER DEFAULT 0');
}

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'settings' AND column_name = 'disabled_to_bottom'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE admin ADD COLUMN latest_version TEXT DEFAULT 'v2.21.1'");
}

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'settings' AND column_name = 'disabled_to_bottom'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE admin ADD COLUMN update_notification INTEGER DEFAULT 0');
}