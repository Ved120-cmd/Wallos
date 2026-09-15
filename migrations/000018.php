<?php

/*
This migration adds a column to the users table to store a monthly budget that will be used to calculate statistics
*/

    $columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'user' AND column_name = 'budget'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE user ADD COLUMN budget INTEGER DEFAULT 0');
}

?>