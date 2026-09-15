<?php
// This migration adds a "cancellation_date" column to the subscriptions table.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'subscriptions' AND column_name = 'cancellation_date'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE subscriptions ADD COLUMN cancellation_date DATE;');
}