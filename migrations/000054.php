<?php

// This migration adds brute-force protection state to the "totp" table.
// It adds a "failed_attempts" column (consecutive failed 2FA verifications)
// and a "lockout_until" column (unix timestamp until which verification is
// blocked). Both are keyed per account so the lockout survives session resets.

$failedAttemptsColumn = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'totp' AND column_name = 'failed_attempts'");
if ($failedAttemptsColumn->fetchArray(PDO::FETCH_ASSOC) === false) {
    $db->exec('ALTER TABLE totp ADD COLUMN failed_attempts INTEGER DEFAULT 0');
}

$lockoutUntilColumn = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'totp' AND column_name = 'failed_attempts'");
if ($lockoutUntilColumn->fetchArray(PDO::FETCH_ASSOC) === false) {
    $db->exec('ALTER TABLE totp ADD COLUMN lockout_until INTEGER DEFAULT 0');
}

$db->exec('UPDATE totp SET failed_attempts = 0 WHERE failed_attempts IS NULL');
$db->exec('UPDATE totp SET lockout_until = 0 WHERE lockout_until IS NULL');

?>
