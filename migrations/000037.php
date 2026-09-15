<?php
// This migration adds "firstname" and "lastname" columns to the user table

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'user' AND column_name = 'firstname'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE user ADD COLUMN firstname TEXT DEFAULT ''");
}

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'user' AND column_name = 'lastname'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE user ADD COLUMN lastname TEXT DEFAULT ''");
}
