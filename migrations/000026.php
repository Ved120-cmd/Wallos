<?php
// This migration adds a "other_emails" column to the email_notifications table.
// It also adds a "show_original_price" column to the settings table.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'email_notifications' AND column_name = 'other_emails'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE email_notifications ADD COLUMN other_emails TEXT DEFAULT ''");
}

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'settings' AND column_name = 'show_original_price'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE settings ADD COLUMN show_original_price INTEGER DEFAULT 0');
}