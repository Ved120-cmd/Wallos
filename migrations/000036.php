<?php

// This migration adds a new column to the webhook_notifications table to store the cancelation payload 
// Also removes the iterator column as it is not used anymore.
// The cancelation payload will be used to send cancelation notifications to the webhook

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'webhook_notifications' AND column_name = 'cancelation_payload'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE webhook_notifications ADD COLUMN cancelation_payload TEXT DEFAULT ''");
}

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'webhook_notifications' AND column_name = 'cancelation_payload'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) !== false;
if ($columnRequired) {
    $db->exec("ALTER TABLE webhook_notifications DROP COLUMN iterator");
}
