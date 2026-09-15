<?php
// This migration adds an "email" column to the members table.
// It allows the household member to receive notifications when their subscriptions are about to expire.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'household' AND column_name = 'email'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE household ADD COLUMN email TEXT DEFAULT ''");
}