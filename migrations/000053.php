<?php

// This migration adds support for payment-period budgeting.
// It adds a "period_summary_at_period_start" column to the "notification_settings" table.
// It also adds "budget_period_type", "budget_period_anchor_date" and "period_budget" columns to the "user" table.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'notification_settings' AND column_name = 'period_summary_at_period_start'");
if ($columnQuery->fetchArray(PDO::FETCH_ASSOC) === false) {
    $db->exec('ALTER TABLE notification_settings ADD COLUMN period_summary_at_period_start INTEGER DEFAULT 0');
}

$db->exec('UPDATE notification_settings
           SET period_summary_at_period_start = 0
           WHERE period_summary_at_period_start IS NULL');

$defaultAnchorDate = (new DateTime('now'))->format('Y-m-d');

$periodTypeColumn = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'user' AND column_name = 'budget_period_type'");
if ($periodTypeColumn->fetchArray(PDO::FETCH_ASSOC) === false) {
    $db->exec("ALTER TABLE user ADD COLUMN budget_period_type TEXT DEFAULT 'monthly'");
}

$anchorDateColumn = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'user' AND column_name = 'budget_period_anchor_date'");
if ($anchorDateColumn->fetchArray(PDO::FETCH_ASSOC) === false) {
    $db->exec("ALTER TABLE user ADD COLUMN budget_period_anchor_date TEXT DEFAULT '" . $defaultAnchorDate . "'");
}

$periodBudgetColumn = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'user' AND column_name = 'period_budget'");
if ($periodBudgetColumn->fetchArray(PDO::FETCH_ASSOC) === false) {
    $db->exec('ALTER TABLE user ADD COLUMN period_budget REAL DEFAULT 0');
}

$db->exec("UPDATE user SET budget_period_type = 'monthly' WHERE budget_period_type IS NULL OR budget_period_type = ''");
$db->exec("UPDATE user SET budget_period_anchor_date = '" . $defaultAnchorDate . "' WHERE budget_period_anchor_date IS NULL OR budget_period_anchor_date = '' OR budget_period_anchor_date = '1970-01-01'");

// Seed period_budget from existing budget for existing users
$db->exec("UPDATE user SET period_budget = budget WHERE (period_budget IS NULL OR period_budget = 0) AND budget > 0");

?>
