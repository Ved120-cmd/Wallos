<?php
// Adds require_email_verified to oauth_settings.
// When enabled (default), account linking by email is only allowed when the
// IdP marks email_verified = true, preventing account takeover via unverified emails.

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'oauth_settings' AND column_name = 'require_email_verified'");
$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec("ALTER TABLE oauth_settings ADD COLUMN require_email_verified INTEGER DEFAULT 1");
}

// Existing rows may still contain NULL after an ALTER TABLE default, so
// PHP's WallosDatabase extension may return NULL for them. Backfill explicitly.
$db->exec("UPDATE oauth_settings SET require_email_verified = 1 WHERE require_email_verified IS NULL");
