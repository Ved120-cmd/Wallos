<?php

// This migration adds a "mobile_nav" column to the settings table

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'settings' AND column_name = 'mobile_nav'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE settings ADD COLUMN mobile_nav INTEGER DEFAULT 0');
}