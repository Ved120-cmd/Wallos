<?php
// This migration adds an "enabled" column to the payment_methods table and sets all values to 1.
// It allows the user to disable payment methods without deleting them.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'payment_methods' AND column_name = 'enabled'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE payment_methods ADD COLUMN enabled INTEGER DEFAULT 1');
    $db->exec('UPDATE payment_methods SET enabled = 1');
}
