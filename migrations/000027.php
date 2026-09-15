<?php

// this migration adds a "totp_enabled" column to the user table
// it also adds a "totp" table to the database

$columnQuery = $db->query("SELECT column_name AS name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = 'user' AND column_name = 'totp_enabled'");

$columnRequired = $columnQuery->fetchArray(PDO::FETCH_ASSOC) === false;

if ($columnRequired) {
    $db->exec('ALTER TABLE user ADD COLUMN totp_enabled INTEGER DEFAULT 0');
}

$db->exec('CREATE TABLE IF NOT EXISTS totp (
    user_id INTEGER NOT NULL,
    totp_secret TEXT NOT NULL,
    backup_codes TEXT NOT NULL,
    last_totp_used INTEGER DEFAULT 0,
    FOREIGN KEY(user_id) REFERENCES user(id)
)');