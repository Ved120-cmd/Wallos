<?php

// This migration lets each user choose how many upcoming payments appear on
// the dashboard (3, 5, 10, or 20); existing users keep the old default.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'settings' AND column_name = 'upcoming_payments_limit'");
if ($columnQuery->fetchArray(PDO::FETCH_ASSOC) === false) {
    $db->exec('ALTER TABLE settings ADD COLUMN upcoming_payments_limit INTEGER DEFAULT 3');
}

// Be defensive about databases that may already contain an invalid value.
$db->exec('UPDATE settings SET upcoming_payments_limit = 3
           WHERE upcoming_payments_limit IS NULL
              OR upcoming_payments_limit NOT IN (3, 5, 10, 20)');
