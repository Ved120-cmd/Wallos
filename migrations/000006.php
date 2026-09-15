<?php
// This migration adds a "provider" column to the fixer table and sets all values to 0.
// It allows the user to chose a different provider for their fixer api keys.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'fixer' AND column_name = 'provider'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE fixer ADD COLUMN provider INT DEFAULT 0');
    $db->exec('UPDATE fixer SET provider = 0');
}