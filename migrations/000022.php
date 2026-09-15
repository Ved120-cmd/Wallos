<?php

/*
This migration adds a column to the admin table to enable the option to disable login
*/

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'admin' AND column_name = 'login_disabled'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE admin ADD COLUMN login_disabled INTEGER DEFAULT 0');
}

?>