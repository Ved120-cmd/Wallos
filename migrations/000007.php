<?php
// This migration adds a new table to store the display and experimental settings
// This settings will now be persisted across sessions and devices

$db->exec('CREATE TABLE IF NOT EXISTS settings (
    dark_theme INTEGER DEFAULT 0,
    monthly_price INTEGER DEFAULT 0,
    convert_currency INTEGER DEFAULT 0,
    remove_background INTEGER DEFAULT 0
)');


$db->exec('INSERT INTO settings (dark_theme, monthly_price, convert_currency, remove_background) VALUES (0, 0, 0, 0)');

