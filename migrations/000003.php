<?php
// This migration adds a "from_email" column to the notifications table.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'notifications' AND column_name = 'from_email'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE notifications ADD COLUMN from_email VARCHAR(255);');
}
