<?php
// This migration adds usage columns to the fixer table. They store the monthly
// quota reported by apilayer response headers (captured during rate updates),
// so the settings page can show usage without spending extra API requests.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'fixer' AND column_name = 'usage_used'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE fixer ADD COLUMN usage_used INTEGER DEFAULT NULL");
    $db->exec("ALTER TABLE fixer ADD COLUMN usage_limit INTEGER DEFAULT NULL");
    $db->exec("ALTER TABLE fixer ADD COLUMN usage_updated_at TEXT DEFAULT NULL");
}
