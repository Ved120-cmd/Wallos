<?php
// This migration adds a "color_theme" column to the settings table and sets it to blue as default.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'settings' AND column_name = 'color_theme'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE settings ADD COLUMN color_theme TEXT DEFAULT 'blue'");
    $db->exec("UPDATE settings SET `color_theme` = 'blue'");
}

// This migrations adds custom_colors table to the database, so the user can set custom accent colors to the application

$customColorsTableQuery = $db->query("SELECT table_name AS name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = 'custom_colors'");
$customColorsTableRequired = $customColorsTableQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($customColorsTableRequired) {
    $db->exec("CREATE TABLE custom_colors (
        main_color TEXT NOT NULL,
        accent_color TEXT NOT NULL,
        hover_color TEXT NOT NULL
    )");
}

