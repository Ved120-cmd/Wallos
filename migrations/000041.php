<?php
// This migration adds a mattermost_notifications table to store Mattermost notification settings.

$tableQuery = $db->query("SELECT table_name AS name FROM information_schema.tables WHERE table_schema = current_schema() AND table_name = 'mattermost_notifications'");
$tableExists = $tableQuery->fetchArray(PDO::FETCH_ASSOC);
if ($tableExists === false) {
    $db->exec("
        CREATE TABLE mattermost_notifications (
            enabled INTEGER NOT NULL DEFAULT 0,
            user_id INTEGER,
            webhook_url TEXT DEFAULT '',
            bot_username TEXT DEFAULT '',
            bot_icon_emoji TEXT DEFAULT ''
        );
    ");
}