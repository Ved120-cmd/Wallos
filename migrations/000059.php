<?php

// Repair schema columns that may have been skipped by earlier SQLite-to-PostgreSQL
// metadata checks. Each operation is safe to run more than once.
$columns = [
    ['payment_methods', 'enabled', 'INTEGER DEFAULT 1'],
    ['notifications', 'from_email', "TEXT DEFAULT ''"],
    ['subscriptions', 'url', "VARCHAR(255) DEFAULT ''"],
    ['user', 'language', "TEXT DEFAULT 'en'"],
    ['fixer', 'provider', 'INTEGER DEFAULT 0'],
    ['subscriptions', 'inactive', 'INTEGER DEFAULT 0'],
    ['household', 'email', "TEXT DEFAULT ''"],
    ['categories', 'order', 'INTEGER DEFAULT 0'],
    ['payment_methods', 'order', 'INTEGER DEFAULT 0'],
    ['notifications', 'encryption', "TEXT DEFAULT 'tls'"],
    ['settings', 'color_theme', "TEXT DEFAULT 'blue'"],
    ['settings', 'hide_disabled', 'INTEGER DEFAULT 0'],
    ['user', 'budget', 'INTEGER DEFAULT 0'],
    ['subscriptions', 'notify_days_before', 'INTEGER DEFAULT 0'],
    ['admin', 'login_disabled', 'INTEGER DEFAULT 0'],
    ['subscriptions', 'cancellation_date', 'DATE'],
    ['settings', 'disabled_to_bottom', 'INTEGER DEFAULT 0'],
    ['admin', 'latest_version', "TEXT DEFAULT 'v2.21.1'"],
    ['admin', 'update_notification', 'INTEGER DEFAULT 0'],
    ['email_notifications', 'other_emails', "TEXT DEFAULT ''"],
    ['settings', 'show_original_price', 'INTEGER DEFAULT 0'],
    ['user', 'totp_enabled', 'INTEGER DEFAULT 0'],
    ['settings', 'mobile_nav', 'INTEGER DEFAULT 0'],
    ['user', 'api_key', 'TEXT'],
    ['webhook_notifications', 'ignore_ssl', 'INTEGER DEFAULT 0'],
    ['ntfy_notifications', 'ignore_ssl', 'INTEGER DEFAULT 0'],
    ['gotify_notifications', 'ignore_ssl', 'INTEGER DEFAULT 0'],
    ['subscriptions', 'replacement_subscription_id', 'INTEGER DEFAULT NULL'],
    ['subscriptions', 'start_date', 'INTEGER DEFAULT NULL'],
    ['subscriptions', 'auto_renew', 'INTEGER DEFAULT 1'],
    ['settings', 'show_subscription_progress', 'INTEGER DEFAULT 0'],
    ['webhook_notifications', 'cancelation_payload', "TEXT DEFAULT ''"],
    ['user', 'firstname', "TEXT DEFAULT ''"],
    ['user', 'lastname', "TEXT DEFAULT ''"],
    ['admin', 'oidc_oauth_enabled', 'INTEGER DEFAULT 0'],
    ['user', 'oidc_sub', 'TEXT'],
    ['oauth_settings', 'password_login_disabled', 'INTEGER DEFAULT 0'],
    ['oauth_settings', 'require_email_verified', 'INTEGER DEFAULT 1'],
    ['settings', 'week_starts_sunday', 'INTEGER DEFAULT 0'],
    ['fixer', 'usage_used', 'INTEGER DEFAULT NULL'],
    ['fixer', 'usage_limit', 'INTEGER DEFAULT NULL'],
    ['fixer', 'usage_updated_at', 'TEXT DEFAULT NULL'],
    ['subscriptions', 'logo_text_color', 'TEXT DEFAULT NULL'],
    ['subscriptions', 'logo_variant', 'TEXT DEFAULT NULL'],
    ['notification_settings', 'period_summary_at_period_start', 'INTEGER DEFAULT 0'],
    ['user', 'budget_period_type', "TEXT DEFAULT 'monthly'"],
    ['user', 'budget_period_anchor_date', "TEXT DEFAULT NULL"],
    ['user', 'period_budget', 'REAL DEFAULT 0'],
    ['totp', 'failed_attempts', 'INTEGER DEFAULT 0'],
    ['totp', 'lockout_until', 'INTEGER DEFAULT 0'],
    ['admin', 'allow_standard_users_local_webhooks', 'INTEGER DEFAULT 0'],
    ['settings', 'upcoming_payments_limit', 'INTEGER DEFAULT 3'],
];

foreach ($columns as [$table, $column, $definition]) {
    $tableLiteral = str_replace("'", "''", $table);
    $columnLiteral = str_replace("'", "''", $column);
    $exists = $db->querySingle("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = '$tableLiteral' AND column_name = '$columnLiteral'");
    if ((int) $exists === 0) {
        $db->exec(sprintf('ALTER TABLE "%s" ADD COLUMN "%s" %s', $table, $column, $definition));
    }
}

$db->exec('UPDATE categories SET "order" = id WHERE "order" IS NULL');
$db->exec('UPDATE payment_methods SET "order" = id WHERE "order" IS NULL');
$db->exec("UPDATE settings SET upcoming_payments_limit = 3 WHERE upcoming_payments_limit IS NULL OR upcoming_payments_limit < 1 OR upcoming_payments_limit > 30");

?>
