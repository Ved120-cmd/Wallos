<?php
// This migration adds a "replacement_subscription_id" column to the subscriptions table
// to allow users to track savings by replacing one subscription with another

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'subscriptions' AND column_name = 'replacement_subscription_id'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE subscriptions ADD COLUMN replacement_subscription_id INTEGER DEFAULT NULL');
}


?>