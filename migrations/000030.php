<?php
// This migration adds a "ignore_ssl" column to the webhook_notifications, ntfy_notifications, and gotify_notifications tables
// This is to allow users to ignore SSL certificate errors when sending notifications 
// This is useful for self-signed certificates or other cases where the SSL certificate is not valid

// Add the ignore_ssl column to the webhook_notifications table

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'webhook_notifications' AND column_name = 'ignore_ssl'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE webhook_notifications ADD COLUMN ignore_ssl INTEGER DEFAULT 0');
}

// Add the ignore_ssl column to the ntfy_notifications table

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'webhook_notifications' AND column_name = 'ignore_ssl'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE ntfy_notifications ADD COLUMN ignore_ssl INTEGER DEFAULT 0');
}

// Add the ignore_ssl column to the gotify_notifications table

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'webhook_notifications' AND column_name = 'ignore_ssl'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE gotify_notifications ADD COLUMN ignore_ssl INTEGER DEFAULT 0');
}



