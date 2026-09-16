<?php

// Adds Gmail API (OAuth, HTTPS/443) as an alternative to SMTP for email
// notifications - some hosts block outbound SMTP ports entirely.
$statements = [
    "ALTER TABLE email_notifications ADD COLUMN IF NOT EXISTS auth_method TEXT NOT NULL DEFAULT 'smtp'",
    'ALTER TABLE email_notifications ADD COLUMN IF NOT EXISTS gmail_client_id TEXT',
    'ALTER TABLE email_notifications ADD COLUMN IF NOT EXISTS gmail_client_secret TEXT',
    'ALTER TABLE email_notifications ADD COLUMN IF NOT EXISTS gmail_refresh_token TEXT',
];

foreach ($statements as $statement) {
    $db->exec($statement);
}

?>
